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
}
