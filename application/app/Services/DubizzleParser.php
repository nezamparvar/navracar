<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    public const DIRECT_FETCH_BLOCKED_MESSAGE = 'دریافت مستقیم این آگهی توسط دابی‌زل مسدود شد. برای ادامه، View Page Source صفحه آگهی را کپی و در کادر زیر وارد کنید.';

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
            Log::warning('Dubizzle direct fetch failed', [
                'classification' => 'transport_error',
                'host' => parse_url($url, PHP_URL_HOST),
                'exception' => $e::class,
            ]);

            return ['html' => null, 'error' => 'خطا در اتصال: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            Log::warning('Dubizzle direct fetch rejected', [
                'classification' => $this->classifyFetchResponse($response->status(), $response->header('content-type')),
                'status' => $response->status(),
                'content_type' => $response->header('content-type'),
                'host' => parse_url($url, PHP_URL_HOST),
            ]);
            if ($response->status() >= 300 && $response->status() < 400) {
                return ['html' => null, 'error' => 'Dubizzle redirected the listing URL. Open the final listing URL or paste its page source manually.'];
            }
            if (in_array($response->status(), [401, 403, 406, 429], true)) {
                return ['html' => null, 'error' => self::DIRECT_FETCH_BLOCKED_MESSAGE.' (HTTP '.$response->status().').'];
            }

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
        if (trim($html) === '') {
            return ['html' => null, 'error' => 'Dubizzle returned an empty response. Paste the listing page source manually.'];
        }
        if (preg_match('/captcha|verify you are human|access denied|challenge/i', $html)) {
            return ['html' => null, 'error' => 'Dubizzle returned a bot-protection or challenge page. Direct server fetching is blocked; paste the listing page source manually.'];
        }
        if (! preg_match('/<html\b|<!doctype\s+html/i', $html)) {
            return ['html' => null, 'error' => 'Dubizzle returned an unexpected non-HTML response. Paste the listing page source manually.'];
        }

        return ['html' => $html, 'error' => null];
    }

    public function classifyFetchResponse(int $status, ?string $contentType = null): string
    {
        if ($status >= 300 && $status < 400) {
            return 'redirect';
        }
        if (in_array($status, [401, 403, 406, 429], true)) {
            return 'remote_access_blocked';
        }
        if ($status >= 500) {
            return 'remote_server_error';
        }
        if ($contentType !== null && ! str_contains(strtolower($contentType), 'html')) {
            return 'unexpected_content';
        }

        return 'http_error';
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
        $data = array_merge($this->extractFromUrl($url), $this->extractStructuredData($html));

        $data['title_en'] = $data['title_en'] ?? $this->extractSimple($html, 'listing-name');
        $data['sub_heading'] = $this->extractSimple($html, 'listing-sub-heading');
        $data['price_aed'] = $data['price_aed'] ?? $this->extractPrice($html);
        $data['model_year'] = $this->extractSimple($html, 'listing-year-value') ?: ($data['model_year'] ?? null);
        $data['kilometers'] = $this->extractSimple($html, 'listing-kilometers-value');
        $data['regional_specs'] = $this->extractSimple($html, 'listing-regional_specs-value');
        $data['steering_side'] = $this->extractSimple($html, 'listing-steering_side-value');
        $data['location_text'] = $this->extractSimple($html, 'listing-location-map');
        $data['posted_on_dubizzle'] = $this->extractPostedOn($html);
        $data['description_en'] = $this->extractDescription($html);

        foreach (self::OVERVIEW_FIELDS as $field) {
            $data[$field] = $data[$field] ?? $this->extractSimple($html, "overview-{$field}-value");
        }
        $data['trim_level'] = $data['motors_trim'] ?? null;
        unset($data['motors_trim']);

        $data['images'] = ($data['images'] ?? []) ?: $this->extractImages($html);

        return $data;
    }

    /** Extract the same normalized fields from JSON-LD used by URL and manual HTML imports. */
    private function extractStructuredData(string $html): array
    {
        $result = [];
        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return $result;
        }
        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode(trim($json), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            $nodes = is_array($decoded) && array_is_list($decoded) ? $decoded : [$decoded];
            foreach ($nodes as $node) {
                if (! is_array($node)) {
                    continue;
                }
                if (isset($node['@graph']) && is_array($node['@graph'])) {
                    $nodes = array_merge($nodes, $node['@graph']);
                }
                $type = strtolower((string) ($node['@type'] ?? ''));
                if (! str_contains($type, 'product') && ! str_contains($type, 'vehicle') && ! isset($node['offers'])) {
                    continue;
                }
                $result['title_en'] ??= $node['name'] ?? null;
                $result['description_en'] ??= $node['description'] ?? null;
                $result['source_url'] ??= $node['url'] ?? null;
                $result['price_aed'] ??= isset($node['offers']['price']) ? (float) $node['offers']['price'] : null;
                $result['model_year'] ??= isset($node['vehicleModelDate']) ? (string) $node['vehicleModelDate'] : null;
                $result['make'] ??= is_array($node['brand'] ?? null) ? ($node['brand']['name'] ?? null) : ($node['brand'] ?? null);
                $result['model'] ??= $node['model'] ?? null;
                $result['body_type'] ??= $node['bodyType'] ?? null;
                $result['fuel_type'] ??= $node['fuelType'] ?? null;
                $result['transmission_type'] ??= $node['vehicleTransmission'] ?? null;
                $images = $node['image'] ?? [];
                $result['images'] ??= is_array($images) ? array_values(array_filter($images, 'is_string')) : (is_string($images) ? [$images] : []);
            }
        }

        return array_filter($result, static fn ($value) => $value !== null && $value !== []);
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
