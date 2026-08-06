<?php
/**
 * geo-helper.php — کشور و شهر مشتری را از روی IP تشخیص می‌دهد
 * از سرویس رایگان ip-api.com استفاده می‌کند (بدون نیاز به کلید).
 * اگر سرور شما دسترسی خروجی به این سرویس نداشته باشد یا IP محلی باشد،
 * به‌آرامی شکست می‌خورد و country/city را خالی برمی‌گرداند — هرگز خطا پرتاب نمی‌کند.
 */
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

function navarakar_geo_lookup($ip) {
    $result = ['country' => null, 'city' => null];

    if (!$ip || $ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return $result;
    }

    $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,city';

    try {
        $context = stream_context_create(['http' => ['timeout' => 1.2]]);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) return $result;
        $data = json_decode($raw, true);
        if (is_array($data) && ($data['status'] ?? '') === 'success') {
            $result['country'] = $data['country'] ?? null;
            $result['city'] = $data['city'] ?? null;
        }
    } catch (\Throwable $e) {
        // silent — geolocation is a nice-to-have, never block the main request
    }

    return $result;
}
