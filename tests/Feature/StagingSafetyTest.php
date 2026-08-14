<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StagingSafetyTest extends TestCase
{
    public function test_staging_responses_are_marked_noindex_and_show_environment_indicator(): void
    {
        $this->app->detectEnvironment(fn () => 'staging');
        config(['navaracar.disable_outbound' => true]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->assertStringContainsString('STAGING', view('components.staging-banner')->render());
    }

    public function test_staging_subdirectory_configuration_is_isolated(): void
    {
        config([
            'app.env' => 'staging',
            'app.url' => 'https://navracar.com/staging',
            'session.cookie' => 'navracar_staging_session',
            'session.path' => '/staging',
            'cache.prefix' => 'navracar_staging_',
            'filesystems.disks.public.url' => 'https://navracar.com/staging/storage',
        ]);

        $this->assertSame('https://navracar.com/staging', config('app.url'));
        $this->assertSame('/staging', config('session.path'));
        $this->assertSame('navracar_staging_session', config('session.cookie'));
        $this->assertSame('navracar_staging_', config('cache.prefix'));
        $this->assertSame('https://navracar.com/staging/storage/vehicle.jpg', Storage::disk('public')->url('vehicle.jpg'));
    }
}
