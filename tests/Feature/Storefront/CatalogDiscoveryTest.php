<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_search_and_brand_and_concern_filters_only_show_matching_products(): void
    {
        $brand = Brand::query()->create(['name_ar' => 'لاروش', 'slug' => 'laroche']);
        $otherBrand = Brand::query()->create(['name_ar' => 'سيرافي', 'slug' => 'cerave']);
        $category = Category::query()->create(['name_ar' => 'سيرومات', 'slug' => 'serums']);
        $concern = Concern::query()->create(['name_ar' => 'الجفاف', 'slug' => 'dryness']);
        $matching = Product::query()->create(['brand_id' => $brand->id, 'category_id' => $category->id, 'name_ar' => 'سيروم ترطيب عميق', 'slug' => 'deep-serum', 'is_active' => true]);
        $matching->createDefaultVariant('SERUM-1', '30.000')->update(['stock_quantity' => 2]);
        $matching->concerns()->attach($concern);
        $hidden = Product::query()->create(['brand_id' => $otherBrand->id, 'category_id' => $category->id, 'name_ar' => 'غسول لطيف', 'slug' => 'cleanser', 'is_active' => true]);
        $hidden->createDefaultVariant('CLEAN-1', '20.000');

        $this->get(route('products.index', ['q' => 'ترطيب', 'brand' => 'laroche', 'concern' => 'dryness', 'availability' => 'in_stock']))
            ->assertOk()->assertSee('سيروم ترطيب عميق')->assertDontSee('غسول لطيف');
    }

    public function test_brand_and_concern_landing_pages_are_available(): void
    {
        $brand = Brand::query()->create(['name_ar' => 'لاروش', 'slug' => 'laroche']);
        $concern = Concern::query()->create(['name_ar' => 'الجفاف', 'slug' => 'dryness']);

        $this->get(route('brands.show', $brand))->assertOk()->assertSee('لاروش');
        $this->get(route('concerns.show', $concern))->assertOk()->assertSee('الجفاف');
    }

    public function test_brand_logos_use_a_relative_public_storage_url(): void
    {
        Brand::query()->create(['name_ar' => 'ماركة مرئية', 'slug' => 'visible-brand', 'logo_path' => 'brands/logo.webp']);

        $this->get(route('brands.index'))
            ->assertOk()
            ->assertSee('src="/storage/brands/logo.webp"', false);
    }

    public function test_search_includes_concerns_and_price_bounds_apply_to_the_same_active_variant(): void
    {
        $concern = Concern::query()->create(['name_ar' => 'التصبغات', 'slug' => 'pigmentation']);
        $product = Product::query()->create(['name_ar' => 'عناية مركزة', 'slug' => 'targeted-care']);
        $product->createDefaultVariant('LOW-PRICE', '5.000')->update(['stock_quantity' => 2]);
        $product->variants()->create(['sku' => 'HIGH-PRICE', 'price' => '50.000', 'stock_quantity' => 2, 'is_active' => true]);
        $product->concerns()->attach($concern);

        $this->get(route('products.index', ['q' => 'التصبغات']))->assertOk()->assertSee('عناية مركزة');
        $this->get(route('products.index', ['min_price' => '10.000', 'max_price' => '20.000']))
            ->assertOk()->assertDontSee('عناية مركزة');
    }

    public function test_sale_filter_ignores_inactive_discounted_variants(): void
    {
        $product = Product::query()->create(['name_ar' => 'منتج بلا تخفيض فعال', 'slug' => 'no-active-sale']);
        $product->createDefaultVariant('REGULAR', '20.000')->update(['stock_quantity' => 2]);
        $product->variants()->create(['sku' => 'OLD-SALE', 'price' => '10.000', 'compare_at_price' => '30.000', 'is_active' => false]);

        $this->get(route('products.index', ['sale' => 1]))->assertOk()->assertDontSee('منتج بلا تخفيض فعال');
    }

    public function test_catalog_displays_the_lowest_active_price_and_stock_state(): void
    {
        $product = Product::query()->create(['name_ar' => 'منتج متعدد الأسعار', 'slug' => 'multi-price']);
        $product->createDefaultVariant('EXPENSIVE', '100.000')->update(['stock_quantity' => 3]);
        $product->variants()->create(['sku' => 'AFFORDABLE', 'price' => '5.000', 'stock_quantity' => 0, 'is_active' => true]);

        $this->get(route('products.index'))->assertOk()->assertSee('5.000 د.ل')->assertSee('متوفر');
    }

    public function test_a_parent_category_lists_products_of_its_active_descendants(): void
    {
        $parent = Category::query()->create(['name_ar' => 'العناية', 'slug' => 'care']);
        $child = Category::query()->create(['name_ar' => 'الوجه', 'slug' => 'face', 'parent_id' => $parent->id]);
        $grandchild = Category::query()->create(['name_ar' => 'السيروم', 'slug' => 'face-serum', 'parent_id' => $child->id]);
        $hidden = Category::query()->create(['name_ar' => 'مخفي', 'slug' => 'hidden-child', 'parent_id' => $parent->id, 'is_active' => false]);
        $other = Category::query()->create(['name_ar' => 'الشعر', 'slug' => 'hair']);
        foreach ([[$grandchild, 'منتج الحفيد'], [$hidden, 'منتج المخفي'], [$other, 'منتج الشعر']] as [$category, $name]) {
            Product::query()->create(['category_id' => $category->id, 'name_ar' => $name, 'slug' => 'in-'.$category->slug])->createDefaultVariant('CAT-'.$category->id, '5.000');
        }

        $this->get(route('products.index', ['category' => 'care']))->assertOk()
            ->assertSee('منتج الحفيد')->assertDontSee('منتج المخفي')->assertDontSee('منتج الشعر');
        $this->get(route('products.index', ['category' => 'missing']))->assertOk()->assertDontSee('منتج الحفيد');
    }

    public function test_malformed_array_parameters_do_not_break_storefront_pages(): void
    {
        foreach (['/products?q[]=x', '/products?brand[]=x', '/products?category[a][b]=x&sort[]=name', '/cart?q[]=x', '/?q[]=x'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_the_primary_image_is_the_first_product_image(): void
    {
        $product = Product::query()->create(['name_ar' => 'كريم', 'slug' => 'primary-image']);
        $product->images()->create(['path' => 'products/first.webp', 'sort_order' => 0]);
        $product->images()->create(['path' => 'products/primary.webp', 'sort_order' => 1, 'is_primary' => true]);

        $this->assertSame('products/primary.webp', $product->load('images')->images->first()->path);
    }
}
