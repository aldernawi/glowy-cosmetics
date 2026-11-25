<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliverySettingController;
use App\Http\Controllers\Admin\HomepageContentController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ReportController;
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
use App\Http\Controllers\Storefront\LocationSearchController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\Storefront\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/cart', CartController::class)->name('cart.index');
Route::post('/cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{cartItem}', [CartItemController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');
Route::get('/checkout', CheckoutController::class)->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
Route::post('/checkout/quote', [CheckoutController::class, 'quote'])->middleware('throttle:checkout')->name('checkout.quote');
Route::get('/locations/search', [LocationSearchController::class, 'search'])->middleware('throttle:geocoding')->name('locations.search');
Route::get('/locations/reverse', [LocationSearchController::class, 'reverse'])->middleware('throttle:geocoding')->name('locations.reverse');
Route::get('/checkout/success', [CustomerOrderController::class, 'success'])->name('checkout.success');
Route::get('/products', [StorefrontProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [StorefrontProductController::class, 'show'])->name('products.show');
Route::get('/brands', [StorefrontBrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand}', [StorefrontBrandController::class, 'show'])->name('brands.show');
Route::get('/concerns/{concern}', [StorefrontConcernController::class, 'show'])->name('concerns.show');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');

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
    ->middleware(['auth', 'permission:admin.dashboard.view|admin.access'])
    ->name('admin.dashboard');

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function (): void {
    Route::get('brands', [BrandController::class, 'index'])->middleware('permission:brands.view|catalog.manage|admin.access')->name('brands.index');
    Route::post('brands', [BrandController::class, 'store'])->middleware('permission:brands.create|catalog.manage|admin.access')->name('brands.store');
    Route::patch('brands/{brand}', [BrandController::class, 'update'])->middleware('permission:brands.update|catalog.manage|admin.access')->name('brands.update');
    Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->middleware('permission:brands.delete|catalog.manage|admin.access')->name('brands.destroy');
    Route::get('users', [AdminUserController::class, 'index'])->middleware('permission:users.manage|admin.access')->name('users.index');
    Route::post('users', [AdminUserController::class, 'store'])->middleware('permission:users.manage|admin.access')->name('users.store');
    Route::patch('users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.manage|admin.access')->name('users.update');
    Route::get('customers', [CustomerController::class, 'index'])->middleware('permission:customers.view|admin.access')->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.view|admin.access')->name('customers.show');
    Route::get('content', [HomepageContentController::class, 'index'])->middleware('permission:content.manage|admin.access')->name('content.index');
    Route::put('content', [HomepageContentController::class, 'update'])->middleware('permission:content.manage|admin.access')->name('content.update');
    Route::post('content/banners', [HomepageContentController::class, 'storeBanner'])->middleware('permission:content.manage|admin.access')->name('content.banners.store');
    Route::patch('content/banners/{banner}', [HomepageContentController::class, 'updateBanner'])->middleware('permission:content.manage|admin.access')->name('content.banners.update');
    Route::delete('content/banners/{banner}', [HomepageContentController::class, 'destroyBanner'])->middleware('permission:content.manage|admin.access')->name('content.banners.destroy');
    Route::put('content/sections', [HomepageContentController::class, 'updateSections'])->middleware('permission:content.manage|admin.access')->name('content.sections.update');
    Route::get('reports', [ReportController::class, 'index'])->middleware('permission:reports.view|admin.access')->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->middleware('permission:reports.view|admin.access')->name('reports.export');
    Route::get('categories', [CategoryController::class, 'index'])->middleware('permission:categories.view|catalog.manage|admin.access')->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->middleware('permission:categories.create|catalog.manage|admin.access')->name('categories.store');
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update|catalog.manage|admin.access')->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete|catalog.manage|admin.access')->name('categories.destroy');
    Route::middleware('permission:categories.view|catalog.manage|admin.access')->group(function (): void {
        Route::get('concerns', [TaxonomyController::class, 'concerns'])->name('concerns.index');
        Route::get('skin-types', [TaxonomyController::class, 'skinTypes'])->name('skin-types.index');
        Route::get('ingredients', [TaxonomyController::class, 'ingredients'])->name('ingredients.index');
    });
    Route::middleware('permission:categories.create|catalog.manage|admin.access')->group(function (): void {
        Route::post('concerns', [TaxonomyController::class, 'storeConcern'])->name('concerns.store');
        Route::post('skin-types', [TaxonomyController::class, 'storeSkinType'])->name('skin-types.store');
        Route::post('ingredients', [TaxonomyController::class, 'storeIngredient'])->name('ingredients.store');
    });
    Route::middleware('permission:categories.update|catalog.manage|admin.access')->group(function (): void {
        Route::patch('concerns/{concern}', [TaxonomyController::class, 'updateConcern'])->name('concerns.update');
        Route::patch('skin-types/{skinType}', [TaxonomyController::class, 'updateSkinType'])->name('skin-types.update');
        Route::patch('ingredients/{ingredient}', [TaxonomyController::class, 'updateIngredient'])->name('ingredients.update');
    });
    Route::middleware('permission:categories.delete|catalog.manage|admin.access')->group(function (): void {
        Route::delete('concerns/{concern}', [TaxonomyController::class, 'destroyConcern'])->name('concerns.destroy');
        Route::delete('skin-types/{skinType}', [TaxonomyController::class, 'destroySkinType'])->name('skin-types.destroy');
        Route::delete('ingredients/{ingredient}', [TaxonomyController::class, 'destroyIngredient'])->name('ingredients.destroy');
    });
    Route::get('products', [ProductController::class, 'index'])->middleware('permission:products.view|catalog.manage|admin.access')->name('products.index');
    Route::get('products/create', [ProductController::class, 'create'])->middleware('permission:products.create|catalog.manage|admin.access')->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->middleware('permission:products.create|catalog.manage|admin.access')->name('products.store');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->middleware('permission:products.update|catalog.manage|admin.access')->name('products.edit');
    Route::patch('products/{product}', [ProductController::class, 'update'])->middleware('permission:products.update|catalog.manage|admin.access')->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete|catalog.manage|admin.access')->name('products.destroy');
    Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->middleware('permission:products.update|catalog.manage|admin.access')->name('products.variants.store');
    Route::patch('variants/{variant}', [ProductVariantController::class, 'update'])->middleware('permission:products.update|catalog.manage|admin.access')->name('variants.update');
    Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])->middleware('permission:products.delete|catalog.manage|admin.access')->name('variants.destroy');
    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->middleware('permission:products.update|catalog.manage|admin.access')->name('products.images.store');
    Route::delete('images/{image}', [ProductImageController::class, 'destroy'])->middleware('permission:products.update|catalog.manage|admin.access')->name('images.destroy');
    Route::patch('images/{image}/primary', [ProductImageController::class, 'makePrimary'])->middleware('permission:products.update|catalog.manage|admin.access')->name('images.primary');
    Route::patch('images/{image}/move', [ProductImageController::class, 'move'])->middleware('permission:products.update|catalog.manage|admin.access')->name('images.move');
    Route::get('stock', [InventoryController::class, 'index'])->middleware('permission:inventory.view|admin.access')->name('stock.index');
    Route::get('stock/adjustments', [StockAdjustmentController::class, 'index'])->middleware('permission:inventory.adjust|admin.access')->name('stock.adjustments.index');
    Route::post('stock/adjustments', [StockAdjustmentController::class, 'store'])->middleware('permission:inventory.adjust|admin.access')->name('stock.adjustments.store');
    Route::get('stock/low', [InventoryController::class, 'lowStock'])->middleware('permission:inventory.view|admin.access')->name('stock.low.index');
    Route::get('stock/out', [InventoryController::class, 'outOfStock'])->middleware('permission:inventory.view|admin.access')->name('stock.out.index');
    Route::get('stock/movements', [InventoryController::class, 'movements'])->middleware('permission:inventory.view|admin.access')->name('stock.movements.index');
    Route::get('delivery', [DeliverySettingController::class, 'index'])->middleware('permission:delivery.manage|admin.access')->name('delivery.index');
    Route::get('orders', [OrderController::class, 'index'])->middleware('permission:orders.view|admin.access')->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->middleware('permission:orders.view|admin.access')->name('orders.show');
    Route::get('orders/{order}/print', [OrderController::class, 'print'])->middleware('permission:orders.view|admin.access')->name('orders.print');
    Route::patch('orders/{order}', [OrderController::class, 'update'])->middleware('permission:orders.update|admin.access')->name('orders.update');
    Route::patch('orders/{order}/payment', [OrderController::class, 'updatePayment'])->middleware('permission:orders.update|admin.access')->name('orders.payment.update');
    Route::put('delivery/origin', [DeliverySettingController::class, 'updateOrigin'])->middleware('permission:delivery.manage|admin.access')->name('delivery.origin.update');
    Route::post('delivery/tiers', [DeliverySettingController::class, 'storeTier'])->middleware('permission:delivery.manage|admin.access')->name('delivery.tiers.store');
    Route::patch('delivery/tiers/{tier}', [DeliverySettingController::class, 'updateTier'])->middleware('permission:delivery.manage|admin.access')->name('delivery.tiers.update');
    Route::delete('delivery/tiers/{tier}', [DeliverySettingController::class, 'destroyTier'])->middleware('permission:delivery.manage|admin.access')->name('delivery.tiers.destroy');
});

require __DIR__.'/auth.php';
