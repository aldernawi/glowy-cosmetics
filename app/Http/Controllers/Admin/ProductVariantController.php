<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use DomainException;
use Illuminate\Http\RedirectResponse;

class ProductVariantController extends Controller
{
    public function __construct(private ProductVariantService $variants) {}

    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $this->variants->create($product, array_merge($request->validated(), ['is_active' => $request->boolean('is_active')]), $request->user());

        return to_route('admin.products.index')->with('status', 'تمت إضافة المتغير القابل للبيع.');
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $variant): RedirectResponse
    {
        $mayAdjustStock = $request->has('stock_quantity') && $request->user()->canAny(['admin.access', 'inventory.adjust']);

        try {
            $this->variants->update(
                $variant,
                array_merge($request->validated(), ['is_active' => $request->boolean('is_active')]),
                $mayAdjustStock ? $request->integer('stock_quantity') : null,
                $request->filled('stock_quantity_original') ? $request->integer('stock_quantity_original') : null,
                $request->user(),
            );
        } catch (DomainException) {
            return back()->withErrors(['stock_quantity' => 'تغيّر المخزون أثناء التحرير ولم يعد يكفي لهذا التعديل. راجعي الرصيد الحالي ثم حاولي مجددًا.']);
        }

        return to_route('admin.products.index')->with('status', 'تم تحديث المتغير.');
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        abort_if($variant->is_default, 422, 'لا يمكن حذف المتغير الافتراضي.');
        $deleted = $this->variants->deleteOrArchive($variant);

        return to_route('admin.products.index')->with('status', $deleted ? 'تم حذف المتغير.' : 'تم إخفاء المتغير بدل حذفه للحفاظ على سجل الطلبات والمخزون.');
    }
}
