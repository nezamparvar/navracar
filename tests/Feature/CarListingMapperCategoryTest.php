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
}
