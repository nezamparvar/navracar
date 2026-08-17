<?php

use App\Http\Controllers\Api\BrowserCapture\CaptureController;
use App\Http\Controllers\Api\BrowserCapture\PairingController;
use App\Http\Controllers\Public\VehiclePricingController;
use App\Http\Controllers\Public\QuoteController;
use App\Http\Controllers\Api\Mobile\V1\AccountController as MobileAccountController;
use App\Http\Controllers\Api\Mobile\V1\AuthController as MobileAuthController;
use App\Http\Controllers\Api\Mobile\V1\BootstrapController as MobileBootstrapController;
use App\Http\Controllers\Api\Mobile\V1\FavoriteController as MobileFavoriteController;
use App\Http\Controllers\Api\Mobile\V1\RequestController as MobileRequestController;
use App\Http\Controllers\Api\Mobile\V1\SharedListingController as MobileSharedListingController;
use App\Http\Controllers\Api\Mobile\V1\VehicleController as MobileVehicleController;
use Illuminate\Support\Facades\Route;

Route::post('/vehicle-pricing/calculate', VehiclePricingController::class)
    ->middleware('throttle:60,1')
    ->name('api.vehicle-pricing.calculate');

Route::prefix('mobile/v1')->name('api.mobile.')->group(function () {
    Route::get('/bootstrap', MobileBootstrapController::class)->middleware('throttle:60,1')->name('bootstrap');
    Route::get('/vehicles', [MobileVehicleController::class, 'index'])->middleware('throttle:120,1')->name('vehicles.index');
    Route::get('/vehicles/{slug}', [MobileVehicleController::class, 'show'])->middleware('throttle:120,1')->name('vehicles.show');
    Route::post('/auth/register', [MobileAuthController::class, 'register'])->middleware('throttle:6,1')->name('auth.register');
    Route::post('/auth/login', [MobileAuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');
    Route::post('/quote-requests', [QuoteController::class, 'store'])->middleware('throttle:5,1')->name('quote-requests.store');

    Route::middleware('mobile.auth')->group(function () {
        Route::post('/auth/logout', [MobileAuthController::class, 'logout'])->name('auth.logout');
        Route::get('/account', [MobileAccountController::class, 'show'])->name('account.show');
        Route::patch('/account', [MobileAccountController::class, 'update'])->name('account.update');
        Route::get('/favorites', [MobileFavoriteController::class, 'index'])->name('favorites.index');
        Route::put('/favorites/{slug}', [MobileFavoriteController::class, 'store'])->name('favorites.store');
        Route::delete('/favorites/{slug}', [MobileFavoriteController::class, 'destroy'])->name('favorites.destroy');
        Route::get('/requests', [MobileRequestController::class, 'index'])->name('requests.index');
        Route::post('/shared-listings', MobileSharedListingController::class)->middleware('throttle:10,1')->name('shared-listings.store');
    });
});

Route::prefix('browser-capture/v1')->group(function () {
    Route::post('/pairing/exchange', [PairingController::class, 'exchange'])
        ->middleware('throttle:10,1');
    Route::post('/listings', [CaptureController::class, 'store'])
        ->middleware('throttle:30,1');
});
