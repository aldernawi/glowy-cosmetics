<?php

namespace App\Providers;

use App\Services\CartService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.storefront', function ($view): void {
            $service = app(CartService::class);
            $cart = $service->find(request())?->loadMissing('items.productVariant.product.images');

            $view->with([
                'headerCart' => $cart,
                'headerCartCount' => $cart?->items->sum('quantity') ?? 0,
                'headerCartSubtotal' => $cart ? $service->subtotal($cart) : '0.000',
            ]);
        });
    }
}
