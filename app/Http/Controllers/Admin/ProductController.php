<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
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
}
