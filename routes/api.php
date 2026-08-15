<?php

use App\Http\Controllers\Api\BrowserCapture\CaptureController;
use App\Http\Controllers\Api\BrowserCapture\PairingController;
use Illuminate\Support\Facades\Route;

Route::prefix('browser-capture/v1')->group(function () {
    Route::post('/pairing/exchange', [PairingController::class, 'exchange']);
    Route::post('/listings', [CaptureController::class, 'store']);
});
