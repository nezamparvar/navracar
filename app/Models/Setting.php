<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const FREE_RATE = 'free_rate';

    public const CUSTOMS_RATE = 'customs_rate';

    public const LICENSE_FEE_AED = 'license_fee_aed';

    public const SEA_FREIGHT_AED = 'sea_freight_aed';

    public const STORAGE_TOMAN = 'storage_toman';

    public const SCRAP_CERT_PRICE_TOMAN = 'scrap_cert_price_toman';

    public const SCRAP_THRESHOLD_AED = 'scrap_threshold_aed';

    public const WHATSAPP_UAE = 'whatsapp_uae_number';

    public const WHATSAPP_IRAN = 'whatsapp_iran_number';

    public const DEFAULT_DELIVERY_DAYS = 'default_delivery_days';

    public const DEFAULTS = [
        self::FREE_RATE => '51000',
        self::CUSTOMS_RATE => '35688',
        self::LICENSE_FEE_AED => '60000',
        self::SEA_FREIGHT_AED => '1500',
        self::STORAGE_TOMAN => '0',
        self::SCRAP_CERT_PRICE_TOMAN => '0',
        self::SCRAP_THRESHOLD_AED => '60000',
        self::WHATSAPP_UAE => '+971 50 515 8484',
        self::WHATSAPP_IRAN => '+98 912 051 2149',
        self::DEFAULT_DELIVERY_DAYS => '40',
    ];

    /**
     * پیشوند کلید تعرفهٔ گمرکی (سود بازرگانی) هر دستهٔ خودرو، به‌صورت درصد.
     * مقدار کامل کلید: tariff_percent_{categoryId}
     */
    public const TARIFF_PREFIX = 'tariff_percent_';

    /**
     * پیشوند کلید رتبهٔ انرژی هر دسته برای محاسبهٔ تعداد گواهی اسقاط
     * (ab | cd | efg — طبق جدول آیین‌نامهٔ پیوست).
     */
    public const SCRAP_TIER_PREFIX = 'scrap_tier_';

    public static function get(string $key, ?string $default = null): string
    {
        return Cache::rememberForever("setting.$key", function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value')
                ?? $default
                ?? self::DEFAULTS[$key]
                ?? '';
        });
    }

    public static function set(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.$key");
    }
}
