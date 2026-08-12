<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\CarListing;
use App\Models\HomeSlide;
use App\Models\MenuItem;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteExpansionTest extends TestCase
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

    private function publishedListing(array $overrides = []): CarListing
    {
        return CarListing::create(array_merge([
            'source_url' => 'https://dubai.dubizzle.com/motors/used-cars/toyota/camry/2023/test/',
            'source_site' => 'dubizzle',
            'status' => 'published',
            'slug' => 'toyota-camry-2023-test',
            'title_fa' => 'تویوتا کمری ۲۰۲۳',
            'make' => 'toyota',
            'model' => 'camry',
            'model_year' => '2023',
            'price_aed' => 95000,
            'category_id' => 'c2000',
            'delivery_days' => 40,
            'published_at' => now(),
        ], $overrides));
    }

    public function test_homepage_shows_slides_latest_listings_and_posts(): void
    {
        HomeSlide::create([
            'title' => 'سلاید تست', 'image_path' => 'home-slides/x.jpg',
            'sort_order' => 1, 'is_active' => true,
        ]);
        $this->publishedListing();
        Post::create([
            'title' => 'مطلب تست', 'slug' => 'post-test', 'body' => '<p>متن</p>',
            'status' => 'published', 'published_at' => now(),
        ]);

        $response = $this->get(route('public.home'));
        $response->assertOk();
        $response->assertSee('سلاید تست');
        $response->assertSee('تویوتا کمری');
        $response->assertSee('مطلب تست');
    }

    public function test_calculator_moved_to_dedicated_route(): void
    {
        $this->get('/calculator')->assertOk();
        $this->get(route('public.calculator'))->assertOk();
    }

    public function test_admin_can_create_a_manual_listing_without_dubizzle_source(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.car-listings.store-manual'), [
            'title_fa' => 'بی‌ام‌و X5 دستی',
            'make' => 'bmw',
            'model' => 'x5',
            'model_year' => '2024',
            'price_aed' => 200000,
            'category_id' => 'c3000',
            'delivery_days' => 40,
        ]);

        $listing = CarListing::firstOrFail();
        $response->assertRedirect(route('admin.car-listings.edit', $listing));
        $this->assertSame('manual', $listing->source_site);
        $this->assertSame('', $listing->source_url);
        $this->assertSame('draft', $listing->status);
        $this->assertNotEmpty($listing->slug);
    }

    public function test_car_prices_can_be_filtered_by_brand_category_and_price_bracket(): void
    {
        Setting::set(Setting::FREE_RATE, '50000');
        $cheap = $this->publishedListing(['slug' => 'cheap-car', 'title_fa' => 'تویوتا کمری ارزان', 'make' => 'toyota', 'category_id' => 'c1500', 'price_aed' => 50000]); // 2.5b toman
        $expensive = $this->publishedListing(['slug' => 'expensive-car', 'title_fa' => 'بی‌ام‌و گران', 'make' => 'bmw', 'category_id' => 'c3000', 'price_aed' => 500000]); // 25b toman

        $byBrand = $this->get(route('public.car-prices.brand', 'toyota'));
        $byBrand->assertOk()->assertSee($cheap->title_fa)->assertDontSee($expensive->title_fa);

        $byCategory = $this->get(route('public.car-prices.category', 'c3000'));
        $byCategory->assertOk()->assertSee($expensive->title_fa)->assertDontSee($cheap->title_fa);

        $byPrice = $this->get(route('public.car-prices.price', 'under-5b'));
        $byPrice->assertOk()->assertSee($cheap->title_fa)->assertDontSee($expensive->title_fa);

        $this->get(route('public.car-prices.category', 'not-a-real-category'))->assertNotFound();
        $this->get(route('public.car-prices.price', 'not-a-real-bracket'))->assertNotFound();
    }

    public function test_blog_lifecycle_admin_write_publish_and_public_visibility(): void
    {
        $admin = $this->admin();

        $store = $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'راهنمای تست وبلاگ',
            'excerpt' => 'خلاصه',
            'body' => '<p>محتوا</p>',
        ]);
        $post = Post::firstOrFail();
        $store->assertRedirect(route('admin.posts.edit', $post));
        $this->assertSame('draft', $post->status);

        // Draft not visible to guests.
        auth()->logout();
        $this->get(route('public.blog.show', $post))->assertNotFound();
        $this->get(route('public.blog.index'))->assertOk()->assertDontSee($post->title);

        $this->actingAs($admin)->post(route('admin.posts.publish', $post))->assertRedirect();
        $post->refresh();
        $this->assertSame('published', $post->status);

        auth()->logout();
        $this->get(route('public.blog.show', $post))->assertOk()->assertSee($post->title);
        $this->get(route('public.blog.index'))->assertOk()->assertSee($post->title);
    }

    public function test_menu_items_appear_on_public_layout_when_active(): void
    {
        MenuItem::create(['label' => 'وبلاگ تست', 'url' => '/blog', 'sort_order' => 1, 'is_active' => true]);
        MenuItem::create(['label' => 'غیرفعال', 'url' => '/x', 'sort_order' => 2, 'is_active' => false]);

        $response = $this->get(route('public.car-prices.index'));
        $response->assertOk();
        $response->assertSee('وبلاگ تست');
        $response->assertDontSee('غیرفعال');
    }

    public function test_sitemap_includes_home_listings_categories_and_posts(): void
    {
        $listing = $this->publishedListing();
        Post::create([
            'title' => 'پست سایت‌مپ', 'slug' => 'sitemap-post', 'body' => '<p>x</p>',
            'status' => 'published', 'published_at' => now(),
        ]);

        $response = $this->get(route('public.sitemap'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(route('public.home'), false);
        $response->assertSee(route('public.car-prices.show', $listing), false);
        $response->assertSee(route('public.car-prices.category', 'c2000'), false);
        $response->assertSee(route('public.blog.show', 'sitemap-post'), false);
    }

    public function test_contact_numbers_are_editable_from_settings_and_reflected_on_calculator_page(): void
    {
        Setting::set(Setting::WHATSAPP_IRAN, '+98 900 000 0001');
        Setting::set(Setting::TEHRAN_OFFICE_PHONE, '+98 21 0000 0000');

        $response = $this->get('/calculator');
        $response->assertOk();
        $response->assertSee('+98 900 000 0001');
        $response->assertSee('+98 21 0000 0000');
    }
}
