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
        'category_id', 'delivery_days', 'location_text', 'description_en', 'specs_json', 'posted_on_dubizzle',
        'meta_title', 'meta_description', 'created_by', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price_aed' => 'decimal:2',
            'delivery_days' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * دسته‌بندی خودرو برای محاسبه عوارض گمرکی بر اساس تعرفه — منطبق با تعرفهٔ
     * گمرک ایران. برچسب و رتبهٔ انرژی پیش‌فرض (برای گواهی اسقاط) اینجاست؛
     * درصد واقعی هر دسته از تنظیمات پنل مدیریت خوانده می‌شود (قابل تغییر توسط ادمین).
     */
    public const CATEGORIES = [
        'ev' => ['label' => 'برقی (تمام برقی)', 'default_coef' => 1.00, 'default_tier' => 'ab'],
        'phev' => ['label' => 'پلاگین هیبرید', 'default_coef' => 1.05, 'default_tier' => 'ab'],
        'hybrid' => ['label' => 'هیبرید (غیرپلاگین)', 'default_coef' => 1.10, 'default_tier' => 'cd'],
        'c1500' => ['label' => 'بنزینی زیر ۱۵۰۰ سی‌سی', 'default_coef' => 1.10, 'default_tier' => 'cd'],
        'c2000' => ['label' => 'بنزینی ۱۵۰۰ تا ۲۰۰۰ سی‌سی', 'default_coef' => 1.20, 'default_tier' => 'cd'],
        'c2500' => ['label' => 'بنزینی ۲۰۰۰ تا ۲۵۰۰ سی‌سی', 'default_coef' => 1.30, 'default_tier' => 'efg'],
        'c3000' => ['label' => 'بنزینی ۲۵۰۰ تا ۳۰۰۰ سی‌سی', 'default_coef' => 1.45, 'default_tier' => 'efg'],
        'c3001' => ['label' => 'بنزینی بالای ۳۰۰۰ سی‌سی', 'default_coef' => 1.65, 'default_tier' => 'efg'],
    ];

    /**
     * تعداد گواهی اسقاط لازم برای خودروی سواری وارداتی صفر کیلومتر، طبق
     * جدول پیوست تصویب‌نامهٔ هیئت وزیران (۱۴۰۵/۵/۱۲) — رتبهٔ انرژی × بازهٔ
     * قیمت گمرکی (تا آستانه / بالای آستانه).
     */
    public const SCRAP_CERT_COUNTS = [
        'ab' => ['upto' => 1, 'above' => 1],
        'cd' => ['upto' => 5, 'above' => 7],
        'efg' => ['upto' => 6, 'above' => 9],
    ];

    /**
     * بازه‌های قیمتی ثابت (تومان، بر پایهٔ نرخ روز) برای صفحات دسته‌بندی سئو —
     * فقط برای مرور/فیلتر است، نه یک مقدار مالی حساس؛ بنابراین هاردکد است نه
     * در تنظیمات پنل.
     */
    public const PRICE_BRACKETS = [
        'under-5b' => ['label' => 'زیر ۵ میلیارد تومان', 'min' => 0, 'max' => 5_000_000_000],
        '5b-10b' => ['label' => '۵ تا ۱۰ میلیارد تومان', 'min' => 5_000_000_000, 'max' => 10_000_000_000],
        '10b-20b' => ['label' => '۱۰ تا ۲۰ میلیارد تومان', 'min' => 10_000_000_000, 'max' => 20_000_000_000],
        '20b-40b' => ['label' => '۲۰ تا ۴۰ میلیارد تومان', 'min' => 20_000_000_000, 'max' => 40_000_000_000],
        'above-40b' => ['label' => 'بالای ۴۰ میلیارد تومان', 'min' => 40_000_000_000, 'max' => null],
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

    /**
     * فیلتر بر اساس بازهٔ قیمت تومانی (PRICE_BRACKETS) — چون قیمت واقعی به
     * درهم ذخیره می‌شود، بازه با نرخ روز به درهم تبدیل و در دیتابیس فیلتر
     * می‌شود (نه در PHP، برای صفحه‌بندی درست).
     */
    public function scopePriceBracket(Builder $query, string $bracketId, float $freeRate): Builder
    {
        $bracket = self::PRICE_BRACKETS[$bracketId] ?? null;
        if (! $bracket || $freeRate <= 0) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('price_aed', '>=', $bracket['min'] / $freeRate);
        if ($bracket['max'] !== null) {
            $query->where('price_aed', '<', $bracket['max'] / $freeRate);
        }

        return $query;
    }

    public function coverImage(): ?CarListingImage
    {
        return $this->images->firstWhere('is_cover', true) ?? $this->images->first();
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category_id]['label'] ?? $this->category_id;
    }

    public static function categoryCoef(string $categoryId): float
    {
        $default = self::CATEGORIES[$categoryId]['default_coef'] ?? 1.20;

        return (float) Setting::get(Setting::TARIFF_PREFIX.$categoryId, (string) ($default * 100)) / 100;
    }

    public static function categoryTier(string $categoryId): string
    {
        $default = self::CATEGORIES[$categoryId]['default_tier'] ?? 'cd';

        return Setting::get(Setting::SCRAP_TIER_PREFIX.$categoryId, $default);
    }

    public function categoryCoefLive(): float
    {
        return self::categoryCoef($this->category_id);
    }

    /**
     * آرایهٔ دسته‌ها به‌همراه درصد تعرفهٔ زندهٔ خوانده‌شده از تنظیمات —
     * برای نمایش در dropdown پنل مدیریت و پاس‌دادن به محاسبه‌گر Alpine.
     */
    public static function categoriesWithLiveRates(): array
    {
        $out = [];
        foreach (self::CATEGORIES as $id => $cat) {
            $out[$id] = [
                'label' => $cat['label'],
                'coef' => self::categoryCoef($id),
                'tier' => self::categoryTier($id),
            ];
        }

        return $out;
    }
}
