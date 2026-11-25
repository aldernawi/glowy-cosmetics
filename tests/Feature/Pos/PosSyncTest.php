<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\PosDevice;
use App\Models\PosSale;
use App\Models\PosStockShortage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\PosDeviceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PosSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private PosDevice $device;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->cashier = User::factory()->create(['name' => 'كاشير أولى']);
        $this->cashier->assignRole('cashier');
        [$this->device, $this->token] = app(PosDeviceService::class)->link($this->cashier, 'كاشير المحل');
    }

    public function test_the_device_downloads_only_sellable_variants(): void
    {
        $variant = $this->variant('SELL-1', 4, barcode: '6281234567890');
        $variant->product->update(['category_id' => Category::query()->create(['name_ar' => 'الغسولات', 'slug' => 'cleansers'])->id]);
        $hidden = $this->variant('HIDDEN-1', 4);
        $hidden->product->update(['is_active' => false]);
        $this->variant('OFF-1', 4)->update(['is_active' => false]);

        $this->bootstrap()->assertOk()
            ->assertJsonPath('device.cashier', 'كاشير أولى')
            ->assertJsonCount(1, 'variants')
            ->assertJsonPath('variants.0.id', $variant->id)
            ->assertJsonPath('variants.0.barcode', '6281234567890')
            ->assertJsonPath('variants.0.category', 'الغسولات')
            ->assertJsonPath('variants.0.price', '12.500')
            ->assertJsonPath('variants.0.stock', 4);
    }

    public function test_a_cash_sale_is_stored_with_server_totals_and_reduces_stock(): void
    {
        $variant = $this->variant('CASH-1', 5);
        $sale = $this->sale([$this->line($variant, 2)], ['amount_received' => '30.000', 'total' => '1.000', 'sold_at' => '2026-01-05T22:30:00.000Z']);

        $this->sync([$sale])->assertOk()->assertJsonPath('results.0.status', 'saved');

        $stored = PosSale::query()->sole();
        $this->assertSame(['25.000', '30.000', '5.000'], [$stored->total, $stored->amount_received, $stored->change_due]);
        $this->assertSame('2026-01-06 00:30', $stored->sold_at->format('Y-m-d H:i'));
        $this->assertSame($this->cashier->id, $stored->user_id);
        $this->assertSame(3, $variant->refresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $variant->id, 'type' => 'pos_sale', 'delta' => -2, 'reference' => $sale['receipt_number']]);
    }

    public function test_uploading_the_same_sale_again_changes_nothing(): void
    {
        $variant = $this->variant('TWICE-1', 5);
        $sale = $this->sale([$this->line($variant, 2)]);

        $this->sync([$sale, $sale])->assertJsonPath('results.1.status', 'saved');
        $this->sync([$sale])->assertJsonPath('results.0.status', 'saved');

        $this->assertDatabaseCount('pos_sales', 1);
        $this->assertSame(3, $variant->refresh()->stock_quantity);
        $this->assertSame(1, StockMovement::query()->count());
    }

    public function test_a_sale_beyond_recorded_stock_is_accepted_and_the_gap_becomes_a_shortage(): void
    {
        $variant = $this->variant('SHORT-1', 1);
        $sale = $this->sale([$this->line($variant, 2), $this->line($variant, 1)]);

        $this->sync([$sale])->assertJsonPath('results.0.status', 'saved');

        $this->assertSame(0, $variant->refresh()->stock_quantity);
        $this->assertSame('37.500', PosSale::query()->sole()->total);
        $this->assertSame([1, 1], PosStockShortage::query()->orderBy('id')->pluck('quantity')->all());
    }

    public function test_one_invalid_sale_does_not_block_the_others(): void
    {
        $variant = $this->variant('MIX-1', 5);
        $bad = $this->sale([$this->line($variant, 0)]);
        $good = $this->sale([$this->line($variant, 1)]);

        $this->sync([$bad, $good])->assertOk()
            ->assertJsonPath('results.0.status', 'rejected')->assertJsonPath('results.0.uuid', $bad['uuid'])
            ->assertJsonPath('results.1.status', 'saved');

        $this->assertDatabaseCount('pos_sales', 1);
    }

    public function test_a_sale_of_a_variant_that_no_longer_exists_keeps_its_snapshot(): void
    {
        $line = ['variant_id' => 9999, 'name' => 'منتج محذوف', 'variant' => '30ml', 'sku' => 'GONE', 'unit_price' => '7.000', 'quantity' => 1];

        $this->sync([$this->sale([$line])])->assertJsonPath('results.0.status', 'saved');

        $this->assertDatabaseHas('pos_sale_items', ['product_variant_id' => null, 'product_name' => 'منتج محذوف', 'sku' => 'GONE', 'subtotal' => '7.000']);
    }

    public function test_the_price_charged_offline_is_kept_and_a_difference_is_flagged(): void
    {
        $variant = $this->variant('PRICE-1', 5);
        $line = $this->line($variant, 1);
        $variant->update(['price' => '15.000']);

        $this->sync([$this->sale([$line])]);

        $item = PosSale::query()->sole()->items->sole();
        $this->assertSame(['12.500', '15.000'], [$item->unit_price, $item->catalog_price]);
        $this->assertTrue($item->hasPriceDifference());
    }

    public function test_a_fast_device_clock_cannot_place_a_sale_in_the_future(): void
    {
        $this->sync([$this->sale([$this->line($this->variant('CLOCK-1', 5), 1)], ['sold_at' => now()->addYear()->toIso8601String()])]);

        $this->assertTrue(PosSale::query()->sole()->sold_at->lessThanOrEqualTo(now()));
    }

    public function test_bank_payments_keep_their_reference_and_no_cash_amounts(): void
    {
        $this->sync([$this->sale([$this->line($this->variant('BANK-1', 5), 1)], ['payment_method' => 'transfer', 'payment_reference' => 'TX-9', 'amount_received' => '99.000'])]);

        $sale = PosSale::query()->sole();
        $this->assertSame(['transfer', 'TX-9', null, null], [$sale->payment_method->value, $sale->payment_reference, $sale->amount_received, $sale->change_due]);
    }

    public function test_requests_without_a_valid_device_are_refused(): void
    {
        $this->getJson(route('pos.bootstrap'), ['X-Glowy-Pos' => '1'])->assertUnauthorized()->assertJsonPath('code', 'unlinked');
        $this->bootstrap('wrong-token')->assertUnauthorized();
        $this->withCredentials()->withCookie(PosDeviceService::COOKIE, $this->token)->getJson(route('pos.bootstrap'))->assertUnauthorized();

        $this->device->update(['revoked_at' => now()]);
        $this->bootstrap()->assertUnauthorized();
        $this->sync([$this->sale([$this->line($this->variant('NO-1', 5), 1)])])->assertUnauthorized();
        $this->assertDatabaseCount('pos_sales', 0);
    }

    public function test_a_cashier_who_lost_the_permission_cannot_sync(): void
    {
        $this->cashier->syncRoles([]);

        $this->bootstrap()->assertUnauthorized();
    }

    public function test_receipt_numbers_resume_after_the_browser_is_cleared(): void
    {
        $this->sync([$this->sale([$this->line($this->variant('SEQ-1', 5), 1)], ['sequence' => 41])]);

        $this->bootstrap()->assertJsonPath('device.last_sequence', 41);
    }

    public function test_the_device_cookie_is_private_to_the_register_api_and_renewed(): void
    {
        $cookie = $this->bootstrap()->getCookie(PosDeviceService::COOKIE, false);

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', $cookie->getSameSite());
        $this->assertSame('/pos/api', $cookie->getPath());
        $this->assertNotSame($this->token, $cookie->getValue());
    }

    private function bootstrap(?string $token = null): TestResponse
    {
        return $this->withCredentials()->withCookie(PosDeviceService::COOKIE, $token ?? $this->token)->getJson(route('pos.bootstrap'), ['X-Glowy-Pos' => '1']);
    }

    /** @param array<int, array<string, mixed>> $sales */
    private function sync(array $sales): TestResponse
    {
        return $this->withCredentials()->withCookie(PosDeviceService::COOKIE, $this->token)->postJson(route('pos.sync'), ['sales' => $sales], ['X-Glowy-Pos' => '1']);
    }

    private function variant(string $sku, int $stock, ?string $barcode = null): ProductVariant
    {
        $variant = Product::query()->create(['name_ar' => 'منتج '.$sku, 'slug' => Str::lower($sku)])->createDefaultVariant($sku, '12.500');
        $variant->update(['stock_quantity' => $stock, 'barcode' => $barcode]);

        return $variant;
    }

    /** @return array<string, mixed> */
    private function line(ProductVariant $variant, int $quantity): array
    {
        return ['variant_id' => $variant->id, 'name' => $variant->product->name_ar, 'variant' => null, 'sku' => $variant->sku, 'unit_price' => '12.500', 'quantity' => $quantity];
    }

    /** @param array<int, array<string, mixed>> $items */
    private function sale(array $items, array $overrides = []): array
    {
        return $overrides + ['uuid' => (string) Str::uuid(), 'sequence' => 1, 'receipt_number' => 'P1-000001', 'sold_at' => now()->toIso8601String(), 'payment_method' => 'cash', 'payment_reference' => null, 'amount_received' => null, 'items' => $items];
    }
}
