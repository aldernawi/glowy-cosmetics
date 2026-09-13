<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_product_with_its_default_variant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');

        $this->actingAs($administrator)
            ->post(route('admin.products.store'), [
                'brand_id' => Brand::query()->create(['name_ar' => 'سيرافي', 'slug' => 'cerave'])->id,
                'category_id' => Category::query()->create(['name_ar' => 'غسولات', 'slug' => 'cleansers'])->id,
                'name_ar' => 'غسول لطيف',
                'slug' => 'gentle-cleanser',
                'sku' => 'GLW-CLN-100',
                'price' => '95.000',
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['slug' => 'gentle-cleanser']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'GLW-CLN-100', 'price' => '95.000', 'is_default' => true]);
    }
}
