<?php

namespace App\Services;

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
    ];

    public static function label(array $row, string $locale = 'fa'): string
    {
        $key = $row['key'] ?? null;
        return $locale === 'en' && $key && isset(self::EN[$key]) ? self::EN[$key] : (string) ($row['label'] ?? '');
    }
}

