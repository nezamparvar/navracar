<?php

use App\Http\Controllers\Public\VehiclePricingController;
use Illuminate\Support\Facades\Route;

Route::post('/vehicle-pricing/calculate', VehiclePricingController::class)
    ->middleware('throttle:60,1')
    ->name('api.vehicle-pricing.calculate');
