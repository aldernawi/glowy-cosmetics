<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Product;
use App\Models\SkinType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['brand', 'images', 'variants' => fn ($query) => $query->where('is_active', true)])
            ->withMin(['variants as catalog_price' => fn ($query) => $query->where('is_active', true)], 'price')
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->string('q'))).'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name_ar', 'like', $term)
                        ->orWhere('name_en', 'like', $term)
                        ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name_ar', 'like', $term)->orWhere('name_en', 'like', $term))
                        ->orWhereHas('variants', fn (Builder $variant) => $variant->where('sku', 'like', $term));
                });
            })
            ->when($request->filled('brand'), fn (Builder $query) => $query->whereHas('brand', fn (Builder $brand) => $brand->where('slug', $request->string('brand'))))
            ->when($request->filled('category'), fn (Builder $query) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $request->string('category'))))
            ->when($request->filled('concern'), fn (Builder $query) => $query->whereHas('concerns', fn (Builder $concern) => $concern->where('slug', $request->string('concern'))))
            ->when($request->filled('skin_type'), fn (Builder $query) => $query->whereHas('skinTypes', fn (Builder $skinType) => $skinType->where('slug', $request->string('skin_type'))))
            ->when($request->string('availability')->value() === 'in_stock', fn (Builder $query) => $query->whereHas('variants', fn (Builder $variant) => $variant->where('is_active', true)->where('stock_quantity', '>', 0)))
            ->when($request->boolean('sale'), fn (Builder $query) => $query->whereHas('variants', fn (Builder $variant) => $variant->whereColumn('compare_at_price', '>', 'price')))
            ->when($request->boolean('new'), fn (Builder $query) => $query->where('is_new', true))
            ->when($request->filled('min_price'), fn (Builder $query) => $query->whereHas('variants', fn (Builder $variant) => $variant->where('price', '>=', $request->string('min_price'))))
            ->when($request->filled('max_price'), fn (Builder $query) => $query->whereHas('variants', fn (Builder $variant) => $variant->where('price', '<=', $request->string('max_price'))));

        match ($request->string('sort')->value()) {
            'price_asc' => $products->orderBy('catalog_price'),
            'price_desc' => $products->orderByDesc('catalog_price'),
            'name' => $products->orderBy('name_ar'),
            default => $products->latest(),
        };

        return view('storefront.products.index', [
            'products' => $products->paginate(24)->withQueryString(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'concerns' => Concern::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'skinTypes' => SkinType::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('storefront.products.show', [
            'product' => $product->load([
                'brand', 'category', 'images', 'skinTypes', 'concerns', 'ingredients',
                'variants' => fn ($query) => $query->where('is_active', true),
            ]),
            'relatedProducts' => Product::query()->where('is_active', true)->whereKeyNot($product->id)
                ->when($product->category_id, fn (Builder $query) => $query->where('category_id', $product->category_id))
                ->with(['brand', 'images', 'variants' => fn ($query) => $query->where('is_active', true)])
                ->limit(4)->get(),
        ]);
    }
}
