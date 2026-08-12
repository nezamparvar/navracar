<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * انتشار محتوا (آگهی خودرو یا پست وبلاگ) در کانال‌های تلگرام و بله.
 *
 * تلگرام و بله هر دو یک Bot API با ساختار یکسان دارند (bale.ai مستندش را
 * سازگار با تلگرام منتشر کرده: https://tapi.bale.ai/bot{token}/METHOD)،
 * بنابراین یک متد مشترک برای هر دو کافی است.
 *
 * واتساپ API رسمی رایگان و بدون تأیید متا برای ارسال خودکار عکس ندارد؛
 * به‌جای آن یک لینک اشتراک‌گذاری wa.me با متن آماده تولید می‌کنیم که با یک
 * کلیک در واتساپ باز می‌شود و کاربر فقط عکس را دستی پیوست می‌کند.
 */
class SocialPublisher
{
    private const TELEGRAM_API = 'https://api.telegram.org/bot%s/%s';

    private const BALE_API = 'https://tapi.bale.ai/bot%s/%s';

    /**
     * @param  array<int, string>  $hashtags  بدون علامت # — خودش اضافه می‌شود
     */
    public function buildCaption(string $title, ?string $description, ?string $priceLine, string $url, array $hashtags): string
    {
        $lines = [$title];

        if ($priceLine) {
            $lines[] = $priceLine;
        }
        if ($description) {
            $lines[] = Str::limit(strip_tags($description), 300);
        }

        $lines[] = $url;

        if (! empty($hashtags)) {
            $lines[] = collect($hashtags)
                ->filter()
                ->map(fn ($tag) => '#'.preg_replace('/[^\p{L}\p{N}_]/u', '_', $tag))
                ->implode(' ');
        }

        return implode("\n\n", $lines);
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    public function publishToTelegram(string $imageUrl, string $caption): array
    {
        return $this->sendPhoto(self::TELEGRAM_API, Setting::get(Setting::TELEGRAM_BOT_TOKEN), Setting::get(Setting::TELEGRAM_CHAT_ID), $imageUrl, $caption);
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    public function publishToBale(string $imageUrl, string $caption): array
    {
        return $this->sendPhoto(self::BALE_API, Setting::get(Setting::BALE_BOT_TOKEN), Setting::get(Setting::BALE_CHAT_ID), $imageUrl, $caption);
    }

    public function whatsAppShareUrl(string $caption): string
    {
        return 'https://wa.me/?text='.rawurlencode($caption);
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    private function sendPhoto(string $apiTemplate, string $botToken, string $chatId, string $imageUrl, string $caption): array
    {
        if (! $botToken || ! $chatId) {
            return ['ok' => false, 'error' => 'توکن ربات یا شناسه کانال تنظیم نشده است — از تنظیمات پنل وارد کنید.'];
        }

        try {
            $response = Http::timeout(20)->post(sprintf($apiTemplate, $botToken, 'sendPhoto'), [
                'chat_id' => $chatId,
                'photo' => $imageUrl,
                'caption' => $caption,
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'خطا در اتصال: '.$e->getMessage()];
        }

        if (! $response->successful() || ! ($response->json('ok') ?? false)) {
            return ['ok' => false, 'error' => $response->json('description') ?? ('کد خطا '.$response->status())];
        }

        return ['ok' => true, 'error' => null];
    }
}
