<?php

use App\Http\Controllers\Admin\BrowserCaptureSettingsController;
use App\Http\Controllers\Api\BrowserCaptureController;
use Illuminate\Support\Facades\Route;

// Public pairing endpoint (no auth required - uses pairing code instead)
Route::post('/browser-capture/v1/pairing/exchange', [BrowserCaptureSettingsController::class, 'exchangeCode'])
    ->middleware('throttle:5,1')
    ->name('api.browser-capture.pairing.exchange');

// Authenticated capture endpoint (requires valid extension token)
Route::post('/browser-capture/v1/listings', [BrowserCaptureController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('api.browser-capture.store');
