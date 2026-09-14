<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_add_an_active_sellable_variant_to_a_product(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'مرطب', 'slug' => 'moisturizer']);

        $this->actingAs($admin)->post(route('admin.products.variants.store', $product), ['sku' => 'MOIST-50', 'name' => '50ml', 'price' => '18.500', 'stock_quantity' => 4, 'low_stock_threshold' => 2, 'is_active' => true])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'sku' => 'MOIST-50', 'price' => '18.500', 'stock_quantity' => 4]);
    }

    public function test_an_administrator_can_update_and_remove_a_non_default_variant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'مرطب', 'slug' => 'moisturizer']);
        $variant = $product->variants()->create(['sku' => 'MOIST-100', 'price' => '20.000', 'stock_quantity' => 1]);

        $this->actingAs($admin)->patch(route('admin.variants.update', $variant), ['sku' => 'MOIST-100', 'price' => '22.000', 'stock_quantity' => 3, 'low_stock_threshold' => 2, 'is_active' => true])->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'price' => '22.000', 'stock_quantity' => 3]);
        $this->actingAs($admin)->delete(route('admin.variants.destroy', $variant))->assertRedirect(route('admin.products.index'));
        $this->assertModelMissing($variant);
    }
}
