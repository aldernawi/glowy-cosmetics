<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;

class ProductVariantController extends Controller
{
    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $product->variants()->create($request->validated() + ['is_active' => $request->boolean('is_active'), 'is_default' => false]);

        return to_route('admin.products.index')->with('status', 'تمت إضافة المتغير القابل للبيع.');
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $variant): RedirectResponse
    {
        $variant->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return to_route('admin.products.index')->with('status', 'تم تحديث المتغير.');
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        abort_if($variant->is_default, 422, 'لا يمكن حذف المتغير الافتراضي.');
        $variant->delete();

        return to_route('admin.products.index')->with('status', 'تم حذف المتغير.');
    }
}
