<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliverySettingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CartItemController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/cart', CartController::class)->name('cart.index');
Route::post('/cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
Route::get('/checkout', CheckoutController::class)->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/products', [StorefrontProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [StorefrontProductController::class, 'show'])->name('products.show');

Route::get('/dashboard', CustomerDashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/admin', DashboardController::class)
    ->middleware(['auth', 'permission:admin.access'])
    ->name('admin.dashboard');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'permission:admin.access'])->group(function (): void {
    Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
    Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('stock/adjustments', [StockAdjustmentController::class, 'index'])->name('stock.adjustments.index');
    Route::post('stock/adjustments', [StockAdjustmentController::class, 'store'])->name('stock.adjustments.store');
    Route::get('delivery', [DeliverySettingController::class, 'index'])->name('delivery.index');
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::put('delivery/origin', [DeliverySettingController::class, 'updateOrigin'])->name('delivery.origin.update');
    Route::post('delivery/tiers', [DeliverySettingController::class, 'storeTier'])->name('delivery.tiers.store');
});

require __DIR__.'/auth.php';
