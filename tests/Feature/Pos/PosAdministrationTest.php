<?php

namespace Tests\Feature\Pos;

use App\Enums\PosSaleStatus;
use App\Models\PosDevice;
use App\Models\PosSale;
use App\Models\PosStockShortage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\PosDeviceService;
use App\Services\PosSaleService;
use App\Services\ProductVariantService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrator');
    }

    public function test_linking_sets_the_device_cookie_and_only_one_device_stays_active(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pos.device.store'), ['name' => 'الأول', 'user_id' => $this->admin->id])
            ->assertRedirect(route('pos.app'))->assertCookie(PosDeviceService::COOKIE);

        $this->actingAs($this->admin)->post(route('admin.pos.device.store'), ['name' => 'الثاني', 'user_id' => $this->admin->id])->assertSessionHasErrors('device');
        $this->actingAs($this->admin)->post(route('admin.pos.device.store'), ['name' => 'الثاني', 'user_id' => $this->admin->id, 'replace' => 1])->assertRedirect(route('pos.app'));

        $this->assertSame(['الثاني'], PosDevice::query()->whereNull('revoked_at')->pluck('name')->all());
        $this->actingAs($this->admin)->get(route('admin.pos.device.index'))->assertOk()->assertSee('الثاني');
    }

    public function test_a_customer_cannot_be_chosen_as_cashier(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pos.device.store'), ['name' => 'جهاز', 'user_id' => User::factory()->create()->id])->assertSessionHasErrors('device');
        $this->assertDatabaseCount('pos_devices', 0);
    }

    public function test_cashiers_land_on_the_register_and_cannot_open_administration(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole('cashier');

        $this->post('/login', ['email' => $cashier->email, 'password' => 'password'])->assertRedirect(route('pos.app', absolute: false));
        foreach (['admin.pos.sales.index', 'admin.pos.shortages.index', 'admin.pos.device.index', 'admin.orders.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_sales_are_listed_and_totalled_by_payment_method(): void
    {
        $variant = $this->variant(10);
        $sale = $this->sell($variant, 2);
        $this->sell($variant, 1, ['payment_method' => 'card']);
        app(PosSaleService::class)->void($this->sell($variant, 1), $this->admin);

        $this->actingAs($this->admin)->get(route('admin.pos.sales.index'))->assertOk()
            ->assertSee($sale->receipt_number)->assertSee('20.000 د.ل')->assertSee('10.000 د.ل')->assertDontSee('30.000 د.ل');
        $this->actingAs($this->admin)->get(route('admin.pos.sales.show', $sale))->assertOk()->assertSee($variant->sku);
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('30.000 د.ل');
    }

    public function test_voiding_returns_only_what_was_deducted_and_only_once(): void
    {
        $variant = $this->variant(1);
        $sale = $this->sell($variant, 3);

        $this->actingAs($this->admin)->patch(route('admin.pos.sales.void', $sale))->assertRedirect(route('admin.pos.sales.show', $sale));
        $this->actingAs($this->admin)->patch(route('admin.pos.sales.void', $sale));

        $this->assertSame(PosSaleStatus::Voided, $sale->refresh()->status);
        $this->assertSame(1, $variant->refresh()->stock_quantity);
        $this->assertSame('voided', PosStockShortage::query()->sole()->resolution);
    }

    public function test_a_shortage_is_deducted_once_stock_is_available(): void
    {
        $variant = $this->variant(0);
        $sale = $this->sell($variant, 2);
        $shortage = PosStockShortage::query()->sole();
        $this->actingAs($this->admin)->get(route('admin.pos.shortages.index'))->assertOk()->assertSee($variant->sku);

        $this->actingAs($this->admin)->patch(route('admin.pos.shortages.update', $shortage), ['resolution' => 'deducted'])->assertSessionHasErrors('resolution');
        $variant->update(['stock_quantity' => 5]);
        $this->actingAs($this->admin)->patch(route('admin.pos.shortages.update', $shortage), ['resolution' => 'deducted'])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->patch(route('admin.pos.shortages.update', $shortage), ['resolution' => 'deducted']);

        $this->assertSame(3, $variant->refresh()->stock_quantity);
        app(PosSaleService::class)->void($sale, $this->admin);
        $this->assertSame(5, $variant->refresh()->stock_quantity);
    }

    public function test_a_shortage_can_be_closed_without_touching_stock(): void
    {
        $variant = $this->variant(0);
        $this->sell($variant, 1);
        $variant->update(['stock_quantity' => 4]);

        $this->actingAs($this->admin)->patch(route('admin.pos.shortages.update', PosStockShortage::query()->sole()), ['resolution' => 'dismissed'])->assertSessionHasNoErrors();

        $this->assertSame(4, $variant->refresh()->stock_quantity);
        $this->assertNotNull(PosStockShortage::query()->sole()->resolved_at);
    }

    public function test_a_variant_sold_in_the_shop_is_archived_instead_of_deleted(): void
    {
        $variant = $this->variant(0);
        $this->sell($variant, 1);

        $this->assertFalse(app(ProductVariantService::class)->deleteOrArchive($variant));
        $this->assertModelExists($variant);
    }

    public function test_barcodes_are_saved_and_unique(): void
    {
        $variant = $this->variant(1);
        $payload = ['price' => '10.000', 'low_stock_threshold' => 1, 'is_active' => 1];
        $this->actingAs($this->admin)->patch(route('admin.variants.update', $variant), ['sku' => $variant->sku, 'barcode' => '6281000000017'] + $payload)->assertSessionHasNoErrors();
        $this->assertSame('6281000000017', $variant->refresh()->barcode);
        $this->actingAs($this->admin)->patch(route('admin.variants.update', $variant), ['sku' => $variant->sku, 'barcode' => '6281000000017'] + $payload)->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('admin.products.store'), ['name_ar' => 'آخر', 'slug' => 'other', 'sku' => 'OTHER', 'price' => '5.000', 'barcode' => '6281000000017'])->assertSessionHasErrors('barcode');
        $this->actingAs($this->admin)->post(route('admin.products.store'), ['name_ar' => 'آخر', 'slug' => 'other', 'sku' => 'OTHER', 'price' => '5.000', 'barcode' => '6281000000024'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_variants', ['sku' => 'OTHER', 'barcode' => '6281000000024']);
        $this->actingAs($this->admin)->get(route('admin.products.edit', 'other'))->assertOk()->assertSee('6281000000024');
    }

    private function variant(int $stock): ProductVariant
    {
        $variant = Product::query()->create(['name_ar' => 'كريم', 'slug' => 'pos-cream'])->createDefaultVariant('POS-CREAM', '10.000');
        $variant->update(['stock_quantity' => $stock]);

        return $variant;
    }

    /** @param array<string, mixed> $overrides */
    private function sell(ProductVariant $variant, int $quantity, array $overrides = []): PosSale
    {
        $device = PosDevice::query()->first() ?? app(PosDeviceService::class)->link($this->admin, 'جهاز')[0];
        $sequence = PosSale::query()->count() + 1;

        return app(PosSaleService::class)->record($device, $overrides + ['uuid' => (string) Str::uuid(), 'sequence' => $sequence, 'receipt_number' => 'P1-00000'.$sequence, 'sold_at' => now()->toIso8601String(), 'payment_method' => 'cash', 'items' => [['variant_id' => $variant->id, 'name' => 'كريم', 'variant' => null, 'sku' => $variant->sku, 'unit_price' => '10.000', 'quantity' => $quantity]]]);
    }
}
