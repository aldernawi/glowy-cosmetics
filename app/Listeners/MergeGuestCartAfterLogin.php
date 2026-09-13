<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class MergeGuestCartAfterLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $request = app(Request::class);
        $carts = app(CartService::class);
        $token = $request->cookie(config('cart.cookie'));

        if (! is_string($token) || $token === '') {
            return;
        }

        $result = $carts->mergeGuestCartIntoUserCart($event->user, $token);
        Cookie::queue(Cookie::forget(config('cart.cookie')));

        if ($result->hasAdjustments()) {
            $request->session()->flash('cart_notice', 'تم تعديل بعض كميات السلة لتتوافق مع المخزون المتاح.');
        }
    }
}
