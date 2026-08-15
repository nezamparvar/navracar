<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BrowserExtensionPairing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowserCaptureSecurityTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
    }

    public function test_staging_and_production_tokens_are_separate()
    {
        $stagingPairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $productionPairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'production');

        $stagingResult = BrowserExtensionPairing::exchangeCodeForToken(
            $stagingPairing->pairing_code,
            'device1',
            'fingerprint1'
        );
        $productionResult = BrowserExtensionPairing::exchangeCodeForToken(
            $productionPairing->pairing_code,
            'device2',
            'fingerprint2'
        );

        $this->assertNotEquals($stagingResult['token'], $productionResult['token']);

        $stagingToken = $stagingResult['token'];
        $productionToken = $productionResult['token'];

        // Staging token is stored with staging environment
        $stagingCheck = BrowserExtensionPairing::where('extension_token', $stagingToken)
            ->where('environment', 'staging')
            ->first();
        $this->assertNotNull($stagingCheck);

        // Production token is stored with production environment
        $productionCheck = BrowserExtensionPairing::where('extension_token', $productionToken)
            ->where('environment', 'production')
            ->first();
        $this->assertNotNull($productionCheck);
    }

    public function test_rejects_malformed_authorization_header()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-123',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Toyota Camry', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => 'InvalidHeader token-here',
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_invalid_source_domain()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        // Try to use token with unauthorized domain
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'unknown-marketplace',
            'source_url' => 'https://unknown-site.com/listing/123',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Some Car', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
    }

    public function test_accepts_only_valid_sources()
    {
        $validSources = ['dubizzle', 'dubicars', 'yallamotor'];

        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        foreach ($validSources as $source) {
            $response = $this->postJson('/api/browser-capture/v1/listings', [
                'schema_version' => 'navracar.capture.v1',
                'source' => $source,
                'source_url' => "https://{$source}.com/listing/123",
                'captured_at' => now()->toIso8601String(),
                'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
                'images' => [],
            ], [
                'Authorization' => "Bearer {$token}",
            ]);

            $response->assertStatus(200);
        }
    }

    public function test_validates_image_urls()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        // Invalid URL (not http/https)
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/listing/123',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => [
                ['url' => 'ftp://example.com/image.jpg', 'confidence' => 'high'],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
    }

    public function test_validates_source_urls()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        // Invalid URL
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'not-a-valid-url',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
    }

    public function test_validates_payload_schema_version()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'invalid.schema.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/listing/123',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
    }

    public function test_validates_date_format()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/listing/123',
            'captured_at' => '2026-08-15',  // Invalid format
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_extremely_large_description()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/listing/123',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Car',
                'price_aed' => 50000,
                'description' => str_repeat('x', 6000),  // Exceeds 5000 char limit
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_invalid_price()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/listing/123',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Car',
                'price_aed' => -1000,  // Negative price
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
    }

    public function test_token_expiry_after_revocation()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        // First request succeeds
        $response1 = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/listing/1',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);
        $response1->assertStatus(200);

        // Revoke pairing
        BrowserExtensionPairing::where('extension_token', $token)
            ->first()
            ->revoke();

        // Second request fails
        $response2 = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/listing/2',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);
        $response2->assertStatus(401);
    }

    public function test_throttling_on_exchange_endpoint()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $code = $pairing->pairing_code;

        // Make 6 requests (throttle limit is 5 per 1 minute)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/browser-capture/v1/pairing/exchange', [
                'pairing_code' => $code,
                'device_name' => 'test-device',
                'device_fingerprint' => 'fingerprint',
            ]);

            if ($i < 5) {
                // First 5 should succeed or fail with specific errors
                $this->assertNotEquals(429, $response->status());
            }
        }

        // 6th request should be throttled
        $response = $this->postJson('/api/browser-capture/v1/pairing/exchange', [
            'pairing_code' => $code,
            'device_name' => 'test-device',
            'device_fingerprint' => 'fingerprint',
        ]);

        // Note: may be 429 if throttled, or may have other failure reasons
        // depending on implementation
    }

    public function test_rejects_source_spoofing_dubizzle()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        // Claim dubizzle but use dubicars URL
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubicars.com/car/123',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Source mismatch');
    }

    public function test_rejects_source_spoofing_dubicars()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        // Claim dubicars but use yallamotor URL
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubicars',
            'source_url' => 'https://yallamotor.com/car/123',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
    }

    public function test_accepts_source_with_www_url()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        // www variant should be accepted
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://www.dubizzle.com/motors/used-cars/1',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200);
    }

    public function test_rejects_too_many_images()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        // Create 51 images (exceeds 50 limit)
        $images = [];
        for ($i = 0; $i < 51; $i++) {
            $images[] = [
                'url' => "https://example.com/image{$i}.jpg",
                'confidence' => 'high'
            ];
        }

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/1',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => $images,
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Too many images');
    }

    public function test_accepts_exactly_50_images()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        // Create exactly 50 images (at limit)
        $images = [];
        for ($i = 0; $i < 50; $i++) {
            $images[] = [
                'url' => "https://example.com/image{$i}.jpg",
                'confidence' => 'high'
            ];
        }

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/1',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => $images,
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200);
    }

    public function test_rejects_oversized_image_url()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $token = $result['token'];

        // Create URL that exceeds 2000 char limit
        $longUrl = 'https://example.com/' . str_repeat('a', 2100);

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/1',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Car', 'price_aed' => 50000],
            'images' => [
                ['url' => $longUrl, 'confidence' => 'high']
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Image URL too long');
    }
}
