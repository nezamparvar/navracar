<?php

use App\Http\Controllers\Api\BrowserCapture\CaptureController;
use App\Http\Controllers\Api\BrowserCapture\PairingController;
use App\Http\Controllers\Public\VehiclePricingController;
use Illuminate\Support\Facades\Route;

Route::post('/vehicle-pricing/calculate', VehiclePricingController::class)
    ->middleware('throttle:60,1')
    ->name('api.vehicle-pricing.calculate');

Route::prefix('browser-capture/v1')->group(function () {
    Route::post('/pairing/exchange', [PairingController::class, 'exchange'])
        ->middleware('throttle:10,1');
    Route::post('/listings', [CaptureController::class, 'store'])
        ->middleware('throttle:30,1');
});
