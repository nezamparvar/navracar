<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\CalculationLog;
use App\Models\CalendarEvent;
use App\Models\CarListing;
use App\Models\CarListingImage;
use App\Models\QuoteRequest;
use App\Models\VinCheck;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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

        foreach ($demoListings as $d) {
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
                    'published_at' => now()->subDays(random_int(0, 10)),
                ],
            );
            $this->attachGallery($listing, $d['label'], $d['color']);
        }

        // Non-zero, meaningful admin/sales dashboard KPIs — visual hierarchy can't be judged
        // against an all-zero dashboard (see QA_REPORT.md remediation notes). created_at is a
        // DB-level useCurrent() default on all these tables (not Eloquent-fillable), so every
        // row seeded here genuinely lands as "today" without needing to backdate anything.
        for ($i = 0; $i < 5; $i++) {
            QuoteRequest::create([
                'name' => 'مشتری تست '.($i + 1),
                'phone' => '0912000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'car_label' => $demoListings[$i % count($demoListings)]['title_fa'],
                'category' => $demoListings[$i % count($demoListings)]['category'],
                'temperature' => $i % 2 === 0 ? 'hot' : 'warm',
                'breakdown_json' => '[]',
                'totals_json' => '{}',
                'total_with_profit' => $demoListings[$i % count($demoListings)]['price'] * 55000,
                'email_sent' => $i % 2 === 0,
                'source' => 'e2e',
                'assigned_to' => $sales->id,
                'created_by' => $sales->id,
                'current_stage_id' => ($i % 9) + 1,
            ]);
        }

        for ($i = 0; $i < 6; $i++) {
            CalculationLog::create([
                'car_label' => $demoListings[$i % count($demoListings)]['title_fa'],
                'category' => $demoListings[$i % count($demoListings)]['category'],
                'real_price_aed' => $demoListings[$i % count($demoListings)]['price'],
            ]);
        }

        VinCheck::create(['vin' => 'WBAJU7101M9E12345']);

        $firstRequest = QuoteRequest::first();
        $today = now();
        $calendarEvents = [
            ['type' => CalendarEvent::TYPE_FOLLOW_UP_CALL, 'offset_hours' => 2, 'duration' => 15, 'status' => CalendarEvent::STATUS_SCHEDULED],
            ['type' => CalendarEvent::TYPE_CONSULTATION_MEETING, 'offset_hours' => 26, 'duration' => 60, 'status' => CalendarEvent::STATUS_SCHEDULED],
            ['type' => CalendarEvent::TYPE_PAYMENT_CALL, 'offset_hours' => 50, 'duration' => 20, 'status' => CalendarEvent::STATUS_SCHEDULED],
            ['type' => CalendarEvent::TYPE_DELIVERY_MEETING, 'offset_hours' => -24, 'duration' => 45, 'status' => CalendarEvent::STATUS_COMPLETED],
            ['type' => CalendarEvent::TYPE_CONSULTATION_MEETING, 'offset_hours' => 96, 'duration' => 30, 'status' => CalendarEvent::STATUS_SCHEDULED],
        ];
        foreach ($calendarEvents as $ce) {
            $start = $today->copy()->addHours($ce['offset_hours'])->setMinute(0)->setSecond(0);
            CalendarEvent::create([
                'type' => $ce['type'],
                'quote_request_id' => $firstRequest?->id,
                'assigned_to' => $sales->id,
                'created_by' => $sales->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($ce['duration']),
                'status' => $ce['status'],
                'notes' => 'رویداد نمونه برای بررسی بصری تقویم.',
            ]);
        }
    }

    /**
     * Attaches a small multi-shot gallery (front / side / rear / interior) per listing, not just
     * a single cover image, so the real thumbnail-gallery UI on the vehicle-detail page has
     * something to actually render (see docs/design-v2 remediation notes).
     */
    private function attachGallery(CarListing $listing, string $label, string $color): void
    {
        if ($listing->images()->exists()) {
            return;
        }

        $angles = ['Front', 'Side', 'Rear', 'Interior'];

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
        // garbage otherwise.
        $width = 800;
        $height = 600;
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
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 55);
        imagefilledellipse($image, (int) ($width / 2), (int) ($height * 0.78), 560, 60, $shadow);

        // A simple car-body silhouette so the placeholder reads as "a car photo slot",
        // not just a color swatch — cabin + body + two wheels.
        $silhouette = imagecolorallocatealpha($image, 255, 255, 255, 90);
        $cx = $width / 2;
        $cy = $height * 0.55;
        imagefilledellipse($image, (int) $cx, (int) $cy + 10, 520, 170, $silhouette);
        imagefilledrectangle($image, (int) $cx - 140, (int) $cy - 80, (int) $cx + 140, (int) $cy + 20, $silhouette);
        $wheel = imagecolorallocatealpha($image, 2, 11, 24, 35);
        imagefilledellipse($image, (int) $cx - 165, (int) $cy + 70, 90, 90, $wheel);
        imagefilledellipse($image, (int) $cx + 165, (int) $cy + 70, 90, 90, $wheel);

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
