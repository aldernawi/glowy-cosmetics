<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product, ProductImageService $images): RedirectResponse
    {
        $images->store($product, $request->file('image'), $request->string('alt')->toString());

        return to_route('admin.products.index')->with('status', 'تم رفع صورة المنتج.');
    }

    public function destroy(ProductImage $image): RedirectResponse
    {
        $path = $image->path;
        DB::transaction(function () use ($image): void {
            $product = $image->product;
            $wasPrimary = $image->is_primary;
            $image->delete();
            if ($wasPrimary) {
                $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
            }
        });
        Storage::disk('public')->delete($path);

        return to_route('admin.products.index')->with('status', 'تم حذف صورة المنتج.');
    }

    public function makePrimary(ProductImage $image): RedirectResponse
    {
        DB::transaction(function () use ($image): void {
            $image->product->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return to_route('admin.products.index')->with('status', 'تم تعيين الصورة الأساسية.');
    }
}
