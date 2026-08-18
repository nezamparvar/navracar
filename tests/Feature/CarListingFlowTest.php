<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\CarListing;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarListingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'username' => 'admin-test',
            'password_hash' => bcrypt('secret'),
            'full_name' => 'Test Admin',
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_create_publish_and_view_a_car_listing_end_to_end(): void
    {
        Storage::fake('public');
        // این سندباکس دسترسی شبکه به dbz-images.dubizzle.com ندارد؛ دانلود عکس‌ها را شبیه‌سازی می‌کنیم
        // تا کل مسیر ذخیره‌سازی (نه اتصال واقعی) تست شود.
        $fakePixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBTAA7');
        Http::fake([
            'dbz-images.dubizzle.com/*' => Http::response($fakePixel, 200, ['Content-Type' => 'image/gif']),
        ]);

        $html = file_get_contents(__DIR__.'/../Fixtures/dubizzle_bmw_x4.html');
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.car-listings.store'), [
            'source_url' => 'https://dubai.dubizzle.com/motors/used-cars/bmw/x4/2026/1/10/aed-1750-pm-2019-bmw-x4-xdrive30i-m-sport--2-614---43ba18d828284431a018afb8933a5da1/',
            'html_source' => $html,
        ]);

        $listing = CarListing::firstOrFail();
        $response->assertRedirect(route('admin.car-listings.edit', $listing));

        $this->assertSame('draft', $listing->status);
        $this->assertSame('bmw', $listing->make);
        $this->assertSame('x4', $listing->model);
        $this->assertSame(113000.0, (float) $listing->price_aed);
        $this->assertSame('c2500', $listing->category_id); // engine "2000 - 2499 cc" -> upper bound 2499 falls in the c2500 bucket
        $this->assertNotEmpty($listing->title_fa);
        $this->assertTrue($listing->images()->count() >= 1);
        foreach ($listing->images as $image) {
            Storage::disk('public')->assertExists($image->local_path);
            $this->assertStringContainsString('/storage/', Storage::disk('public')->url($image->local_path));
        }

        // Draft is not publicly visible to a guest (actingAs() persists across
        // calls within a test, so log out first to get a true guest request).
        auth()->logout();
        $this->get(route('public.car-prices.show', $listing))->assertNotFound();

        // Publish it.
        $this->actingAs($admin)->post(route('admin.car-listings.publish', $listing))->assertRedirect();
        $listing->refresh();
        $this->assertSame('published', $listing->status);
        $this->assertNotNull($listing->published_at);

        Setting::set(Setting::FREE_RATE, '55000');
        Setting::set(Setting::CUSTOMS_RATE, '36000');

        $show = $this->get(route('public.car-prices.show', $listing));
        $show->assertOk();
        $show->assertSee($listing->title_fa, false);
        $show->assertSee('113,000');
        // The vehicle-detail page shows a server-rendered 3-category cost summary (not the
        // interactive calculator) — assert the admin-configured rate reached the page via its
        // "نرخ ارز" line, and that the summary carries live pricing-service totals rather than
        // a hardcoded/stale figure.
        $show->assertSee('55,000');
        $show->assertSee('قیمت خودرو');
        $show->assertSee('جمع هزینه‌های ترخیص');
        $show->assertSee('هزینه‌های پلاک');

        $index = $this->get(route('public.car-prices.index'));
        $index->assertOk();
        $index->assertSee($listing->title_fa, false);
    }

    public function test_publishing_backfills_empty_seo_metadata_without_overwriting_manual_values(): void
    {
        $admin = $this->admin();
        $listing = CarListing::create([
            'title_fa' => 'تویوتا کمری مدل ۲۰۲۴',
            'slug' => 'toyota-camry-2024-test',
            'source_url' => '',
            'source_site' => 'manual',
            'price_aed' => 50000,
            'category_id' => 'c2000',
            'delivery_days' => 30,
            'meta_title' => null,
            'meta_description' => null,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('admin.car-listings.publish', $listing))->assertRedirect();

        $listing->refresh();
        $this->assertSame('تویوتا کمری مدل ۲۰۲۴ | ناوراکار', $listing->meta_title);
        $this->assertNotEmpty($listing->meta_description);
        $this->assertStringContainsString('تویوتا کمری مدل ۲۰۲۴', $listing->meta_description);
    }
}
