<?php
/**
 * create-admin.php — ساخت یا بازنشانی حساب کاربر پنل (مدیر یا کارشناس فروش)
 *
 * روش استفاده:
 *   create-admin.php?u=USERNAME&p=YOUR_STRONG_PASSWORD&role=admin&name=نام کامل
 *
 * role می‌تواند admin (دسترسی کامل + مدیریت کاربران) یا sales (فقط فرم‌های الحاق‌شده به خودش) باشد.
 * اگر role مشخص نشود، پیش‌فرض admin است.
 *
 * ⚠️ بسیار مهم: بلافاصله بعد از اجرای موفق، این فایل را از روی سرور حذف کنید.
 */

require 'db-config.php';

$username = trim($_GET['u'] ?? '');
$password = trim($_GET['p'] ?? '');
$role = trim($_GET['role'] ?? 'admin');
$fullName = trim($_GET['name'] ?? '');

header('Content-Type: text/html; charset=utf-8');

if ($username === '' || $password === '') {
    echo 'استفاده صحیح: create-admin.php?u=USERNAME&p=PASSWORD&role=admin&name=نام کامل';
    exit;
}
if (strlen($password) < 6) {
    echo 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    exit;
}
if (!in_array($role, ['admin', 'sales'], true)) {
    echo 'role باید admin یا sales باشد.';
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO admin_users (username, password_hash, full_name, role) VALUES (:u, :h, :n, :r)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), full_name = VALUES(full_name), role = VALUES(role)"
);
$stmt->execute(['u' => $username, 'h' => $hash, 'n' => $fullName ?: null, 'r' => $role]);

echo 'حساب «' . htmlspecialchars($username) . '» با نقش «' . htmlspecialchars($role) . '» با موفقیت ثبت/به‌روزرسانی شد. ورود از آدرس admin/login.php<br><br>'
   . '<b style="color:red;">اگر این آخرین حسابی است که نیاز داشتید بسازید، اکنون این فایل (create-admin.php) را حتماً از روی سرور حذف کنید.</b>';
