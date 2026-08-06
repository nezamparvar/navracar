<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$username = $_SESSION['admin_username'] ?? null;
if ($username) {
    require '../db-config.php';
    if (function_exists('navarakar_log')) {
        navarakar_log('info', 'خروج از پنل مدیریت', ['username' => $username]);
    }
}
session_unset();
session_destroy();
header('Location: login.php');
exit;
