<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\OutOfDeliveryRangeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\DeliveryQuoteRequest;
use App\Http\Requests\Storefront\StoreCheckoutRequest;
use App\Models\DeliverySetting;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DeliveryService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CheckoutController extends Controller
{
    private const OUT_OF_RANGE_MESSAGE = 'موقعك خارج نطاق التوصيل الحالي. نأسف لذلك، ويسعدنا تواصلك معنا لنساعدك.';

    public function __invoke(Request $request, CartService $carts): View|RedirectResponse
    {
        $cart = $carts->current($request)->loadMissing('items.productVariant.product');

        if ($cart->items->isEmpty()) {
            return to_route('cart.index');
        }

        return view('storefront.checkout.index', [
            'cart' => $cart,
            'subtotal' => $carts->subtotal($cart),
            'origin' => DeliverySetting::current(),
        ]);
    }

    public function store(StoreCheckoutRequest $request, CartService $carts, DeliveryService $delivery, CheckoutService $checkout): RedirectResponse
    {
        if ($carts->current($request)->items->isEmpty()) {
            return to_route('cart.index');
        }

        try {
            $quote = $delivery->quote($request->string('latitude')->toString(), $request->string('longitude')->toString());
            $order = $checkout->create($carts->resolve($request), $request->user(), $quote, ['customer_name' => $request->string('customer_name')->toString(), 'phone' => $request->string('phone')->toString(), 'secondary_phone' => $request->string('secondary_phone')->toString(), 'city_region' => $request->string('city_region')->toString(), 'address' => $request->string('address')->toString(), 'landmark' => $request->string('landmark')->toString(), 'place_id' => $request->string('place_id')->toString(), 'notes' => $request->string('notes')->toString(), 'latitude' => $request->string('latitude')->toString(), 'longitude' => $request->string('longitude')->toString()]);
        } catch (OutOfDeliveryRangeException) {
            return back()->withInput()->withErrors(['checkout' => self::OUT_OF_RANGE_MESSAGE]);
        } catch (DomainException|RuntimeException) {
            return back()->withInput()->withErrors(['checkout' => 'تعذر تأكيد الطلب. يرجى تحديث موقع التوصيل أو السلة ثم المحاولة.']);
        }

        $request->session()->put('last_order_number', $order->number);

        return to_route('checkout.success');
    }

    public function quote(DeliveryQuoteRequest $request, DeliveryService $delivery): JsonResponse
    {
        try {
            $quote = $delivery->quote($request->string('latitude')->toString(), $request->string('longitude')->toString());
        } catch (OutOfDeliveryRangeException) {
            return response()->json(['message' => self::OUT_OF_RANGE_MESSAGE], 422);
        } catch (DomainException|RuntimeException) {
            return response()->json(['message' => 'تعذر حساب رسوم التوصيل لهذا الموقع.'], 422);
        }

        return response()->json(['distance_km' => $quote->distanceKm, 'duration_minutes' => (int) ceil($quote->durationSeconds / 60), 'delivery_fee' => $quote->fee]);
    }
}
