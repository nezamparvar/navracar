<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\VehiclePricing\VehiclePricingInput;
use App\Services\VehiclePricing\VehiclePricingService;
use App\Services\VehiclePricing\VehiclePricingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCostDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_payment_and_timeline_copy_matches_owner_acceptance(): void
    {
        $template = file_get_contents(resource_path('views/components/car-calculator.blade.php'));

        $this->assertStringContainsString('هزینه‌های ترخیص خودرو (جمع کل هزینه‌های گمرکی)', $template);
        $this->assertStringNotContainsString("label: 'پرداخت هزینه‌های ترخیص خودرو (جمع کل هزینه‌های گمرکی)'", $template);
        $this->assertStringContainsString("label: 'پرداخت کارمزد ترخیص‌کار و کارگزار (ناوراکار)'", $template);
        $this->assertStringNotContainsString("shortLabel: 'کارمزد کارگزار'", $template);
        $this->assertStringContainsString("shortLabel: 'ترخیص گمرکی'", $template);
    }

    public function test_public_summary_shows_three_cost_categories(): void
    {
        Setting::set(Setting::FREE_RATE, '50000');
        Setting::set(Setting::OTHER_COSTS_AED, '1000');

        $service = new VehiclePricingService();
        $result = $service->calculate(
            VehiclePricingInput::fromArray([
                'real_price_aed' => 50000,
                'customs_price_aed' => 30000,
                'category_id' => 'c2000',
            ]),
            VehiclePricingSettings::current()
        );

        $summary = $result->publicDisplaySummary();

        $this->assertArrayHasKey('car_price_toman', $summary);
        $this->assertArrayHasKey('clearance_total_toman', $summary);
        $this->assertArrayHasKey('plate_total_toman', $summary);
        $this->assertArrayHasKey('grand_total_toman', $summary);
    }

    public function test_service_fee_included_in_clearance_total_not_separate(): void
    {
        Setting::set(Setting::SERVICE_FEE_PERCENT, '15');

        $service = new VehiclePricingService();
        $result = $service->calculate(
            VehiclePricingInput::fromArray([
                'real_price_aed' => 50000,
                'customs_price_aed' => 30000,
                'category_id' => 'c2000',
            ]),
            VehiclePricingSettings::current()
        );

        $rows = $result->publicCustomsRows();
        $this->assertCount(1, $rows, 'Public should see only 1 row: clearance_total');

        $clearanceRow = $rows[0];
        $this->assertSame('clearance_total', $clearanceRow['key']);

        $expectedClearance = $result->totals['customsSubtotalToman'] + $result->totals['serviceFeeToman'];
        $this->assertEquals($expectedClearance, $clearanceRow['value']);
    }

    public function test_public_endpoint_returns_summary_with_three_categories(): void
    {
        $response = $this->postJson(route('public.vehicle-pricing.calculate'), [
            'real_price_aed' => 50000,
            'customs_price_aed' => 30000,
            'category' => 'c2000',
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('publicSummary', $data);
        $summary = $data['publicSummary'];

        $this->assertArrayHasKey('car_price_toman', $summary);
        $this->assertArrayHasKey('clearance_total_toman', $summary);
        $this->assertArrayHasKey('plate_total_toman', $summary);
        $this->assertArrayHasKey('grand_total_toman', $summary);

        $this->assertTrue($summary['car_price_toman'] > 0);
        $this->assertTrue($summary['clearance_total_toman'] > 0);
        $this->assertTrue($summary['plate_total_toman'] > 0);
        $this->assertTrue($summary['grand_total_toman'] > 0);
    }

    public function test_full_breakdown_includes_service_fee_when_requested(): void
    {
        $service = new VehiclePricingService();
        $result = $service->calculate(
            VehiclePricingInput::fromArray([
                'real_price_aed' => 50000,
                'customs_price_aed' => 30000,
                'category_id' => 'c2000',
            ]),
            VehiclePricingSettings::current()
        );

        $breakdown = $result->breakdownRows();
        $serviceFeeLine = collect($breakdown)->firstWhere('key', 'service_fee');
        $this->assertNotNull($serviceFeeLine, 'Full breakdown should include service_fee when requested');
    }

    public function test_breakdown_can_exclude_service_fee(): void
    {
        $service = new VehiclePricingService();
        $result = $service->calculate(
            VehiclePricingInput::fromArray([
                'real_price_aed' => 50000,
                'customs_price_aed' => 30000,
                'category_id' => 'c2000',
            ]),
            VehiclePricingSettings::current()
        );

        $breakdown = $result->breakdownRows(excludeServiceFee: true);
        $serviceFeeLine = collect($breakdown)->firstWhere('key', 'service_fee');
        $this->assertNull($serviceFeeLine, 'Breakdown should exclude service_fee when requested');
    }

    public function test_totals_include_all_costs_including_service_fee(): void
    {
        $service = new VehiclePricingService();
        $result = $service->calculate(
            VehiclePricingInput::fromArray([
                'real_price_aed' => 50000,
                'customs_price_aed' => 30000,
                'category_id' => 'c2000',
            ]),
            VehiclePricingSettings::current()
        );

        $this->assertArrayHasKey('serviceFeeToman', $result->totals);
        $this->assertTrue($result->totals['serviceFeeToman'] > 0);

        $expectedFinalTotal = $result->totals['preServiceTotalToman'] + $result->totals['serviceFeeToman'];
        $this->assertEquals($expectedFinalTotal, $result->totals['finalTotalToman']);
    }
}
