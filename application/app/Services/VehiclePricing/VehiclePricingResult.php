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

    public function breakdownRows(bool $formatted = false): array
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

    private function formatPercent(float $percent): string
    {
        return rtrim(rtrim(number_format($percent, 3, '.', ''), '0'), '.').'٪';
    }
}
