<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\Setting;
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
            'categories' => CarListing::categoriesWithLiveRates(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
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
            'tariff_percent' => ['required', 'array'],
            'tariff_percent.*' => ['required', 'numeric', 'min:0', 'max:500'],
            'scrap_tier' => ['required', 'array'],
            'scrap_tier.*' => ['required', Rule::in(['ab', 'cd', 'efg'])],
        ]);

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

        foreach ($data['tariff_percent'] as $categoryId => $percent) {
            if (array_key_exists($categoryId, CarListing::CATEGORIES)) {
                Setting::set(Setting::TARIFF_PREFIX.$categoryId, (string) $percent);
            }
        }

        foreach ($data['scrap_tier'] as $categoryId => $tier) {
            if (array_key_exists($categoryId, CarListing::CATEGORIES)) {
                Setting::set(Setting::SCRAP_TIER_PREFIX.$categoryId, (string) $tier);
            }
        }

        return back()->with('success', 'تنظیمات به‌روزرسانی شد.');
    }
}
