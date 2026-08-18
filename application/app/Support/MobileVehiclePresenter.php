<?php

namespace App\Support;

use App\Models\CarListing;
use App\Services\VehiclePricing\VehiclePricingService;

final class MobileVehiclePresenter
{
    public function __construct(private readonly VehiclePricingService $pricing) {}

    public function summary(CarListing $listing): array
    {
        $result = $this->calculate($listing);
        $cover = $listing->coverImage();

        return [
            'slug' => $listing->slug,
            'title' => $listing->title_fa ?: $listing->title_en,
            'make' => $listing->make,
            'model' => $listing->model,
            'year' => $listing->model_year,
            'price_aed' => (float) $listing->price_aed,
            'price_toman' => $result->totals['realPriceToman'],
            'cover_image' => $cover ? url($cover->url()) : null,
            'specs' => [
                'engine_capacity_cc' => $listing->engine_capacity_cc,
                'fuel_type' => $listing->fuel_type,
                'transmission' => $listing->transmission_type,
                'kilometers' => $listing->kilometers,
            ],
        ];
    }

    public function detail(CarListing $listing): array
    {
        $result = $this->calculate($listing);
        $public = $result->publicDisplaySummary();

        return [
            ...$this->summary($listing),
            'gallery' => $listing->images->map(fn ($image) => [
                'url' => url($image->url()),
                'is_cover' => $image->is_cover,
            ])->values()->all(),
            'description' => $listing->description_en,
            'location' => $listing->location_text,
            'pricing' => [
                'category' => $result->category,
                'public_summary' => [
                    ['key' => 'car_price', 'label' => 'قیمت خودرو', 'value_toman' => $public['car_price_toman']],
                    ['key' => 'clearance', 'label' => 'جمع هزینه‌های ترخیص', 'value_toman' => $public['clearance_total_toman']],
                    ['key' => 'plate', 'label' => 'هزینه‌های پلاک', 'value_toman' => $public['plate_total_toman']],
                ],
                'grand_total_toman' => $public['grand_total_toman'],
            ],
        ];
    }

    private function calculate(CarListing $listing)
    {
        return $this->pricing->calculate($this->pricing->inputFromArray([
            'real_price_aed' => (float) $listing->price_aed,
            'customs_price_aed' => $listing->customs_price_aed,
            'category' => $listing->category_id,
        ]));
    }
}
