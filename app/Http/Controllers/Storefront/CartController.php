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
        return view('storefront.cart.index', ['cart' => $carts->resolve($request)->load('items.productVariant.product')]);
    }
}
