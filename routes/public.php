<?php

use App\Http\Controllers\Public\CalculationLogController;
use App\Http\Controllers\Public\CalculatorController;
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
