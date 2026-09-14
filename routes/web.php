<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliverySettingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Storefront\BrandController as StorefrontBrandController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CartItemController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ConcernController as StorefrontConcernController;
use App\Http\Controllers\Storefront\CustomerOrderController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/cart', CartController::class)->name('cart.index');
Route::post('/cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{cartItem}', [CartItemController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');
Route::get('/checkout', CheckoutController::class)->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
Route::get('/checkout/success', [CustomerOrderController::class, 'success'])->name('checkout.success');
Route::get('/products', [StorefrontProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [StorefrontProductController::class, 'show'])->name('products.show');
Route::get('/brands/{brand}', [StorefrontBrandController::class, 'show'])->name('brands.show');
Route::get('/concerns/{concern}', [StorefrontConcernController::class, 'show'])->name('concerns.show');

Route::get('/dashboard', CustomerDashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('customer.orders.index');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('customer.orders.show');
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
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::get('concerns', [TaxonomyController::class, 'concerns'])->name('concerns.index');
    Route::post('concerns', [TaxonomyController::class, 'storeConcern'])->name('concerns.store');
    Route::get('skin-types', [TaxonomyController::class, 'skinTypes'])->name('skin-types.index');
    Route::post('skin-types', [TaxonomyController::class, 'storeSkinType'])->name('skin-types.store');
    Route::get('ingredients', [TaxonomyController::class, 'ingredients'])->name('ingredients.index');
    Route::post('ingredients', [TaxonomyController::class, 'storeIngredient'])->name('ingredients.store');
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
    Route::get('stock/adjustments', [StockAdjustmentController::class, 'index'])->name('stock.adjustments.index');
    Route::post('stock/adjustments', [StockAdjustmentController::class, 'store'])->name('stock.adjustments.store');
    Route::get('delivery', [DeliverySettingController::class, 'index'])->name('delivery.index');
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
    Route::patch('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::patch('orders/{order}/payment', [OrderController::class, 'updatePayment'])->name('orders.payment.update');
    Route::put('delivery/origin', [DeliverySettingController::class, 'updateOrigin'])->name('delivery.origin.update');
    Route::post('delivery/tiers', [DeliverySettingController::class, 'storeTier'])->name('delivery.tiers.store');
});

require __DIR__.'/auth.php';
