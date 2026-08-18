<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class IntegratedClientContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_android_and_browser_capture_contracts_coexist(): void
    {
        $this->assertTrue(Route::has('api.mobile.bootstrap'));
        $this->assertTrue(Route::has('api.browser-capture.pairing.exchange'));
        $this->assertTrue(Route::has('api.browser-capture.listings.store'));

        $this->getJson('/api/mobile/v1/bootstrap')->assertOk();
        $this->postJson('/api/browser-capture/v1/listings', [])->assertUnauthorized();
    }
}
