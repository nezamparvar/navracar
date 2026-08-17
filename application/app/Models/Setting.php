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

    public const OTHER_COSTS_AED = 'other_costs_aed';

    public const STORAGE_TOMAN = 'storage_toman';

    public const SCRAP_CERT_PRICE_TOMAN = 'scrap_cert_price_toman';

    public const SCRAP_THRESHOLD_AED = 'scrap_threshold_aed';

    public const CUSTOMS_FIXED_PERCENT = 'customs_fixed_percent';

    public const CUSTOMS_VALUE_DISCOUNT_PERCENT = 'customs_value_discount_percent';

    public const GASOLINE_LEVY_PERCENT = 'gasoline_levy_percent';

    public const FOB_LEVY_PERCENT = 'fob_levy_percent';

    public const VAT_PERCENT = 'vat_percent';

    public const ADVANCE_IMPORT_TAX_PERCENT = 'advance_import_tax_percent';

    public const RED_CRESCENT_PERCENT = 'red_crescent_percent';

    public const CUSTOMS_SUPERVISION_PERCENT = 'customs_supervision_percent';

    public const WASTE_LEVY_PERCENT = 'waste_levy_percent';

    public const STANDARD_FEE_PERCENT = 'standard_fee_percent';

    public const REGISTRATION_PERCENT = 'registration_percent';

    public const TRANSFER_TAX_PERCENT = 'transfer_tax_percent';

    public const MUNICIPAL_PERCENT = 'municipal_percent';

    public const INDIVIDUAL_PERSON_PERCENT = 'individual_person_percent';

    public const SERVICE_FEE_PERCENT = 'service_fee_percent';

    public const WHATSAPP_UAE = 'whatsapp_uae_number';

    public const WHATSAPP_IRAN = 'whatsapp_iran_number';

    public const TEHRAN_OFFICE_PHONE = 'tehran_office_phone';

    public const DEFAULT_DELIVERY_DAYS = 'default_delivery_days';

    public const TELEGRAM_BOT_TOKEN = 'telegram_bot_token';

    public const TELEGRAM_CHAT_ID = 'telegram_chat_id';

    public const BALE_BOT_TOKEN = 'bale_bot_token';

    public const BALE_CHAT_ID = 'bale_chat_id';

    public const USD_TO_AED_RATE = 'usd_to_aed_rate';

    public const LOAN_MAX_AMOUNT_TOMAN = 'loan_max_amount_toman';

    public const LOAN_TERM_YEARS = 'loan_term_years';

    public const LOAN_INTEREST_RATE_PERCENT = 'loan_interest_rate_percent';

    public const DEFAULTS = [
        self::FREE_RATE => '51000',
        self::CUSTOMS_RATE => '35688',
        self::LICENSE_FEE_AED => '60000',
        self::SEA_FREIGHT_AED => '1500',
        self::OTHER_COSTS_AED => '0',
        self::STORAGE_TOMAN => '0',
        self::SCRAP_CERT_PRICE_TOMAN => '0',
        self::SCRAP_THRESHOLD_AED => '60000',
        self::CUSTOMS_FIXED_PERCENT => '4',
        self::CUSTOMS_VALUE_DISCOUNT_PERCENT => '30',
        self::GASOLINE_LEVY_PERCENT => '10',
        self::FOB_LEVY_PERCENT => '5',
        self::VAT_PERCENT => '10',
        self::ADVANCE_IMPORT_TAX_PERCENT => '2',
        self::RED_CRESCENT_PERCENT => '1',
        self::CUSTOMS_SUPERVISION_PERCENT => '0.5',
        self::WASTE_LEVY_PERCENT => '0.05',
        self::STANDARD_FEE_PERCENT => '0.8',
        self::REGISTRATION_PERCENT => '10',
        self::TRANSFER_TAX_PERCENT => '3',
        self::MUNICIPAL_PERCENT => '1',
        self::INDIVIDUAL_PERSON_PERCENT => '5',
        self::SERVICE_FEE_PERCENT => '10',
        self::WHATSAPP_UAE => '+971 50 515 8484',
        self::WHATSAPP_IRAN => '+98 912 051 2149',
        self::TEHRAN_OFFICE_PHONE => '+98 21 8887 0878',
        self::DEFAULT_DELIVERY_DAYS => '40',
        self::USD_TO_AED_RATE => '3.6725',
        self::LOAN_MAX_AMOUNT_TOMAN => '10000000000',
        self::LOAN_TERM_YEARS => '2,3,5',
        self::LOAN_INTEREST_RATE_PERCENT => '28',
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

    public const SCRAP_CERT_COUNT_PREFIX = 'scrap_cert_count_';

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

    public static function scrapCertificateCountKey(string $tier, string $bracket): string
    {
        return self::SCRAP_CERT_COUNT_PREFIX.$tier.'_'.$bracket;
    }
}

