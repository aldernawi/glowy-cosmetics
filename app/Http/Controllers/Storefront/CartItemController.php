<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreCartItemRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function store(StoreCartItemRequest $request, CartService $carts): JsonResponse|RedirectResponse
    {
        try {
            $cart = $carts->resolve($request);
            $carts->add(
                $cart,
                ProductVariant::query()->findOrFail($request->integer('product_variant_id')),
                $request->integer('quantity'),
            );
        } catch (DomainException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'الكمية المطلوبة غير متوفرة حاليًا.'], 422);
            }

            return to_route('cart.index')->withErrors(['cart' => 'الكمية المطلوبة غير متوفرة حاليًا.']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت إضافة المنتج إلى سلتك.',
                'cart_count' => $cart->items()->sum('quantity'),
            ]);
        }

        return to_route('cart.index')->with('status', 'تمت إضافة المنتج إلى سلتك.');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem, CartService $carts): RedirectResponse
    {
        try {
            $carts->update($carts->resolve($request), $cartItem, $request->integer('quantity'));
        } catch (DomainException) {
            return to_route('cart.index')->withErrors(['cart' => 'الكمية المطلوبة غير متوفرة حاليًا.']);
        }

        return to_route('cart.index')->with('status', 'تم تحديث الكمية.');
    }

    public function destroy(Request $request, CartItem $cartItem, CartService $carts): RedirectResponse
    {
        $carts->remove($carts->resolve($request), $cartItem);

        return to_route('cart.index')->with('status', 'تم حذف المنتج من السلة.');
    }
}
