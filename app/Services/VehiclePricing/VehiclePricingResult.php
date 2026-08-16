<?php

namespace App\Services\VehiclePricing;

final readonly class VehiclePricingResult
{
    public function __construct(
        public array $input,
        public array $category,
        public array $customsRows,
        public array $plateRows,
        public array $totals,
        public array $settingsSnapshot,
    ) {}

    public function toArray(): array
    {
        return [
            'input' => $this->input,
            'category' => $this->category,
            'customsRows' => $this->customsRows,
            'plateRows' => $this->plateRows,
            ...$this->totals,
            'settingsSnapshot' => $this->settingsSnapshot,
        ];
    }

    public function breakdownRows(bool $formatted = false, bool $excludeServiceFee = false): array
    {
        $rows = [
            ...$this->customsRows,
            ...$this->plateRows,
            [
                'key' => 'real_vehicle_price',
                'label' => 'قیمت خودرو (اصل کالا)',
                'rate' => 'قیمت واقعی خودرو × نرخ ارز آزاد',
                'value' => $this->totals['realPriceToman'],
            ],
            [
                'key' => 'service_fee',
                'label' => 'کارمزد ترخیص‌کار و کارگزار (ناوراکار)',
                'rate' => $this->formatPercent($this->settingsSnapshot['percentages']['serviceFee']).' از پایه کارمزد',
                'value' => $this->totals['serviceFeeToman'],
            ],
        ];

        if ($excludeServiceFee) {
            $rows = array_filter($rows, fn (array $row) => ($row['key'] ?? '') !== 'service_fee');
        }

        if (! $formatted) {
            return $rows;
        }

        return array_map(fn (array $row) => [
            'key' => $row['key'],
            'label' => $row['label'],
            'rate' => $row['rate'],
            'amount' => number_format($row['value']).' تومان',
            'value' => $row['value'],
        ], $rows);
    }

    public function displayTotals(): array
    {
        return [
            'جمع کل بدون کارمزد' => number_format($this->totals['preServiceTotalToman']).' تومان',
            'کارمزد ترخیص‌کار و کارگزار (ناوراکار)' => number_format($this->totals['serviceFeeToman']).' تومان',
            'جمع کل نهایی' => number_format($this->totals['finalTotalToman']).' تومان',
        ];
    }

    public function publicDisplaySummary(): array
    {
        $carPrice = $this->totals['realPriceToman'];
        $clearanceTotal = $this->totals['customsSubtotalToman'] + $this->totals['serviceFeeToman'];
        $plateTotal = $this->totals['plateSubtotalToman'];
        $grandTotal = $this->totals['finalTotalToman'];

        $freeRate = $this->settingsSnapshot['freeRate'] ?? 1;
        $usdToAedRate = $this->settingsSnapshot['usdToAedRate'] ?? 1;

        return [
            'car_price_toman' => $carPrice,
            'clearance_total_toman' => $clearanceTotal,
            'plate_total_toman' => $plateTotal,
            'grand_total_toman' => $grandTotal,
            'grand_total_aed' => $grandTotal / $freeRate,
            'grand_total_usd' => ($grandTotal / $freeRate) / $usdToAedRate,
            'formatted' => [
                'car_price' => number_format($carPrice).' تومان',
                'clearance_total' => number_format($clearanceTotal).' تومان',
                'plate_total' => number_format($plateTotal).' تومان',
                'grand_total_toman' => number_format($grandTotal).' تومان',
                'grand_total_aed' => number_format($grandTotal / $freeRate, 2).' درهم',
                'grand_total_usd' => number_format(($grandTotal / $freeRate) / $usdToAedRate, 2).' دلار',
            ],
        ];
    }

    public function publicCustomsRows(): array
    {
        $clearanceTotal = $this->totals['customsSubtotalToman'] + $this->totals['serviceFeeToman'];
        return [
            [
                'key' => 'clearance_total',
                'label' => 'جمع هزینه ترخیص',
                'value' => $clearanceTotal,
                'formatted' => number_format($clearanceTotal).' تومان',
            ],
        ];
    }

    private function formatPercent(float $percent): string
    {
        return rtrim(rtrim(number_format($percent, 3, '.', ''), '0'), '.').'٪';
    }
}
