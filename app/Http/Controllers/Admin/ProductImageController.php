<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product): RedirectResponse
    {
        $path = $request->file('image')->store("products/{$product->id}", 'public');
        $product->images()->create(['path' => $path, 'alt_text' => $request->string('alt')->toString(), 'sort_order' => $product->images()->count(), 'is_primary' => ! $product->images()->exists()]);

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
