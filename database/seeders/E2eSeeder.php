<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\CalculationLog;
use App\Models\CalendarEvent;
use App\Models\CarListing;
use App\Models\CarListingImage;
use App\Models\HomeSlide;
use App\Models\ImportQueueItem;
use App\Models\Invoice;
use App\Models\PipelineStage;
use App\Models\Post;
use App\Models\QuoteRequest;
use App\Models\VinCheck;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Local/E2E fixture data only (guarded by app()->environment('local'), same as before).
 * Not real inventory or leads — safe synthetic data for Playwright specs and for design-parity
 * screenshots (see docs/design-v2/implementation/QA_REPORT.md), never seeded in production.
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $sales = AdminUser::updateOrCreate(
            ['username' => 'e2e-sales'],
            [
                'password_hash' => Hash::make('not-used-for-login'),
                'full_name' => 'E2E Sales',
                'role' => 'sales',
            ]
        );

        // Keep the original single fixture listing exactly as-is — tests/e2e/critical-flows.spec.js
        // navigates to this exact slug.
        $original = CarListing::updateOrCreate(
            ['slug' => 'e2e-bmw-x4'],
            [
                'source_url' => 'https://example.test/e2e-bmw-x4',
                'source_site' => 'e2e',
                'status' => 'published',
                'title_en' => 'E2E BMW X4',
                'title_fa' => 'بی‌ام‌و X4 تست',
                'make' => 'bmw',
                'model' => 'x4',
                'model_year' => 2025,
                'price_aed' => 100000,
                'category_id' => 'c2000',
                'published_at' => now(),
            ],
        );
        $this->attachGallery($original, 'BMW X4', '#0A1B32');

        // Additional synthetic listings so list/grid/detail pages have enough populated cards
        // for a real visual-parity comparison (a single card cannot demonstrate grid density).
        $demoListings = [
            ['slug' => 'demo-mercedes-gle', 'title_fa' => 'مرسدس بنز GLE 450 4MATIC', 'label' => 'Mercedes GLE 450', 'make' => 'mercedes-benz', 'model' => 'gle', 'year' => 2024, 'price' => 320000, 'category' => 'c3000', 'km' => 8000, 'fuel' => 'بنزینی', 'color' => '#122C4A'],
            ['slug' => 'demo-toyota-land-cruiser', 'title_fa' => 'تویوتا لندکروزر 300 VX', 'label' => 'Land Cruiser 300', 'make' => 'toyota', 'model' => 'land-cruiser', 'year' => 2024, 'price' => 310000, 'category' => 'c3500', 'km' => 5000, 'fuel' => 'بنزینی', 'color' => '#1A3554'],
            ['slug' => 'demo-lexus-lx600', 'title_fa' => 'لکسوس LX 600', 'label' => 'Lexus LX 600', 'make' => 'lexus', 'model' => 'lx', 'year' => 2024, 'price' => 375000, 'category' => 'c3500', 'km' => 3000, 'fuel' => 'بنزینی', 'color' => '#0F2038'],
            ['slug' => 'demo-porsche-cayenne', 'title_fa' => 'پورشه کاین (Cayenne)', 'label' => 'Porsche Cayenne', 'make' => 'porsche', 'model' => 'cayenne', 'year' => 2024, 'price' => 420000, 'category' => 'c3000', 'km' => 6000, 'fuel' => 'بنزینی', 'color' => '#14243F'],
            ['slug' => 'demo-audi-q7', 'title_fa' => 'آئودی Q7 55 TFSI', 'label' => 'Audi Q7 55 TFSI', 'make' => 'audi', 'model' => 'q7', 'year' => 2024, 'price' => 315000, 'category' => 'c3000', 'km' => 12000, 'fuel' => 'بنزینی', 'color' => '#0D1B30'],
            ['slug' => 'demo-range-rover-sport', 'title_fa' => 'رنج روور اسپرت P400', 'label' => 'Range Rover Sport', 'make' => 'land-rover', 'model' => 'range-rover-sport', 'year' => 2024, 'price' => 360000, 'category' => 'c3000', 'km' => 9000, 'fuel' => 'بنزینی', 'color' => '#16294A'],
            ['slug' => 'demo-volvo-xc90', 'title_fa' => 'ولوو XC90 B6', 'label' => 'Volvo XC90 B6', 'make' => 'volvo', 'model' => 'xc90', 'year' => 2024, 'price' => 295000, 'category' => 'c2500', 'km' => 15000, 'fuel' => 'بنزینی', 'color' => '#102240'],
        ];

        foreach ($demoListings as $idx => $d) {
            $listing = CarListing::updateOrCreate(
                ['slug' => $d['slug']],
                [
                    'source_url' => 'https://example.test/'.$d['slug'],
                    'source_site' => 'e2e',
                    'status' => 'published',
                    'title_en' => $d['title_fa'],
                    'title_fa' => $d['title_fa'],
                    'make' => $d['make'],
                    'model' => $d['model'],
                    'model_year' => $d['year'],
                    'price_aed' => $d['price'],
                    'kilometers' => $d['km'],
                    'fuel_type' => $d['fuel'],
                    'transmission_type' => 'اتوماتیک',
                    'category_id' => $d['category'],
                    'published_at' => now()->subDays(5 + ($idx % 3)),
                ],
            );
            $this->attachGallery($listing, $d['label'], $d['color']);
        }

        // Non-zero, meaningful admin/sales dashboard KPIs — visual hierarchy can't be judged
        // against an all-zero dashboard (see QA_REPORT.md remediation notes). created_at is a
        // DB-level useCurrent() default on all these tables (not Eloquent-fillable), so every
        // row seeded here genuinely lands as "today" without needing to backdate anything.
        // Rich quote request data spread across 14 days + pipeline stages for dashboard widgets
        $stages = PipelineStage::where('is_active', true)->get();
        $stageCount = $stages->count();
        $requestIndex = 0;
        $fixedHours = [9, 13, 15, 17, 11];  // Fixed time slots for deterministic scheduling
        for ($dayOffset = 13; $dayOffset >= 0; $dayOffset--) {
            $dayBase = now()->subDays($dayOffset)->startOfDay();
            $dailyCount = match ($dayOffset) {
                0 => 5,     // today: 5 requests
                1 => 3,     // yesterday: 3
                2 => 4,     // 2 days ago: 4
                3 => 3,     // 3 days ago: 3
                4 => 2,     // 4 days ago: 2
                5 => 4,     // 5 days ago: 4
                6 => 3,     // 6 days ago: 3
                7 => 2,     // 7 days ago: 2
                8 => 3,     // 8 days ago: 3
                9 => 4,     // 9 days ago: 4
                10 => 2,    // 10 days ago: 2
                11 => 3,    // 11 days ago: 3
                12 => 2,    // 12 days ago: 2
                13 => 3,    // 13 days ago: 3
                default => 2,
            };
            for ($j = 0; $j < $dailyCount; $j++) {
                $listingIdx = ($requestIndex + $j) % count($demoListings);
                $listing = $demoListings[$listingIdx];
                $isHot = ($requestIndex + $j) % 3 === 0;
                $nextCall = match ($dayOffset) {
                    0, 1 => null,
                    2, 3 => $dayBase->copy()->addDays(2)->toDateString(),
                    4, 5 => $dayBase->copy()->addDays(3)->toDateString(),
                    default => $dayBase->copy()->addDays(1)->toDateString(),
                };
                $stageId = $stageCount > 0 ? $stages[($requestIndex + $j) % $stageCount]->id : null;
                $hourOffset = $fixedHours[$j % count($fixedHours)];
                QuoteRequest::create([
                    'name' => 'مشتری '.($requestIndex + $j + 1),
                    'phone' => '0912000'.str_pad((string) ($requestIndex + $j), 4, '0', STR_PAD_LEFT),
                    'car_label' => $listing['title_fa'],
                    'category' => $listing['category'],
                    'temperature' => $isHot ? 'hot' : 'warm',
                    'breakdown_json' => '[]',
                    'totals_json' => '{}',
                    'total_with_profit' => $listing['price'] * 55000,
                    'email_sent' => ($requestIndex + $j) % 2 === 0,
                    'source' => 'e2e',
                    'assigned_to' => $sales->id,
                    'created_by' => $sales->id,
                    'current_stage_id' => $stageId,
                    'next_call_date' => $nextCall,
                    'created_at' => $dayBase->copy()->addHours($hourOffset)->setMinute(0)->setSecond(0),
                ]);
            }
            $requestIndex += $dailyCount;
        }

        // Rich calculation logs across categories for category distribution + top cars widgets
        $categoryList = array_values(array_unique(array_column($demoListings, 'category')));
        $carLabelList = array_column($demoListings, 'title_fa');
        $calcHours = [9, 11, 14, 16, 18, 10, 13, 17];  // Fixed time slots
        for ($i = 0; $i < 40; $i++) {
            $catIdx = $i % count($categoryList);
            $carIdx = ($i * 3) % count($carLabelList);
            $dayOffset = ($i * 2) % 14;  // Distribute across 14 days deterministically
            $carLabelIdx = array_search($carLabelList[$carIdx], array_column($demoListings, 'title_fa'));
            $hourOffset = $calcHours[$i % count($calcHours)];
            CalculationLog::create([
                'car_label' => $carLabelList[$carIdx],
                'category' => $categoryList[$catIdx],
                'real_price_aed' => $demoListings[$carLabelIdx]['price'],
                'created_at' => now()->subDays($dayOffset)->addHours($hourOffset)->setMinute(0)->setSecond(0),
            ]);
        }

        VinCheck::create(['vin' => 'WBAJU7101M9E12345']);

        // Content dashboard seed data: import queue with deterministic, varied records
        // Multiple needs_review items to populate the review queue widget (controller filters by status='needs_review')
        $importQueueRecords = [
            // needs_review: confidence < threshold (multiple items for review queue widget)
            ['status' => 'needs_review', 'title' => 'BMW X5 2024', 'make' => 'bmw', 'model' => 'x5', 'year' => 2024, 'price' => 450000, 'mileage' => 0, 'engine' => 3.0, 'images' => 6, 'error' => null, 'day' => 0, 'hour' => 9],
            // needs_review: raw data received, needs QA verification
            ['status' => 'needs_review', 'title' => 'Mercedes GLE 450', 'make' => 'mercedes', 'model' => 'gle', 'year' => 2023, 'price' => 380000, 'mileage' => 5000, 'engine' => 3.0, 'images' => 4, 'error' => null, 'day' => 1, 'hour' => 11],
            // needs_review: partial data, awaiting verification
            ['status' => 'needs_review', 'title' => 'Lexus LX600', 'make' => 'lexus', 'model' => 'lx', 'year' => 2024, 'price' => 420000, 'mileage' => 100, 'engine' => 3.5, 'images' => 5, 'error' => null, 'day' => 2, 'hour' => 14],
            // needs_review: low confidence, flagged for manual review
            ['status' => 'needs_review', 'title' => 'Porsche Cayenne', 'make' => 'porsche', 'model' => 'cayenne', 'year' => 2022, 'price' => 390000, 'mileage' => 8000, 'engine' => 2.9, 'images' => 3, 'error' => null, 'day' => 3, 'hour' => 10],
            // needs_review: high-value import awaiting approval
            ['status' => 'needs_review', 'title' => 'Audi Q7 55', 'make' => 'audi', 'model' => 'q7', 'year' => 2023, 'price' => 350000, 'mileage' => 3000, 'engine' => 3.2, 'images' => 8, 'error' => null, 'day' => 4, 'hour' => 13],
            // needs_review: premium listing in review
            ['status' => 'needs_review', 'title' => 'Range Rover Sport', 'make' => 'range-rover', 'model' => 'sport', 'year' => 2023, 'price' => 360000, 'mileage' => 6000, 'engine' => 3.0, 'images' => 7, 'error' => null, 'day' => 5, 'hour' => 15],
            // needs_review: image quality verification pending
            ['status' => 'needs_review', 'title' => 'Volvo XC90', 'make' => 'volvo', 'model' => 'xc90', 'year' => 2021, 'price' => 295000, 'mileage' => 12000, 'engine' => 2.0, 'images' => 5, 'error' => null, 'day' => 6, 'hour' => 9],
            // needs_review: recent capture awaiting processing
            ['status' => 'needs_review', 'title' => 'Tesla Model X', 'make' => 'tesla', 'model' => 'model-x', 'year' => 2024, 'price' => 500000, 'mileage' => 50, 'engine' => null, 'images' => 2, 'error' => null, 'day' => 0, 'hour' => 16],
        ];

        foreach ($importQueueRecords as $idx => $record) {
            $publishedId = ($record['status'] === 'published')
                ? CarListing::where('slug', 'e2e-bmw-x4')->first()?->id
                : null;

            ImportQueueItem::create([
                'user_id' => $sales->id,
                'source' => 'dubizzle',
                'source_platform' => 'web',
                'capture_method' => 'link',
                'source_url' => 'https://dubizzle.ae/motors/cars/'.($idx + 1),
                'status' => $record['status'],
                'payload_json' => [
                    'title' => $record['title'],
                    'price' => $record['price'],
                    'description' => 'Sample vehicle description',
                ],
                'parsed_json' => [
                    'make' => $record['make'],
                    'model' => $record['model'],
                    'year' => $record['year'],
                    'price' => $record['price'],
                    'mileage' => $record['mileage'],
                    'engine_capacity' => $record['engine'],
                    'body_type' => 'SUV',
                    'fuel_type' => 'Petrol',
                    'transmission' => 'Automatic',
                    'color' => 'Black',
                ],
                'warnings_json' => $record['status'] === 'needs_review' ? ['Low confidence score'] : [],
                'confidence' => $record['status'] === 'needs_review' ? 0.65 : (0.85 + ($idx * 0.01)),
                'error' => $record['error'],
                'published_listing_id' => $publishedId,
                'images_imported' => $record['images'] > 0,
                'created_at' => today()->copy()->subDays(7 - $record['day'])->setTime($record['hour'], 0, 0),
            ]);
        }

        // Home slides for carousel: Delete old records then seed fresh for idempotency
        HomeSlide::truncate();

        $homeSlides = [
            ['order' => 1, 'title' => 'محاسبه هزینه واردات', 'subtitle' => 'هزینه دقیق را محاسبه کنید', 'cta' => 'شروع کنید', 'url' => '/calculator', 'color' => '#1677FF'],
            ['order' => 2, 'title' => 'خودروهای وارداتی معتبر', 'subtitle' => 'بزرگ‌ترین بانک آگهی خودروهای وارداتی', 'cta' => 'مشاهده فهرست', 'url' => '/car-prices', 'color' => '#14243F'],
            ['order' => 3, 'title' => 'مشاوره تخصصی', 'subtitle' => 'تیم متخصص ما آماده کمک است', 'cta' => 'درخواست مشاوره', 'url' => '/lead-form', 'color' => '#122C4A'],
        ];

        foreach ($homeSlides as $slide) {
            $imagePath = 'home-slides/slide-'.$slide['order'].'.png';
            if (! Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->put($imagePath, $this->placeholderImage($slide['title'], 'Slide '.$slide['order'], $slide['color']));
            }
            HomeSlide::create([
                'sort_order' => $slide['order'],
                'title' => $slide['title'],
                'subtitle' => $slide['subtitle'],
                'image_path' => $imagePath,
                'cta_label' => $slide['cta'],
                'cta_url' => $slide['url'],
                'is_active' => true,
            ]);
        }

        // Blog posts for content dashboard and public blog: 3-5 recent posts
        Post::truncate();
        $blogPosts = [
            ['title' => 'نکات مهم در واردات خودرو', 'excerpt' => 'راهنمای جامع برای خریداران خودروی وارداتی و مراحل انجام کار', 'status' => 'published', 'day' => 0],
            ['title' => 'مقایسه هزینه‌های ترخیص گمرکی', 'excerpt' => 'درک کامل عوارض و هزینه‌های اضافی در واردات خودرو', 'status' => 'published', 'day' => 2],
            ['title' => 'بهترین خودروهای وارداتی سال جاری', 'excerpt' => 'معرفی برترین مدل‌های موجود در بازار وارداتی', 'status' => 'published', 'day' => 5],
            ['title' => 'پرسش‌های متکرر درباره خودروی وارداتی', 'excerpt' => 'پاسخ به سوالات رایج خریداران و صادرکنندگان', 'status' => 'published', 'day' => 7],
            ['title' => 'قوانین جدید واردات خودرو', 'excerpt' => 'تغییرات اخیر در مقررات و الگوی واردات خودروی شخصی', 'status' => 'draft', 'day' => 1],
        ];

        foreach ($blogPosts as $post) {
            $imagePath = 'blog-covers/post-'.Str::slug($post['title']).'.png';
            if (! Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->put($imagePath, $this->placeholderImage($post['title'], 'Blog', '#20C7E9'));
            }
            Post::create([
                'title' => $post['title'],
                'slug' => Post::slugify($post['title']),
                'excerpt' => $post['excerpt'],
                'body' => '<p>' . $post['excerpt'] . '</p><p>محتوای کامل پست اینجا قرار می‌گیرد.</p>',
                'cover_image_path' => $imagePath,
                'status' => $post['status'],
                'meta_title' => $post['title'],
                'meta_description' => $post['excerpt'],
                'created_by' => $sales->id,
                'published_at' => $post['status'] === 'published' ? today()->subDays(7 - $post['day']) : null,
            ]);
        }

        // Invoices/proformas linked to quote requests: 3-5 recent, varied statuses
        $requests = QuoteRequest::limit(10)->get();
        $invoiceStatuses = ['draft', 'sent', 'viewed', 'paid', 'expired'];
        foreach (array_slice($requests->all(), 0, 5) as $idx => $request) {
            $status = $invoiceStatuses[$idx % count($invoiceStatuses)];
            Invoice::create([
                'request_id' => $request->id,
                'invoice_number' => 'INV-' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                'customer_name' => $request->name,
                'customer_phone' => $request->phone,
                'customer_email' => 'customer' . ($idx + 1) . '@example.test',
                'car_label' => $request->car_label,
                'category' => $request->category,
                'breakdown_json' => json_encode(['rows' => [
                    ['key' => 'vehicle_price', 'label' => 'قیمت خودرو', 'amount' => $request->total_with_profit / 55000],
                    ['key' => 'customs_duty', 'label' => 'عوارض گمرکی', 'amount' => $request->total_with_profit / 55000 * 0.15],
                    ['key' => 'registration_fee', 'label' => 'هزینه پلاک', 'amount' => 500000],
                ]]),
                'total_amount' => $request->total_with_profit,
                'discount_amount' => $status === 'paid' ? $request->total_with_profit * 0.05 : 0,
                'currency' => 'toman',
                'exchange_rate' => 55000,
                'valid_until' => today()->addDays(30),
                'payment_terms' => 'بانکی',
                'invoice_type' => 'proforma',
                'status' => $status,
                'created_by' => $sales->id,
            ]);
        }

        // Rich calendar events for day/week/list views: today + this week + next week, mixed statuses
        // Using fixed dates (always relative to database seeding date, never "now")
        $requests = QuoteRequest::limit(10)->get();
        $today = today();
        $calendarSchedule = [
            // Today: 3 events at fixed times (09:00, 13:00, 18:00)
            ['day_offset' => 0, 'hour' => 9, 'minute' => 0, 'type' => CalendarEvent::TYPE_CONSULTATION_MEETING, 'duration' => 45, 'status' => CalendarEvent::STATUS_SCHEDULED],
            ['day_offset' => 0, 'hour' => 13, 'minute' => 0, 'type' => CalendarEvent::TYPE_FOLLOW_UP_CALL, 'duration' => 20, 'status' => CalendarEvent::STATUS_SCHEDULED],
            ['day_offset' => 0, 'hour' => 18, 'minute' => 0, 'type' => CalendarEvent::TYPE_PAYMENT_CALL, 'duration' => 30, 'status' => CalendarEvent::STATUS_SCHEDULED],
            // Next day: 1 event
            ['day_offset' => 1, 'hour' => 10, 'minute' => 0, 'type' => CalendarEvent::TYPE_DELIVERY_MEETING, 'duration' => 60, 'status' => CalendarEvent::STATUS_SCHEDULED],
            // Day +2: 1 event
            ['day_offset' => 2, 'hour' => 14, 'minute' => 30, 'type' => CalendarEvent::TYPE_CONSULTATION_MEETING, 'duration' => 45, 'status' => CalendarEvent::STATUS_SCHEDULED],
            // Day +3: 1 completed event
            ['day_offset' => 3, 'hour' => 11, 'minute' => 0, 'type' => CalendarEvent::TYPE_FOLLOW_UP_CALL, 'duration' => 15, 'status' => CalendarEvent::STATUS_COMPLETED],
            // Day +4: 1 event
            ['day_offset' => 4, 'hour' => 16, 'minute' => 0, 'type' => CalendarEvent::TYPE_PAYMENT_CALL, 'duration' => 25, 'status' => CalendarEvent::STATUS_SCHEDULED],
            // Yesterday: 1 completed event for visual reference
            ['day_offset' => -1, 'hour' => 10, 'minute' => 0, 'type' => CalendarEvent::TYPE_DELIVERY_MEETING, 'duration' => 45, 'status' => CalendarEvent::STATUS_COMPLETED],
        ];
        $eventIndex = 0;
        foreach ($calendarSchedule as $ce) {
            $start = $today->copy()->addDays($ce['day_offset'])->setTime($ce['hour'], $ce['minute'], 0);
            $req = $requests[$eventIndex % $requests->count()] ?? $requests->first();
            CalendarEvent::create([
                'type' => $ce['type'],
                'quote_request_id' => $req?->id,
                'assigned_to' => $sales->id,
                'created_by' => $sales->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($ce['duration']),
                'status' => $ce['status'],
                'notes' => 'رویداد نمونه برای بررسی بصری تقویم.',
            ]);
            $eventIndex++;
        }
    }

    /**
     * Attaches a small multi-shot gallery (front / side / rear / interior) per listing, not just
     * a single cover image, so the real thumbnail-gallery UI on the vehicle-detail page has
     * something to actually render (see docs/design-v2 remediation notes).
     *
     * Uses design-derived vehicle images extracted from the approved design reference commit
     * (1cdab114920cdc2431f983a1c1ea9efb88e26f82) instead of GD-generated silhouettes.
     */
    private function attachGallery(CarListing $listing, string $label, string $color): void
    {
        if ($listing->images()->exists()) {
            return;
        }

        $angles = ['Front', 'Side', 'Rear', 'Interior'];
        static $vehicleImageIndex = 0;

        // Try to use extracted design-derived vehicle images first
        $designVehicles = glob(storage_path('app/public/e2e/design-derived-vehicles/vehicle-*.png'));
        if (empty($designVehicles)) {
            // Fallback to GD generation if extracted images not available
            $this->attachGalleryWithPlaceholders($listing, $label, $color, $angles);
            return;
        }

        // Use extracted images in round-robin fashion across the 8 demo listings
        $vehicleImagePath = $designVehicles[$vehicleImageIndex % count($designVehicles)];
        $vehicleImageIndex++;
        $vehicleContent = file_get_contents($vehicleImagePath);

        foreach ($angles as $i => $angle) {
            $path = 'car-listings-demo/'.$listing->slug.'-'.$i.'.png';
            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, $vehicleContent);
            }

            CarListingImage::create([
                'car_listing_id' => $listing->id,
                'local_path' => $path,
                'source_url' => 'https://example.test/'.$listing->slug.'-'.$i.'.png',
                'sort_order' => $i,
                'is_cover' => $i === 0,
            ]);
        }
    }

    /**
     * Fallback gallery generation using GD silhouettes when design-derived images unavailable.
     */
    private function attachGalleryWithPlaceholders(CarListing $listing, string $label, string $color, array $angles): void
    {
        foreach ($angles as $i => $angle) {
            $path = 'car-listings-demo/'.$listing->slug.'-'.$i.'.png';
            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, $this->placeholderImage($label, $angle, $color));
            }

            CarListingImage::create([
                'car_listing_id' => $listing->id,
                'local_path' => $path,
                'source_url' => 'https://example.test/'.$listing->slug.'-'.$i.'.png',
                'sort_order' => $i,
                'is_cover' => $i === 0,
            ]);
        }
    }

    /**
     * A generated placeholder (gradient background + car silhouette + angle label) so screenshots
     * show a real loaded image instead of a broken link — this sandbox has no outbound access to
     * real photo hosts. Not a real vehicle photo; purely a QA fixture.
     */
    private function placeholderImage(string $label, string $angle, string $hexColor): string
    {
        // GD's built-in bitmap font only supports Latin-1, so all drawn text must be ASCII
        // (the English make/model and angle name, never title_fa) — Persian text renders as
        // garbage otherwise. Canvas is 1200x630 (~19:10) to match the card's real display
        // aspect ratio, so object-cover doesn't need to crop the silhouette awkwardly.
        $width = 1200;
        $height = 630;
        $image = imagecreatetruecolor($width, $height);

        [$r, $g, $b] = sscanf($hexColor, '#%02x%02x%02x');
        $rLight = min(255, $r + 40);
        $gLight = min(255, $g + 40);
        $bLight = min(255, $b + 40);

        // Vertical gradient background so the placeholder reads as a lit photo studio backdrop
        // rather than a flat color swatch.
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $row = imagecolorallocate(
                $image,
                (int) ($r + ($rLight - $r) * (1 - $ratio)),
                (int) ($g + ($gLight - $g) * (1 - $ratio)),
                (int) ($b + ($bLight - $b) * (1 - $ratio)),
            );
            imageline($image, 0, $y, $width, $y, $row);
        }

        // Studio "floor" shadow ellipse under the car.
        $cx = $width * 0.5;
        $cy = $height * 0.58;
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 55);
        imagefilledellipse($image, (int) $cx, (int) ($height * 0.86), 780, 50, $shadow);

        // A side-profile car-body silhouette (polygon, not a raw rectangle) so the placeholder
        // reads as an actual vehicle shape — sloped hood/roof/trunk line, cabin, two wheels.
        $silhouette = imagecolorallocatealpha($image, 255, 255, 255, 88);
        $bodyTop = $cy - 90;
        $bodyBottom = $cy + 40;
        $points = [
            $cx - 380, $bodyBottom,
            $cx - 380, $cy - 10,
            $cx - 280, $cy - 10,
            $cx - 210, $bodyTop + 15,
            $cx - 60, $bodyTop,
            $cx + 90, $bodyTop,
            $cx + 220, $cy - 20,
            $cx + 380, $cy - 10,
            $cx + 380, $bodyBottom,
        ];
        imagefilledpolygon($image, $points, $silhouette);
        $wheel = imagecolorallocatealpha($image, 2, 11, 24, 30);
        imagefilledellipse($image, (int) ($cx - 220), (int) ($bodyBottom + 5), 110, 110, $wheel);
        imagefilledellipse($image, (int) ($cx + 220), (int) ($bodyBottom + 5), 110, 110, $wheel);
        $hub = imagecolorallocatealpha($image, 180, 190, 205, 60);
        imagefilledellipse($image, (int) ($cx - 220), (int) ($bodyBottom + 5), 44, 44, $hub);
        imagefilledellipse($image, (int) ($cx + 220), (int) ($bodyBottom + 5), 44, 44, $hub);

        // Windshield/window band on the cabin, slightly darker than the body silhouette.
        $glass = imagecolorallocatealpha($image, 10, 20, 35, 70);
        imagefilledpolygon($image, [
            $cx - 185, $cy - 15,
            $cx - 130, $bodyTop + 20,
            $cx + 60, $bodyTop + 20,
            $cx + 150, $cy - 15,
        ], $glass);

        $accent = imagecolorallocate($image, 22, 119, 255);
        imagefilledrectangle($image, 0, $height - 14, $width, $height, $accent);

        $text = imagecolorallocate($image, 248, 250, 252);
        imagestring($image, 5, 24, $height - 40, $label, $text);
        $badge = imagecolorallocatealpha($image, 10, 27, 50, 30);
        imagefilledrectangle($image, $width - 130, 20, $width - 20, 52, $badge);
        imagestring($image, 4, $width - 122, 28, $angle, $text);

        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        return $data;
    }
}
