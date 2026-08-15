<?php

namespace App\Services\VehiclePricing;

use App\Models\Setting;

final readonly class VehiclePricingSettings
{
    public function __construct(
        public float $freeRate,
        public float $customsRate,
        public float $usdToAedRate,
        public float $seaFreightAed,
        public float $licenseFeeAed,
        public float $storageToman,
        public float $scrapCertificatePriceToman,
        public float $scrapThresholdAed,
        public float $customsValueDiscountPercent,
        public array $percentages,
        public array $categories,
        public array $scrapCertificateCounts,
    ) {}

    public static function current(): self
    {
        $categories = [];
        foreach (VehiclePricingCatalog::CATEGORIES as $id => $category) {
            $tier = Setting::get(Setting::SCRAP_TIER_PREFIX.$id, $category['default_scrap_tier']);
            $categories[$id] = [
                'label' => $category['label'],
                'tariffPercent' => (float) Setting::get(
                    Setting::TARIFF_PREFIX.$id,
                    (string) $category['default_tariff_percent'],
                ),
                'scrapTier' => in_array($tier, ['ab', 'cd', 'efg'], true)
                    ? $tier
                    : $category['default_scrap_tier'],
            ];
        }

        $scrapCounts = [];
        foreach (VehiclePricingCatalog::SCRAP_CERTIFICATE_COUNT_DEFAULTS as $tier => $brackets) {
            foreach ($brackets as $bracket => $default) {
                $scrapCounts[$tier][$bracket] = max(0, (int) Setting::get(
                    Setting::scrapCertificateCountKey($tier, $bracket),
                    (string) $default,
                ));
            }
        }

        return new self(
            freeRate: (float) Setting::get(Setting::FREE_RATE),
            customsRate: (float) Setting::get(Setting::CUSTOMS_RATE),
            usdToAedRate: (float) Setting::get(Setting::USD_TO_AED_RATE),
            seaFreightAed: (float) Setting::get(Setting::SEA_FREIGHT_AED),
            licenseFeeAed: (float) Setting::get(Setting::LICENSE_FEE_AED),
            storageToman: (float) Setting::get(Setting::STORAGE_TOMAN),
            scrapCertificatePriceToman: (float) Setting::get(Setting::SCRAP_CERT_PRICE_TOMAN),
            scrapThresholdAed: (float) Setting::get(Setting::SCRAP_THRESHOLD_AED),
            customsValueDiscountPercent: (float) Setting::get(Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT),
            percentages: [
                'customsFixed' => (float) Setting::get(Setting::CUSTOMS_FIXED_PERCENT),
                'gasolineLevy' => (float) Setting::get(Setting::GASOLINE_LEVY_PERCENT),
                'fobLevy' => (float) Setting::get(Setting::FOB_LEVY_PERCENT),
                'vat' => (float) Setting::get(Setting::VAT_PERCENT),
                'advanceImportTax' => (float) Setting::get(Setting::ADVANCE_IMPORT_TAX_PERCENT),
                'redCrescent' => (float) Setting::get(Setting::RED_CRESCENT_PERCENT),
                'customsSupervision' => (float) Setting::get(Setting::CUSTOMS_SUPERVISION_PERCENT),
                'wasteLevy' => (float) Setting::get(Setting::WASTE_LEVY_PERCENT),
                'standardFee' => (float) Setting::get(Setting::STANDARD_FEE_PERCENT),
                'registration' => (float) Setting::get(Setting::REGISTRATION_PERCENT),
                'transferTax' => (float) Setting::get(Setting::TRANSFER_TAX_PERCENT),
                'municipal' => (float) Setting::get(Setting::MUNICIPAL_PERCENT),
                'individualPerson' => (float) Setting::get(Setting::INDIVIDUAL_PERSON_PERCENT),
                'serviceFee' => (float) Setting::get(Setting::SERVICE_FEE_PERCENT),
            ],
            categories: $categories,
            scrapCertificateCounts: $scrapCounts,
        );
    }

    public function withExchangeRates(float $freeRate, float $customsRate): self
    {
        return new self(
            freeRate: $freeRate,
            customsRate: $customsRate,
            usdToAedRate: $this->usdToAedRate,
            seaFreightAed: $this->seaFreightAed,
            licenseFeeAed: $this->licenseFeeAed,
            storageToman: $this->storageToman,
            scrapCertificatePriceToman: $this->scrapCertificatePriceToman,
            scrapThresholdAed: $this->scrapThresholdAed,
            customsValueDiscountPercent: $this->customsValueDiscountPercent,
            percentages: $this->percentages,
            categories: $this->categories,
            scrapCertificateCounts: $this->scrapCertificateCounts,
        );
    }

    public function toArray(): array
    {
        return [
            'freeRate' => $this->freeRate,
            'customsRate' => $this->customsRate,
            'usdToAedRate' => $this->usdToAedRate,
            'seaFreightAed' => $this->seaFreightAed,
            'licenseFeeAed' => $this->licenseFeeAed,
            'storageToman' => $this->storageToman,
            'scrapCertificatePriceToman' => $this->scrapCertificatePriceToman,
            'scrapThresholdAed' => $this->scrapThresholdAed,
            'customsValueDiscountPercent' => $this->customsValueDiscountPercent,
            'percentages' => $this->percentages,
            'categories' => $this->categories,
            'scrapCertificateCounts' => $this->scrapCertificateCounts,
        ];
    }
}

