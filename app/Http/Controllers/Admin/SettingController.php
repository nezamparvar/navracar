<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\Setting;
use App\Services\VehiclePricing\VehiclePricingCatalog;
use App\Services\VehiclePricing\VehiclePricingSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'pageTitle' => 'تنظیمات نرخ‌ها و هزینه‌ها',
            'pageSubtitle' => 'این نرخ‌ها به‌صورت زنده در صفحات «قیمت خودروها» و برای نمایش نرخ روز به مشتری استفاده می‌شوند.',
            'freeRate' => Setting::get(Setting::FREE_RATE),
            'customsRate' => Setting::get(Setting::CUSTOMS_RATE),
            'licenseFeeAed' => Setting::get(Setting::LICENSE_FEE_AED),
            'seaFreightAed' => Setting::get(Setting::SEA_FREIGHT_AED),
            'storageToman' => Setting::get(Setting::STORAGE_TOMAN),
            'scrapCertPriceToman' => Setting::get(Setting::SCRAP_CERT_PRICE_TOMAN),
            'scrapThresholdAed' => Setting::get(Setting::SCRAP_THRESHOLD_AED),
            'whatsappUae' => Setting::get(Setting::WHATSAPP_UAE),
            'whatsappIran' => Setting::get(Setting::WHATSAPP_IRAN),
            'tehranOfficePhone' => Setting::get(Setting::TEHRAN_OFFICE_PHONE),
            'defaultDeliveryDays' => Setting::get(Setting::DEFAULT_DELIVERY_DAYS),
            'telegramBotToken' => Setting::get(Setting::TELEGRAM_BOT_TOKEN),
            'telegramChatId' => Setting::get(Setting::TELEGRAM_CHAT_ID),
            'baleBotToken' => Setting::get(Setting::BALE_BOT_TOKEN),
            'baleChatId' => Setting::get(Setting::BALE_CHAT_ID),
            'usdToAedRate' => Setting::get(Setting::USD_TO_AED_RATE),
            'loanMaxAmountToman' => Setting::get(Setting::LOAN_MAX_AMOUNT_TOMAN),
            'loanTermYears' => Setting::get(Setting::LOAN_TERM_YEARS),
            'loanInterestRatePercent' => Setting::get(Setting::LOAN_INTEREST_RATE_PERCENT),
            'categories' => CarListing::categoriesWithLiveRates(),
            'pricingSettings' => VehiclePricingSettings::current()->toArray(),
        ]);
    }

    public function update(Request $request)
    {
        $percentageKeys = [
            'customsFixed' => Setting::CUSTOMS_FIXED_PERCENT,
            'gasolineLevy' => Setting::GASOLINE_LEVY_PERCENT,
            'fobLevy' => Setting::FOB_LEVY_PERCENT,
            'vat' => Setting::VAT_PERCENT,
            'advanceImportTax' => Setting::ADVANCE_IMPORT_TAX_PERCENT,
            'redCrescent' => Setting::RED_CRESCENT_PERCENT,
            'customsSupervision' => Setting::CUSTOMS_SUPERVISION_PERCENT,
            'wasteLevy' => Setting::WASTE_LEVY_PERCENT,
            'standardFee' => Setting::STANDARD_FEE_PERCENT,
            'registration' => Setting::REGISTRATION_PERCENT,
            'transferTax' => Setting::TRANSFER_TAX_PERCENT,
            'municipal' => Setting::MUNICIPAL_PERCENT,
            'individualPerson' => Setting::INDIVIDUAL_PERSON_PERCENT,
            'serviceFee' => Setting::SERVICE_FEE_PERCENT,
        ];

        $rules = [
            'free_rate' => ['required', 'numeric', 'min:1'],
            'customs_rate' => ['required', 'numeric', 'min:1'],
            'license_fee_aed' => ['required', 'numeric', 'min:0'],
            'sea_freight_aed' => ['required', 'numeric', 'min:0'],
            'storage_toman' => ['required', 'numeric', 'min:0'],
            'scrap_cert_price_toman' => ['required', 'numeric', 'min:0'],
            'scrap_threshold_aed' => ['required', 'numeric', 'min:0'],
            'whatsapp_uae_number' => ['required', 'string', 'max:32'],
            'whatsapp_iran_number' => ['required', 'string', 'max:32'],
            'tehran_office_phone' => ['required', 'string', 'max:32'],
            'default_delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'bale_bot_token' => ['nullable', 'string', 'max:255'],
            'bale_chat_id' => ['nullable', 'string', 'max:255'],
            'usd_to_aed_rate' => ['required', 'numeric', 'min:0.01'],
            'loan_max_amount_toman' => ['required', 'numeric', 'min:0'],
            'loan_term_years' => ['required', 'string', 'max:50', 'regex:/^\d+(,\d+)*$/'],
            'loan_interest_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'tariff_percent' => ['required', 'array'],
            'tariff_percent.*' => ['required', 'numeric', 'min:0', 'max:500'],
            'scrap_tier' => ['required', 'array'],
            'scrap_tier.*' => ['required', Rule::in(['ab', 'cd', 'efg'])],
            'pricing_percentages' => ['required', 'array'],
            'pricing_percentages.*' => ['required', 'numeric', 'min:0', 'max:500'],
            'scrap_counts' => ['required', 'array'],
            'scrap_counts.*.*' => ['required', 'integer', 'min:0', 'max:100'],
        ];
        foreach (array_keys($percentageKeys) as $key) {
            $rules['pricing_percentages.'.$key] = ['required', 'numeric', 'min:0', 'max:500'];
        }
        foreach (array_keys(VehiclePricingCatalog::CATEGORIES) as $categoryId) {
            $rules['tariff_percent.'.$categoryId] = ['required', 'numeric', 'min:0', 'max:500'];
            $rules['scrap_tier.'.$categoryId] = ['required', Rule::in(['ab', 'cd', 'efg'])];
        }
        foreach (VehiclePricingCatalog::SCRAP_CERTIFICATE_COUNT_DEFAULTS as $tier => $brackets) {
            foreach (array_keys($brackets) as $bracket) {
                $rules['scrap_counts.'.$tier.'.'.$bracket] = ['required', 'integer', 'min:0', 'max:100'];
            }
        }

        $data = $request->validate($rules);

        Setting::set(Setting::FREE_RATE, (string) $data['free_rate']);
        Setting::set(Setting::CUSTOMS_RATE, (string) $data['customs_rate']);
        Setting::set(Setting::LICENSE_FEE_AED, (string) $data['license_fee_aed']);
        Setting::set(Setting::SEA_FREIGHT_AED, (string) $data['sea_freight_aed']);
        Setting::set(Setting::STORAGE_TOMAN, (string) $data['storage_toman']);
        Setting::set(Setting::SCRAP_CERT_PRICE_TOMAN, (string) $data['scrap_cert_price_toman']);
        Setting::set(Setting::SCRAP_THRESHOLD_AED, (string) $data['scrap_threshold_aed']);
        Setting::set(Setting::WHATSAPP_UAE, (string) $data['whatsapp_uae_number']);
        Setting::set(Setting::WHATSAPP_IRAN, (string) $data['whatsapp_iran_number']);
        Setting::set(Setting::TEHRAN_OFFICE_PHONE, (string) $data['tehran_office_phone']);
        Setting::set(Setting::DEFAULT_DELIVERY_DAYS, (string) $data['default_delivery_days']);
        Setting::set(Setting::TELEGRAM_BOT_TOKEN, (string) ($data['telegram_bot_token'] ?? ''));
        Setting::set(Setting::TELEGRAM_CHAT_ID, (string) ($data['telegram_chat_id'] ?? ''));
        Setting::set(Setting::BALE_BOT_TOKEN, (string) ($data['bale_bot_token'] ?? ''));
        Setting::set(Setting::BALE_CHAT_ID, (string) ($data['bale_chat_id'] ?? ''));
        Setting::set(Setting::USD_TO_AED_RATE, (string) $data['usd_to_aed_rate']);
        Setting::set(Setting::LOAN_MAX_AMOUNT_TOMAN, (string) $data['loan_max_amount_toman']);
        Setting::set(Setting::LOAN_TERM_YEARS, (string) $data['loan_term_years']);
        Setting::set(Setting::LOAN_INTEREST_RATE_PERCENT, (string) $data['loan_interest_rate_percent']);

        foreach ($percentageKeys as $inputKey => $settingKey) {
            Setting::set($settingKey, (string) $data['pricing_percentages'][$inputKey]);
        }

        foreach (VehiclePricingCatalog::SCRAP_CERTIFICATE_COUNT_DEFAULTS as $tier => $brackets) {
            foreach (array_keys($brackets) as $bracket) {
                Setting::set(
                    Setting::scrapCertificateCountKey($tier, $bracket),
                    (string) $data['scrap_counts'][$tier][$bracket],
                );
            }
        }

        foreach ($data['tariff_percent'] as $categoryId => $percent) {
            if (array_key_exists($categoryId, VehiclePricingCatalog::CATEGORIES)) {
                Setting::set(Setting::TARIFF_PREFIX.$categoryId, (string) $percent);
            }
        }

        foreach ($data['scrap_tier'] as $categoryId => $tier) {
            if (array_key_exists($categoryId, VehiclePricingCatalog::CATEGORIES)) {
                Setting::set(Setting::SCRAP_TIER_PREFIX.$categoryId, (string) $tier);
            }
        }

        return back()->with('success', 'تنظیمات به‌روزرسانی شد.');
    }
}
