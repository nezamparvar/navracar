<?php

namespace Tests\Feature;

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
}
