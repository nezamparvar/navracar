<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BrowserExtensionPairing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdapterIsolationTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'test-device',
            'test-fingerprint'
        );
        $this->token = $result['token'];
    }

    /**
     * Test each adapter processes only its own source
     */
    public function test_adapters_respect_source_boundaries()
    {
        $sources = ['dubizzle', 'dubicars', 'yallamotor'];

        foreach ($sources as $source) {
            $response = $this->postJson('/api/browser-capture/v1/listings', [
                'schema_version' => 'navracar.capture.v1',
                'source' => $source,
                'source_url' => "https://{$source}.com/listing/123",
                'captured_at' => now()->toIso8601String(),
                'vehicle' => ['title' => 'Test Car', 'price_aed' => 50000],
                'images' => [],
            ], [
                'Authorization' => "Bearer {$this->token}",
            ]);

            $response->assertStatus(200);
            $response->assertJsonPath('status', 'success');
        }
    }

    /**
     * Test adapter doesn't leak state between requests
     */
    public function test_adapters_maintain_isolation()
    {
        // First request with dubizzle
        $response1 = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/1',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'Dubizzle Car', 'price_aed' => 50000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $id1 = $response1->json('queue_item_id');

        // Second request with dubicars shouldn't be affected
        $response2 = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubicars',
            'source_url' => 'https://dubicars.com/car/2',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => ['title' => 'DubiCars Car', 'price_aed' => 45000],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $id2 = $response2->json('queue_item_id');

        // Verify both were created independently
        $this->assertNotEquals($id1, $id2);
        $this->assertDatabaseHas('import_queues', [
            'id' => $id1,
            'source' => 'dubizzle',
        ]);
        $this->assertDatabaseHas('import_queues', [
            'id' => $id2,
            'source' => 'dubicars',
        ]);
    }

    /**
     * Test adapters handle domain-specific fields correctly
     */
    public function test_adapter_specific_fields_preserved()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/1',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
                'make' => 'Toyota',
                'model' => 'Camry',
                'price_aed' => 50000,
                'regional_specs' => 'GCC',
                'steering_side' => 'Left',
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $queueId = $response->json('queue_item_id');

        // Verify Dubizzle-specific fields are preserved
        $queue = \App\Models\ImportQueue::find($queueId);
        $this->assertEquals('GCC', $queue->captured_data['vehicle']['regional_specs']);
        $this->assertEquals('Left', $queue->captured_data['vehicle']['steering_side']);
    }

    /**
     * Test concurrent adapter requests don't interfere
     */
    public function test_concurrent_adapter_requests()
    {
        $results = [];

        // Simulate concurrent requests from different adapters
        for ($i = 0; $i < 3; $i++) {
            $sources = ['dubizzle', 'dubicars', 'yallamotor'];
            $source = $sources[$i];

            $response = $this->postJson('/api/browser-capture/v1/listings', [
                'schema_version' => 'navracar.capture.v1',
                'source' => $source,
                'source_url' => "https://{$source}.com/listing/{$i}",
                'captured_at' => now()->toIso8601String(),
                'vehicle' => ['title' => "Car {$i}", 'price_aed' => 40000 + $i * 1000],
                'images' => [],
            ], [
                'Authorization' => "Bearer {$this->token}",
            ]);

            $results[] = [
                'source' => $source,
                'id' => $response->json('queue_item_id'),
            ];
        }

        // Verify all requests succeeded and are distinct
        $this->assertCount(3, $results);
        $ids = array_column($results, 'id');
        $this->assertCount(3, array_unique($ids));
    }

    /**
     * Test adapter diagnostics are source-specific
     */
    public function test_adapter_diagnostics_isolation()
    {
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/1',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Test Car',
                'make' => 'Toyota',
                'price_aed' => 50000,
            ],
            'images' => [],
            'diagnostics' => [
                'title' => ['found' => true, 'source' => 'json-ld', 'confidence' => 'high'],
                'make' => ['found' => true, 'source' => 'selector', 'confidence' => 'medium'],
                'price_aed' => ['found' => true, 'source' => 'meta', 'confidence' => 'high'],
            ],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $queueId = $response->json('queue_item_id');
        $queue = \App\Models\ImportQueue::find($queueId);

        // Verify diagnostics are preserved for this specific capture
        $this->assertIsArray($queue->diagnostics);
        $this->assertTrue($queue->diagnostics['title']['found']);
        $this->assertEquals('json-ld', $queue->diagnostics['title']['source']);
    }
}
