<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class ProductVariantController extends Controller
{
    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $product->variants()->create($request->validated() + ['is_active' => $request->boolean('is_active'), 'is_default' => false]);

        return to_route('admin.products.index')->with('status', 'تمت إضافة المتغير القابل للبيع.');
    }
}
