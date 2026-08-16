<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use App\Services\Capture\MarketplaceHtmlImportService;
use App\Services\Capture\DubizzleHtmlAdapter;
use App\Services\Capture\DubiCarsHtmlAdapter;
use App\Services\Capture\YallaMotorHtmlAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MarketplaceHtmlImportService::class, fn ($app) => new MarketplaceHtmlImportService([
            $app->make(DubizzleHtmlAdapter::class),
            $app->make(DubiCarsHtmlAdapter::class),
            $app->make(YallaMotorHtmlAdapter::class),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Staging must never deliver real customer email, even if a copied
        // database contains notification settings. Use a fake transport.
        if (app()->environment('staging') && config('navaracar.disable_outbound', true)) {
            Mail::fake();
        }
    }
}
