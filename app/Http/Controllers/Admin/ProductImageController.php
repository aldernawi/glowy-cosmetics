<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\MediaCleanupService;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product, ProductImageService $images): RedirectResponse
    {
        collect($request->file('images', []))->prepend($request->file('image'))->filter()->each(fn ($file) => $images->store($product, $file, $request->string('alt')->toString()));

        return to_route('admin.products.edit', $product)->with('status', 'تم رفع صور المنتج.');
    }

    public function destroy(ProductImage $image, MediaCleanupService $media): RedirectResponse
    {
        $path = $image->path;
        $product = $image->product;
        DB::transaction(function () use ($image): void {
            $product = $image->product;
            $wasPrimary = $image->is_primary;
            $image->delete();
            if ($wasPrimary) {
                $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
            }
        });
        $media->deleteProductImageIfUnreferenced($path);

        return to_route('admin.products.edit', $product)->with('status', 'تم حذف صورة المنتج.');
    }

    public function makePrimary(ProductImage $image): RedirectResponse
    {
        DB::transaction(function () use ($image): void {
            $image->product->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return to_route('admin.products.edit', $image->product)->with('status', 'تم تعيين الصورة الأساسية.');
    }

    public function move(ProductImage $image, Request $request, ProductImageService $images): RedirectResponse
    {
        $validated = $request->validate(['direction' => ['required', 'in:up,down']]);
        $images->move($image, $validated['direction']);

        return to_route('admin.products.edit', $image->product)->with('status', 'تم ترتيب صورة المنتج.');
    }
}
