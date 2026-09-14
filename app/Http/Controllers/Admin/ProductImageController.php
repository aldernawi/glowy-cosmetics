<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product): RedirectResponse
    {
        $path = $request->file('image')->store("products/{$product->id}", 'public');
        $product->images()->create(['path' => $path, 'alt_text' => $request->string('alt')->toString(), 'sort_order' => $product->images()->count(), 'is_primary' => ! $product->images()->exists()]);

        return to_route('admin.products.index')->with('status', 'تم رفع صورة المنتج.');
    }
}
