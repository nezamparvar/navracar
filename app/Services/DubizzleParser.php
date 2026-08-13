<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;

/**
 * دریافت و استخراج اطلاعات از صفحهٔ آگهی دابیزل.
 *
 * صفحات دابیزل با Next.js رندر سمت سرور می‌شوند و مقادیر مورد نیاز همیشه با
 * ویژگی‌های پایدار data-testid="..." مشخص شده‌اند (مثل overview-fuel_type-value).
 * این الگو از یک نمونهٔ واقعیِ آگهی بررسی و تأیید شده است.
 */
class DubizzleParser
{
    private const ALLOWED_HOSTS = ['dubai.dubizzle.com', 'uae.dubizzle.com', 'www.dubizzle.com', 'dubizzle.com'];

    private const MAX_HTML_BYTES = 5 * 1024 * 1024;

    public function __construct(private ?OutboundUrlGuard $urlGuard = null) {}

    private const OVERVIEW_FIELDS = [
        'body_type', 'doors', 'engine_capacity_cc', 'exterior_color', 'fuel_type',
        'horsepower', 'interior_color', 'motors_trim', 'no_of_cylinders',
        'seating_capacity', 'seller_type', 'target_market', 'transmission_type', 'warranty',
    ];

    /**
     * @return array{html: ?string, error: ?string}
     */
    public function fetch(string $url): array
    {
        if (! $this->isAllowedSourceUrl($url)) {
            return ['html' => null, 'error' => 'Only approved HTTPS Dubizzle listing URLs are allowed.'];
        }

        try {
            $response = Http::withoutRedirecting()->withOptions(['stream' => true])->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,ar;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => 'https://www.google.com/',
                'Sec-Ch-Ua' => '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
                'Sec-Ch-Ua-Mobile' => '?0',
                'Sec-Ch-Ua-Platform' => '"Windows"',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'cross-site',
                'Upgrade-Insecure-Requests' => '1',
            ])->timeout(25)->connectTimeout(10)->get($url);
        } catch (\Throwable $e) {
            return ['html' => null, 'error' => 'خطا در اتصال: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            return [
                'html' => null,
                'error' => 'دریافت صفحه ناموفق بود (کد '.$response->status().'). دابیزل معمولاً درخواست‌های خودکار از سرورها را مسدود می‌کند (حفاظت ضد ربات) — '
                    .'این محدودیت مربوط به IP سرور است، نه یک باگ در سایت. راه‌حل: صفحهٔ آگهی را در مرورگر خودتان باز کنید، روی آن راست‌کلیک و «مشاهدهٔ کد صفحه / View Page Source» را بزنید، '
                    .'کل کد HTML را کپی کنید و در کادر «یا HTML صفحه را دستی پیست کنید» همین فرم پیست کنید.',
            ];
        }

        $html = $this->readLimited($response->toPsrResponse()->getBody(), self::MAX_HTML_BYTES);
        if ($html === null) {
            return ['html' => null, 'error' => 'The remote page exceeded the 5 MB response limit.'];
        }

        return ['html' => $html, 'error' => null];
    }

    public function isAllowedSourceUrl(string $url): bool
    {
        return ($this->urlGuard ?? new OutboundUrlGuard)->allows($url, self::ALLOWED_HOSTS);
    }

    private function readLimited(StreamInterface $stream, int $limit): ?string
    {
        $body = '';
        while (! $stream->eof()) {
            $body .= $stream->read(8192);
            if (strlen($body) > $limit) {
                return null;
            }
        }

        return $body;
    }

    /**
     * فیلدهای کلیدی که برای تشخیص موفقیت/شکست استخراج بررسی می‌شوند — برای
     * پیام خطای دقیق (کدام data-testid پیدا نشد) نه فقط «ناموفق بود».
     *
     * @return array<string, bool>
     */
    public function diagnostics(string $html): array
    {
        return [
            'عنوان آگهی (listing-name)' => $this->extractSimple($html, 'listing-name') !== null,
            'قیمت (listing-price)' => $this->extractPrice($html) !== null,
            'سال ساخت (listing-year-value)' => $this->extractSimple($html, 'listing-year-value') !== null,
            'کارکرد (listing-kilometers-value)' => $this->extractSimple($html, 'listing-kilometers-value') !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function parse(string $html, string $url): array
    {
        $data = $this->extractFromUrl($url);

        $data['title_en'] = $this->extractSimple($html, 'listing-name');
        $data['sub_heading'] = $this->extractSimple($html, 'listing-sub-heading');
        $data['price_aed'] = $this->extractPrice($html);
        $data['model_year'] = $this->extractSimple($html, 'listing-year-value') ?: ($data['model_year'] ?? null);
        $data['kilometers'] = $this->extractSimple($html, 'listing-kilometers-value');
        $data['regional_specs'] = $this->extractSimple($html, 'listing-regional_specs-value');
        $data['steering_side'] = $this->extractSimple($html, 'listing-steering_side-value');
        $data['location_text'] = $this->extractSimple($html, 'listing-location-map');
        $data['posted_on_dubizzle'] = $this->extractPostedOn($html);
        $data['description_en'] = $this->extractDescription($html);

        foreach (self::OVERVIEW_FIELDS as $field) {
            $data[$field] = $this->extractSimple($html, "overview-{$field}-value");
        }
        $data['trim_level'] = $data['motors_trim'] ?? null;
        unset($data['motors_trim']);

        $data['images'] = $this->extractImages($html);

        return $data;
    }

    private function extractSimple(string $html, string $testid): ?string
    {
        $pattern = '/data-testid="'.preg_quote($testid, '/').'"[^>]*>([^<]*)</';
        if (preg_match($pattern, $html, $m)) {
            $value = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function extractWindow(string $html, string $testid, int $length = 400): ?string
    {
        $pos = strpos($html, 'data-testid="'.$testid.'"');
        if ($pos === false) {
            return null;
        }

        $gt = strpos($html, '>', $pos);
        if ($gt === false) {
            return null;
        }

        return substr($html, $gt + 1, $length);
    }

    private function cleanFragment(string $fragment): string
    {
        $fragment = preg_replace('/<!--.*?-->/s', '', $fragment);
        $fragment = preg_replace('/<br\s*\/?>/i', "\n", $fragment);
        $fragment = preg_replace('/<button[^>]*>.*?<\/button>/is', '', $fragment);
        $fragment = strip_tags($fragment);

        return html_entity_decode(trim($fragment), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function extractPrice(string $html): ?float
    {
        $window = $this->extractWindow($html, 'listing-price', 250);
        if ($window === null) {
            return null;
        }

        $clean = $this->cleanFragment($window);
        if (preg_match('/([\d,]+)/', $clean, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }

        return null;
    }

    private function extractPostedOn(string $html): ?string
    {
        $window = $this->extractWindow($html, 'posted-on', 200);
        if ($window === null) {
            return null;
        }

        $clean = $this->cleanFragment($window);
        $clean = preg_replace('/^posted on\s*:?\s*/i', '', $clean);

        return trim($clean) ?: null;
    }

    private function extractDescription(string $html): ?string
    {
        $window = $this->extractWindow($html, 'description', 10000);
        if ($window === null) {
            return null;
        }

        // بستن span توصیفات معمولاً قبل از دکمهٔ "نمایش شماره" است.
        $endMarkers = ['data-testid="show-number"', 'data-testid="report-this-ad"', '</div><div class="MuiBox-root mui-style-37oxnf"'];
        $cut = strlen($window);
        foreach ($endMarkers as $marker) {
            $p = strpos($window, $marker);
            if ($p !== false && $p < $cut) {
                $cut = $p;
            }
        }

        return $this->cleanFragment(substr($window, 0, $cut)) ?: null;
    }

    /**
     * @return array<int, string>
     */
    private function extractImages(string $html): array
    {
        preg_match_all('#https://dbz-images\.dubizzle\.com/images/[^"\'\s]+#i', $html, $matches);

        $seen = [];
        $urls = [];
        foreach ($matches[0] ?? [] as $raw) {
            $base = preg_replace('/\?.*$/', '', $raw);
            if (isset($seen[$base])) {
                continue;
            }
            $seen[$base] = true;
            $urls[] = $base.'?impolicy=dpv';
        }

        return $urls;
    }

    /**
     * @return array{make: ?string, model: ?string, model_year: ?string, external_id: ?string}
     */
    private function extractFromUrl(string $url): array
    {
        $result = ['make' => null, 'model' => null, 'model_year' => null, 'external_id' => null];

        if (preg_match('#/motors/(?:used-cars|new-cars|export-cars)/([a-z0-9-]+)/([a-z0-9-]+)/(\d{4})/#i', $url, $m)) {
            $result['make'] = $m[1];
            $result['model'] = $m[2];
            $result['model_year'] = $m[3];
        }

        if (preg_match('#-([a-f0-9]{32})/?$#i', $url, $m)) {
            $result['external_id'] = $m[1];
        }

        return $result;
    }
}
