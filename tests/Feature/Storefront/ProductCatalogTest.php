<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_can_browse_active_products_and_open_their_details(): void
    {
        $product = Product::query()->create([
            'brand_id' => Brand::query()->create(['name_ar' => 'غلووي', 'slug' => 'glowy'])->id,
            'category_id' => Category::query()->create(['name_ar' => 'العناية', 'slug' => 'skincare'])->id,
            'name_ar' => 'سيروم الترطيب', 'slug' => 'hydration-serum', 'is_active' => true,
            'benefits' => 'يدعم حاجز البشرة', 'usage' => 'يستخدم مساءً', 'warnings' => 'للاستخدام الخارجي فقط',
        ]);
        $product->createDefaultVariant('GLW-CATALOG-1', '25.500')->update(['stock_quantity' => 4, 'size' => '30 مل']);
        $product->variants()->create(['sku' => 'GLW-CATALOG-2', 'name' => 'الحجم الكبير', 'size' => '60 مل', 'price' => '42.000', 'stock_quantity' => 2, 'is_active' => true]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('سيروم الترطيب');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('سيروم الترطيب')
            ->assertSee('أضيفي إلى السلة')
            ->assertSee('30 مل')
            ->assertSee('60 مل')
            ->assertSee('الفوائد')
            ->assertSee('طريقة الاستخدام')
            ->assertSee('تنبيهات');
    }
}
