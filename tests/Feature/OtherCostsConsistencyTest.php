<?php

namespace Tests\Feature;

use App\Models\CarListing;
use App\Models\Setting;
use App\Services\VehiclePricing\VehiclePricingInput;
use App\Services\VehiclePricing\VehiclePricingService;
use App\Services\VehiclePricing\VehiclePricingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherCostsConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_costs_aed_uses_configured_exchange_rate(): void
    {
        Setting::set(Setting::FREE_RATE, '50000');
        Setting::set(Setting::OTHER_COSTS_AED, '1000');

        $settings = VehiclePricingSettings::current();
        $this->assertSame(1000.0, $settings->otherCostsAed);

        $service = new VehiclePricingService();
        $result = $service->calculate(VehiclePricingInput::fromArray([
            'real_price_aed' => 50000,
            'customs_price_aed' => 30000,
            'category_id' => 'c2000',
        ]), $settings);

        $expectedOtherCostsToman = 1000 * 50000;
        $otherCostsRow = collect($result->customsRows)->firstWhere('key', 'other_costs');
        $this->assertNotNull($otherCostsRow);
        $this->assertEquals($expectedOtherCostsToman, $otherCostsRow['value']);
    }

    public function test_other_costs_zero_is_handled_correctly(): void
    {
        Setting::set(Setting::OTHER_COSTS_AED, '0');

        $settings = VehiclePricingSettings::current();
        $this->assertSame(0.0, $settings->otherCostsAed);

        $service = new VehiclePricingService();
        $result = $service->calculate(VehiclePricingInput::fromArray([
            'real_price_aed' => 50000,
            'customs_price_aed' => 30000,
            'category_id' => 'c2000',
        ]), $settings);

        $customsRowKeys = array_map(fn (array $row) => $row['key'] ?? null, $result->customsRows);
        $this->assertNotContains('other_costs', $customsRowKeys, 'Zero other_costs should not appear in customs rows');
    }

    public function test_other_costs_positive_value_included_in_breakdown(): void
    {
        Setting::set(Setting::OTHER_COSTS_AED, '2500');

        $service = new VehiclePricingService();
        $result = $service->calculate(VehiclePricingInput::fromArray([
            'real_price_aed' => 50000,
            'customs_price_aed' => 30000,
            'category_id' => 'c2000',
        ]), VehiclePricingSettings::current());

        $customsRowKeys = array_map(fn (array $row) => $row['key'] ?? null, $result->customsRows);
        $this->assertContains('other_costs', $customsRowKeys, 'Positive other_costs should appear in customs rows');

        $otherCostsRow = collect($result->customsRows)->firstWhere('key', 'other_costs');
        $this->assertNotNull($otherCostsRow);
        $this->assertStringContainsString('هزینه‌های دیگر', $otherCostsRow['label']);
        $this->assertTrue($otherCostsRow['value'] > 0);
    }

    public function test_public_endpoint_respects_other_costs_setting(): void
    {
        Setting::set(Setting::OTHER_COSTS_AED, '1500');

        $response = $this->postJson(route('public.vehicle-pricing.calculate'), [
            'real_price_aed' => 50000,
            'customs_price_aed' => 30000,
            'category' => 'c2000',
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('customsSubtotalToman', $data);
        $this->assertTrue($data['customsSubtotalToman'] > 0, 'customsSubtotal should include other_costs');
    }

    public function test_setting_constant_exists_and_defaults_to_zero(): void
    {
        $this->assertSame('other_costs_aed', Setting::OTHER_COSTS_AED);
        $this->assertArrayHasKey(Setting::OTHER_COSTS_AED, Setting::DEFAULTS);
        $this->assertSame('0', Setting::DEFAULTS[Setting::OTHER_COSTS_AED]);
    }
}
