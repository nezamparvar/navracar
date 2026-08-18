<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\CalculationLog;
use App\Models\CarListing;
use App\Models\Invoice;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Services\VehiclePricing\VehiclePricingCatalog;
use App\Services\VehiclePricing\VehiclePricingInput;
use App\Services\VehiclePricing\VehiclePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehiclePricingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set(Setting::FREE_RATE, '50000');
        Setting::set(Setting::CUSTOMS_RATE, '35000');
        Setting::set(Setting::SEA_FREIGHT_AED, '1500');
        Setting::set(Setting::LICENSE_FEE_AED, '60000');
        Setting::set(Setting::STORAGE_TOMAN, '100000000');
        Setting::set(Setting::SCRAP_CERT_PRICE_TOMAN, '25000000');
        Setting::set(Setting::SCRAP_THRESHOLD_AED, '60000');
    }

    private function input(): array
    {
        return [
            'real_price_aed' => 100000,
            'customs_price_aed' => 80000,
            'category' => 'c2000',
        ];
    }

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'username' => 'pricing-admin',
            'password_hash' => bcrypt('secret'),
            'full_name' => 'Pricing Admin',
            'role' => 'admin',
        ]);
    }

    public function test_engine_preserves_the_established_dubizzle_formula_exactly(): void
    {
        $result = app(VehiclePricingService::class)->calculate(VehiclePricingInput::fromArray($this->input()));

        $this->assertSame(8, count($result->settingsSnapshot['categories']));
        $this->assertEqualsWithDelta(2_800_000_000, $result->totals['cifToman'], 0.01);
        $this->assertEqualsWithDelta(3_360_000_000, $result->totals['tariffDutyToman'], 0.01);
        $this->assertEqualsWithDelta(4_705_400_000, $result->totals['customsPercentageSubtotalToman'], 0.01);
        $this->assertSame(7, $result->totals['scrapCertificateCount']);
        $this->assertEqualsWithDelta(175_000_000, $result->totals['scrapCostToman'], 0.01);
        $this->assertEqualsWithDelta(7_880_400_000, $result->totals['customsSubtotalToman'], 0.01);
        $this->assertEqualsWithDelta(707_000_000, $result->totals['plateSubtotalToman'], 0.01);
        $this->assertEqualsWithDelta(848_740_000, $result->totals['serviceFeeToman'], 0.01);
        $this->assertEqualsWithDelta(14_436_140_000, $result->totals['finalTotalToman'], 0.01);
        $this->assertNotContains('scrappage_percent', array_column($result->plateRows, 'key'));
    }

    public function test_public_pricing_endpoint_uses_live_settings_and_all_canonical_categories(): void
    {
        $response = $this->postJson(route('public.vehicle-pricing.calculate'), $this->input());

        $response->assertOk()
            ->assertJsonPath('category.id', 'c2000')
            ->assertJsonPath('scrapCertificateCount', 7)
            ->assertJsonCount(8, 'settingsSnapshot.categories');

        Setting::set(Setting::SERVICE_FEE_PERCENT, '12.5');
        $updated = $this->postJson(route('public.vehicle-pricing.calculate'), $this->input());
        $updated->assertOk()->assertJsonPath('settingsSnapshot.percentages.serviceFee', 12.5);
        $this->assertGreaterThan($response->json('finalTotalToman'), $updated->json('finalTotalToman'));
    }

    public function test_missing_customs_price_uses_the_configured_discount_on_the_server(): void
    {
        Setting::set(Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT, '30');

        $response = $this->postJson(route('public.vehicle-pricing.calculate'), [
            'real_price_aed' => 100000,
            'category' => 'c2000',
        ]);

        $response->assertOk()->assertJsonPath('input.customsPriceAed', 70000);
    }

    public function test_native_mobile_origin_can_use_the_stateless_pricing_api_but_random_origins_are_not_allowed(): void
    {
        $payload = ['real_price_aed' => 100000, 'category' => 'c2000'];

        $this->withHeader('Origin', 'https://localhost')
            ->postJson(route('api.vehicle-pricing.calculate'), $payload)
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://localhost')
            ->assertJsonPath('input.customsPriceAed', 70000);

        $this->withHeader('Origin', 'https://untrusted.example')
            ->postJson(route('api.vehicle-pricing.calculate'), $payload)
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_zero_percent_setting_and_explicit_zero_customs_override_are_not_replaced_by_defaults(): void
    {
        Setting::set(Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT, '0');
        $pricing = app(VehiclePricingService::class);

        $suggested = $pricing->inputFromArray([
            'real_price_aed' => 100000,
            'category' => 'c2000',
        ]);
        $this->assertSame(100000.0, $suggested->customsPriceAed);

        Setting::set(Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT, '30');
        $explicitZero = $pricing->inputFromArray([
            'real_price_aed' => 100000,
            'customs_price_aed' => 0,
            'category' => 'c2000',
        ]);
        $this->assertSame(0.0, $explicitZero->customsPriceAed);
    }

    public function test_automatic_invoice_computes_a_blank_customs_price_on_the_server(): void
    {
        Setting::set(Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT, '30');

        $this->actingAs($this->admin())->post(route('admin.invoices.store'), [
            'customer_name' => 'Server Customs Default',
            'customer_phone' => '09121111112',
            'category' => 'c2000',
            'pricing_mode' => 'automatic',
            'real_price_aed' => 100000,
            'customs_price_aed' => '',
            'adjustment_amount' => 0,
            'discount_amount' => '0',
            'currency' => 'toman',
            'invoice_type' => 'full',
        ])->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $this->assertSame(70000.0, (float) $invoice->pricingMetadata()['pricing_input']['customsPriceAed']);
    }

    public function test_every_category_and_representative_price_matches_the_previous_correct_listing_formula(): void
    {
        foreach (VehiclePricingCatalog::categoryIds() as $categoryId) {
            foreach ([25_000, 60_000, 150_000] as $priceAed) {
                $input = new VehiclePricingInput($priceAed, $priceAed, $categoryId);
                $result = app(VehiclePricingService::class)->calculate($input);
                $legacyTotal = $this->legacyDubizzleTotal($priceAed, $categoryId, $result->settingsSnapshot);

                $this->assertEqualsWithDelta($legacyTotal, $result->totals['finalTotalToman'], 0.01, $categoryId.' @ '.$priceAed);

                // customs_price_aed is set explicitly here (equal to $priceAed, matching $input
                // above) so this stays a delegate-correctness check — without it,
                // estimatedLandedCostToman() would correctly apply the default customs
                // discount via suggestCustomsPrice(), which is a different, already-covered
                // scenario (see PublicCostDisplayTest and the customs-price-default test above).
                $listing = new CarListing(['price_aed' => $priceAed, 'customs_price_aed' => $priceAed, 'category_id' => $categoryId]);
                $this->assertEqualsWithDelta(
                    $result->totals['finalTotalToman'],
                    $listing->estimatedLandedCostToman(50_000, 35_000),
                    0.01,
                    'Listing delegate mismatch for '.$categoryId.' @ '.$priceAed,
                );
            }
        }
    }

    public function test_scrappage_tiers_and_threshold_boundary_are_exact(): void
    {
        $cases = [
            ['ev', 59_999, 1], ['ev', 60_001, 1],
            ['c2000', 59_999, 5], ['c2000', 60_000, 5], ['c2000', 60_001, 7],
            ['c3000', 59_999, 6], ['c3000', 60_001, 9],
        ];

        foreach ($cases as [$category, $customsPrice, $expectedCount]) {
            $result = app(VehiclePricingService::class)->calculate(new VehiclePricingInput(80_000, $customsPrice, $category));
            $this->assertSame($expectedCount, $result->totals['scrapCertificateCount'], $category.' @ '.$customsPrice);
            $this->assertEqualsWithDelta($expectedCount * 25_000_000, $result->totals['scrapCostToman'], 0.01);
        }

        Setting::set(Setting::SCRAP_TIER_PREFIX.'c2000', 'invalid');
        $fallback = app(VehiclePricingService::class)->calculate(new VehiclePricingInput(80_000, 80_000, 'c2000'));
        $this->assertSame('cd', $fallback->category['scrapTier']);
        $this->assertSame(7, $fallback->totals['scrapCertificateCount']);
    }

    public function test_each_pricing_setting_change_is_visible_immediately_to_new_calculations(): void
    {
        $cases = [
            [Setting::FREE_RATE, '55000', 'finalTotalToman'],
            [Setting::CUSTOMS_RATE, '40000', 'finalTotalToman'],
            [Setting::SCRAP_CERT_PRICE_TOMAN, '30000000', 'scrapCostToman'],
            [Setting::SCRAP_THRESHOLD_AED, '100000', 'scrapCertificateCount'],
            [Setting::TARIFF_PREFIX.'c2000', '130', 'tariffDutyToman'],
            [Setting::VAT_PERCENT, '11', 'finalTotalToman'],
            [Setting::SERVICE_FEE_PERCENT, '12', 'serviceFeeToman'],
            [Setting::scrapCertificateCountKey('cd', 'above'), '8', 'scrapCertificateCount'],
        ];

        foreach ($cases as [$key, $newValue, $field]) {
            $oldValue = Setting::get($key);
            $before = app(VehiclePricingService::class)->calculate(VehiclePricingInput::fromArray($this->input()));
            Setting::set($key, $newValue);
            $after = app(VehiclePricingService::class)->calculate(VehiclePricingInput::fromArray($this->input()));

            $this->assertNotEquals($before->totals[$field], $after->totals[$field], $key.' did not invalidate immediately');
            $this->assertSame($newValue, Setting::get($key));
            Setting::set($key, $oldValue);
        }
    }

    public function test_quote_and_calculation_log_ignore_client_supplied_totals(): void
    {
        Storage::fake('public');
        Mail::fake();

        $expected = app(VehiclePricingService::class)->calculate(VehiclePricingInput::fromArray($this->input()));
        $this->postJson(route('public.quote-requests.store'), [
            'name' => 'Authoritative Quote',
            'phone' => '09120000000',
            'car' => 'BMW X4',
            'pricing' => $this->input(),
            'breakdown' => [['label' => 'tampered', 'amount' => '1']],
            'totals' => ['جمع کل نهایی' => '1 تومان'],
            'website' => '',
            'pageLoadedAt' => 0,
        ])->assertOk();

        $quote = QuoteRequest::firstOrFail();
        $this->assertEqualsWithDelta($expected->totals['finalTotalToman'], $quote->total_with_profit, 0.01);
        $this->assertSame('v1.2.0', $quote->pricingMetadata()['engine_version']);
        $this->assertNotSame('tampered', $quote->breakdown()[0]['label']);

        $this->postJson(route('public.calculation-logs.store'), [
            'car' => 'BMW X4',
            'pricing' => $this->input(),
            'total_with_profit' => 1,
        ])->assertOk();

        $log = CalculationLog::firstOrFail();
        $this->assertEqualsWithDelta($expected->totals['finalTotalToman'], $log->total_with_profit, 0.01);
    }

    public function test_automatic_invoice_ignores_browser_total_and_keeps_a_historical_snapshot(): void
    {
        $expected = app(VehiclePricingService::class)->calculate(VehiclePricingInput::fromArray($this->input()));

        $this->actingAs($this->admin())->post(route('admin.invoices.store'), [
            'customer_name' => 'Server Authority',
            'customer_phone' => '09121111111',
            'car_label' => 'BMW X4',
            'category' => 'c2000',
            'pricing_mode' => 'automatic',
            'real_price_aed' => 100000,
            'customs_price_aed' => 80000,
            'adjustment_amount' => 0,
            'discount_amount' => '0',
            'currency' => 'usd',
            'invoice_type' => 'full',
            'total_amount' => '1',
        ])->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('toman', $invoice->currency);
        $this->assertEqualsWithDelta($expected->totals['finalTotalToman'], $invoice->total_amount, 0.01);
        $metadata = $invoice->pricingMetadata();
        $this->assertSame('automatic', $metadata['pricing_mode']);
        $this->assertSame('v1.2.0', $metadata['engine_version']);
        $this->assertEquals(50000.0, $metadata['pricing_snapshot']['freeRate']);
    }

    public function test_manual_invoice_requires_a_reason_and_sums_rows_on_the_server(): void
    {
        $admin = $this->admin();
        $payload = [
            'customer_name' => 'Manual Invoice',
            'customer_phone' => '09123333333',
            'pricing_mode' => 'manual',
            'category' => 'c2000',
            'discount_amount' => '0',
            'currency' => 'toman',
            'invoice_type' => 'single_item',
            'b_label' => ['خدمت اول', 'خدمت دوم'],
            'b_rate' => ['قطعی', 'قطعی'],
            'b_amount' => ['1,000', '2,500'],
            'total_amount' => '999999999',
        ];

        $this->actingAs($admin)->post(route('admin.invoices.store'), $payload)
            ->assertSessionHasErrors('adjustment_reason');

        $this->actingAs($admin)->post(route('admin.invoices.store'), $payload + [
            'adjustment_reason' => 'خدمت سفارشی خارج از محاسبه خودرو',
        ])->assertRedirect();

        $this->assertEqualsWithDelta(3500, Invoice::firstOrFail()->total_amount, 0.01);
    }

    private function legacyDubizzleTotal(float $priceAed, string $categoryId, array $settings): float
    {
        $category = $settings['categories'][$categoryId];
        $p = $settings['percentages'];
        $cif = $priceAed * $settings['customsRate'];
        $duty = ($category['tariffPercent'] / 100) * $cif;
        $base9 = $cif + $duty;
        $customsPercentageSubtotal = $duty
            + ($p['customsFixed'] / 100) * $cif
            + ($p['gasolineLevy'] / 100) * $cif
            + ($p['fobLevy'] / 100) * $cif
            + ($p['vat'] / 100) * $base9
            + ($p['advanceImportTax'] / 100) * $base9
            + ($p['redCrescent'] / 100) * $duty
            + ($p['customsSupervision'] / 100) * $duty
            + ($p['wasteLevy'] / 100) * $cif
            + ($p['standardFee'] / 100) * $cif;
        $freight = $settings['seaFreightAed'] * $settings['freeRate'];
        $permits = $settings['licenseFeeAed'] * $settings['freeRate'];
        $bracket = $priceAed > $settings['scrapThresholdAed'] ? 'above' : 'upto';
        $count = $settings['scrapCertificateCounts'][$category['scrapTier']][$bracket];
        $plate = $count * $settings['scrapCertificatePriceToman']
            + (($p['registration'] + $p['transferTax'] + $p['municipal'] + $p['individualPerson']) / 100) * $cif;
        $customs = $customsPercentageSubtotal + $freight + $permits + $settings['storageToman'];
        $vehicle = $priceAed * $settings['freeRate'];
        $service = ($p['serviceFee'] / 100) * ($customsPercentageSubtotal + $plate + $freight + $permits);

        return $vehicle + $customs + $plate + $service;
    }
}
