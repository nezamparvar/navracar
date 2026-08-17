<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BrowserExtensionPairing;
use App\Models\CarListing;
use App\Models\ImportQueueItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowserExtensionFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): AdminUser
    {
        return AdminUser::create([
            'username' => $role.'-extension-test',
            'password_hash' => bcrypt('secret'),
            'full_name' => ucfirst($role).' Extension Test',
            'role' => $role,
        ]);
    }

    public function test_pairing_secrets_are_hashed_and_code_is_single_use(): void
    {
        $admin = $this->makeUser('admin');
        $issued = BrowserExtensionPairing::issue($admin, 'staging', 24);
        $code = $issued['pairing_code'];
        $pairing = $issued['pairing']->fresh();

        $this->assertNotSame($code, $pairing->pairing_code_hash);
        $this->assertSame(hash('sha256', $code), $pairing->pairing_code_hash);
        $this->assertNull($pairing->token_hash);

        $response = $this->postJson('/api/browser-capture/v1/pairing/exchange', [
            'pairing_code' => $code,
            'environment' => 'staging',
            'device_name' => 'Test browser',
        ])->assertOk();
        $token = $response->json('token');

        $pairing->refresh();
        $this->assertNull($pairing->pairing_code_hash);
        $this->assertSame(hash('sha256', $token), $pairing->token_hash);
        $this->assertNotSame($token, $pairing->token_hash);
        $this->assertSame('active', $pairing->status);

        $this->postJson('/api/browser-capture/v1/pairing/exchange', [
            'pairing_code' => $code,
            'environment' => 'staging',
        ])->assertUnprocessable()->assertJsonValidationErrors('pairing_code');
    }

    public function test_capture_requires_token_matching_marketplace_host_and_rejects_credentials(): void
    {
        $admin = $this->makeUser('admin');
        $issued = BrowserExtensionPairing::issue($admin, 'staging', 24);
        $token = BrowserExtensionPairing::exchange($issued['pairing_code'], 'staging', 'Test')['token'];

        $this->postJson('/api/browser-capture/v1/listings', [])->assertUnauthorized();

        $this->withToken($token)->postJson('/api/browser-capture/v1/listings', [
            'source' => 'dubizzle',
            'source_url' => 'https://dubizzle.com.attacker.example/listing/1',
            'vehicle' => ['title' => 'Unsafe'],
        ])->assertUnprocessable()->assertJsonValidationErrors('source_url');

        $this->withToken($token)->postJson('/api/browser-capture/v1/listings', [
            'source' => 'dubizzle',
            'source_url' => 'https://dubai.dubizzle.com/motors/used-cars/1',
            'vehicle' => ['title' => 'Unsafe'],
            'cookies' => 'secret',
        ])->assertUnprocessable()->assertJsonValidationErrors('capture');

        $this->withToken($token)->postJson('/api/browser-capture/v1/listings', [
            'source' => 'dubizzle',
            'source_url' => 'https://dubai.dubizzle.com/motors/used-cars/1',
            'vehicle' => ['title' => 'Unsafe'],
            'diagnostics' => ['sessionToken' => 'must-not-be-stored'],
        ])->assertUnprocessable()->assertJsonValidationErrors('diagnostics');
    }

    public function test_capture_enters_review_queue_detects_duplicate_and_builds_draft_listing(): void
    {
        $admin = $this->makeUser('admin');
        $issued = BrowserExtensionPairing::issue($admin, 'staging', 24);
        $token = BrowserExtensionPairing::exchange($issued['pairing_code'], 'staging', 'Test')['token'];
        $payload = [
            'schema_version' => 'navracar.capture.v1',
            'source' => 'dubicars',
            'source_url' => 'https://www.dubicars.com/car/789',
            'vehicle' => [
                'title' => 'Toyota Camry 2020',
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => '2020',
                'price_aed' => 50000,
                'fuel_type' => 'Petrol',
            ],
            'images' => [],
        ];

        $first = $this->withToken($token)->postJson('/api/browser-capture/v1/listings', $payload)
            ->assertOk()->assertJsonPath('duplicate_detected', false);
        $second = $this->withToken($token)->postJson('/api/browser-capture/v1/listings', $payload)
            ->assertOk()->assertJsonPath('duplicate_detected', true);

        $item = ImportQueueItem::findOrFail($first->json('queue_item_id'));
        $duplicate = ImportQueueItem::findOrFail($second->json('queue_item_id'));
        $this->assertSame('needs_review', $item->status);
        $this->assertSame($item->id, $duplicate->payload_json['duplicate_queue_item_id']);

        $this->actingAs($admin)->get(route('admin.import-queue.show', $item))->assertOk()->assertSee('Toyota Camry 2020');
        $this->actingAs($admin)->post(route('admin.import-queue.publish', $item))->assertRedirect();

        $item->refresh();
        $listing = CarListing::findOrFail($item->published_listing_id);
        $this->assertSame('published', $item->status);
        $this->assertSame('draft', $listing->status);
        $this->assertNull($listing->customs_price_aed);
        $this->assertSame(50000.0, (float) $listing->price_aed);
    }

    public function test_sales_user_cannot_manage_pairings_or_import_queue(): void
    {
        $sales = $this->makeUser('sales');
        $this->actingAs($sales)->get(route('admin.extension-pairing.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.import-queue.index'))->assertForbidden();
    }
}
