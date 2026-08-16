<?php

namespace Tests\Feature;

use App\Services\Capture\MarketplaceHtmlImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_html_adapters_detect_dubicars_and_yallamotor(): void
    {
        $service = app(MarketplaceHtmlImportService::class);
        $dubi = $service->import(file_get_contents(base_path('tests/Fixtures/dubicars_sample.html')), 'https://www.dubicars.com/sample');
        $yalla = $service->import(file_get_contents(base_path('tests/Fixtures/yallamotor_sample.html')), 'https://uae.yallamotor.com/sample');

        $this->assertSame('dubicars', $dubi['source_platform']);
        $this->assertSame('manual_html', $dubi['capture_method']);
        $this->assertSame(85000.0, $dubi['data']['price_aed']);
        $this->assertSame('yallamotor', $yalla['source_platform']);
        $this->assertSame('manual_html', $yalla['capture_method']);
        $this->assertSame('Sample Sedan', $yalla['data']['title_en']);
    }

    public function test_unsupported_or_ambiguous_html_is_rejected(): void
    {
        $service = app(MarketplaceHtmlImportService::class);
        $this->expectException(\InvalidArgumentException::class);
        $service->import('<html><body>dubizzle dubicars</body></html>', 'https://example.test/listing');
    }
}
