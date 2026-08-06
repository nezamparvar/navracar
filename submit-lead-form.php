<?php
/**
 * submit-lead-form.php — دریافت و ثبت گزارش تماس فروش از فرم عمومی (بدون لاگین)
 * فروشنده منتخب به‌عنوان assigned_to و created_by ثبت می‌شود تا بلافاصله
 * در پنل مدیریت زیر نام همان کارشناس دیده شود.
 */
require __DIR__ . '/ajax-bootstrap.php';

function lf_txt($v, $len = 255) {
    $v = str_replace(["\r", "\n"], ' ', (string)($v ?? ''));
    return mb_substr(trim($v), 0, $len);
}

ajax_handle(function () use ($pdo) {
    navarakar_log('info', 'submit-lead-form.php درخواست دریافت شد');
    ajax_require_post();
    $data = ajax_input();

    // --- محافظت ساده در برابر اسپم/ربات ---
    if (!empty($data['website'])) {
        // فیلد تله برای ربات‌ها پر شده — بی‌سروصدا موفقیت نمایش بده تا ربات متوجه نشود
        ajax_ok([], 'گزارش با موفقیت ثبت شد.');
    }
    $loadedAt = (float)($data['formLoadedAt'] ?? 0);
    if ($loadedAt > 0 && (microtime(true) * 1000 - $loadedAt) < 1500) {
        ajax_fail('لطفاً کمی صبر کنید و دوباره تلاش کنید.');
    }

    $userId   = (int)($data['userId'] ?? 0);
    $name     = lf_txt($data['name'] ?? '');
    $phone    = lf_txt($data['phone'] ?? '', 64);
    $emailRaw = trim((string)($data['email'] ?? ''));
    $email    = filter_var($emailRaw, FILTER_VALIDATE_EMAIL) ? $emailRaw : '';
    $budget   = lf_txt($data['budget'] ?? '', 100);
    $car      = lf_txt($data['carInterest'] ?? '');
    $source   = lf_txt($data['source'] ?? '', 50);
    $status   = lf_txt($data['status'] ?? '', 50);
    $city     = lf_txt($data['city'] ?? '', 100);
    $notes    = trim((string)($data['notes'] ?? ''));
    $nextCallRaw = trim((string)($data['nextCall'] ?? ''));
    $nextCall = preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextCallRaw) ? $nextCallRaw : null;

    if ($userId <= 0 || $name === '' || $phone === '' || $budget === '' || $car === '' || $source === '' || $status === '' || $city === '') {
        ajax_fail('همه فیلدهای الزامی را پر کنید.');
    }

    $stmt = $pdo->prepare("SELECT id, username, full_name FROM admin_users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $staffUser = $stmt->fetch();
    if (!$staffUser) {
        ajax_fail('کارشناس انتخاب‌شده معتبر نیست. لطفاً دوباره از لیست انتخاب کنید.');
    }

    $geo = ajax_geo();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $pdo->prepare(
        "INSERT INTO quote_requests
        (name, phone, email, notes, car_label, category, breakdown_json, totals_json, total_with_profit,
         email_sent, source, budget_range, country, city, assigned_to, created_by, follow_up_status, next_call_date, ip_address)
        VALUES (:name,:phone,:email,:notes,:car,'','[]','{}',0,
         0,:src,:budget,:country,:city,:assigned1,:assigned2,:status,:nextcall,:ip)"
    );
    $stmt->execute([
        'name' => $name, 'phone' => $phone, 'email' => $email, 'notes' => $notes,
        'car' => $car, 'src' => $source, 'budget' => $budget,
        'country' => $geo['country'], 'city' => $city ?: $geo['city'],
        'assigned1' => $staffUser['id'], 'assigned2' => $staffUser['id'],
        'status' => $status, 'nextcall' => $nextCall, 'ip' => $ip,
    ]);
    $newId = $pdo->lastInsertId();

    $staffName = $staffUser['full_name'] ?: $staffUser['username'];
    $pdo->prepare("INSERT INTO lead_activities (request_id, admin_user_id, activity_type, note) VALUES (:rid,:uid,'note',:note)")
        ->execute(['rid' => $newId, 'uid' => $staffUser['id'], 'note' => 'فرصت جدید از فرم عمومی توسط ' . $staffName . ' ثبت شد (منبع: ' . $source . ')']);

    navarakar_log('info', 'فرم عمومی فروش با موفقیت ثبت شد', ['id' => $newId, 'staff' => $staffName, 'name' => $name, 'phone' => $phone]);

    // اطلاع‌رسانی ایمیلی به مدیر سیستم (اختیاری — نباید باعث شکست کل ثبت شود)
    try {
        $subjectText = 'فرصت فروش جدید ثبت شد: ' . $name . ' — ' . $car;
        $subject = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';
        $body = "<div dir='rtl' style='font-family:Tahoma,Arial,sans-serif;font-size:14px;'>"
            . "<h3>فرصت فروش جدید — ناوراکار</h3>"
            . "<p><b>کارشناس:</b> " . htmlspecialchars($staffName) . "</p>"
            . "<p><b>نام مشتری:</b> " . htmlspecialchars($name) . "</p>"
            . "<p><b>تلفن:</b> " . htmlspecialchars($phone) . "</p>"
            . "<p><b>خودروی مورد نظر:</b> " . htmlspecialchars($car) . "</p>"
            . "<p><b>بودجه:</b> " . htmlspecialchars($budget) . "</p>"
            . "<p><b>شهر:</b> " . htmlspecialchars($city) . "</p>"
            . "<p><b>وضعیت:</b> " . htmlspecialchars($status) . "</p>"
            . ($nextCall ? "<p><b>تماس بعدی:</b> " . htmlspecialchars($nextCall) . "</p>" : "")
            . ($notes ? "<p><b>توضیحات:</b> " . nl2br(htmlspecialchars($notes)) . "</p>" : "")
            . "</div>";
        $fromDomain = $_SERVER['HTTP_HOST'] ?? 'yourdomain.com';
        $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: \xd9\x86\xd8\xa7\xd9\x88\xd8\xb1\xd8\xa7\xda\xa9\xd8\xa7\xd8\xb1 <no-reply@" . $fromDomain . ">\r\n";
        @mail('nezamparvar@gmail.com', $subject, $body, $headers);
    } catch (\Throwable $mailErr) {
        navarakar_log('error', 'ارسال ایمیل اطلاع‌رسانی فرصت جدید ناموفق بود', ['error' => $mailErr->getMessage()]);
    }

    ajax_ok(['id' => $newId], 'گزارش با موفقیت ثبت شد.');
}, 'submit-lead-form.php');
