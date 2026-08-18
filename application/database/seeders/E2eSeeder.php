<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\CarListing;
use App\Models\MobileAnalyticsEvent;
use App\Models\MobileAppInstallation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2eSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        AdminUser::updateOrCreate(
            ['username' => 'e2e-sales'],
            [
                'password_hash' => Hash::make('not-used-for-login'),
                'full_name' => 'E2E Sales',
                'role' => 'sales',
            ]
        );

        CarListing::updateOrCreate(
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

        $installation = MobileAppInstallation::updateOrCreate(
            ['installation_id' => '018f55ce-3d62-7d81-a0c3-7f5e05f2e2e2'],
            [
                'secret_hash' => hash('sha256', str_repeat('e', 43)),
                'analytics_consent' => true,
                'notifications_consent' => false,
                'device_manufacturer' => 'Samsung',
                'device_model' => 'SM-S928B',
                'platform' => 'android',
                'os_version' => '14',
                'app_version' => '1.1.0',
                'country' => 'United Arab Emirates',
                'city' => 'Dubai',
                'acquisition_source' => 'direct',
                'last_seen_at' => now(),
            ]
        );
        $installation->events()->delete();
        MobileAnalyticsEvent::create([
            'mobile_app_installation_id' => $installation->id,
            'name' => 'search',
            'page' => 'vehicles',
            'properties' => ['query' => 'BMW X5'],
            'occurred_at' => now(),
        ]);
        MobileAnalyticsEvent::create([
            'mobile_app_installation_id' => $installation->id,
            'name' => 'whatsapp_click',
            'page' => 'home',
            'properties' => ['placement' => 'home'],
            'occurred_at' => now(),
        ]);
    }
}
