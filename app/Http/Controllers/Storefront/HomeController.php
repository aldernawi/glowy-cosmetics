<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $productRelations = ['brand', 'images', 'variants' => fn ($query) => $query->where('is_active', true)];

        return view('storefront.home', [
            'concerns' => Concern::query()->where('is_active', true)->orderBy('sort_order')->limit(8)->get(),
            'categories' => Category::query()->where('is_active', true)->where('is_featured', true)->orderBy('sort_order')->limit(8)->get(),
            'brands' => Brand::query()->where('is_active', true)->where('is_featured', true)->orderBy('sort_order')->limit(8)->get(),
            'newProducts' => Product::query()->where('is_active', true)->where('is_new', true)->with($productRelations)->latest()->limit(4)->get(),
            'bestSellers' => Product::query()->where('is_active', true)->where('is_featured', true)->with($productRelations)->latest()->limit(4)->get(),
        ]);
    }
}
