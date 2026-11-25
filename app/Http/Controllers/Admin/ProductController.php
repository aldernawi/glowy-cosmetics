<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\SkinType;
use App\Services\MediaCleanupService;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()->with(['brand', 'category', 'variants', 'images']);
        $products->when($request->filled('q'), fn ($query) => $query->where(fn ($nested) => $nested->where('name_ar', 'like', '%'.$request->string('q').'%')->orWhere('slug', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('brand_id'), fn ($query) => $query->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->when($request->filled('featured'), fn ($query) => $query->where('is_featured', $request->boolean('featured')))
            ->when($request->string('stock')->value() === 'out', fn ($query) => $query->whereDoesntHave('variants', fn ($variant) => $variant->where('stock_quantity', '>', 0)))
            ->when($request->string('stock')->value() === 'low', fn ($query) => $query->whereHas('variants', fn ($variant) => $variant->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')));

        return view('admin.products.index', [
            'brands' => Brand::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'products' => $products->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', $this->formData());
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', $this->formData(['product' => $product->load(['variants', 'images', 'skinTypes', 'concerns', 'ingredients'])]));
    }

    public function store(StoreProductRequest $request, ProductService $products): RedirectResponse
    {
        $product = $products->createWithDefaultVariant($request->validated(), $request->file('images', []));

        return to_route('admin.products.edit', $product)->with('status', 'تمت إضافة المنتج والمتغير الافتراضي.');
    }

    public function update(UpdateProductRequest $request, Product $product, ProductService $products): RedirectResponse
    {
        $products->update($product, $request->validated() + ['is_active' => $request->boolean('is_active'), 'is_featured' => $request->boolean('is_featured'), 'is_new' => $request->boolean('is_new')], $request->file('images', []));

        return to_route('admin.products.edit', $product)->with('status', 'تم تحديث المنتج.');
    }

    public function destroy(Product $product, MediaCleanupService $media, ProductService $products): RedirectResponse
    {
        $paths = $products->deleteOrArchive($product);
        $paths->each(fn (string $path) => $media->deleteProductImageIfUnreferenced($path));

        return to_route('admin.products.index')->with('status', $paths->isEmpty() ? 'تمت أرشفة المنتج للحفاظ على سجل الطلبات.' : 'تم حذف المنتج.');
    }

    /** @param array<string, mixed> $extra */
    private function formData(array $extra = []): array
    {
        return array_merge(['brands' => Brand::query()->orderBy('name_ar')->get(), 'categories' => Category::query()->orderBy('name_ar')->get(), 'skinTypes' => SkinType::query()->orderBy('name_ar')->get(), 'concerns' => Concern::query()->orderBy('name_ar')->get(), 'ingredients' => Ingredient::query()->orderBy('name_ar')->get()], $extra);
    }
}
