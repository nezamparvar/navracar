<?php

namespace Tests\Feature;

use App\Services\CarListingMapper;
use Tests\TestCase;

/**
 * قفل‌کردن قاعدهٔ مرزبندی دسته‌های سی‌سی: بازهٔ «X تا Y» یعنی (X, Y] — یعنی
 * دقیقاً X سی‌سی در دستهٔ پایین‌تر قرار می‌گیرد، نه در «X تا Y». مثال: خودروی
 * ۲۵۰۰ سی‌سی باید در «۲۰۰۰ تا ۲۵۰۰» (c2500) باشد نه «۲۵۰۰ تا ۳۰۰۰» (c3000).
 */
class CarListingMapperCategoryTest extends TestCase
{
    public function test_cc_boundaries_place_the_exact_boundary_value_in_the_lower_bracket(): void
    {
        $mapper = new CarListingMapper;

        $cases = [
            1499 => 'c1500', 1500 => 'c1500', 1501 => 'c2000',
            1999 => 'c2000', 2000 => 'c2000', 2001 => 'c2500',
            2499 => 'c2500', 2500 => 'c2500', 2501 => 'c3000',
            2999 => 'c3000', 3000 => 'c3000', 3001 => 'c3001',
        ];

        foreach ($cases as $cc => $expected) {
            $this->assertSame($expected, $mapper->detectCategory((string) $cc, 'Petrol'), "cc={$cc}");
        }
    }

    public function test_engine_parser_distinguishes_exact_values_and_ranges(): void
    {
        $mapper = new CarListingMapper;

        $this->assertSame(['kind' => 'exact', 'min' => 1499, 'max' => 1499], $mapper->parseEngineCapacity('1499 cc'));
        $this->assertSame(['kind' => 'exact', 'min' => 2000, 'max' => 2000], $mapper->parseEngineCapacity('2.0 L'));
        $this->assertSame(['kind' => 'range', 'min' => 1500, 'max' => 1999], $mapper->parseEngineCapacity('1,500 - 1,999 cc'));
        $this->assertSame('c2000', $mapper->detectCategory('1500–1999 cc', 'Petrol'));
        $this->assertSame('c2000', $mapper->detectCategory('1500 to 1999', 'Petrol'));
        $this->assertSame('c2500', $mapper->detectCategory('2001 cc', 'Petrol'));
    }

    public function test_engine_parser_handles_messy_patterns_with_cc_prefix_or_plus(): void
    {
        $mapper = new CarListingMapper;

        // cc before number with or without space/plus
        $this->assertSame(['kind' => 'exact', 'min' => 4000, 'max' => 4000], $mapper->parseEngineCapacity('cc 4000'), 'cc 4000');
        $this->assertSame(['kind' => 'exact', 'min' => 4000, 'max' => 4000], $mapper->parseEngineCapacity('cc +4000'), 'cc +4000');
        $this->assertSame(['kind' => 'exact', 'min' => 4000, 'max' => 4000], $mapper->parseEngineCapacity('cc+ 4000'), 'cc+ 4000');

        // plus before number with cc after
        $this->assertSame(['kind' => 'exact', 'min' => 4000, 'max' => 4000], $mapper->parseEngineCapacity('+4000 cc'), '+4000 cc');
        $this->assertSame(['kind' => 'exact', 'min' => 4000, 'max' => 4000], $mapper->parseEngineCapacity('+ 4000cc'), '+ 4000cc');

        // Standard formats still work
        $this->assertSame(['kind' => 'exact', 'min' => 4000, 'max' => 4000], $mapper->parseEngineCapacity('4000cc'), '4000cc');
        $this->assertSame(['kind' => 'exact', 'min' => 4000, 'max' => 4000], $mapper->parseEngineCapacity('4000 cc'), '4000 cc');
    }

    public function test_detectCategory_with_all_engine_sizes(): void
    {
        $mapper = new CarListingMapper;

        // Test boundary values for all categories
        $testCases = [
            // c1500 boundary
            1499 => 'c1500',
            1500 => 'c1500',

            // c2000 boundary
            1501 => 'c2000',
            1999 => 'c2000',
            2000 => 'c2000',

            // c2500 boundary
            2001 => 'c2500',
            2499 => 'c2500',
            2500 => 'c2500',

            // c3000 boundary
            2501 => 'c3000',
            2999 => 'c3000',
            3000 => 'c3000',

            // c3001 (everything above 3000)
            3001 => 'c3001',
            4000 => 'c3001',
            5700 => 'c3001',
        ];

        foreach ($testCases as $cc => $expected) {
            $this->assertSame($expected, $mapper->detectCategory((string) $cc, 'Petrol'), "cc={$cc}");
        }
    }

    public function test_detectCategory_classifies_messy_strings_correctly(): void
    {
        $mapper = new CarListingMapper;

        // Messy strings that previously failed
        $this->assertSame('c3001', $mapper->detectCategory('cc +4000', 'Petrol'), 'cc +4000 → 4000cc → c3001');
        $this->assertSame('c3001', $mapper->detectCategory('+4000 cc', 'Petrol'), '+4000 cc → 4000cc → c3001');
        $this->assertSame('c3001', $mapper->detectCategory('cc 4000', 'Petrol'), 'cc 4000 → 4000cc → c3001');

        // Other engine sizes with messy patterns
        $this->assertSame('c2000', $mapper->detectCategory('cc 1800', 'Petrol'), 'cc 1800 → c2000');
        $this->assertSame('c2500', $mapper->detectCategory('+2500 cc', 'Petrol'), '+2500 cc → c2500');
        $this->assertSame('c3000', $mapper->detectCategory('cc +3000', 'Petrol'), 'cc +3000 → c3000');
    }

    public function test_fuel_type_overrides_engine_size(): void
    {
        $mapper = new CarListingMapper;

        $this->assertSame('ev', $mapper->detectCategory('any cc value', 'Electric'), 'Electric overrides cc');
        $this->assertSame('phev', $mapper->detectCategory('2000 cc', 'Plug-in Hybrid'), 'PHEV overrides cc');
        $this->assertSame('hybrid', $mapper->detectCategory('5000 cc', 'Hybrid'), 'Hybrid overrides cc');
    }

    public function test_range_classification_uses_upper_bound(): void
    {
        $mapper = new CarListingMapper;

        // Ranges should use upper bound for safer/higher classification
        $this->assertSame('c2500', $mapper->detectCategory('2000-2499 cc', 'Petrol'), '2000-2499 cc → c2500');
        $this->assertSame('c2500', $mapper->detectCategory('cc 2499 - 2000', 'Petrol'), 'cc 2499 - 2000 → c2500');
        $this->assertSame('c2000', $mapper->detectCategory('1500-2000 cc', 'Petrol'), '1500-2000 cc → c2000');
        $this->assertSame('c3001', $mapper->detectCategory('3000-3500 cc', 'Petrol'), '3000-3500 cc → c3001');

        // Verify parser extracts ranges correctly
        $this->assertSame(['kind' => 'range', 'min' => 2000, 'max' => 2499], $mapper->parseEngineCapacity('2000-2499 cc'));
        $this->assertSame(['kind' => 'range', 'min' => 2499, 'max' => 2000], $mapper->parseEngineCapacity('cc 2499 - 2000'));
    }

    public function test_liters_converted_to_cc(): void
    {
        $mapper = new CarListingMapper;

        $this->assertSame('c2500', $mapper->detectCategory('2.5L', 'Petrol'), '2.5L → 2500cc → c2500');
        $this->assertSame('c2000', $mapper->detectCategory('2.0 liter', 'Petrol'), '2.0 liter → 2000cc → c2000');
    }
}
