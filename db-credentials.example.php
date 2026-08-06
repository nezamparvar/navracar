<?php
/**
 * db-credentials.example.php — نمونه فایل اطلاعات اتصال دیتابیس
 *
 * این فایل را کپی کنید و نامش را به db-credentials.php تغییر دهید،
 * سپس مقادیر واقعی دیتابیستان را داخلش وارد کنید.
 * فایل db-credentials.php هرگز نباید در گیت commit شود (داخل .gitignore قرار دارد)
 * و مستقیم از مرورگر هم باز نمی‌شود.
 */

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

$DB_HOST = 'localhost';
$DB_NAME = 'your_database_name';
$DB_USER = 'your_database_user';
$DB_PASS = 'your_database_password';
