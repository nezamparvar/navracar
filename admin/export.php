<?php
/**
 * export.php — خروجی اکسل (CSV با پشتیبانی کامل فارسی) از درخواست‌ها یا محاسبات
 * این فایل CSV با BOM تولید می‌کند که در Excel/Google Sheets فارسی را درست نمایش می‌دهد.
 */
require 'auth-check.php';
require '../db-config.php';

$type = $_GET['type'] ?? 'requests';
$range = $_GET['range'] ?? '';
$q = trim($_GET['q'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$cat = trim($_GET['cat'] ?? '');

if ($range === 'today') { $from = date('Y-m-d'); $to = date('Y-m-d'); }
if ($range === 'month') { $from = date('Y-m-d', strtotime('-30 day')); $to = date('Y-m-d'); }

$where = [];
$params = [];
if ($from !== '') { $where[] = "created_at >= :from"; $params['from'] = $from . ' 00:00:00'; }
if ($to !== '')   { $where[] = "created_at <= :to";   $params['to']   = $to . ' 23:59:59'; }

if ($type === 'requests') {
    if ($q !== '') {
        $where[] = "(name LIKE :q1 OR phone LIKE :q2 OR car_label LIKE :q3)";
        $params['q1'] = '%'.$q.'%';
        $params['q2'] = '%'.$q.'%';
        $params['q3'] = '%'.$q.'%';
    }
    if (current_admin_role() !== 'admin') { $where[] = "assigned_to = :myId"; $params['myId'] = current_admin_id(); }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $stmt = $pdo->prepare("SELECT * FROM quote_requests $whereSql ORDER BY created_at DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $filename = 'navarakar-requests-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM برای نمایش صحیح فارسی در اکسل

    $out = fopen('php://output', 'w');
    fputcsv($out, ['شماره', 'تاریخ ثبت', 'نام', 'تلفن', 'ایمیل', 'خودرو', 'دسته', 'منبع', 'کشور', 'شهر', 'جمع کل نهایی (تومان)', 'وضعیت ایمیل', 'وضعیت پیگیری', 'توضیحات', 'IP']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['created_at'], $r['name'], $r['phone'], $r['email'],
            $r['car_label'], $r['category'], $r['source'], $r['country'], $r['city'], $r['total_with_profit'],
            $r['email_sent'] ? 'ارسال شد' : 'نامشخص', $r['follow_up_status'], $r['notes'], $r['ip_address'],
        ]);
    }
    fclose($out);
    exit;
}

if ($type === 'calculations') {
    if ($cat !== '') { $where[] = "category = :cat"; $params['cat'] = $cat; }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $stmt = $pdo->prepare("SELECT * FROM calculation_logs $whereSql ORDER BY created_at DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $filename = 'navarakar-calculations-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'شماره', 'تاریخ', 'خودرو', 'دسته', 'قیمت واقعی (درهم)', 'قیمت گمرکی (درهم)',
        'نرخ ارز آزاد', 'نرخ ارز گمرک', 'حمل دریایی (درهم)', 'مجوزها (درهم)', 'انبارداری (تومان)',
        'جمع ترخیص گمرکی', 'جمع پلاک', 'جمع بدون سود', 'سود خدمات', 'جمع کل نهایی', 'IP',
    ]);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['created_at'], $r['car_label'], $r['category'],
            $r['real_price_aed'], $r['customs_price_aed'], $r['free_rate'], $r['customs_rate'],
            $r['sea_freight_aed'], $r['permits_aed'], $r['storage_toman'],
            $r['sum_customs'], $r['sum_plate'], $r['total_no_profit'], $r['service_profit'],
            $r['total_with_profit'], $r['ip_address'],
        ]);
    }
    fclose($out);
    exit;
}

http_response_code(400);
echo 'نوع خروجی نامعتبر است.';
