<?php

use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\CalculationLogController;
use App\Http\Controllers\Public\CalculatorController;
use App\Http\Controllers\Public\CarPriceController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LeadFormController;
use App\Http\Controllers\Public\QuoteController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\VehiclePricingController;
use App\Http\Controllers\Public\VinLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('public.home');
Route::get('/calculator', [CalculatorController::class, 'index'])->name('public.calculator');
Route::get('/app', fn() => view('public.mobile-app'))->name('public.mobile-app');
Route::post('/vehicle-pricing/calculate', VehiclePricingController::class)
    ->middleware('throttle:60,1')->name('public.vehicle-pricing.calculate');
Route::post('/api/vehicle-pricing/calculate', VehiclePricingController::class)
    ->middleware('throttle:60,1')->name('api.vehicle-pricing.calculate');
Route::post('/quote-requests', [QuoteController::class, 'store'])->middleware('throttle:5,1')->name('public.quote-requests.store');
Route::get('/quote-requests/{quoteRequest}/pdf', [QuoteController::class, 'downloadPdf'])
    ->middleware('signed')->name('public.quote-requests.pdf');
Route::post('/calculation-logs', [CalculationLogController::class, 'store'])->middleware('throttle:30,1')->name('public.calculation-logs.store');
Route::post('/vin-checks', [VinLogController::class, 'store'])->middleware('throttle:30,1')->name('public.vin-checks.store');

Route::get('/lead-form', [LeadFormController::class, 'create'])->name('public.lead-form');
Route::post('/lead-form', [LeadFormController::class, 'store'])->middleware('throttle:5,1')->name('public.lead-form.store');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('public.sitemap');

Route::get('/blog', [BlogController::class, 'index'])->name('public.blog.index');
Route::get('/blog/{post}', [BlogController::class, 'show'])->name('public.blog.show');

Route::get('/car-prices', [CarPriceController::class, 'index'])->name('public.car-prices.index');
Route::get('/car-prices/sitemap.xml', [CarPriceController::class, 'sitemap'])->name('public.car-prices.sitemap');
// این سه مسیر باید قبل از {carListing} ثبت شوند وگرنه route-model binding آن‌ها را به‌عنوان اسلاگ می‌بلعد.
Route::get('/car-prices/brand/{make}', [CarPriceController::class, 'brand'])->name('public.car-prices.brand');
Route::get('/car-prices/category/{categoryId}', [CarPriceController::class, 'category'])->name('public.car-prices.category');
Route::get('/car-prices/price/{bracket}', [CarPriceController::class, 'price'])->name('public.car-prices.price');
Route::get('/car-prices/{carListing}', [CarPriceController::class, 'show'])->name('public.car-prices.show');
