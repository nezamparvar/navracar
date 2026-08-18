<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\CarListing;
use App\Models\ImportQueueItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'username' => 'admin-content-dash',
            'password_hash' => bcrypt('secret'),
            'full_name' => 'Admin Test',
            'role' => 'admin',
        ]);
    }

    private function contentManager(): AdminUser
    {
        return AdminUser::create([
            'username' => 'content-mgr-dash',
            'password_hash' => bcrypt('secret'),
            'full_name' => 'Content Test',
            'role' => 'content_manager',
        ]);
    }

    private function listing(array $overrides = []): CarListing
    {
        return CarListing::create(array_merge([
            'source_url' => 'https://dubai.dubizzle.com/motors/used-cars/toyota/camry/2023/test/',
            'source_site' => 'dubizzle',
            'status' => 'published',
            'slug' => 'toyota-camry-2023-'.uniqid(),
            'title_fa' => 'تویوتا کمری ۲۰۲۳',
            'make' => 'toyota',
            'model' => 'camry',
            'model_year' => '2023',
            'price_aed' => 95000,
            'category_id' => 'c2000',
            'delivery_days' => 40,
        ], $overrides));
    }

    public function test_sales_cannot_open_the_content_dashboard(): void
    {
        $sales = AdminUser::create([
            'username' => 'sales-no-content', 'password_hash' => bcrypt('secret'),
            'full_name' => 'Sales Test', 'role' => 'sales',
        ]);

        $this->actingAs($sales)->get(route('admin.content-dashboard'))->assertForbidden();
    }

    public function test_review_queue_shows_needs_review_items_with_a_deterministic_score(): void
    {
        $user = $this->contentManager();

        ImportQueueItem::create([
            'source' => 'html', 'source_platform' => 'dubicars', 'capture_method' => 'paste',
            'source_url' => 'https://dubicars.com/listing/1', 'status' => 'needs_review',
            'parsed_json' => [
                'title' => 'BMW X5 2022', 'make' => 'bmw', 'model' => 'x5', 'year' => '2022',
                'price_aed' => 200000, 'mileage_km' => 30000, 'engine_capacity_cc' => 3000,
                'description' => str_repeat('توضیحات کامل و طولانی درباره خودرو. ', 5),
            ],
            'images_imported' => 4,
        ]);

        // Only 2 of the 7 quality fields present -> 2/7 rounded = 29%.
        ImportQueueItem::create([
            'source' => 'html', 'source_platform' => 'yallamotor', 'capture_method' => 'paste',
            'source_url' => 'https://yallamotor.com/listing/2', 'status' => 'needs_review',
            'parsed_json' => ['title' => 'Nissan Patrol', 'make' => 'nissan'],
            'images_imported' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('admin.content-dashboard'));
        $response->assertOk();

        $rows = $response->viewData('reviewQueue');
        $this->assertCount(2, $rows);

        $full = collect($rows)->firstWhere('source', 'DubiCars');
        $this->assertSame(100, $full['score']);
        $this->assertSame('کامل', $full['metaState']);

        $partial = collect($rows)->firstWhere('source', 'YallaMotor');
        $this->assertSame((int) round(2 / 7 * 100), $partial['score']);
        $this->assertSame('بدون متا', $partial['metaState']);
    }

    public function test_content_health_is_the_average_of_real_field_completion_across_the_catalog(): void
    {
        $user = $this->contentManager();

        // Fully complete listing: all 7 tracked fields present.
        $complete = $this->listing([
            'meta_title' => 'Meta title', 'meta_description' => 'Meta description',
            'engine_capacity_cc' => 2000,
        ]);
        \App\Models\CarListingImage::create(['car_listing_id' => $complete->id, 'local_path' => 'x.jpg', 'sort_order' => 0]);

        // Missing meta + images entirely.
        $this->listing(['meta_title' => null, 'meta_description' => null, 'engine_capacity_cc' => null]);

        $response = $this->actingAs($user)->get(route('admin.content-dashboard'));
        $response->assertOk();

        $fields = $response->viewData('healthFields');
        // title/brand+model/price are present on both listings -> 100%; meta/engine/images only on one -> 50%.
        $this->assertSame(100, $fields['عنوان']);
        $this->assertSame(100, $fields['برند و مدل']);
        $this->assertSame(100, $fields['قیمت']);
        $this->assertSame(50, $fields['حجم موتور']);
        $this->assertSame(50, $fields['متا تایتل']);
        $this->assertSame(50, $fields['متا دیسکریپشن']);
        $this->assertSame(50, $fields['تصاویر']);

        $overall = $response->viewData('healthOverall');
        $expected = (int) round((100 + 100 + 50 + 100 + 50 + 50 + 50) / 7);
        $this->assertSame($expected, $overall);
    }

    public function test_content_health_is_zero_not_a_misleading_hundred_when_catalog_is_empty(): void
    {
        $user = $this->contentManager();

        $response = $this->actingAs($user)->get(route('admin.content-dashboard'));
        $response->assertOk()
            ->assertViewHas('healthOverall', 0)
            ->assertViewHas('healthFields', []);
    }

    public function test_urgent_tasks_reflect_real_incomplete_and_failed_content(): void
    {
        $user = $this->contentManager();

        $this->listing(['meta_title' => null, 'meta_description' => null]);
        ImportQueueItem::create(['source' => 'html', 'source_platform' => 'dubizzle', 'source_url' => 'https://dubizzle.com/listing/3', 'status' => 'failed']);
        $this->listing(['status' => 'draft']);

        $response = $this->actingAs($user)->get(route('admin.content-dashboard'));
        $response->assertOk();

        $tasks = collect($response->viewData('urgentTasks'))->keyBy('label');
        $this->assertGreaterThanOrEqual(1, $tasks['آگهی متای ناقص دارند']['count']);
        $this->assertSame(1, $tasks['ایمپورت ناموفق نیاز به بررسی دارند']['count']);
        $this->assertSame(1, $tasks['آگهی پیش‌نویس منتظر انتشار هستند']['count']);
    }
}
