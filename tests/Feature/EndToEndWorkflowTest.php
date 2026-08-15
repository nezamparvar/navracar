<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BrowserExtensionPairing;
use App\Models\ImportQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * End-to-end staging workflow test:
     * 1. Generate pairing code
     * 2. Exchange code for token
     * 3. Capture vehicle listing via API
     * 4. Review in admin panel
     * 5. Edit captured data
     * 6. Publish listing
     * 7. Verify final state
     */
    public function test_complete_staging_workflow()
    {
        $admin = AdminUser::factory()->create();

        // Step 1: Generate pairing code
        $pairing = BrowserExtensionPairing::generatePairingCode($admin, 'staging');
        $this->assertEquals('staging', $pairing->environment);
        $this->assertEquals('pending', $pairing->status);
        $this->assertTrue($pairing->isPending());

        // Step 2: Exchange code for token
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'MacBook Pro - Safari',
            'mac-fingerprint-123'
        );
        $this->assertEquals('success', $result['status']);
        $token = $result['token'];
        $this->assertNotNull($token);

        // Verify pairing is now active
        $activePairing = BrowserExtensionPairing::where('extension_token', $token)->first();
        $this->assertTrue($activePairing->isActive());
        $this->assertEquals('MacBook Pro - Safari', $activePairing->device_name);
        $this->assertNotNull($activePairing->paired_at);

        // Step 3: Capture vehicle listing via API
        $capturePayload = [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-2020-abc123',
            'source_listing_id' => 'abc123',
            'captured_at' => now()->toIso8601String(),
            'page_title' => 'Toyota Camry 2020 - Dubizzle',
            'vehicle' => [
                'title' => 'Toyota Camry 2020',
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => '2020',
                'price_aed' => 47500,
                'mileage_km' => '45000',
                'fuel_type' => 'Petrol',
                'transmission' => 'Automatic',
                'body_type' => 'Sedan',
                'color' => 'Silver',
                'description' => 'Well maintained Toyota Camry with full service history.',
            ],
            'images' => [
                ['url' => 'https://images.dubizzle.com/photo1.jpg', 'confidence' => 'high'],
                ['url' => 'https://images.dubizzle.com/photo2.jpg', 'confidence' => 'high'],
            ],
            'diagnostics' => [
                'title' => ['found' => true, 'confidence' => 'high'],
                'price_aed' => ['found' => true, 'confidence' => 'high'],
                'make' => ['found' => true, 'confidence' => 'high'],
                'model' => ['found' => true, 'confidence' => 'high'],
            ],
        ];

        $captureResponse = $this->postJson('/api/browser-capture/v1/listings', $capturePayload, [
            'Authorization' => "Bearer {$token}",
        ]);

        $captureResponse->assertStatus(200);
        $captureResponse->assertJsonPath('status', 'success');
        $queueItemId = $captureResponse->json('queue_item_id');
        $this->assertNotNull($queueItemId);

        // Verify queue item was created
        $this->assertDatabaseHas('import_queues', [
            'id' => $queueItemId,
            'source' => 'dubizzle',
            'status' => 'images_pending',  // Will be set to needs_review after image processing
        ]);

        // Step 4: Check item in admin review panel
        $queueItem = ImportQueue::find($queueItemId);

        // Simulate images being imported (in real scenario, the job would handle this)
        $queueItem->update([
            'status' => 'needs_review',
            'images_imported' => 2,
        ]);

        $reviewResponse = $this->actingAs($admin)
            ->get("/admin/import-queue/{$queueItemId}");

        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('Toyota Camry 2020');
        $reviewResponse->assertSee('47500');
        $reviewResponse->assertSee('Dubizzle');

        // Step 5: Edit captured data before publishing
        $editResponse = $this->actingAs($admin)
            ->put("/admin/import-queue/{$queueItemId}", [
                'vehicle' => [
                    'title' => 'Toyota Camry 2020 - Excellent Condition',
                    'make' => 'Toyota',
                    'model' => 'Camry',
                    'year' => '2020',
                    'price_aed' => 47000,  // Edited price
                    'mileage_km' => '45000',
                    'fuel_type' => 'Petrol',
                    'transmission' => 'Automatic',
                    'description' => 'Well maintained Toyota Camry with full service history and warranty.',
                ],
            ]);

        $editResponse->assertStatus(302);

        // Verify edited data is saved
        $queueItem->refresh();
        $this->assertEquals(
            'Toyota Camry 2020 - Excellent Condition',
            $queueItem->captured_data['vehicle']['title']
        );
        $this->assertEquals(47000, $queueItem->captured_data['vehicle']['price_aed']);

        // Step 6: Publish listing
        $publishResponse = $this->actingAs($admin)
            ->post("/admin/import-queue/{$queueItemId}/publish");

        $publishResponse->assertStatus(302);

        // Verify queue item status changed to published
        $queueItem->refresh();
        $this->assertEquals('published', $queueItem->status);

        // Step 7: Verify final state
        // Check that images were imported
        $this->assertEquals(2, $queueItem->images_imported);
        $this->assertEquals(2, $queueItem->image_count);

        // Check that pairing last_used was updated
        $activePairing->refresh();
        $this->assertNotNull($activePairing->last_used_at);
    }

    /**
     * Test workflow with image import and retry
     */
    public function test_workflow_with_image_import_retry()
    {
        $admin = AdminUser::factory()->create();
        $pairing = BrowserExtensionPairing::generatePairingCode($admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'device',
            'fingerprint'
        );
        $token = $result['token'];

        // Capture with images
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubicars',
            'source_url' => 'https://dubicars.com/car/12345',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Honda Accord 2019',
                'make' => 'Honda',
                'model' => 'Accord',
                'price_aed' => 45000,
            ],
            'images' => [
                ['url' => 'https://images.dubicars.com/img1.jpg', 'confidence' => 'high'],
                ['url' => 'https://images.dubicars.com/img2.jpg', 'confidence' => 'high'],
                ['url' => 'https://images.dubicars.com/img3.jpg', 'confidence' => 'high'],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $queueItemId = $response->json('queue_item_id');
        $queueItem = ImportQueue::find($queueItemId);

        // Simulate partial image import failure
        $queueItem->update([
            'status' => 'images_pending',
            'images_imported' => 1,  // Only 1 out of 3 imported
        ]);

        // Admin can see incomplete images
        $reviewResponse = $this->actingAs($admin)
            ->get("/admin/import-queue/{$queueItemId}");

        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('1');  // Images imported count
        $reviewResponse->assertSee('3');  // Total images

        // Admin can retry failed images
        $retryResponse = $this->actingAs($admin)
            ->post("/admin/import-queue/{$queueItemId}/retry-images");

        $retryResponse->assertStatus(302);
    }

    /**
     * Test cancellation workflow
     */
    public function test_workflow_cancellation()
    {
        $admin = AdminUser::factory()->create();
        $pairing = BrowserExtensionPairing::generatePairingCode($admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'device',
            'fingerprint'
        );
        $token = $result['token'];

        // Capture listing
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'yallamotor',
            'source_url' => 'https://yallamotor.com/listing/999',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Nissan Altima',
                'price_aed' => 35000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $queueItemId = $response->json('queue_item_id');
        $queueItem = ImportQueue::find($queueItemId);
        $queueItem->update(['status' => 'needs_review']);

        // Admin cancels import
        $cancelResponse = $this->actingAs($admin)
            ->post("/admin/import-queue/{$queueItemId}/cancel");

        $cancelResponse->assertStatus(302);

        // Verify status changed
        $queueItem->refresh();
        $this->assertIn($queueItem->status, ['cancelled', 'failed']);
    }

    /**
     * Test workflow with multiple captures and duplicate detection
     */
    public function test_workflow_with_duplicate_detection()
    {
        $admin = AdminUser::factory()->create();
        $pairing = BrowserExtensionPairing::generatePairingCode($admin, 'staging');
        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $pairing->pairing_code,
            'device',
            'fingerprint'
        );
        $token = $result['token'];

        $url = 'https://dubizzle.com/motors/used-cars/ford/mustang-xyz789';

        // First capture
        $response1 = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => $url,
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Ford Mustang 2021',
                'make' => 'Ford',
                'model' => 'Mustang',
                'price_aed' => 120000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $id1 = $response1->json('queue_item_id');
        $item1 = ImportQueue::find($id1);
        $item1->update(['status' => 'published']);

        // Second capture of same listing
        $response2 = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => $url,
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Ford Mustang 2021',
                'make' => 'Ford',
                'model' => 'Mustang',
                'price_aed' => 120000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response2->assertStatus(200);
        $response2->assertJsonHasPath('duplicate_detected');
        $id2 = $response2->json('queue_item_id');

        // Verify duplicate flag is set
        $item2 = ImportQueue::find($id2);
        $this->assertNotNull($item2->duplicate_detected_with);

        // Admin can still see and review the duplicate
        $reviewResponse = $this->actingAs($admin)
            ->get("/admin/import-queue/{$id2}");

        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('احتمال تکرار');
    }
}
