<?php

namespace Tests\Feature;

use App\Models\CarListing;
use App\Models\QuoteRequest;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_exposes_server_settings_and_only_published_vehicles(): void
    {
        Setting::set(Setting::FREE_RATE, '24870');
        $published = $this->listing(['slug' => 'bmw-x5', 'status' => 'published']);
        $this->listing(['slug' => 'draft-car', 'status' => 'draft']);

        $response = $this->getJson('/api/mobile/v1/bootstrap');

        $response->assertOk()
            ->assertJsonPath('environment', 'testing')
            ->assertJsonPath('rates.aed_to_toman', 24870)
            ->assertJsonCount(1, 'featured_vehicles')
            ->assertJsonPath('featured_vehicles.0.slug', $published->slug)
            ->assertJsonMissing(['slug' => 'draft-car'])
            ->assertJsonStructure(['categories', 'contact' => ['whatsapp_uae', 'whatsapp_iran', 'phone'], 'rates' => ['updated_at']]);
    }

    public function test_vehicle_index_filters_sorts_and_never_returns_drafts(): void
    {
        $this->listing(['slug' => 'bmw-old', 'make' => 'BMW', 'model' => 'X3', 'model_year' => '2022', 'price_aed' => 130000]);
        $this->listing(['slug' => 'bmw-new', 'make' => 'BMW', 'model' => 'X5', 'model_year' => '2025', 'price_aed' => 330000]);
        $this->listing(['slug' => 'toyota', 'title_en' => 'Toyota Camry', 'title_fa' => 'تویوتا کمری', 'make' => 'Toyota', 'model' => 'Camry', 'model_year' => '2024', 'price_aed' => 90000]);
        $this->listing(['slug' => 'secret-draft', 'make' => 'BMW', 'status' => 'draft']);

        $response = $this->getJson('/api/mobile/v1/vehicles?q=BMW&year_min=2023&sort=price_desc');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'bmw-new')
            ->assertJsonMissing(['slug' => 'secret-draft'])
            ->assertJsonStructure(['data' => ['*' => ['title', 'price_aed', 'price_toman', 'cover_image', 'specs']], 'facets']);
    }

    public function test_vehicle_detail_returns_gallery_specs_and_three_public_pricing_blocks(): void
    {
        $listing = $this->listing([
            'slug' => 'bmw-x5-detail',
            'price_aed' => 345000,
            'engine_capacity_cc' => '3000',
            'fuel_type' => 'بنزین',
        ]);
        $listing->images()->createMany([
            ['local_path' => 'cars/x5-1.jpg', 'sort_order' => 0, 'is_cover' => true],
            ['local_path' => 'cars/x5-2.jpg', 'sort_order' => 1, 'is_cover' => false],
        ]);

        $response = $this->getJson('/api/mobile/v1/vehicles/bmw-x5-detail');

        $response->assertOk()
            ->assertJsonCount(2, 'gallery')
            ->assertJsonPath('pricing.category.id', 'c2000')
            ->assertJsonCount(3, 'pricing.public_summary')
            ->assertJsonMissing(['label' => 'کارمزد ترخیص‌کار و کارگزار (ناوراکار)']);
    }

    public function test_customer_registration_login_profile_and_logout_use_revocable_bearer_tokens(): void
    {
        $registration = $this->postJson('/api/mobile/v1/auth/register', [
            'name' => 'مریم احمدی',
            'phone' => '+989121234567',
            'email' => 'maryam@example.com',
            'password' => 'correct-horse-123',
        ])->assertCreated();

        $token = $registration->json('token');
        $this->assertMatchesRegularExpression('/^\d+\|[A-Za-z0-9_-]{43}$/', $token);
        $this->assertDatabaseHas('mobile_customers', ['phone' => '+989121234567']);
        $this->assertTrue(Hash::check('correct-horse-123', (string) $this->app['db']->table('mobile_customers')->value('password_hash')));

        $this->withToken($token)->getJson('/api/mobile/v1/account')->assertOk()->assertJsonPath('customer.name', 'مریم احمدی');
        $this->withToken($token)->patchJson('/api/mobile/v1/account', ['name' => 'مریم نادری'])->assertOk()->assertJsonPath('customer.name', 'مریم نادری');
        $this->withToken($token)->postJson('/api/mobile/v1/auth/logout')->assertNoContent();
        $this->withToken($token)->getJson('/api/mobile/v1/account')->assertUnauthorized();

        $login = $this->postJson('/api/mobile/v1/auth/login', ['login' => '+989121234567', 'password' => 'correct-horse-123']);
        $login->assertOk()->assertJsonPath('customer.name', 'مریم نادری');
    }

    public function test_favorites_are_server_backed_and_scoped_to_customer(): void
    {
        $listing = $this->listing(['slug' => 'favorite-bmw']);
        $first = $this->registerCustomer('+989120000001', 'first@example.com');
        $second = $this->registerCustomer('+989120000002', 'second@example.com');

        $this->withToken($first)->putJson("/api/mobile/v1/favorites/{$listing->slug}")->assertNoContent();
        $this->withToken($first)->getJson('/api/mobile/v1/favorites')->assertJsonCount(1, 'data');
        $this->withToken($second)->getJson('/api/mobile/v1/favorites')->assertJsonCount(0, 'data');
        $this->withToken($first)->deleteJson("/api/mobile/v1/favorites/{$listing->slug}")->assertNoContent();
    }

    public function test_guest_quote_reuses_pricing_engine_and_authenticated_quote_is_linked_to_customer(): void
    {
        $token = $this->registerCustomer('+989120000003', 'quote@example.com');
        $payload = [
            'name' => 'کاربر اپ',
            'phone' => '+989120000003',
            'email' => 'quote@example.com',
            'car' => 'BMW X5',
            'pricing' => ['real_price_aed' => 200000, 'category' => 'c2000'],
        ];

        $response = $this->withToken($token)->postJson('/api/mobile/v1/quote-requests', $payload);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('quote_requests', ['source' => 'Android', 'phone' => '+989120000003']);
        $quote = QuoteRequest::firstOrFail();
        $this->assertNotNull($quote->mobile_customer_id);
        $this->withToken($token)->getJson('/api/mobile/v1/requests')
            ->assertOk()->assertJsonPath('data.0.id', $quote->id);
    }

    public function test_shared_listing_accepts_only_supported_https_marketplaces_without_credentials(): void
    {
        $token = $this->registerCustomer('+989120000004', 'share@example.com');

        $this->withToken($token)->postJson('/api/mobile/v1/shared-listings', [
            'url' => 'https://dubai.dubizzle.com/motors/used-cars/bmw/x5/123',
        ])->assertAccepted()->assertJsonPath('source', 'dubizzle');

        $this->withToken($token)->postJson('/api/mobile/v1/shared-listings', [
            'url' => 'http://127.0.0.1/internal',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('import_queue', ['capture_method' => 'android_share', 'status' => 'pending']);
    }

    private function registerCustomer(string $phone, string $email): string
    {
        return (string) $this->postJson('/api/mobile/v1/auth/register', [
            'name' => 'کاربر تست',
            'phone' => $phone,
            'email' => $email,
            'password' => 'correct-horse-123',
        ])->assertCreated()->json('token');
    }

    private function listing(array $overrides = []): CarListing
    {
        return CarListing::create(array_merge([
            'source_url' => 'https://example.com/'.uniqid('', true),
            'source_site' => 'dubizzle',
            'status' => 'published',
            'slug' => 'car-'.uniqid(),
            'title_en' => 'BMW X5',
            'title_fa' => 'بی ام و X5',
            'make' => 'BMW',
            'model' => 'X5',
            'model_year' => '2024',
            'price_aed' => 300000,
            'kilometers' => '30000',
            'engine_capacity_cc' => '2000',
            'fuel_type' => 'بنزین',
            'transmission_type' => 'اتوماتیک',
            'category_id' => 'c2000',
            'published_at' => now(),
        ], $overrides));
    }
}
