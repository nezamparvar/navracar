<?php

use App\Http\Controllers\Public\CalculationLogController;
use App\Http\Controllers\Public\CalculatorController;
use App\Http\Controllers\Public\CarPriceController;
use App\Http\Controllers\Public\LeadFormController;
use App\Http\Controllers\Public\QuoteController;
use App\Http\Controllers\Public\VinLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalculatorController::class, 'index'])->name('public.calculator');
Route::post('/quote-requests', [QuoteController::class, 'store'])->name('public.quote-requests.store');
Route::post('/calculation-logs', [CalculationLogController::class, 'store'])->name('public.calculation-logs.store');
Route::post('/vin-checks', [VinLogController::class, 'store'])->name('public.vin-checks.store');

Route::get('/lead-form', [LeadFormController::class, 'create'])->name('public.lead-form');
Route::post('/lead-form', [LeadFormController::class, 'store'])->name('public.lead-form.store');

Route::get('/car-prices', [CarPriceController::class, 'index'])->name('public.car-prices.index');
Route::get('/car-prices/sitemap.xml', [CarPriceController::class, 'sitemap'])->name('public.car-prices.sitemap');
Route::get('/car-prices/{carListing}', [CarPriceController::class, 'show'])->name('public.car-prices.show');
