<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_and_delete_an_empty_product(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'قديم', 'slug' => 'old-product']);

        $this->actingAs($admin)->patch(route('admin.products.update', $product), ['name_ar' => 'جديد', 'slug' => 'new-product', 'is_active' => true])->assertRedirect(route('admin.products.edit', 'new-product'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name_ar' => 'جديد']);
        $product->refresh();
        $this->actingAs($admin)->delete(route('admin.products.destroy', $product))->assertRedirect(route('admin.products.index'));
        $this->assertModelMissing($product);
    }
}
