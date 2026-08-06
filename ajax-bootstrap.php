<?php
/**
 * ajax-bootstrap.php — پایه مشترک برای همه endpointهای عمومی (AJAX/JSON)
 *
 * این فایل کاری را متمرکز می‌کند که قبلاً در هر فایل جدا تکرار می‌شد و همین تکرار
 * باعث شده بود بعضی فایل‌ها (مثل submit-lead-form.php) یک بخش از کوئری‌هایشان
 * بیرون از try/catch بماند و در صورت خطا، به‌جای JSON یک صفحه خطای خام برگردانند
 * و هیچ‌چیز هم در لاگ ثبت نشود. با استفاده از این فایل، آن دسته از خطاها دیگر
 * تکرار نمی‌شود — چون:
 *   ۱) هر Warning/Notice ساده PHP هم به یک Exception تبدیل می‌شود.
 *   ۲) خروجی همیشه JSON معتبر است، حتی در بدترین حالت.
 *   ۳) هر خطا حتماً در logs/activity.log ثبت می‌شود.
 *
 * استفاده در یک endpoint جدید:
 *
 *   require __DIR__ . '/ajax-bootstrap.php';
 *   ajax_handle(function() use ($pdo) {
 *       $data = ajax_input();
 *       if (empty($data['name'])) ajax_fail('نام الزامی است.');
 *       ... کار با دیتابیس ...
 *       ajax_ok(['id' => $newId]);
 *   }, 'نام-endpoint-برای-لاگ');
 */

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db-config.php';
require_once __DIR__ . '/geo-helper.php';

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // برخی هشدارهای بی‌ضرر (مثل deprecated یا notice‌های سطح پایین) را نادیده بگیر
    if (!(error_reporting() & $errno)) return false;
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

/** بدنه JSON درخواست را برمی‌گرداند (آرایه خالی اگر نامعتبر بود) */
function ajax_input() {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

/** پاسخ خطا می‌دهد و اجرا را متوقف می‌کند */
function ajax_fail($message, $httpCode = 400) {
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/** پاسخ موفقیت می‌دهد و اجرا را متوقف می‌کند */
function ajax_ok($extra = [], $message = null) {
    $payload = ['success' => true];
    if ($message !== null) $payload['message'] = $message;
    echo json_encode(array_merge($payload, $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

/** فقط POST را مجاز می‌کند، در غیر این صورت خطا می‌دهد */
function ajax_require_post() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        ajax_fail('روش درخواست مجاز نیست.', 405);
    }
}

/** موقعیت جغرافیایی کاربر را برمی‌گرداند؛ هرگز خطا پرتاب نمی‌کند */
function ajax_geo() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (function_exists('navarakar_geo_lookup')) {
        try {
            return navarakar_geo_lookup($ip);
        } catch (\Throwable $e) {
            navarakar_log('error', 'geo lookup failed (non-fatal)', ['error' => $e->getMessage()]);
        }
    }
    return ['country' => null, 'city' => null];
}

/**
 * تمام منطق یک endpoint را اجرا می‌کند و تضمین می‌دهد که هرگز چیزی جز
 * JSON معتبر خروجی داده نشود — حتی اگر یک خطای پیش‌بینی‌نشده رخ دهد.
 */
function ajax_handle(callable $logic, $endpointName = 'endpoint') {
    try {
        $logic();
        // اگر منطق داخلی خودش ajax_ok/ajax_fail را صدا نزده و برگشته، یک پاسخ پیش‌فرض بده
        echo json_encode(['success' => true]);
    } catch (\Throwable $e) {
        navarakar_log('error', $endpointName . ' failed', [
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطای غیرمنتظره رخ داد. جزئیات در لاگ سیستم (پنل مدیریت ← لاگ سیستم) ثبت شد.',
        ], JSON_UNESCAPED_UNICODE);
    }
}
