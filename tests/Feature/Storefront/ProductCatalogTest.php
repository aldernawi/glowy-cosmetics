<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_can_browse_active_products_and_open_their_details(): void
    {
        $brand = Brand::query()->create(['name_ar' => 'غلووي', 'slug' => 'glowy']);
        $category = Category::query()->create(['name_ar' => 'العناية', 'slug' => 'skincare']);
        $concern = Concern::query()->create(['name_ar' => 'الجفاف', 'slug' => 'dryness']);
        $product = Product::query()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name_ar' => 'سيروم الترطيب', 'slug' => 'hydration-serum', 'is_active' => true,
            'benefits' => 'يدعم حاجز البشرة', 'usage' => 'يستخدم مساءً', 'warnings' => 'للاستخدام الخارجي فقط',
        ]);
        $product->createDefaultVariant('GLW-CATALOG-1', '25.500')->update(['stock_quantity' => 4, 'size' => '30 مل']);
        $product->variants()->create(['sku' => 'GLW-CATALOG-2', 'name' => 'الحجم الكبير', 'size' => '60 مل', 'price' => '42.000', 'stock_quantity' => 2, 'is_active' => true]);
        $product->concerns()->attach($concern);
        $sameBrand = Product::query()->create(['brand_id' => $brand->id, 'name_ar' => 'مرطب من نفس الماركة', 'slug' => 'brand-moisturizer']);
        $sameBrand->createDefaultVariant('GLW-SAME-BRAND', '19.000');
        $routine = Product::query()->create(['name_ar' => 'تونر يكمل الروتين', 'slug' => 'routine-toner']);
        $routine->createDefaultVariant('GLW-ROUTINE', '15.000');
        $routine->concerns()->attach($concern);

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
            ->assertSee('تنبيهات')
            ->assertSee('من نفس الماركة')
            ->assertSee('مرطب من نفس الماركة')
            ->assertSee('يكمّل روتينك')
            ->assertSee('تونر يكمل الروتين');
    }

    public function test_empty_optional_product_sections_are_not_rendered(): void
    {
        $product = Product::query()->create(['name_ar' => 'منتج بسيط', 'slug' => 'simple-product']);
        $product->createDefaultVariant('SIMPLE-1', '10.000');

        $this->get(route('products.show', $product))->assertOk()
            ->assertDontSee('الفوائد')->assertDontSee('طريقة الاستخدام')->assertDontSee('تنبيهات')->assertDontSee('المكونات البارزة');
    }

    public function test_catalog_cards_add_a_single_sellable_variant_to_the_cart_and_link_multi_variant_products_to_their_choices(): void
    {
        $single = Product::query()->create(['name_ar' => 'غسول سريع', 'slug' => 'quick-cleanser']);
        $singleVariant = $single->createDefaultVariant('QUICK-1', '14.000');
        $singleVariant->update(['stock_quantity' => 2]);

        $multiple = Product::query()->create(['name_ar' => 'سيروم متعدد الأحجام', 'slug' => 'multi-serum']);
        $multiple->createDefaultVariant('MULTI-1', '20.000')->update(['stock_quantity' => 2]);
        $multiple->variants()->create(['sku' => 'MULTI-2', 'name' => '60 مل', 'price' => '30.000', 'stock_quantity' => 2, 'is_active' => true]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('أضيفي إلى السلة')
            ->assertSee('product_variant_id" value="'.$singleVariant->id.'"', false)
            ->assertSee('اختاري الحجم');
    }

    public function test_product_structured_data_cannot_break_out_of_its_script_tag(): void
    {
        $product = Product::query()->create(['name_ar' => 'سيروم', 'slug' => 'structured-data', 'description' => '</script><script>alert(1)</script> نقاء & وهج']);
        $product->createDefaultVariant('STRUCTURED-1', '10.000');

        $html = $this->get(route('products.show', $product))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches));
        $this->assertSame($product->description, json_decode($matches[1], true)['description']);
    }
}
