<?php

namespace Tests\Feature;

use App\Jobs\SendMobilePushNotification;
use App\Models\AdminUser;
use App\Models\MobileAnalyticsEvent;
use App\Models\MobileAppInstallation;
use App\Models\MobilePushDelivery;
use App\Models\MobilePushNotification;
use App\Services\FcmClient;
use App\Services\FcmSendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileInsightsAndPushTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_reports_online_search_device_location_contact_and_usage_metrics(): void
    {
        $installation = $this->installation([
            'analytics_consent' => true,
            'last_seen_at' => now()->subMinute(),
            'device_manufacturer' => 'Samsung',
            'device_model' => 'SM-S928B',
            'country' => 'United Arab Emirates',
            'city' => 'Dubai',
        ]);
        $this->event($installation, 'app_open');
        $this->event($installation, 'search', ['query' => 'BMW X5']);
        $this->event($installation, 'whatsapp_click', ['placement' => 'vehicle_detail']);

        $admin = $this->admin();
        $response = $this->actingAs($admin)->get(route('admin.mobile-insights.index'));

        $response->assertOk()
            ->assertSee('آمار اپلیکیشن')
            ->assertSee('BMW X5')
            ->assertSee('Samsung SM-S928B')
            ->assertSee('Dubai')
            ->assertSee('WhatsApp');

        $this->actingAs($admin)
            ->getJson(route('admin.mobile-insights.summary'))
            ->assertOk()
            ->assertJsonPath('online_now', 1)
            ->assertJsonPath('active_installations', 1)
            ->assertJsonPath('top_searches.0.label', 'BMW X5');
    }

    public function test_mobile_insights_are_admin_only(): void
    {
        $sales = AdminUser::create([
            'username' => 'sales-mobile-insights', 'password_hash' => bcrypt('secret'), 'full_name' => 'Sales', 'role' => 'sales',
        ]);

        $this->actingAs($sales)->get('/admin/mobile-insights')->assertForbidden();
    }

    public function test_push_token_requires_consent_is_encrypted_and_can_be_revoked(): void
    {
        $installation = $this->installation();
        $headers = $this->headers($installation);
        $token = 'firebase-token-'.str_repeat('x', 80);

        $this->withHeaders($headers)->postJson("/api/mobile/v1/installations/{$installation->installation_id}/push-token", [
            'token' => $token,
        ])->assertStatus(409);

        $this->withHeaders($headers)->patchJson("/api/mobile/v1/installations/{$installation->installation_id}/consent", [
            'notifications_consent' => true,
        ])->assertOk();
        $this->withHeaders($headers)->postJson("/api/mobile/v1/installations/{$installation->installation_id}/push-token", [
            'token' => $token,
        ])->assertNoContent();

        $stored = $installation->refresh();
        $this->assertSame($token, $stored->push_token);
        $this->assertSame(hash('sha256', $token), $stored->push_token_hash);
        $this->assertNotSame($token, (string) $this->app['db']->table('mobile_app_installations')->value('push_token'));

        $this->withHeaders($headers)
            ->deleteJson("/api/mobile/v1/installations/{$installation->installation_id}/push-token")
            ->assertNoContent();
        $this->assertNull($installation->refresh()->push_token);
    }

    public function test_push_creation_is_recorded_as_disabled_when_firebase_is_not_configured(): void
    {
        config(['services.firebase.project_id' => null, 'services.firebase.credentials' => null]);
        $installation = $this->installation(['notifications_consent' => true]);
        $installation->update(['push_token' => 'encrypted-token-value', 'push_token_hash' => hash('sha256', 'encrypted-token-value')]);

        $this->actingAs($this->admin())->post(route('admin.mobile-insights.push.store'), [
            'title' => 'خودروهای تازه',
            'body' => 'مدل‌های جدید را ببینید.',
            'target' => 'all',
        ])->assertRedirect(route('admin.mobile-insights.index'));

        $this->assertDatabaseHas('mobile_push_notifications', [
            'title' => 'خودروهای تازه', 'status' => 'disabled', 'targeted_count' => 1, 'disabled_count' => 1,
        ]);
    }

    public function test_push_job_records_delivery_and_removes_an_unregistered_token(): void
    {
        $installation = $this->installation(['notifications_consent' => true]);
        $installation->update(['push_token' => 'expired-token-value', 'push_token_hash' => hash('sha256', 'expired-token-value')]);
        $notification = MobilePushNotification::create([
            'title' => 'اعلان تست', 'body' => 'متن تست', 'status' => 'queued', 'segment' => ['target' => 'all'],
        ]);
        $client = new class extends FcmClient
        {
            public function __construct() {}

            public function send(string $deviceToken, string $title, string $body, array $data = []): FcmSendResult
            {
                return new FcmSendResult(false, true, 'UNREGISTERED');
            }
        };

        (new SendMobilePushNotification($notification->id))->handle($client);

        $this->assertDatabaseHas('mobile_push_deliveries', [
            'mobile_push_notification_id' => $notification->id, 'status' => 'failed', 'error_code' => 'UNREGISTERED',
        ]);
        $this->assertNull($installation->refresh()->push_token);
        $this->assertDatabaseHas('mobile_push_notifications', [
            'id' => $notification->id, 'status' => 'completed', 'targeted_count' => 1, 'failed_count' => 1,
        ]);
    }

    public function test_opening_a_push_is_counted_only_once_for_the_authenticated_installation(): void
    {
        $installation = $this->installation(['notifications_consent' => true]);
        $notification = MobilePushNotification::create(['title' => 'اعلان', 'body' => 'متن', 'status' => 'completed']);
        MobilePushDelivery::create([
            'mobile_push_notification_id' => $notification->id,
            'mobile_app_installation_id' => $installation->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $url = "/api/mobile/v1/push/opened/{$notification->id}";
        $this->withHeaders($this->headers($installation))->postJson($url)->assertNoContent();
        $this->withHeaders($this->headers($installation))->postJson($url)->assertNoContent();

        $this->assertSame(1, $notification->refresh()->opened_count);
        $this->assertNotNull($notification->deliveries()->firstOrFail()->opened_at);
    }

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'username' => 'admin-mobile-insights', 'password_hash' => bcrypt('secret'), 'full_name' => 'Admin', 'role' => 'admin',
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function installation(array $overrides = []): MobileAppInstallation
    {
        return MobileAppInstallation::create(array_merge([
            'installation_id' => '018f55ce-3d62-7d81-a0c3-'.substr(hash('sha256', uniqid('', true)), 0, 12),
            'secret_hash' => hash('sha256', str_repeat('a', 43)),
            'platform' => 'android',
            'app_version' => '1.1.0',
        ], $overrides));
    }

    private function event(MobileAppInstallation $installation, string $name, array $properties = []): MobileAnalyticsEvent
    {
        return MobileAnalyticsEvent::create([
            'mobile_app_installation_id' => $installation->id,
            'name' => $name,
            'properties' => $properties,
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, string> */
    private function headers(MobileAppInstallation $installation): array
    {
        return [
            'X-Navracar-Installation' => $installation->installation_id,
            'X-Navracar-Installation-Secret' => str_repeat('a', 43),
        ];
    }
}
