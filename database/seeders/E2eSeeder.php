<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\CalculationLog;
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
        $this->attachCoverImage($original, 'BMW X4', '#0A1B32');

        // Additional synthetic listings so list/grid/detail pages have enough populated cards
        // for a real visual-parity comparison (a single card cannot demonstrate grid density).
        $demoListings = [
            ['slug' => 'demo-mercedes-gle', 'title_fa' => 'مرسدس بنز GLE 450 4MATIC', 'make' => 'mercedes-benz', 'model' => 'gle', 'year' => 2024, 'price' => 320000, 'category' => 'c3000', 'km' => 8000, 'fuel' => 'بنزینی', 'color' => '#122C4A'],
            ['slug' => 'demo-toyota-land-cruiser', 'title_fa' => 'تویوتا لندکروزر 300 VX', 'make' => 'toyota', 'model' => 'land-cruiser', 'year' => 2024, 'price' => 310000, 'category' => 'c3500', 'km' => 5000, 'fuel' => 'بنزینی', 'color' => '#1A3554'],
            ['slug' => 'demo-lexus-lx600', 'title_fa' => 'لکسوس LX 600', 'make' => 'lexus', 'model' => 'lx', 'year' => 2024, 'price' => 375000, 'category' => 'c3500', 'km' => 3000, 'fuel' => 'بنزینی', 'color' => '#0F2038'],
            ['slug' => 'demo-porsche-cayenne', 'title_fa' => 'پورشه کاین (Cayenne)', 'make' => 'porsche', 'model' => 'cayenne', 'year' => 2024, 'price' => 420000, 'category' => 'c3000', 'km' => 6000, 'fuel' => 'بنزینی', 'color' => '#14243F'],
            ['slug' => 'demo-audi-q7', 'title_fa' => 'آئودی Q7 55 TFSI', 'make' => 'audi', 'model' => 'q7', 'year' => 2024, 'price' => 315000, 'category' => 'c3000', 'km' => 12000, 'fuel' => 'بنزینی', 'color' => '#0D1B30'],
            ['slug' => 'demo-range-rover-sport', 'title_fa' => 'رنج روور اسپرت P400', 'make' => 'land-rover', 'model' => 'range-rover-sport', 'year' => 2024, 'price' => 360000, 'category' => 'c3000', 'km' => 9000, 'fuel' => 'بنزینی', 'color' => '#16294A'],
            ['slug' => 'demo-volvo-xc90', 'title_fa' => 'ولوو XC90 B6', 'make' => 'volvo', 'model' => 'xc90', 'year' => 2024, 'price' => 295000, 'category' => 'c2500', 'km' => 15000, 'fuel' => 'بنزینی', 'color' => '#102240'],
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
            $this->attachCoverImage($listing, $d['title_fa'], $d['color']);
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
    }

    private function attachCoverImage(CarListing $listing, string $label, string $color): void
    {
        if ($listing->images()->exists()) {
            return;
        }

        $path = 'car-listings-demo/'.$listing->slug.'.png';
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $this->placeholderImage($label, $color));
        }

        CarListingImage::create([
            'car_listing_id' => $listing->id,
            'local_path' => $path,
            'source_url' => 'https://example.test/'.$listing->slug.'.png',
            'sort_order' => 0,
            'is_cover' => true,
        ]);
    }

    /**
     * A simple generated placeholder (solid color + label) so screenshots show a real loaded
     * image instead of a broken link — this sandbox has no outbound access to real photo hosts.
     * Not a real vehicle photo; purely a QA fixture.
     */
    private function placeholderImage(string $label, string $hexColor): string
    {
        $width = 640;
        $height = 480;
        $image = imagecreatetruecolor($width, $height);

        [$r, $g, $b] = sscanf($hexColor, '#%02x%02x%02x');
        $bg = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, $bg);

        $accent = imagecolorallocate($image, 22, 119, 255);
        imagefilledrectangle($image, 0, $height - 12, $width, $height, $accent);

        $text = imagecolorallocate($image, 248, 250, 252);
        imagestring($image, 5, 24, $height / 2 - 10, $label, $text);

        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        return $data;
    }
}
