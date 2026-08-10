<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CarListing extends Model
{
    protected $fillable = [
        'source_url', 'source_site', 'status', 'slug',
        'title_en', 'title_fa',
        'make', 'model', 'trim_level', 'model_year',
        'price_aed', 'kilometers',
        'body_type', 'fuel_type', 'transmission_type', 'regional_specs', 'steering_side',
        'seller_type', 'warranty', 'exterior_color', 'interior_color', 'horsepower',
        'engine_capacity_cc', 'no_of_cylinders', 'doors', 'seating_capacity',
        'category_id', 'location_text', 'description_en', 'specs_json', 'posted_on_dubizzle',
        'meta_title', 'meta_description', 'created_by', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price_aed' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * دسته‌بندی خودرو برای محاسبه سود بازرگانی — دقیقاً هم‌ارز با آرایهٔ
     * categories در resources/views/public/calculator.blade.php
     */
    public const CATEGORIES = [
        'ev' => ['label' => 'هیبرید / برقی', 'coef' => 1.00],
        'c1500' => ['label' => 'زیر ۱۵۰۰ سی‌سی', 'coef' => 1.10],
        'c2000' => ['label' => '۱۵۰۱ تا ۲۰۰۰', 'coef' => 1.20],
        'c2500' => ['label' => '۲۰۰۱ تا ۲۵۰۰', 'coef' => 1.30],
        'c3000' => ['label' => '۲۵۰۱ تا ۳۰۰۰', 'coef' => 1.45],
        'c3001' => ['label' => 'بالای ۳۰۰۱', 'coef' => 1.65],
    ];

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
        return self::CATEGORIES[$this->category_id]['label'] ?? $this->category_id;
    }

    public function categoryCoef(): float
    {
        return self::CATEGORIES[$this->category_id]['coef'] ?? 1.20;
    }
}
