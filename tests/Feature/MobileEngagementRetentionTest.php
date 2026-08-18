<?php

namespace Tests\Feature;

use App\Models\MobileAnalyticsEvent;
use App\Models\MobileAppInstallation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileEngagementRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_removes_analytics_older_than_configured_retention(): void
    {
        config(['mobile.analytics_retention_days' => 180]);
        $installation = MobileAppInstallation::create([
            'installation_id' => '018f55ce-3d62-7d81-a0c3-7f5e05f2a999',
            'secret_hash' => hash('sha256', str_repeat('a', 43)),
            'analytics_consent' => true,
        ]);
        $old = MobileAnalyticsEvent::create([
            'mobile_app_installation_id' => $installation->id, 'name' => 'app_open', 'occurred_at' => now()->subDays(181),
        ]);
        $fresh = MobileAnalyticsEvent::create([
            'mobile_app_installation_id' => $installation->id, 'name' => 'app_open', 'occurred_at' => now()->subDays(179),
        ]);

        $this->artisan('mobile:prune-engagement')->assertSuccessful();

        $this->assertDatabaseMissing('mobile_analytics_events', ['id' => $old->id]);
        $this->assertDatabaseHas('mobile_analytics_events', ['id' => $fresh->id]);
    }
}
