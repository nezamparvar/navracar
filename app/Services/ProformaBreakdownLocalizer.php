<?php

namespace App\Services;

use App\Services\VehiclePricing\VehiclePricingCatalog;

final class ProformaBreakdownLocalizer
{
    private const EN = [
        'customs_duty' => 'Customs duty', 'fixed_customs_fee' => 'Fixed customs fee',
        'gasoline_levy' => 'Gasoline levy', 'fob_levy' => 'FOB levy', 'vat' => 'VAT',
        'advance_import_tax' => 'Advance import tax', 'red_crescent' => 'Red Crescent',
        'customs_supervision' => 'Customs supervision', 'waste_levy' => 'Waste levy',
        'standard_fee' => 'Standard fee', 'sea_freight' => 'Sea freight',
        'license_fee' => 'License fee', 'storage' => 'Storage', 'scrappage' => 'Scrappage',
        'registration' => 'Registration', 'transfer_tax' => 'Transfer tax',
        'municipal_fee' => 'Municipal fee', 'individual_person_fee' => 'Individual person fee',
        'vehicle_price' => 'Vehicle price', 'service_fee' => 'Service fee',
        'tariff_duty' => 'Customs duty', 'customs_fixed' => 'Fixed customs fee',
        'municipal' => 'Municipal fee', 'individual_person' => 'Individual person fee',
        'real_vehicle_price' => 'Vehicle price',
        'approved_adjustment' => 'Approved adjustment',
        'other_costs' => 'Other costs',
    ];

    private const EN_CATEGORIES = [
        'ev' => 'Battery electric vehicle',
        'phev' => 'Plug-in hybrid',
        'hybrid' => 'Hybrid (non plug-in)',
        'c1500' => 'Petrol, up to 1,500 cc',
        'c2000' => 'Petrol, 1,500–2,000 cc',
        'c2500' => 'Petrol, 2,000–2,500 cc',
        'c3000' => 'Petrol, 2,500–3,000 cc',
        'c3001' => 'Petrol, above 3,000 cc',
    ];

    private const EN_CURRENCIES = [
        'toman' => 'Toman',
        'aed' => 'AED',
        'usd' => 'USD',
    ];

    public static function label(array $row, string $locale = 'fa'): string
    {
        $key = $row['key'] ?? null;
        return $locale === 'en' && $key && isset(self::EN[$key]) ? self::EN[$key] : (string) ($row['label'] ?? '');
    }

    public static function rate(array $row, string $locale = 'fa'): string
    {
        $original = (string) ($row['rate'] ?? '');
        if ($locale !== 'en') {
            return $original;
        }

        $key = (string) ($row['key'] ?? '');
        preg_match('/([0-9]+(?:\.[0-9]+)?)/', $original, $matches);
        $number = $matches[1] ?? null;

        if (in_array($key, ['tariff_duty', 'customs_fixed', 'gasoline_levy', 'fob_levy', 'waste_levy', 'standard_fee', 'registration', 'transfer_tax', 'municipal', 'individual_person'], true)) {
            return $number !== null ? $number.'% of customs value' : 'Based on customs value';
        }
        if (in_array($key, ['vat', 'advance_import_tax'], true)) {
            return $number !== null ? $number.'% of customs value + tariff duty' : 'Based on customs value + tariff duty';
        }
        if (in_array($key, ['red_crescent', 'customs_supervision'], true)) {
            return $number !== null ? $number.'% of tariff duty' : 'Based on tariff duty';
        }
        if (in_array($key, ['sea_freight', 'license_fee', 'other_costs', 'real_vehicle_price', 'vehicle_price'], true)) {
            return 'AED × free-market exchange rate';
        }
        if ($key === 'storage') {
            return 'Configured fixed amount';
        }
        if ($key === 'scrappage') {
            return $number !== null ? $number.' certificate(s) × configured certificate price' : 'Configured certificate price';
        }

        return $original;
    }

    public static function category(?string $categoryId, string $fallback = ''): string
    {
        if (isset(self::EN_CATEGORIES[$categoryId ?? ''])) {
            return self::EN_CATEGORIES[$categoryId];
        }

        foreach (VehiclePricingCatalog::CATEGORIES as $id => $category) {
            if (($category['label'] ?? null) === $categoryId || ($category['label'] ?? null) === $fallback) {
                return self::EN_CATEGORIES[$id];
            }
        }

        return preg_match('/[\x{0600}-\x{06FF}]/u', $fallback) ? 'Not specified' : $fallback;
    }

    public static function currency(string $currency): string
    {
        return self::EN_CURRENCIES[$currency] ?? strtoupper($currency);
    }
}

