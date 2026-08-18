<?php

namespace Tests\Feature;

use App\Models\MobileAnalyticsEvent;
use App\Models\MobileAppInstallation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileEngagementApiTest extends TestCase
{
    use RefreshDatabase;

    private string $installationId = '018f55ce-3d62-7d81-a0c3-7f5e05f2a111';

    private string $secret = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    public function test_installation_registration_hashes_secret_and_never_returns_it(): void
    {
        $response = $this->putJson("/api/mobile/v1/installations/{$this->installationId}", [
            'secret' => $this->secret,
            'analytics_consent' => false,
            'device' => [
                'manufacturer' => 'Samsung',
                'model' => 'SM-S928B',
                'platform' => 'android',
                'os_version' => '14',
                'app_version' => '1.1.0',
                'locale' => 'fa-IR',
            ],
            'acquisition' => ['source' => 'direct'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('installation_id', $this->installationId)
            ->assertJsonPath('analytics_consent', false)
            ->assertJsonMissing(['secret' => $this->secret]);

        $installation = MobileAppInstallation::firstOrFail();
        $this->assertSame(hash('sha256', $this->secret), $installation->secret_hash);
        $this->assertSame('SM-S928B', $installation->device_model);
        $this->assertDatabaseMissing('mobile_app_installations', ['secret_hash' => $this->secret]);
    }

    public function test_existing_installation_requires_its_secret(): void
    {
        $this->registerInstallation();

        $this->withHeaders($this->installationHeaders('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'))
            ->putJson("/api/mobile/v1/installations/{$this->installationId}", [
                'device' => ['app_version' => '1.2.0'],
            ])
            ->assertForbidden();
    }

    public function test_consented_event_batch_is_stored_and_heartbeat_updates_presence(): void
    {
        $this->registerInstallation();
        $headers = $this->installationHeaders();

        $this->withHeaders($headers)
            ->patchJson("/api/mobile/v1/installations/{$this->installationId}/consent", [
                'analytics_consent' => true,
            ])
            ->assertOk()
            ->assertJsonPath('analytics_consent', true);

        $occurredAt = now()->subSecond()->toIso8601String();
        $this->withHeaders($headers)
            ->postJson('/api/mobile/v1/analytics/events', [
                'events' => [
                    ['name' => 'heartbeat', 'page' => 'home', 'occurred_at' => $occurredAt],
                    ['name' => 'search', 'page' => 'vehicles', 'occurred_at' => $occurredAt, 'properties' => ['query' => 'BMW X5']],
                ],
            ])
            ->assertAccepted()
            ->assertJsonPath('accepted', 2);

        $this->assertDatabaseHas('mobile_analytics_events', ['name' => 'search', 'page' => 'vehicles']);
        $this->assertNotNull(MobileAppInstallation::firstOrFail()->last_seen_at);
    }

    public function test_events_are_rejected_without_analytics_consent(): void
    {
        $this->registerInstallation();

        $this->withHeaders($this->installationHeaders())
            ->postJson('/api/mobile/v1/analytics/events', [
                'events' => [['name' => 'app_open', 'occurred_at' => now()->toIso8601String()]],
            ])
            ->assertStatus(409);
    }

    public function test_sensitive_event_properties_are_rejected(): void
    {
        $this->registerInstallation(true);

        foreach (['phone', 'email', 'password', 'token', 'vin'] as $sensitiveKey) {
            $this->withHeaders($this->installationHeaders())
                ->postJson('/api/mobile/v1/analytics/events', [
                    'events' => [[
                        'name' => 'contact_click',
                        'occurred_at' => now()->toIso8601String(),
                        'properties' => [$sensitiveKey => 'must-not-be-stored'],
                    ]],
                ])
                ->assertUnprocessable();
        }

        $this->assertSame(0, MobileAnalyticsEvent::count());
    }

    public function test_revoking_analytics_consent_deletes_events_and_presence(): void
    {
        $this->registerInstallation(true);
        $headers = $this->installationHeaders();
        $this->withHeaders($headers)->postJson('/api/mobile/v1/analytics/events', [
            'events' => [['name' => 'app_open', 'occurred_at' => now()->toIso8601String()]],
        ])->assertAccepted();

        $this->withHeaders($headers)
            ->patchJson("/api/mobile/v1/installations/{$this->installationId}/consent", [
                'analytics_consent' => false,
            ])
            ->assertOk();

        $this->assertSame(0, MobileAnalyticsEvent::count());
        $this->assertNull(MobileAppInstallation::firstOrFail()->last_seen_at);
    }

    public function test_capacitor_preflight_allows_engagement_methods_and_installation_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://localhost',
            'Access-Control-Request-Method' => 'PATCH',
            'Access-Control-Request-Headers' => 'content-type,x-navracar-installation,x-navracar-installation-secret',
        ])->options("/api/mobile/v1/installations/{$this->installationId}/consent");

        $response->assertNoContent();
        $this->assertStringContainsString('PATCH', (string) $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('x-navracar-installation', strtolower((string) $response->headers->get('Access-Control-Allow-Headers')));
        $this->assertStringContainsString('x-navracar-installation-secret', strtolower((string) $response->headers->get('Access-Control-Allow-Headers')));
    }

    private function registerInstallation(bool $analyticsConsent = false): void
    {
        $this->putJson("/api/mobile/v1/installations/{$this->installationId}", [
            'secret' => $this->secret,
            'analytics_consent' => $analyticsConsent,
            'device' => ['platform' => 'android', 'app_version' => '1.1.0'],
        ])->assertCreated();
    }

    /** @return array<string, string> */
    private function installationHeaders(?string $secret = null): array
    {
        return [
            'X-Navracar-Installation' => $this->installationId,
            'X-Navracar-Installation-Secret' => $secret ?? $this->secret,
        ];
    }
}
