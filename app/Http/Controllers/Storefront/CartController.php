<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __invoke(Request $request, CartService $carts): View
    {
        $cart = $carts->current($request)->loadMissing('items.productVariant.product.images');

        return view('storefront.cart.index', ['cart' => $cart, 'subtotal' => $carts->subtotal($cart)]);
    }
}
