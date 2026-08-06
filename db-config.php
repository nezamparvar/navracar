<?php
/**
 * db-config.php — تنظیمات اتصال به پایگاه داده ناوراکار
 * این فایل باید همیشه include شود، نه مستقیم باز شود.
 */

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

$debugLogPath = __DIR__ . '/debug-log.php';
if (file_exists($debugLogPath)) {
    require_once $debugLogPath;
} elseif (!function_exists('navarakar_log')) {
    // اگر debug-log.php هنوز آپلود/جابه‌جا نشده، یک نسخه بی‌اثر تعریف می‌کنیم
    // تا کل سایت به‌خاطر یک فایل گم‌شده از کار نیفتد.
    function navarakar_log($level, $message, $context = []) { /* no-op — logs/activity.log هنوز فعال نیست */ }
}

$DB_HOST = 'localhost';
$DB_NAME = 'navralim_cal';      // نام دیتابیس
$DB_USER = 'navralim_car';      // یوزرنیم دیتابیس
$DB_PASS = 'TxJadq@ZqG0C8U(D';  // پسورد دیتابیس

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true, // اجازه می‌دهد یک پارامتر نام‌گذاری‌شده چند بار در یک کوئری استفاده شود
        ]
    );
} catch (PDOException $e) {
    navarakar_log('error', 'اتصال به پایگاه داده ناموفق بود', ['error' => $e->getMessage()]);
    http_response_code(500);
    if (php_sapi_name() !== 'cli' && strpos($_SERVER['REQUEST_URI'] ?? '', 'admin') === false) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['success' => false, 'message' => 'خطا در اتصال به پایگاه داده.']));
    }
    die('خطا در اتصال به پایگاه داده. لطفاً مشخصات db-config.php را بررسی کنید. جزئیات در logs/activity.log ثبت شد.');
}
