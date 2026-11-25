<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Concern;
use App\Services\CartService;
use App\Services\HomepageService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

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
        RateLimiter::for('geocoding', fn (Request $request) => [
            Limit::perSecond(1)->by('geocoding:'.$request->ip()),
            Limit::perMinute(20)->by('geocoding:'.$request->ip()),
        ]);

        RateLimiter::for('checkout', function (Request $request): array {
            $customer = $request->user()?->getAuthIdentifier() ?? $request->session()->getId();
            $response = function (Request $request, array $headers): Response {
                $message = 'محاولات كثيرة خلال وقت قصير. انتظري دقيقة ثم أعيدي المحاولة.';

                return $request->expectsJson()
                    ? response()->json(['message' => $message], 429, $headers)
                    : back()->withInput()->withErrors(['checkout' => $message]);
            };

            return [
                Limit::perMinute(10)->by('checkout-customer:'.$customer)->response($response),
                Limit::perMinute(40)->by('checkout-ip:'.$request->ip())->response($response),
            ];
        });

        View::composer('layouts.storefront', function ($view): void {
            $service = app(CartService::class);
            $cart = $service->find(request())?->loadMissing('items.productVariant.product.images');

            $view->with([
                'headerCart' => $cart,
                'headerCartCount' => $cart?->items->sum('quantity') ?? 0,
                'headerCartSubtotal' => $cart ? $service->subtotal($cart) : '0.000',
                'headerSunscreenCategory' => Category::query()->where('is_active', true)->where('name_ar', 'like', '%واقي الشمس%')->first(),
                'headerCategories' => Category::query()->where('is_active', true)->orderBy('sort_order')->limit(6)->get(),
                'headerConcerns' => Concern::query()->where('is_active', true)->orderBy('sort_order')->limit(8)->get(),
                'storeContent' => app(HomepageService::class)->all(),
            ]);
        });
    }
}
