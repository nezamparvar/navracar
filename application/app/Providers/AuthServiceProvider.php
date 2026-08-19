<?php

namespace App\Providers;

use App\Models\CalendarEvent;
use App\Models\QuoteRequest;
use App\Policies\CalendarEventPolicy;
use App\Policies\QuoteRequestPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        QuoteRequest::class => QuoteRequestPolicy::class,
        CalendarEvent::class => CalendarEventPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
