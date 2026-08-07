<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CalculationLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\KanbanController;
use App\Http\Controllers\Admin\MessageTemplateController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\TemplateUseController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VinCheckController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/export', ExportController::class)->name('export');

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
    });

    Route::post('/template-use', TemplateUseController::class)->name('template-use');

    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/create', [InvoiceController::class, 'create'])->name('create');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::post('/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('status');
    });

    Route::get('/calculations', [CalculationLogController::class, 'index'])->name('calculations.index');
    Route::get('/vin-checks', [VinCheckController::class, 'index'])->name('vin-checks.index');

    Route::middleware('admin.role')->group(function () {
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
    });
});
