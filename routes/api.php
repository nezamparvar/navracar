<?php

use App\Http\Controllers\Api\BrowserCaptureController;
use Illuminate\Support\Facades\Route;

Route::post('/browser-capture/v1/listings', [BrowserCaptureController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('api.browser-capture.store');
