<?php
/**
 * log-vin.php — ثبت هر استعلام شماره شاسی (VIN) در پایگاه داده
 * تا در پنل مدیریت قابل مشاهده و گزارش‌گیری باشد.
 */
require __DIR__ . '/ajax-bootstrap.php';

function vlog_txt($v, $len = 255) { return mb_substr(trim((string)($v ?? '')), 0, $len); }

ajax_handle(function () use ($pdo) {
    ajax_require_post();
    $data = ajax_input();
    $geo = ajax_geo();

    $stmt = $pdo->prepare(
        "INSERT INTO vin_checks (vin, make, model, model_year, plant_country, verdict, source, country, city, ip_address)
         VALUES (:vin,:make,:model,:year,:pc,:verdict,:src,:country,:city,:ip)"
    );
    $stmt->execute([
        'vin'     => vlog_txt($data['vin'] ?? '', 20),
        'make'    => vlog_txt($data['make'] ?? ''),
        'model'   => vlog_txt($data['model'] ?? ''),
        'year'    => vlog_txt($data['year'] ?? '', 10),
        'pc'      => vlog_txt($data['plantCountry'] ?? ''),
        'verdict' => vlog_txt($data['verdict'] ?? '', 50),
        'src'     => vlog_txt($data['source'] ?? '', 30),
        'country' => $geo['country'],
        'city'    => $geo['city'],
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    ajax_ok();
}, 'log-vin.php');
