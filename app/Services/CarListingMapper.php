<?php

namespace App\Services;

use App\Models\CarListing;
use Illuminate\Support\Str;

/**
 * تبدیل داده‌های خام دابیزل به مقادیر آمادهٔ ذخیره: دسته‌بندی خودرو،
 * عنوان و اسلاگ و متای فارسی.
 */
class CarListingMapper
{
    /** @return array{kind: 'exact'|'range'|'unknown', min: ?int, max: ?int} */
    public function parseEngineCapacity(?string $text): array
    {
        if (! $text) {
            return ['kind' => 'unknown', 'min' => null, 'max' => null];
        }

        $normalized = str_replace([',', '–', '—', '−'], ['', '-', '-', '-'], $text);

        // Liters (e.g., "2.0L", "2.5 liter")
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:l|liter|litre)\b/i', $normalized, $m)) {
            $cc = (int) round((float) $m[1] * 1000);
            return ['kind' => 'exact', 'min' => $cc, 'max' => $cc];
        }

        // Ranges with cc (e.g., "1500-2000 cc", "1500 to 1999")
        if (preg_match('/(\d{3,5})\s*(?:cc)?\s*(?:-|to)\s*(\d{3,5})\s*(?:cc)?/i', $normalized, $m)) {
            return ['kind' => 'range', 'min' => (int) $m[1], 'max' => (int) $m[2]];
        }

        // CC with prefix/suffix patterns: "cc 4000", "cc +4000", "+4000 cc", "4000 cc", "4000cc"
        // Extract largest 3-5 digit number when cc or + is present
        if (preg_match('/cc\s*[+]?\s*(\d{3,5})/i', $normalized, $m) ||
            preg_match('/[+]\s*(\d{3,5})\s*cc/i', $normalized, $m)) {
            $cc = (int) $m[1];
            return ['kind' => 'exact', 'min' => $cc, 'max' => $cc];
        }

        // Plain cc pattern (word boundary before)
        if (preg_match('/\b(\d{3,5})\s*cc\b/i', $normalized, $m)) {
            $cc = (int) $m[1];
            return ['kind' => 'exact', 'min' => $cc, 'max' => $cc];
        }

        // Plain digits only
        if (preg_match('/^\s*(\d{3,5})\s*$/', $normalized, $m)) {
            $cc = (int) $m[1];
            return ['kind' => 'exact', 'min' => $cc, 'max' => $cc];
        }

        return ['kind' => 'unknown', 'min' => null, 'max' => null];
    }

    /**
     * کران پایین بازهٔ حجم موتور را می‌گیرد و روی آستانه‌های categories
     * صفحهٔ محاسبه‌گر (۱۵۰۰/۲۰۰۰/۲۵۰۰/۳۰۰۰) می‌نشاند. عمداً کران پایین
     * (نه میانگین) استفاده می‌شود تا برآورد سود بازرگانی محافظه‌کارانه بماند.
     * این فقط یک پیش‌فرض است و در پنل مدیریت قابل اصلاح دستی است.
     */
    public function detectCategory(?string $engineCapacityCcText, ?string $fuelTypeText): string
    {
        $fuel = mb_strtolower($fuelTypeText ?? '');
        if (str_contains($fuel, 'electric') && ! str_contains($fuel, 'hybrid')) {
            return 'ev';
        }
        if (str_contains($fuel, 'plug')) {
            return 'phev';
        }
        if (str_contains($fuel, 'hybrid')) {
            return 'hybrid';
        }

        $engine = $this->parseEngineCapacity($engineCapacityCcText);
        if ($engine['kind'] === 'unknown') {
            return 'c2000';
        }

        $cc = $engine['min'];

        // For ranges, prefer the upper bound when it is meaningfully higher
        // (e.g. "2000-2499" or "cc 2499 - 2000" should land in c2500, not c2000)
        if ($engine['kind'] === 'range' && ($engine['max'] ?? 0) > ($engine['min'] ?? 0)) {
            $cc = (int) $engine['max'];
        }

        // Keep the old 1500 special case only if upper is still ≤ 1500
        if ($engine['kind'] === 'range' && $cc <= 1500) {
            $cc = 1500;
        }

        return match (true) {
            $cc <= 1500 => 'c1500',
            $cc <= 2000 => 'c2000',
            $cc <= 2500 => 'c2500',
            $cc <= 3000 => 'c3000',
            default => 'c3001',
        };
    }

    public function buildPersianTitle(array $data): string
    {
        $make = $data['make'] ? Str::of($data['make'])->replace('-', ' ')->upper() : '';
        $model = $data['model'] ? Str::of($data['model'])->replace('-', ' ')->upper() : '';
        $trim = $data['trim_level'] ?? '';
        $year = $data['model_year'] ?? '';

        $parts = array_filter([trim("$make $model"), $trim]);
        $title = implode(' ', $parts);

        return trim($title.($year ? " مدل {$year}" : ''));
    }

    public function buildMetaDescription(array $data, string $titleFa): string
    {
        $price = isset($data['price_aed']) ? number_format((float) $data['price_aed']) : null;
        $km = $data['kilometers'] ?? null;

        $bits = [$titleFa];
        if ($price) {
            $bits[] = "قیمت {$price} درهم";
        }
        if ($km) {
            $bits[] = "کارکرد {$km}";
        }
        $bits[] = 'به همراه جدول کامل هزینه ترخیص، عوارض گمرکی و پلاک برای واردات به ایران.';

        return Str::limit(implode(' — ', $bits), 300);
    }

    public function slugify(array $data): string
    {
        $make = Str::slug($data['make'] ?? 'car');
        $model = Str::slug($data['model'] ?? '');
        $year = $data['model_year'] ?? '';
        $shortId = Str::lower(Str::random(6));

        $base = trim(implode('-', array_filter([$make, $model, $year])), '-');
        $slug = trim($base.'-'.$shortId, '-');

        while (CarListing::where('slug', $slug)->exists()) {
            $shortId = Str::lower(Str::random(6));
            $slug = trim($base.'-'.$shortId, '-');
        }

        return $slug;
    }
}
