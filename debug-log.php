<?php
/**
 * debug-log.php — سیستم لاگ متمرکز فعالیت‌ها و خطاهای سایت
 * چون ممکن است خودِ دیتابیس علت مشکل باشد، این لاگ روی فایل نوشته می‌شود
 * (نه دیتابیس) تا همیشه در دسترس باشد، حتی وقتی چیز دیگری خراب است.
 *
 * استفاده در هر فایل PHP دیگر:
 *   require_once __DIR__ . '/debug-log.php';
 *   navarakar_log('info', 'یک فرم جدید ثبت شد', ['id' => $newId]);
 *   navarakar_log('error', 'ثبت ناموفق بود', ['error' => $e->getMessage()]);
 *
 * مشاهده لاگ از پنل مدیریت: admin/activity-log.php (فقط مدیر)
 */

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

define('NAVARAKAR_LOG_DIR', __DIR__ . '/logs');
define('NAVARAKAR_LOG_FILE', NAVARAKAR_LOG_DIR . '/activity.log');
define('NAVARAKAR_LOG_MAX_BYTES', 5 * 1024 * 1024); // 5MB — بعد از این حجم، لاگ چرخش می‌کند

function navarakar_log($level, $message, $context = []) {
    try {
        if (!is_dir(NAVARAKAR_LOG_DIR)) {
            @mkdir(NAVARAKAR_LOG_DIR, 0755, true);
            // جلوگیری از دسترسی مستقیم به فایل لاگ از طریق مرورگر
            @file_put_contents(NAVARAKAR_LOG_DIR . '/.htaccess', "Require all denied\nDeny from all\n");
        }

        // چرخش ساده: اگر فایل خیلی بزرگ شد، آرشیو کن
        if (file_exists(NAVARAKAR_LOG_FILE) && filesize(NAVARAKAR_LOG_FILE) > NAVARAKAR_LOG_MAX_BYTES) {
            @rename(NAVARAKAR_LOG_FILE, NAVARAKAR_LOG_FILE . '.' . date('Ymd_His') . '.old');
        }

        $time = date('Y-m-d H:i:s');
        $uri = $_SERVER['REQUEST_URI'] ?? ($argv[0] ?? 'cli');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
        $levelUpper = strtoupper($level);

        $line = "[$time] [$levelUpper] [$ip] [$uri] $message";
        if (!empty($context)) {
            $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        $line .= "\n";

        @file_put_contents(NAVARAKAR_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    } catch (\Throwable $e) {
        // لاگ نباید هرگز خودش باعث خرابی درخواست اصلی شود
    }
}

/** خواندن N خط آخر لاگ برای نمایش در پنل مدیریت */
function navarakar_log_tail($lines = 300) {
    if (!file_exists(NAVARAKAR_LOG_FILE)) return [];
    $all = @file(NAVARAKAR_LOG_FILE, FILE_IGNORE_NEW_LINES);
    if (!$all) return [];
    return array_slice($all, -$lines);
}
