<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('storefront.products.index', [
            'products' => Product::query()
                ->where('is_active', true)
                ->with(['brand', 'images', 'variants' => fn ($query) => $query->where('is_active', true)])
                ->latest()
                ->paginate(24),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('storefront.products.show', [
            'product' => $product->load([
                'brand', 'images', 'skinTypes', 'concerns',
                'variants' => fn ($query) => $query->where('is_active', true),
            ]),
        ]);
    }
}
