<?php

namespace App\Services\VehiclePricing;

final class VehiclePricingCatalog
{
    public const CATEGORIES = [
        'ev' => ['label' => 'برقی (تمام برقی)', 'default_tariff_percent' => 100.0, 'default_scrap_tier' => 'ab'],
        'phev' => ['label' => 'پلاگین هیبرید', 'default_tariff_percent' => 105.0, 'default_scrap_tier' => 'ab'],
        'hybrid' => ['label' => 'هیبرید (غیرپلاگین)', 'default_tariff_percent' => 110.0, 'default_scrap_tier' => 'cd'],
        'c1500' => ['label' => 'بنزینی تا ۱۵۰۰ سی‌سی', 'default_tariff_percent' => 110.0, 'default_scrap_tier' => 'cd'],
        'c2000' => ['label' => 'بنزینی ۱۵۰۰ تا ۲۰۰۰ سی‌سی', 'default_tariff_percent' => 120.0, 'default_scrap_tier' => 'cd'],
        'c2500' => ['label' => 'بنزینی ۲۰۰۰ تا ۲۵۰۰ سی‌سی', 'default_tariff_percent' => 130.0, 'default_scrap_tier' => 'efg'],
        'c3000' => ['label' => 'بنزینی ۲۵۰۰ تا ۳۰۰۰ سی‌سی', 'default_tariff_percent' => 145.0, 'default_scrap_tier' => 'efg'],
        'c3001' => ['label' => 'بنزینی بالای ۳۰۰۰ سی‌سی', 'default_tariff_percent' => 165.0, 'default_scrap_tier' => 'efg'],
    ];

    public const SCRAP_CERTIFICATE_COUNT_DEFAULTS = [
        'ab' => ['upto' => 1, 'above' => 1],
        'cd' => ['upto' => 5, 'above' => 7],
        'efg' => ['upto' => 6, 'above' => 9],
    ];

    public const FALLBACK_CATEGORY = 'c2000';

    public static function categoryIds(): array
    {
        return array_keys(self::CATEGORIES);
    }

    public static function category(string $categoryId): array
    {
        return self::CATEGORIES[$categoryId] ?? self::CATEGORIES[self::FALLBACK_CATEGORY];
    }

    public static function normalizeCategory(string $categoryId): string
    {
        return array_key_exists($categoryId, self::CATEGORIES) ? $categoryId : self::FALLBACK_CATEGORY;
    }
}
