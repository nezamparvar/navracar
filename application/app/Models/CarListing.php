<?php

namespace App\Models;

use App\Services\VehiclePricing\VehiclePricingCatalog;
use App\Services\VehiclePricing\VehiclePricingInput;
use App\Services\VehiclePricing\VehiclePricingService;
use App\Services\VehiclePricing\VehiclePricingSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CarListing extends Model
{
    protected $fillable = [
        'source_url', 'source_site', 'status', 'slug',
        'title_en', 'title_fa',
        'make', 'model', 'trim_level', 'model_year',
        'price_aed', 'customs_price_aed', 'kilometers',
        'body_type', 'fuel_type', 'transmission_type', 'regional_specs', 'steering_side',
        'seller_type', 'warranty', 'exterior_color', 'interior_color', 'horsepower',
        'engine_capacity_cc', 'no_of_cylinders', 'doors', 'seating_capacity',
        'category_id', 'delivery_days', 'location_text', 'description_en', 'specs_json', 'posted_on_dubizzle',
        'meta_title', 'meta_description', 'created_by', 'published_at',
    ];

    public const PRICE_BRACKETS = [
        'under-5b' => ['label' => 'زیر ۵ میلیارد تومان', 'min' => 0, 'max' => 5_000_000_000],
        '5b-10b' => ['label' => '۵ تا ۱۰ میلیارد تومان', 'min' => 5_000_000_000, 'max' => 10_000_000_000],
        '10b-20b' => ['label' => '۱۰ تا ۲۰ میلیارد تومان', 'min' => 10_000_000_000, 'max' => 20_000_000_000],
        '20b-40b' => ['label' => '۲۰ تا ۴۰ میلیارد تومان', 'min' => 20_000_000_000, 'max' => 40_000_000_000],
        'above-40b' => ['label' => 'بالای ۴۰ میلیارد تومان', 'min' => 40_000_000_000, 'max' => null],
    ];

    protected function casts(): array
    {
        return [
            'price_aed' => 'decimal:2',
            'customs_price_aed' => 'decimal:2',
            'delivery_days' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function images()
    {
        return $this->hasMany(CarListingImage::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function coverImage(): ?CarListingImage
    {
        return $this->images->firstWhere('is_cover', true) ?? $this->images->first();
    }

    public function categoryLabel(): string
    {
        return VehiclePricingCatalog::category($this->category_id)['label'];
    }

    public function priceBracketId(float $freeRate, ?float $customsRate = null): ?string
    {
        if ($freeRate <= 0) {
            return null;
        }

        $priceToman = $customsRate !== null && $customsRate > 0
            ? $this->estimatedLandedCostToman($freeRate, $customsRate)
            : (float) $this->price_aed * $freeRate;

        foreach (self::PRICE_BRACKETS as $id => $bracket) {
            if ($priceToman >= $bracket['min'] && ($bracket['max'] === null || $priceToman < $bracket['max'])) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Backward-compatible model API. The formula itself lives only in VehiclePricingService.
     */
    public function estimatedLandedCostToman(float $freeRate, float $customsRate): float
    {
        $settings = VehiclePricingSettings::current()->withExchangeRates($freeRate, $customsRate);
        $result = app(VehiclePricingService::class)->calculate(new VehiclePricingInput(
            realPriceAed: (float) $this->price_aed,
            customsPriceAed: (float) $this->price_aed,
            categoryId: $this->category_id,
        ), $settings);

        return $result->totals['finalTotalToman'];
    }

    public static function categoryCoef(string $categoryId): float
    {
        $categoryId = VehiclePricingCatalog::normalizeCategory($categoryId);

        return VehiclePricingSettings::current()->categories[$categoryId]['tariffPercent'] / 100;
    }

    public static function categoryTier(string $categoryId): string
    {
        $categoryId = VehiclePricingCatalog::normalizeCategory($categoryId);

        return VehiclePricingSettings::current()->categories[$categoryId]['scrapTier'];
    }

    public function categoryCoefLive(): float
    {
        return self::categoryCoef($this->category_id);
    }

    public static function categoriesWithLiveRates(): array
    {
        $categories = [];
        foreach (VehiclePricingSettings::current()->categories as $id => $category) {
            $categories[$id] = [
                'label' => $category['label'],
                'coef' => $category['tariffPercent'] / 100,
                'tariffPercent' => $category['tariffPercent'],
                'tier' => $category['scrapTier'],
            ];
        }

        return $categories;
    }
}

