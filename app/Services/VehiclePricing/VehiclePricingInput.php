<?php

namespace App\Services\VehiclePricing;

final readonly class VehiclePricingInput
{
    public function __construct(
        public float $realPriceAed,
        public float $customsPriceAed,
        public string $categoryId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            realPriceAed: max(0, (float) ($data['real_price_aed'] ?? 0)),
            customsPriceAed: max(0, (float) ($data['customs_price_aed'] ?? 0)),
            categoryId: (string) ($data['category'] ?? VehiclePricingCatalog::FALLBACK_CATEGORY),
        );
    }

    public function toArray(): array
    {
        return [
            'realPriceAed' => $this->realPriceAed,
            'customsPriceAed' => $this->customsPriceAed,
            'categoryId' => $this->categoryId,
        ];
    }
}
