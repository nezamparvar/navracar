<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
