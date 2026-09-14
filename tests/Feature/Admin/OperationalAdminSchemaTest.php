<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperationalAdminSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_variants_default_to_a_low_stock_threshold_and_operational_roles_are_seeded(): void
    {
        $variant = Product::query()->create([
            'brand_id' => Brand::query()->create(['name_ar' => 'ماركة', 'slug' => 'brand'])->id,
            'category_id' => Category::query()->create(['name_ar' => 'تصنيف', 'slug' => 'category'])->id,
            'name_ar' => 'منتج',
            'slug' => 'product',
        ])->createDefaultVariant('LOW-STOCK-1', '12.000');

        $this->assertSame(5, $variant->refresh()->low_stock_threshold);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue(Role::findByName('administrator')->hasPermissionTo('admin.access'));
        $this->assertTrue(Role::findByName('catalog-manager')->hasPermissionTo('catalog.manage'));
    }
}
