<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Concern;
use App\Models\HomepageBanner;
use App\Models\Product;
use App\Services\HomepageService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(HomepageService $homepage): View
    {
        $productRelations = ['brand', 'images', 'variants' => fn ($query) => $query->where('is_active', true)];
        $content = $homepage->all();

        return view('storefront.home', [
            'content' => $content,
            'sections' => $homepage->sections(),
            'banners' => HomepageBanner::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'concerns' => Concern::query()->where('is_active', true)->when($content['featured_concern_ids'], fn ($query) => $query->whereKey($content['featured_concern_ids']))->orderBy('sort_order')->limit(8)->get(),
            'categories' => Category::query()->where('is_active', true)->when($content['featured_category_ids'], fn ($query) => $query->whereKey($content['featured_category_ids']), fn ($query) => $query->where('is_featured', true))->orderBy('sort_order')->limit(8)->get(),
            'brands' => Brand::query()->where('is_active', true)->when($content['featured_brand_ids'], fn ($query) => $query->whereKey($content['featured_brand_ids']), fn ($query) => $query->where('is_featured', true))->orderBy('sort_order')->limit(8)->get(),
            'newProducts' => Product::query()->sellable()->when($content['new_product_ids'], fn ($query) => $query->whereKey($content['new_product_ids']), fn ($query) => $query->where('is_new', true))->with($productRelations)->latest()->limit(4)->get(),
            'bestSellers' => Product::query()->sellable()->when($content['best_seller_ids'], fn ($query) => $query->whereKey($content['best_seller_ids']), fn ($query) => $query->where('is_featured', true))->with($productRelations)->latest()->limit(4)->get(),
        ]);
    }
}
