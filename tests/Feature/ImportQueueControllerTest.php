<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ImportQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportQueueControllerTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;
    private ImportQueue $queueItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
        $this->queueItem = ImportQueue::create([
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com/motors/used-cars/toyota/camry-123',
            'status' => 'needs_review',
            'captured_data' => [
                'vehicle' => [
                    'title' => 'Toyota Camry 2020',
                    'make' => 'Toyota',
                    'model' => 'Camry',
                    'year' => '2020',
                    'price_aed' => 50000,
                    'mileage_km' => '45000',
                ],
            ],
            'image_count' => 0,
            'images_imported' => 0,
        ]);
    }

    public function test_index_lists_queue_items()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/import-queue');

        $response->assertStatus(200);
        $response->assertSee($this->queueItem->captured_data['vehicle']['title']);
    }

    public function test_index_filters_by_status()
    {
        $readyItem = ImportQueue::create([
            'source' => 'dubicars',
            'source_url' => 'https://dubicars.com/car/456',
            'status' => 'ready',
            'captured_data' => ['vehicle' => ['title' => 'Honda Accord']],
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/import-queue?status=ready');

        $response->assertStatus(200);
        $response->assertSee('Honda Accord');
        $response->assertDontSee('Toyota Camry 2020');
    }

    public function test_index_filters_by_source()
    {
        ImportQueue::create([
            'source' => 'yallamotor',
            'source_url' => 'https://yallamotor.com/listing/789',
            'status' => 'needs_review',
            'captured_data' => ['vehicle' => ['title' => 'Nissan Altima']],
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/import-queue?source=yallamotor');

        $response->assertStatus(200);
        $response->assertSee('Nissan Altima');
        $response->assertDontSee('Toyota Camry');
    }

    public function test_show_displays_queue_item_details()
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/import-queue/{$this->queueItem->id}");

        $response->assertStatus(200);
        $response->assertSee('Toyota Camry 2020');
        $response->assertSee('50000');
    }

    public function test_show_displays_duplicate_warning()
    {
        $this->queueItem->update(['duplicate_detected_with' => 'existing-slug']);

        $response = $this->actingAs($this->admin)
            ->get("/admin/import-queue/{$this->queueItem->id}");

        $response->assertStatus(200);
        $response->assertSee('احتمال تکرار');
    }

    public function test_can_update_captured_data()
    {
        $response = $this->actingAs($this->admin)
            ->put("/admin/import-queue/{$this->queueItem->id}", [
                'vehicle' => [
                    'title' => 'Updated Title',
                    'price_aed' => 55000,
                    'make' => 'Toyota',
                    'model' => 'Camry',
                    'year' => '2020',
                    'mileage_km' => '45000',
                    'fuel_type' => 'Petrol',
                    'transmission' => 'Automatic',
                    'description' => 'Updated description',
                ],
            ]);

        $response->assertStatus(302);

        $this->queueItem->refresh();
        $this->assertEquals('Updated Title', $this->queueItem->captured_data['vehicle']['title']);
        $this->assertEquals(55000, $this->queueItem->captured_data['vehicle']['price_aed']);
    }

    public function test_cannot_update_published_item()
    {
        $this->queueItem->update(['status' => 'published']);

        $response = $this->actingAs($this->admin)
            ->put("/admin/import-queue/{$this->queueItem->id}", [
                'vehicle' => [
                    'title' => 'New Title',
                    'price_aed' => 50000,
                ],
            ]);

        $response->assertStatus(302);

        $this->queueItem->refresh();
        $this->assertNotEquals('New Title', $this->queueItem->captured_data['vehicle']['title']);
    }

    public function test_can_publish_queue_item()
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/import-queue/{$this->queueItem->id}/publish");

        $response->assertStatus(302);

        $this->queueItem->refresh();
        $this->assertEquals('published', $this->queueItem->status);
    }

    public function test_can_only_publish_ready_or_needs_review()
    {
        $this->queueItem->update(['status' => 'images_pending']);

        $response = $this->actingAs($this->admin)
            ->post("/admin/import-queue/{$this->queueItem->id}/publish");

        $response->assertStatus(302);

        $this->queueItem->refresh();
        $this->assertNotEquals('published', $this->queueItem->status);
    }

    public function test_can_cancel_import()
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/import-queue/{$this->queueItem->id}/cancel");

        $response->assertStatus(302);

        $this->queueItem->refresh();
        $this->assertIn($this->queueItem->status, ['cancelled', 'failed']);
    }

    public function test_can_retry_image_import()
    {
        $this->queueItem->update([
            'image_count' => 2,
            'images_imported' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/import-queue/{$this->queueItem->id}/retry-images");

        $response->assertStatus(302);
    }

    public function test_shows_image_progress()
    {
        $this->queueItem->update([
            'image_count' => 5,
            'images_imported' => 3,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/import-queue/{$this->queueItem->id}");

        $response->assertStatus(200);
        $response->assertSee('3');
        $response->assertSee('5');
    }

    public function test_shows_diagnostics()
    {
        $this->queueItem->update([
            'diagnostics' => [
                'title' => [
                    'found' => true,
                    'confidence' => 'high',
                ],
                'price_aed' => [
                    'found' => true,
                    'confidence' => 'high',
                ],
                'make' => [
                    'found' => false,
                    'confidence' => 'low',
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/import-queue/{$this->queueItem->id}");

        $response->assertStatus(200);
        $response->assertSee('title');
        $response->assertSee('high');
    }

    public function test_requires_authentication()
    {
        $response = $this->get('/admin/import-queue');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access()
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin/import-queue');

        $response->assertStatus(403);
    }
}
