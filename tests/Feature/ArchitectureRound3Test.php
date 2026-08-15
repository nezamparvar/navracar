<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Setting;
use App\Services\Capture\BrowserCaptureSource;
use App\Services\Capture\DirectUrlCaptureSource;
use App\Services\Capture\DubizzleImportService;
use App\Services\Capture\ManualHtmlCaptureSource;
use App\Services\VehiclePricing\VehiclePricingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchitectureRound3Test extends TestCase
{
    use RefreshDatabase;

    public function test_customs_discount_defaults_to_thirty_and_is_editable(): void
    {
        $this->assertSame('30', Setting::get(Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT));
        Setting::set(Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT, '22.5');
        $this->assertSame(22.5, VehiclePricingSettings::current()->customsValueDiscountPercent);
    }

    public function test_capture_sources_share_one_import_pipeline(): void
    {
        $parser = app(\App\Services\DubizzleParser::class);
        $service = new DubizzleImportService($parser);
        $html = file_get_contents(base_path('tests/Fixtures/dubizzle_bmw_x4.html'));
        $manual = $service->import(new ManualHtmlCaptureSource, 'https://dubai.dubizzle.com/motors/used-cars/example', ['html' => $html]);
        $browser = $service->import(new BrowserCaptureSource, 'https://dubai.dubizzle.com/motors/used-cars/example', ['html' => $html, 'structured' => []]);
        $this->assertSame('parsed', $manual['status']);
        $this->assertSame($manual['data']['price_aed'], $browser['data']['price_aed']);
    }

    public function test_browser_capture_rejects_credentials_and_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.imports.browser-capture'), [
            'url' => 'https://dubai.dubizzle.com/motors/used-cars/example',
            'html' => '<html></html>',
            'cookies' => 'should-never-be-accepted',
        ]);
        $response->assertUnauthorized();
    }
}

