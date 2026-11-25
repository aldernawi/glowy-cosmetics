<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BarcodeEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->create();
        $this->manager->assignRole('catalog-manager');
    }

    public function test_the_page_lists_active_variants_and_is_linked_from_the_sidebar(): void
    {
        $this->variant('LIST-1');
        $this->variant('HIDDEN-1')->product->update(['is_active' => false]);

        $this->actingAs($this->manager)->get(route('admin.barcodes.index'))->assertOk()
            ->assertSee('"sku":"LIST-1"', false)
            ->assertDontSee('HIDDEN-1')
            ->assertSee('href="'.route('admin.barcodes.index').'"', false);
    }

    public function test_a_scanned_barcode_is_saved_normalized_and_can_be_removed(): void
    {
        $variant = $this->variant('SCAN-1');

        $this->scan($variant, ' ٦٢٨١٠٠٠٠٠٠٠١٧ ')->assertOk()->assertJsonPath('barcode', '6281000000017');
        $this->assertSame('6281000000017', $variant->refresh()->barcode);
        $this->scan($variant, '6281000000017')->assertOk();

        $this->scan($variant, null)->assertOk()->assertJsonPath('barcode', null);
        $this->assertNull($variant->refresh()->barcode);
    }

    public function test_a_duplicate_names_the_product_that_already_owns_the_code(): void
    {
        $this->scan($this->variant('OWNER-1'), '6281000000017')->assertOk();
        $other = $this->variant('OTHER-1');

        $this->scan($other, '6281000000017')->assertUnprocessable()
            ->assertJsonPath('errors.barcode.0', 'هذا الباركود مسجل للمنتج «منتج OWNER-1» (OWNER-1).');
        $this->assertNull($other->refresh()->barcode);
    }

    public function test_codes_typed_on_an_arabic_layout_or_too_long_are_refused(): void
    {
        $variant = $this->variant('BAD-1');

        foreach (['شسي', '628 100', '<script>', str_repeat('1', 65)] as $barcode) {
            $this->scan($variant, $barcode)->assertUnprocessable()->assertJsonValidationErrors('barcode');
        }
        $this->assertNull($variant->refresh()->barcode);
    }

    public function test_only_the_barcode_can_change_through_this_page(): void
    {
        $variant = $this->variant('SAFE-1');

        $this->actingAs($this->manager)->patchJson(route('admin.barcodes.update', $variant), ['barcode' => 'A1', 'price' => '1.000', 'stock_quantity' => 999])->assertOk();

        $this->assertSame(['12.500', 0], [$variant->refresh()->price, $variant->stock_quantity]);
    }

    public function test_staff_without_product_permission_cannot_open_or_save(): void
    {
        $variant = $this->variant('DENY-1');
        $cashier = User::factory()->create();
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)->get(route('admin.barcodes.index'))->assertForbidden();
        $this->actingAs($cashier)->patchJson(route('admin.barcodes.update', $variant), ['barcode' => '1'])->assertForbidden();
    }

    private function scan(ProductVariant $variant, ?string $barcode): TestResponse
    {
        return $this->actingAs($this->manager)->patchJson(route('admin.barcodes.update', $variant), ['barcode' => $barcode]);
    }

    private function variant(string $sku): ProductVariant
    {
        return Product::query()->create(['name_ar' => 'منتج '.$sku, 'slug' => strtolower($sku)])->createDefaultVariant($sku, '12.500');
    }
}
