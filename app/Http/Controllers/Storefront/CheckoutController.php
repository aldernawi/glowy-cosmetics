<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreCheckoutRequest;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DeliveryService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __invoke(Request $request, CartService $carts): View
    {
        return view('storefront.checkout.index', ['cart' => $carts->resolve($request)->load('items.productVariant.product')]);
    }

    public function store(StoreCheckoutRequest $request, CartService $carts, DeliveryService $delivery, CheckoutService $checkout): RedirectResponse
    {
        try {
            $quote = $delivery->quote($request->string('latitude')->toString(), $request->string('longitude')->toString());
            $order = $checkout->create($carts->resolve($request), $request->user(), $quote, ['customer_name' => $request->string('customer_name')->toString(), 'phone' => $request->string('phone')->toString(), 'latitude' => $request->string('latitude')->toString(), 'longitude' => $request->string('longitude')->toString()]);
        } catch (DomainException|RuntimeException $e) {
            return back()->withErrors(['checkout' => 'تعذر تأكيد الطلب. يرجى تحديث موقع التوصيل أو السلة ثم المحاولة.']);
        }

        return to_route('checkout.index')->with('status', 'تم إنشاء طلبك بنجاح: '.$order->number);
    }
}
