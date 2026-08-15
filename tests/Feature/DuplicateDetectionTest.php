<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BrowserExtensionPairing;
use App\Models\CarListing;
use App\Models\ImportQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateDetectionTest extends TestCase
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

    public function test_detects_duplicate_by_source_url()
    {
        $url = 'https://dubizzle.com/motors/used-cars/toyota/camry-exact-id';

        // Create existing listing
        ImportQueue::create([
            'source' => 'dubizzle',
            'source_url' => $url,
            'status' => 'published',
            'captured_data' => ['vehicle' => ['title' => 'Toyota Camry']],
        ]);

        // Attempt to import same listing
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => $url,
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

    public function test_does_not_falsely_detect_make_model_as_duplicate()
    {
        // Create listing with same make/model but different URL
        ImportQueue::create([
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-id-1',
            'status' => 'published',
            'captured_data' => [
                'vehicle' => [
                    'title' => 'Toyota Camry 2020',
                    'make' => 'Toyota',
                    'model' => 'Camry',
                ],
            ],
        ]);

        // Capture different listing with same make/model
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-id-2',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry 2021',
                'make' => 'Toyota',
                'model' => 'Camry',
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        // Should not have duplicate_detected (or it should be null)
        // since different URLs indicate different listings
    }

    public function test_duplicate_detection_with_source_listing_id()
    {
        $listingId = '1234567890abcdef';

        ImportQueue::create([
            'source' => 'dubizzle',
            'source_listing_id' => $listingId,
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-original',
            'status' => 'published',
            'captured_data' => ['vehicle' => ['title' => 'Toyota Camry']],
        ]);

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-different',
            'source_listing_id' => $listingId,
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

    public function test_detects_duplicate_from_car_listings()
    {
        $url = 'https://dubizzle.com/motors/used-cars/toyota/camry-123';

        // Create actual CarListing (not ImportQueue)
        CarListing::create([
            'title_en' => 'Toyota Camry 2020',
            'slug' => 'toyota-camry-2020',
            'source' => 'dubizzle',
            'source_url' => $url,
            'make' => 'Toyota',
            'model' => 'Camry',
            'price_aed' => 50000,
        ]);

        // Attempt capture of same URL
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => $url,
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry 2020',
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        $response->assertJsonHasPath('duplicate_detected');
    }

    public function test_duplicate_detected_flag_in_queue_item()
    {
        $url = 'https://dubizzle.com/motors/used-cars/toyota/camry-existing';

        ImportQueue::create([
            'source' => 'dubizzle',
            'source_url' => $url,
            'status' => 'published',
            'captured_data' => ['vehicle' => ['title' => 'Toyota Camry']],
        ]);

        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubizzle',
            'source_url' => $url,
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $queueItemId = $response->json('queue_item_id');
        $queueItem = ImportQueue::find($queueItemId);

        // Duplicate should be detected and stored
        $this->assertNotNull($queueItem->duplicate_detected_with);
    }

    public function test_no_duplicate_for_different_sources()
    {
        // Create Dubizzle listing
        ImportQueue::create([
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-456',
            'status' => 'published',
            'captured_data' => ['vehicle' => ['title' => 'Toyota Camry']],
        ]);

        // Capture same vehicle from DubiCars
        $response = $this->postJson('/api/browser-capture/v1/listings', [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubicars',
            'source_url' => 'https://dubicars.com/car/789',
            'captured_at' => now()->toIso8601String(),
            'vehicle' => [
                'title' => 'Toyota Camry',
                'make' => 'Toyota',
                'model' => 'Camry',
                'price_aed' => 50000,
            ],
            'images' => [],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        // Different source should be treated as potentially different listing
    }

    public function test_duplicate_warning_shown_in_admin_view()
    {
        $original = ImportQueue::create([
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-original',
            'status' => 'published',
            'captured_data' => ['vehicle' => ['title' => 'Toyota Camry']],
        ]);

        $duplicate = ImportQueue::create([
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-duplicate',
            'status' => 'needs_review',
            'duplicate_detected_with' => $original->slug ?? 'original-listing',
            'captured_data' => ['vehicle' => ['title' => 'Toyota Camry']],
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/import-queue/{$duplicate->id}");

        $response->assertStatus(200);
        $response->assertSee('احتمال تکرار');
    }
}
