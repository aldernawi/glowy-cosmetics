<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreCartItemRequest;
use App\Models\ProductVariant;
use App\Services\CartService;
use DomainException;
use Illuminate\Http\RedirectResponse;

class CartItemController extends Controller
{
    public function store(StoreCartItemRequest $request, CartService $carts): RedirectResponse
    {
        try {
            $carts->add(
                $carts->resolve($request),
                ProductVariant::query()->findOrFail($request->integer('product_variant_id')),
                $request->integer('quantity'),
            );
        } catch (DomainException) {
            return to_route('cart.index')->withErrors(['cart' => 'الكمية المطلوبة غير متوفرة حاليًا.']);
        }

        return to_route('cart.index')->with('status', 'تمت إضافة المنتج إلى سلتك.');
    }
}
