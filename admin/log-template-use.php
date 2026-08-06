<?php
/**
 * log-template-use.php — ثبت اینکه کدام قالب پیام برای کدام سرنخ کپی شد
 * (برای تاریخچه پیگیری CRM)
 */
require 'auth-check.php';
require '../db-config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $leadId = (int)($data['leadId'] ?? 0);
    $templateId = (int)($data['templateId'] ?? 0);
    if ($leadId <= 0 || $templateId <= 0) { echo json_encode(['success' => false]); exit; }

    $stmt = $pdo->prepare("SELECT title FROM message_templates WHERE id = :id");
    $stmt->execute(['id' => $templateId]);
    $tpl = $stmt->fetch();
    $title = $tpl ? $tpl['title'] : ('#' . $templateId);

    $pdo->prepare("INSERT INTO lead_activities (request_id, admin_user_id, activity_type, note) VALUES (:rid,:uid,'note',:note)")
        ->execute(['rid' => $leadId, 'uid' => current_admin_id(), 'note' => 'قالب پیام «' . $title . '» کپی شد']);

    echo json_encode(['success' => true]);
} catch (\Throwable $e) {
    if (function_exists('navarakar_log')) { navarakar_log('error', 'log-template-use.php failed', ['error' => $e->getMessage()]); }
    echo json_encode(['success' => false]);
}
