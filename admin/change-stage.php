<?php
/**
 * change-stage.php — تغییر مرحله پایپ‌لاین یک سرنخ (برای درگ‌اند‌دراپ کانبان)
 * هم مدیر هم کارشناس فروش می‌توانند مرحله سرنخ‌های خودشان را عوض کنند؛
 * کارشناس فروش فقط روی سرنخ‌های الحاق‌شده به خودش اجازه دارد.
 */
require 'auth-check.php';
require '../db-config.php';
header('Content-Type: application/json; charset=utf-8');

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$myId = current_admin_id();
$myRole = current_admin_role();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'روش درخواست مجاز نیست.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $leadId = (int)($data['leadId'] ?? 0);
    $stageId = (int)($data['stageId'] ?? 0);
    $lossReason = trim($data['lossReason'] ?? '');

    if ($leadId <= 0 || $stageId <= 0) {
        echo json_encode(['success' => false, 'message' => 'داده نامعتبر است.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = :id");
    $stmt->execute(['id' => $leadId]);
    $lead = $stmt->fetch();
    if (!$lead) {
        echo json_encode(['success' => false, 'message' => 'سرنخ یافت نشد.']);
        exit;
    }
    if ($myRole !== 'admin' && (int)$lead['assigned_to'] !== $myId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'این سرنخ به شما الحاق نشده است.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM pipeline_stages WHERE id = :id");
    $stmt->execute(['id' => $stageId]);
    $stage = $stmt->fetch();
    if (!$stage) {
        echo json_encode(['success' => false, 'message' => 'مرحله نامعتبر است.']);
        exit;
    }

    // انتقال به «از دست رفته» الزاماً باید دلیل داشته باشد
    if ($stage['slug'] === 'lost' && $lossReason === '') {
        echo json_encode(['success' => false, 'message' => 'برای انتقال به «از دست رفته» انتخاب دلیل الزامی است.', 'needsLossReason' => true]);
        exit;
    }

    $pdo->prepare("UPDATE quote_requests SET current_stage_id = :sid, loss_reason = :lr WHERE id = :id")
        ->execute(['sid' => $stageId, 'lr' => $stage['slug'] === 'lost' ? $lossReason : null, 'id' => $leadId]);

    $note = 'تغییر مرحله به «' . $stage['name'] . '»' . ($lossReason ? ' — دلیل: ' . $lossReason : '');
    $pdo->prepare("INSERT INTO lead_activities (request_id, admin_user_id, activity_type, note) VALUES (:rid,:uid,'status_change',:note)")
        ->execute(['rid' => $leadId, 'uid' => $myId, 'note' => $note]);

    if (function_exists('navarakar_log')) {
        navarakar_log('info', 'تغییر مرحله پایپ‌لاین', ['lead' => $leadId, 'stage' => $stage['slug']]);
    }

    echo json_encode(['success' => true]);
} catch (\Throwable $e) {
    if (function_exists('navarakar_log')) {
        navarakar_log('error', 'change-stage.php failed', ['error' => $e->getMessage()]);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطای غیرمنتظره رخ داد.']);
}
