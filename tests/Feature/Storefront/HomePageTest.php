<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_storefront_homepage_renders_as_an_arabic_rtl_document(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('Glowy');
    }

    public function test_homepage_exposes_the_complete_discovery_journey_from_database_content(): void
    {
        $brand = Brand::query()->create(['name_ar' => 'لاروش بوزيه', 'slug' => 'laroche', 'is_featured' => true]);
        $category = Category::query()->create(['name_ar' => 'السيرومات', 'slug' => 'serums', 'is_featured' => true]);
        $concern = Concern::query()->create(['name_ar' => 'الجفاف والترطيب', 'slug' => 'hydration']);
        $product = Product::query()->create(['brand_id' => $brand->id, 'category_id' => $category->id, 'name_ar' => 'سيروم الهيالورونيك', 'slug' => 'ha-serum', 'is_active' => true, 'is_new' => true, 'is_featured' => true]);
        $product->createDefaultVariant('HA-1', '32.000')->update(['stock_quantity' => 3]);
        $product->concerns()->attach($concern);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('تسوّقي حسب احتياج بشرتك')
            ->assertSee('الجفاف والترطيب')
            ->assertSee('تسوّقي حسب التصنيف')
            ->assertSee('وصل حديثًا')
            ->assertSee('الأكثر طلبًا')
            ->assertSee('ماركات نثق بها')
            ->assertSee('روتين عناية أبسط')
            ->assertSee('لماذا Glowy؟')
            ->assertSee('سيروم الهيالورونيك');
    }
}
