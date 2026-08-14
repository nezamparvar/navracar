<?php

namespace App\Services\VehiclePricing;

final class VehiclePricingService
{
    public function calculate(
        VehiclePricingInput $input,
        ?VehiclePricingSettings $settings = null,
    ): VehiclePricingResult {
        $settings ??= VehiclePricingSettings::current();

        $categoryId = VehiclePricingCatalog::normalizeCategory($input->categoryId);
        $category = $settings->categories[$categoryId];
        $tariffPercent = (float) $category['tariffPercent'];
        $tariffCoefficient = $tariffPercent / 100;
        $scrapTier = $category['scrapTier'];
        $percentages = $settings->percentages;

        $cif = $input->customsPriceAed * $settings->customsRate;
        $realPriceToman = $input->realPriceAed * $settings->freeRate;
        $dutyProfit = $tariffCoefficient * $cif;
        $base9 = $dutyProfit + $cif;

        $customsRows = [
            $this->row('tariff_duty', 'عوارض گمرکی بر اساس تعرفه', $tariffPercent, 'از ارزش گمرکی', $dutyProfit),
            $this->row('customs_fixed', 'حقوق گمرکی ثابت', $percentages['customsFixed'], 'از ارزش گمرکی', $this->percent($percentages['customsFixed'], $cif)),
            $this->row('gasoline_levy', 'عوارض بنزین‌سوز', $percentages['gasolineLevy'], 'از ارزش گمرکی', $this->percent($percentages['gasolineLevy'], $cif)),
            $this->row('fob_levy', 'عوارض فوب', $percentages['fobLevy'], 'از ارزش گمرکی', $this->percent($percentages['fobLevy'], $cif)),
            $this->row('vat', 'مالیات ارزش افزوده (VAT)', $percentages['vat'], 'از ارزش گمرکی + عوارض تعرفه', $this->percent($percentages['vat'], $base9)),
            $this->row('advance_import_tax', 'مالیات علی‌الحساب واردات', $percentages['advanceImportTax'], 'از ارزش گمرکی + عوارض تعرفه', $this->percent($percentages['advanceImportTax'], $base9)),
            $this->row('red_crescent', 'عوارض هلال احمر', $percentages['redCrescent'], 'از عوارض تعرفه', $this->percent($percentages['redCrescent'], $dutyProfit)),
            $this->row('customs_supervision', 'حق نظارت کارشناسان گمرک', $percentages['customsSupervision'], 'از عوارض تعرفه', $this->percent($percentages['customsSupervision'], $dutyProfit)),
            $this->row('waste_levy', 'عوارض پسماند کالا', $percentages['wasteLevy'], 'از ارزش گمرکی', $this->percent($percentages['wasteLevy'], $cif)),
            $this->row('standard_fee', 'هزینه استاندارد', $percentages['standardFee'], 'از ارزش گمرکی', $this->percent($percentages['standardFee'], $cif)),
        ];

        $customsPercentageSubtotal = array_sum(array_column($customsRows, 'value'));
        $freightToman = $settings->seaFreightAed * $settings->freeRate;
        $permitsToman = $settings->licenseFeeAed * $settings->freeRate;

        $customsRows[] = $this->fixedRow('sea_freight', 'حمل دریایی', 'درهم × نرخ ارز آزاد', $freightToman);
        $customsRows[] = $this->fixedRow('license_fee', 'هزینه صدور مجوز واردات', 'درهم × نرخ ارز آزاد', $permitsToman);
        $customsRows[] = $this->fixedRow('storage', 'انبارداری، دموراژ و THC', 'مبلغ ثابت تنظیمات', $settings->storageToman);
        $customsSubtotal = $customsPercentageSubtotal + $freightToman + $permitsToman + $settings->storageToman;

        $scrapBracket = $input->customsPriceAed > $settings->scrapThresholdAed ? 'above' : 'upto';
        $scrapCertificateCount = $settings->scrapCertificateCounts[$scrapTier][$scrapBracket];
        $scrapCost = $scrapCertificateCount * $settings->scrapCertificatePriceToman;

        $plateRows = [
            [
                'key' => 'scrappage',
                'label' => 'گواهی اسقاط خودرو فرسوده',
                'rate' => $scrapCertificateCount.' گواهی × نرخ هر گواهی',
                'value' => $scrapCost,
            ],
            $this->row('registration', 'عوارض شماره‌گذاری راهور', $percentages['registration'], 'از ارزش گمرکی', $this->percent($percentages['registration'], $cif)),
            $this->row('transfer_tax', 'مالیات نقل و انتقال', $percentages['transferTax'], 'از ارزش گمرکی', $this->percent($percentages['transferTax'], $cif)),
            $this->row('municipal', 'عوارض سالانه شهرداری', $percentages['municipal'], 'از ارزش گمرکی', $this->percent($percentages['municipal'], $cif)),
            $this->row('individual_person', 'عوارض شخص حقیقی', $percentages['individualPerson'], 'از ارزش گمرکی', $this->percent($percentages['individualPerson'], $cif)),
        ];

        $plateSubtotal = array_sum(array_column($plateRows, 'value'));
        $preServiceTotal = $customsSubtotal + $plateSubtotal + $realPriceToman;

        // Preserve the established Dubizzle base exactly: storage and vehicle price are excluded.
        $serviceFeeBase = $customsPercentageSubtotal + $plateSubtotal + $freightToman + $permitsToman;
        $serviceFee = $this->percent($percentages['serviceFee'], $serviceFeeBase);
        $finalTotal = $preServiceTotal + $serviceFee;

        return new VehiclePricingResult(
            input: [
                'realPriceAed' => $input->realPriceAed,
                'customsPriceAed' => $input->customsPriceAed,
                'categoryId' => $categoryId,
            ],
            category: [
                'id' => $categoryId,
                'label' => $category['label'],
                'tariffPercent' => $tariffPercent,
                'tariffCoefficient' => $tariffCoefficient,
                'scrapTier' => $scrapTier,
                'scrapBracket' => $scrapBracket,
            ],
            customsRows: $customsRows,
            plateRows: $plateRows,
            totals: [
                'realPriceToman' => $realPriceToman,
                'cifToman' => $cif,
                'tariffDutyToman' => $dutyProfit,
                'vatAndAdvanceTaxBaseToman' => $base9,
                'customsPercentageSubtotalToman' => $customsPercentageSubtotal,
                'freightToman' => $freightToman,
                'licenseFeeToman' => $permitsToman,
                'storageToman' => $settings->storageToman,
                'customsSubtotalToman' => $customsSubtotal,
                'scrapCertificateCount' => $scrapCertificateCount,
                'scrapCostToman' => $scrapCost,
                'plateSubtotalToman' => $plateSubtotal,
                'preServiceTotalToman' => $preServiceTotal,
                'serviceFeeBaseToman' => $serviceFeeBase,
                'serviceFeeToman' => $serviceFee,
                'finalTotalToman' => $finalTotal,
            ],
            settingsSnapshot: $settings->toArray(),
        );
    }

    private function percent(float $percent, float $base): float
    {
        return ($percent / 100) * $base;
    }

    private function row(string $key, string $label, float $percent, string $baseLabel, float $value): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'rate' => $this->formatPercent($percent).' '.$baseLabel,
            'value' => $value,
        ];
    }

    private function fixedRow(string $key, string $label, string $rate, float $value): array
    {
        return compact('key', 'label', 'rate', 'value');
    }

    private function formatPercent(float $percent): string
    {
        return rtrim(rtrim(number_format($percent, 3, '.', ''), '0'), '.').'٪';
    }
}
