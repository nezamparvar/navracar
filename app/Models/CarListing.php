<?php

namespace App\Models;

use App\Services\VehiclePricing\VehiclePricingCatalog;
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
        return $this->pricingTotals($freeRate, $customsRate)['finalTotalToman'];
    }

    /**
     * Full totals array from the central pricing service. Uses inputFromArray() (not a direct
     * VehiclePricingInput) so a missing customs_price_aed goes through the service's real
     * suggestCustomsPrice() default discount, same as every other pricing entry point — a
     * listing without an explicit customs price must not be priced as if customsPriceAed
     * equalled the full realPriceAed.
     */
    public function pricingTotals(float $freeRate, float $customsRate): array
    {
        return $this->pricingResult($freeRate, $customsRate)->totals;
    }

    /**
     * The 3-category public display summary (vehicle price / customs clearance total, with the
     * service fee folded in, never shown as its own line / plate costs) — see
     * VehiclePricingResult::publicDisplaySummary() and PublicCostDisplayTest for the contract
     * this must follow. Public views must use this, not the raw totals array, for the 3-category
     * breakdown so the displayed categories still sum to the real grand total.
     */
    public function publicPricingSummary(float $freeRate, float $customsRate): array
    {
        return $this->pricingResult($freeRate, $customsRate)->publicDisplaySummary();
    }

    private function pricingResult(float $freeRate, float $customsRate): \App\Services\VehiclePricing\VehiclePricingResult
    {
        $settings = VehiclePricingSettings::current()->withExchangeRates($freeRate, $customsRate);
        $service = app(VehiclePricingService::class);

        return $service->calculate($service->inputFromArray([
            'real_price_aed' => (float) $this->price_aed,
            'customs_price_aed' => $this->customs_price_aed !== null ? (float) $this->customs_price_aed : null,
            'category' => $this->category_id,
        ], $settings), $settings);
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

