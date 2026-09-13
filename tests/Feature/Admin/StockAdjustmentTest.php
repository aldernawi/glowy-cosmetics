<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_adjust_variant_stock(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');
        $variant = Product::query()->create([
            'brand_id' => Brand::query()->create(['name_ar' => 'اختبار', 'slug' => 'test-brand'])->id,
            'category_id' => Category::query()->create(['name_ar' => 'اختبار', 'slug' => 'test-category'])->id,
            'name_ar' => 'منتج اختبار', 'slug' => 'test-product',
        ])->createDefaultVariant('GLW-ADJ-1', '10.000');

        $this->actingAs($administrator)
            ->post(route('admin.stock.adjustments.store'), [
                'product_variant_id' => $variant->id,
                'delta' => 8,
                'type' => 'manual_in',
                'notes' => 'رصيد افتتاحي',
            ])
            ->assertRedirect(route('admin.stock.adjustments.index'));

        $this->assertSame(8, $variant->refresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $variant->id, 'delta' => 8, 'type' => 'manual_in']);
    }
}
