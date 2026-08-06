<?php
/**
 * auth-check.php — این فایل در ابتدای هر صفحه پنل مدیریت include می‌شود
 * و در صورتی که مدیر وارد نشده باشد، به صفحه ورود هدایت می‌کند.
 */
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ]);
    session_start();
}

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// جلوگیری از هایجک ساده نشست بعد از عدم فعالیت طولانی (۴ ساعت)
if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > 4 * 3600)) {
    session_unset();
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}
$_SESSION['admin_last_activity'] = time();

/** دسترسی‌های CRM:
 *  admin: دسترسی کامل — می‌تواند فرم‌ها را به هرکس الحاق کند و همه را ببیند.
 *  sales: فقط فرم‌هایی که به خودش الحاق شده را می‌بیند و پیگیری می‌کند — اختیار الحاق ندارد.
 */
function current_admin_id() { return (int)($_SESSION['admin_id'] ?? 0); }
function current_admin_role() { return $_SESSION['admin_role'] ?? 'admin'; }
function is_admin_role() { return current_admin_role() === 'admin'; }
function require_admin_role() {
    if (!is_admin_role()) {
        http_response_code(403);
        die('دسترسی غیرمجاز — این بخش فقط برای مدیر سیستم است.');
    }
}
