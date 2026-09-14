<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', [
            'brands' => Brand::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'products' => Product::query()->with(['brand', 'variants', 'images'])->latest()->paginate(20),
        ]);
    }

    public function store(StoreProductRequest $request, ProductService $products): RedirectResponse
    {
        $products->createWithDefaultVariant($request->validated());

        return to_route('admin.products.index')->with('status', 'تمت إضافة المنتج والمتغير الافتراضي.');
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated() + ['is_active' => $request->boolean('is_active'), 'is_featured' => $request->boolean('is_featured'), 'is_new' => $request->boolean('is_new')]);

        return to_route('admin.products.index')->with('status', 'تم تحديث المنتج.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('admin.products.index')->with('status', 'تم حذف المنتج.');
    }
}
