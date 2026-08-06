<?php
/**
 * log-calculation.php — ثبت خودکار هر محاسبه انجام‌شده روی سایت
 * چه کاربر مشخصات تماس بزند چه نزند، این endpoint یک رکورد در جدول
 * calculation_logs ثبت می‌کند تا در پنل مدیریت قابل گزارش‌گیری باشد.
 */
require __DIR__ . '/ajax-bootstrap.php';

function nlog_num($v) { return is_numeric($v) ? (float)$v : 0; }
function nlog_txt($v) { return mb_substr(trim((string)($v ?? '')), 0, 255); }

ajax_handle(function () use ($pdo) {
    ajax_require_post();
    $data = ajax_input();
    $geo = ajax_geo();

    $stmt = $pdo->prepare(
        "INSERT INTO calculation_logs
        (car_label, category, real_price_aed, customs_price_aed, free_rate, customs_rate,
         sea_freight_aed, permits_aed, storage_toman, sum_customs, sum_plate,
         total_no_profit, service_profit, total_with_profit, country, city, ip_address, user_agent)
        VALUES (:car,:cat,:rp,:cp,:fr,:cr,:sf,:pm,:st,:sc,:sp,:tnp,:spf,:twp,:country,:city,:ip,:ua)"
    );
    $stmt->execute([
        'car' => nlog_txt($data['car'] ?? ''),
        'cat' => nlog_txt($data['category'] ?? ''),
        'rp'  => nlog_num($data['realPriceAED'] ?? 0),
        'cp'  => nlog_num($data['customsPriceAED'] ?? 0),
        'fr'  => nlog_num($data['freeRate'] ?? 0),
        'cr'  => nlog_num($data['customsRate'] ?? 0),
        'sf'  => nlog_num($data['seaFreightAED'] ?? 0),
        'pm'  => nlog_num($data['permitsAED'] ?? 0),
        'st'  => nlog_num($data['storage'] ?? 0),
        'sc'  => nlog_num($data['sumCustoms'] ?? 0),
        'sp'  => nlog_num($data['sumPlate'] ?? 0),
        'tnp' => nlog_num($data['totalNoProfit'] ?? 0),
        'spf' => nlog_num($data['serviceProfit'] ?? 0),
        'twp' => nlog_num($data['totalWithProfit'] ?? 0),
        'country' => $geo['country'],
        'city'    => $geo['city'],
        'ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua'  => nlog_txt($_SERVER['HTTP_USER_AGENT'] ?? ''),
    ]);

    ajax_ok();
}, 'log-calculation.php');
