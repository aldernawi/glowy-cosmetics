<?php

use App\Http\Controllers\Pos\AppController;
use App\Http\Controllers\Pos\BootstrapController;
use App\Http\Controllers\Pos\SyncController;
use App\Http\Middleware\AuthenticatePosDevice;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Route;

// Stateless on purpose: the register keeps working long after any session has expired.
Route::get('/pos', AppController::class)->name('pos.app');

Route::prefix('pos/api')->name('pos.')->middleware([EncryptCookies::class, 'throttle:pos', AuthenticatePosDevice::class])->group(function (): void {
    Route::get('bootstrap', BootstrapController::class)->name('bootstrap');
    Route::post('sync', SyncController::class)->name('sync');
});
