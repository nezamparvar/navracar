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

// بعد از آپلود روی سرور، این سه خط را با مشخصات واقعی دیتابیستان پر کنید.
// این فایل عمداً بدون مقدار در گیت نگهداری می‌شود تا رمز دیتابیس هیچ‌وقت commit نشود؛
// آپلودهای بعدی همین فایل را جایگزین می‌کنند، پس این سه خط را دوباره پر کنید.
$DB_HOST = 'localhost';
$DB_NAME = '';
$DB_USER = '';
$DB_PASS = '';

if ($DB_NAME === '' || $DB_USER === '') {
    http_response_code(500);
    die('مشخصات دیتابیس وارد نشده. فایل db-config.php را روی سرور باز کنید و $DB_NAME / $DB_USER / $DB_PASS را پر کنید.');
}

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
