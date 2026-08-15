<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BrowserExtensionPairing;
use App\Models\ImportQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowserCaptureApiTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;
    private BrowserExtensionPairing $pairing;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
        $this->pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $this->pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $this->token = $result['token'];
    }

    public function test_requires_authentication_token()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-1234567890abcdef',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
                'price_aed' => 50000,
            ],
            'images' => [],
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_invalid_token()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-1234567890abcdef',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_revoked_token()
    {
        $this->pairing->refresh()->revoke();

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-1234567890abcdef',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(401);
    }

    public function test_requires_price_aed()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-1234567890abcdef',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'price_aed is required');
    }

    public function test_requires_title_or_make_model()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-1234567890abcdef',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
    }

    public function test_accepts_capture_with_title_and_price()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-1234567890abcdef',
            'source_listing_id' => '1234567890abcdef',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry 2020',
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => '2020',
                'price_aed' => 50000,
                'mileage_km' => '45000',
                'fuel_type' => 'Petrol',
            ],
            'images' => [
                ['url' => 'https://example.com/image1.jpg', 'confidence' => 'high'],
                ['url' => 'https://example.com/image2.jpg', 'confidence' => 'high'],
            ],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonHasPath('queue_item_id');

        $this->assertDatabaseHas('import_queues', [
            'source' => 'dubizzle',
            'status' => 'images_pending',
        ]);
    }

    public function test_accepts_capture_with_make_model_and_price()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubicars',
            'source_url' => 'https://dubicars.com/car/123456',
            'source_listing_id' => '123456',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'make' => 'Honda',
                'model' => 'Accord',
                'price_aed' => 45000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_creates_import_queue_item()
    {
        $url = 'https://dubizzle.com/motors/used-cars/toyota/camry-1234567890abcdef';
        $payload = [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => $url,
            'source_listing_id' => '1234567890abcdef',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
                'price_aed' => 50000,
            ],
            'images' => [],
        ];

        $response = $this->postJson('/api/browser-capture/v1/listings', $payload, [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $this->assertDatabaseHas('import_queues', [
            'source' => 'dubizzle',
            'source_url' => $url,
            'source_listing_id' => '1234567890abcdef',
            'status' => 'needs_review',
        ]);

        $queue = ImportQueue::where('source', 'dubizzle')->first();
        $this->assertEquals(0, $queue->image_count);
        $this->assertIsArray($queue->captured_data);
    }

    public function test_updates_last_used_on_capture()
    {
        $pairing = BrowserExtensionPairing::where('extension_token', $this->token)->first();
        $oldLastUsed = $pairing->last_used_at;

        sleep(1);

        $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-1234567890abcdef',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $pairing->refresh();
        $this->assertNotEquals($oldLastUsed, $pairing->last_used_at);
    }

    public function test_rejects_unsupported_source()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'unknown-site',
            'source_url' => 'https://unknown.com/listing/123',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Some Car',
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
    }

    public function test_detects_duplicates()
    {
        // Create an existing listing
        $existingUrl = 'https://dubizzle.com/motors/used-cars/toyota/camry-existing';
        ImportQueue::create([
            'source' => 'dubizzle',
            'source_url' => $existingUrl,
            'status' => 'published',
            'captured_data' => ['vehicle' => ['title' => 'Toyota Camry']],
        ]);

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => $existingUrl,
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        $response->assertJsonHasPath('duplicate_detected');
    }

    public function test_returns_review_url()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-1234567890abcdef',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertJsonHasPath('review_url');
        $this->assertStringContainsString('admin/import-queue', $response->json('review_url'));
    }
}
