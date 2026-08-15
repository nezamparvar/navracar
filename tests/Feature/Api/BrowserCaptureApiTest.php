<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\BrowserExtensionPairing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowserCaptureApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function pairing_code_exchange_requires_valid_code(): void
    {
        $response = $this->postJson('/api/browser-capture/v1/pairing/exchange', [
            'pairing_code' => '000000',
            'environment' => 'staging',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pairing_code']);
    }

    /** @test */
    public function pairing_code_exchange_succeeds_with_valid_code(): void
    {
        $user = AdminUser::factory()->create();
        $pairing = BrowserExtensionPairing::create([
            'admin_user_id' => $user->id,
            'pairing_code' => '123456',
            'token' => BrowserExtensionPairing::generateToken(),
            'device_name' => 'Test Device',
            'environment' => 'staging',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->postJson('/api/browser-capture/v1/pairing/exchange', [
            'pairing_code' => '123456',
            'environment' => 'staging',
            'device_name' => 'Browser Extension',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'token',
            'environment',
            'message',
        ]);
        $response->assertJson([
            'status' => 'success',
            'token' => $pairing->token,
            'environment' => 'staging',
        ]);
    }

    /** @test */
    public function pairing_code_exchange_fails_if_expired(): void
    {
        $pairing = BrowserExtensionPairing::create([
            'pairing_code' => '654321',
            'token' => BrowserExtensionPairing::generateToken(),
            'device_name' => 'Test Device',
            'environment' => 'staging',
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->postJson('/api/browser-capture/v1/pairing/exchange', [
            'pairing_code' => '654321',
            'environment' => 'staging',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pairing_code']);
    }

    /** @test */
    public function pairing_code_exchange_fails_if_revoked(): void
    {
        $pairing = BrowserExtensionPairing::create([
            'pairing_code' => '111111',
            'token' => BrowserExtensionPairing::generateToken(),
            'device_name' => 'Test Device',
            'environment' => 'staging',
            'expires_at' => now()->addHours(24),
            'revoked_at' => now(),
        ]);

        $response = $this->postJson('/api/browser-capture/v1/pairing/exchange', [
            'pairing_code' => '111111',
            'environment' => 'staging',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pairing_code']);
    }

    /** @test */
    public function pairing_code_exchange_fails_if_environment_mismatch(): void
    {
        $pairing = BrowserExtensionPairing::create([
            'pairing_code' => '222222',
            'token' => BrowserExtensionPairing::generateToken(),
            'device_name' => 'Test Device',
            'environment' => 'staging',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->postJson('/api/browser-capture/v1/pairing/exchange', [
            'pairing_code' => '222222',
            'environment' => 'production',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['environment']);
    }

    /** @test */
    public function pairing_code_exchange_updates_last_used_timestamp(): void
    {
        $pairing = BrowserExtensionPairing::create([
            'pairing_code' => '333333',
            'token' => BrowserExtensionPairing::generateToken(),
            'device_name' => 'Test Device',
            'environment' => 'staging',
            'expires_at' => now()->addHours(24),
            'last_used_at' => null,
        ]);

        $response = $this->postJson('/api/browser-capture/v1/pairing/exchange', [
            'pairing_code' => '333333',
            'environment' => 'staging',
        ]);

        $response->assertStatus(200);
        $pairing->refresh();
        $this->assertNotNull($pairing->last_used_at);
    }

    /** @test */
    public function capture_requires_bearer_token(): void
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/123',
            'vehicle' => ['make' => 'Toyota', 'model' => 'Camry'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['authorization']);
    }

    /** @test */
    public function capture_requires_valid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/browser-capture/v1/listings', [
                'source' => 'dubizzle',
                'source_url' => 'https://dubizzle.com/motors/used-cars/123',
                'vehicle' => ['make' => 'Toyota', 'model' => 'Camry'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['authorization']);
    }

    /** @test */
    public function capture_succeeds_with_valid_token(): void
    {
        $user = AdminUser::factory()->create();
        $pairing = BrowserExtensionPairing::create([
            'admin_user_id' => $user->id,
            'pairing_code' => '444444',
            'token' => BrowserExtensionPairing::generateToken(),
            'device_name' => 'Test Device',
            'environment' => 'staging',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $pairing->token)
            ->postJson('/api/browser-capture/v1/listings', [
                'source' => 'dubizzle',
                'source_url' => 'https://dubizzle.com/motors/used-cars/123456',
                'vehicle' => [
                    'title' => '2020 Toyota Camry',
                    'make' => 'Toyota',
                    'model' => 'Camry',
                    'year' => '2020',
                    'price_aed' => 80000,
                ],
                'images' => [
                    ['url' => 'https://example.com/img1.jpg', 'confidence' => 'high'],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'queue_item_id',
            'review_url',
            'duplicate_detected',
            'message',
        ]);
        $response->assertJson([
            'status' => 'success',
            'duplicate_detected' => false,
        ]);
    }

    /** @test */
    public function capture_validates_required_fields(): void
    {
        $pairing = BrowserExtensionPairing::create([
            'pairing_code' => '555555',
            'token' => BrowserExtensionPairing::generateToken(),
            'device_name' => 'Test Device',
            'environment' => 'staging',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $pairing->token)
            ->postJson('/api/browser-capture/v1/listings', [
                // Missing required fields
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source', 'source_url', 'vehicle']);
    }

    /** @test */
    public function capture_validates_source_platform(): void
    {
        $pairing = BrowserExtensionPairing::create([
            'pairing_code' => '666666',
            'token' => BrowserExtensionPairing::generateToken(),
            'device_name' => 'Test Device',
            'environment' => 'staging',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $pairing->token)
            ->postJson('/api/browser-capture/v1/listings', [
                'source' => 'invalid-source',
                'source_url' => 'https://example.com/listing/123',
                'vehicle' => ['make' => 'Toyota'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source']);
    }

    /** @test */
    public function capture_requires_valid_url(): void
    {
        $pairing = BrowserExtensionPairing::create([
            'pairing_code' => '777777',
            'token' => BrowserExtensionPairing::generateToken(),
            'device_name' => 'Test Device',
            'environment' => 'staging',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $pairing->token)
            ->postJson('/api/browser-capture/v1/listings', [
                'source' => 'dubizzle',
                'source_url' => 'not-a-valid-url',
                'vehicle' => ['make' => 'Toyota'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source_url']);
    }
}
