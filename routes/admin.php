<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CalculationLogController;
use App\Http\Controllers\Admin\CarListingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\HomeSlideController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\KanbanController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\MessageTemplateController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\BrowserCaptureController;
use App\Http\Controllers\Admin\TemplateUseController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VinCheckController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // بخش‌های فروش: هم مدیر کامل و هم «کارشناس فروش» دسترسی دارند (کارشناس
    // فروش فقط درخواست‌ها/پیش‌فاکتورهای الحاق‌شده به خودش را می‌بیند — کنترل
    // دقیق‌تر داخل خود کنترلرهاست).
    Route::middleware('sales.role')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/kanban', [KanbanController::class, 'index'])->name('kanban');
        Route::post('/kanban/change-stage', [KanbanController::class, 'updateStage'])->name('kanban.change-stage');

        Route::prefix('requests')->name('requests.')->group(function () {
            Route::get('/', [RequestController::class, 'index'])->name('index');
            Route::get('/create', [RequestController::class, 'create'])->name('create');
            Route::post('/', [RequestController::class, 'store'])->name('store');
            Route::get('/{lead}', [RequestController::class, 'show'])->name('show');
            Route::post('/{lead}/assign', [RequestController::class, 'assign'])->name('assign');
            Route::post('/{lead}/temperature', [RequestController::class, 'temperature'])->name('temperature');
            Route::post('/{lead}/status', [RequestController::class, 'status'])->name('status');
            Route::post('/{lead}/close', [RequestController::class, 'close'])->name('close');
            Route::post('/{lead}/archive', [RequestController::class, 'archive'])->name('archive');
            Route::post('/{lead}/unarchive', [RequestController::class, 'unarchive'])->name('unarchive');
        });

        Route::post('/template-use', TemplateUseController::class)->name('template-use');

        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::get('/create', [InvoiceController::class, 'create'])->name('create');
            Route::post('/', [InvoiceController::class, 'store'])->name('store');
            Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
            Route::get('/{invoice}/pdf/{language?}', [InvoiceController::class, 'downloadPdf'])
                ->where('language', 'fa|en')->name('pdf');
            Route::post('/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('status');
        });
    });

    // بخش‌های محتوایی: هم مدیر کامل و هم «مدیر محتوا» به این‌ها دسترسی دارند.
    Route::middleware('content.role')->group(function () {
        Route::prefix('car-listings')->name('car-listings.')->group(function () {
            Route::get('/', [CarListingController::class, 'index'])->name('index');
            Route::post('/', [CarListingController::class, 'store'])->name('store');
            Route::get('/create', [CarListingController::class, 'create'])->name('create');
            Route::post('/store-manual', [CarListingController::class, 'storeManual'])->name('store-manual');
            Route::get('/import', [CarListingController::class, 'showImport'])->name('import');
            Route::post('/import', [CarListingController::class, 'import'])->name('import.store');
            Route::get('/{carListing}/edit', [CarListingController::class, 'edit'])->name('edit');
            Route::put('/{carListing}', [CarListingController::class, 'update'])->name('update');
            Route::delete('/{carListing}', [CarListingController::class, 'destroy'])->name('destroy');
            Route::post('/{carListing}/publish', [CarListingController::class, 'publish'])->name('publish');
            Route::post('/{carListing}/unpublish', [CarListingController::class, 'unpublish'])->name('unpublish');
            Route::post('/{carListing}/refetch', [CarListingController::class, 'refetch'])->name('refetch');
            Route::post('/{carListing}/images', [CarListingController::class, 'storeImage'])->name('images.store');
            Route::delete('/{carListing}/images/{image}', [CarListingController::class, 'destroyImage'])->name('images.destroy');
            Route::post('/{carListing}/publish-social', [CarListingController::class, 'publishSocial'])->name('publish-social');
        });

        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/', [PostController::class, 'index'])->name('index');
            Route::get('/create', [PostController::class, 'create'])->name('create');
            Route::post('/', [PostController::class, 'store'])->name('store');
            Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit');
            Route::put('/{post}', [PostController::class, 'update'])->name('update');
            Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
            Route::post('/{post}/publish', [PostController::class, 'publish'])->name('publish');
            Route::post('/{post}/unpublish', [PostController::class, 'unpublish'])->name('unpublish');
            Route::post('/{post}/publish-social', [PostController::class, 'publishSocial'])->name('publish-social');
        });

        Route::prefix('home-slides')->name('home-slides.')->group(function () {
            Route::get('/', [HomeSlideController::class, 'index'])->name('index');
            Route::post('/', [HomeSlideController::class, 'store'])->name('store');
            Route::put('/{homeSlide}', [HomeSlideController::class, 'update'])->name('update');
            Route::post('/{homeSlide}/toggle', [HomeSlideController::class, 'toggle'])->name('toggle');
            Route::delete('/{homeSlide}', [HomeSlideController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('menu-items')->name('menu-items.')->group(function () {
            Route::get('/', [MenuItemController::class, 'index'])->name('index');
            Route::post('/', [MenuItemController::class, 'store'])->name('store');
            Route::post('/{menuItem}/toggle', [MenuItemController::class, 'toggle'])->name('toggle');
            Route::delete('/{menuItem}', [MenuItemController::class, 'destroy'])->name('destroy');
        });
    });

    // بخش‌های فقط برای مدیر کامل.
    Route::middleware('admin.role')->group(function () {
        Route::delete('/requests/{lead}', [RequestController::class, 'destroy'])->name('requests.destroy');
        Route::patch('/pipeline-stages/{stage}/name', [KanbanController::class, 'updateStageName'])->name('pipeline-stages.update-name');
        Route::get('/export', ExportController::class)->name('export');
        Route::get('/calculations', [CalculationLogController::class, 'index'])->name('calculations.index');
        Route::get('/vin-checks', [VinCheckController::class, 'index'])->name('vin-checks.index');

        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [MessageTemplateController::class, 'index'])->name('index');
            Route::post('/', [MessageTemplateController::class, 'store'])->name('store');
            Route::post('/{template}/toggle', [MessageTemplateController::class, 'toggle'])->name('toggle');
            Route::delete('/{template}', [MessageTemplateController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::post('/{user}/role', [UserController::class, 'updateRole'])->name('role');
            Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        });

        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/imports/browser-capture', BrowserCaptureController::class)->name('imports.browser-capture');
    });
});

