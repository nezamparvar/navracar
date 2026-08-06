<?php
/**
 * send-quote.php — ناوراکار
 * دریافت درخواست استعلام قیمت از فرم سایت: ثبت در پایگاه داده + ارسال ایمیل گزارش
 * به nezamparvar@gmail.com
 *
 * نکته: تابع PHP mail() به تنظیمات سرویس ایمیل هاست شما وابسته است. اگر ایمیل‌ها
 * ارسال نشدند یا اسپم شدند، توصیه می‌شود از PHPMailer + SMTP واقعی استفاده کنید
 * (راهنما در پایین فایل).
 */
require __DIR__ . '/ajax-bootstrap.php';

function sq_clean($v) {
    $v = str_replace(["\r", "\n"], ' ', (string)($v ?? ''));
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

ajax_handle(function () use ($pdo) {
    ajax_require_post();
    $data = ajax_input();

    // --- محافظت ساده در برابر اسپم/ربات ---
    if (!empty($data['website'])) {
        ajax_ok(['id' => 0], 'درخواست با موفقیت ثبت و ارسال شد.');
    }
    $loadedAt = (float)($data['pageLoadedAt'] ?? 0);
    if ($loadedAt > 0 && (microtime(true) * 1000 - $loadedAt) < 1500) {
        ajax_fail('لطفاً کمی صبر کنید و دوباره تلاش کنید.');
    }

    $name     = sq_clean($data['name']     ?? '');
    $phone    = sq_clean($data['phone']    ?? '');
    $notes    = sq_clean($data['notes']    ?? '');
    $car      = sq_clean($data['car']      ?? '');
    $category = sq_clean($data['category'] ?? '');

    $emailRaw = trim((string)($data['email'] ?? ''));
    $email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL) ? $emailRaw : '';

    $breakdown = is_array($data['breakdown'] ?? null) ? $data['breakdown'] : [];
    $totals    = is_array($data['totals']    ?? null) ? $data['totals']    : [];

    if ($name === '' || $phone === '') {
        ajax_fail('نام و شماره تماس الزامی است.');
    }

    // جمع کل نهایی را برای ذخیره عددی در دیتابیس استخراج کن
    $totalWithProfitNumeric = 0;
    foreach ($totals as $label => $val) {
        if (mb_strpos((string)$label, 'نهایی') !== false) {
            $digits = preg_replace('/[^0-9.]/', '', (string)$val);
            $totalWithProfitNumeric = $digits !== '' ? (float)$digits : 0;
        }
    }

    $geo = ajax_geo();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $pdo->prepare(
        "INSERT INTO quote_requests
        (name, phone, email, notes, car_label, category, breakdown_json, totals_json, total_with_profit, email_sent, source, country, city, ip_address)
        VALUES (:name,:phone,:email,:notes,:car,:cat,:bj,:tj,:twp,:es,:src,:country,:city,:ip)"
    );
    $stmt->execute([
        'name'  => $name, 'phone' => $phone, 'email' => $email, 'notes' => $notes,
        'car'   => $car, 'cat'   => $category,
        'bj'    => json_encode($breakdown, JSON_UNESCAPED_UNICODE),
        'tj'    => json_encode($totals, JSON_UNESCAPED_UNICODE),
        'twp'   => $totalWithProfitNumeric, 'es' => 0, 'src' => 'سایت',
        'country' => $geo['country'], 'city' => $geo['city'], 'ip' => $ip,
    ]);
    $insertedId = $pdo->lastInsertId();
    navarakar_log('info', 'درخواست استعلام قیمت ثبت شد', ['id' => $insertedId, 'name' => $name, 'phone' => $phone]);

    // --- ساخت و ارسال ایمیل گزارش (اختیاری — نباید باعث شکست کل ثبت شود) ---
    $emailOk = false;
    try {
        $rowsHtml = '';
        foreach ($breakdown as $row) {
            $rowsHtml .= '<tr>'
                . '<td style="padding:8px 10px;border:1px solid #ddd;">' . sq_clean($row['label'] ?? '') . '</td>'
                . '<td style="padding:8px 10px;border:1px solid #ddd;color:#555;font-size:12px;">' . sq_clean($row['rate'] ?? '') . '</td>'
                . '<td style="padding:8px 10px;border:1px solid #ddd;text-align:left;font-weight:bold;white-space:nowrap;">' . sq_clean($row['amount'] ?? '') . '</td>'
                . '</tr>';
        }
        $totalsHtml = '';
        foreach ($totals as $label => $val) {
            $totalsHtml .= '<tr>'
                . '<td style="padding:9px 10px;border:1px solid #ddd;font-weight:bold;">' . sq_clean($label) . '</td>'
                . '<td style="padding:9px 10px;border:1px solid #ddd;text-align:left;font-weight:bold;">' . sq_clean($val) . '</td>'
                . '</tr>';
        }
        $now = date('Y-m-d H:i');
        $body = '
        <div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;font-size:14px;color:#222;background:#f5f7fc;padding:20px;">
          <div style="max-width:640px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e0e4f0;">
            <div style="background:#171433;color:#fff;padding:16px 22px;">
              <h2 style="margin:0;font-size:18px;">درخواست استعلام قیمت واردات خودرو — ناوراکار</h2>
              <div style="font-size:12px;color:#c9d6ff;margin-top:4px;">تاریخ ثبت: ' . htmlspecialchars($now) . ' — شماره درخواست: #' . (int)$insertedId . '</div>
            </div>
            <div style="padding:20px 22px;">
              <table style="width:100%;border-collapse:collapse;margin-bottom:18px;">
                <tr><td style="padding:6px 0;color:#666;width:140px;">نام مشتری:</td><td style="padding:6px 0;font-weight:bold;">' . $name . '</td></tr>
                <tr><td style="padding:6px 0;color:#666;">شماره تماس:</td><td style="padding:6px 0;font-weight:bold;">' . $phone . '</td></tr>
                <tr><td style="padding:6px 0;color:#666;">ایمیل مشتری:</td><td style="padding:6px 0;">' . ($email ?: '-') . '</td></tr>
                <tr><td style="padding:6px 0;color:#666;">خودروی انتخابی:</td><td style="padding:6px 0;font-weight:bold;">' . $car . '</td></tr>
                <tr><td style="padding:6px 0;color:#666;">دسته خودرو:</td><td style="padding:6px 0;">' . $category . '</td></tr>
                <tr><td style="padding:6px 0;color:#666;vertical-align:top;">توضیحات:</td><td style="padding:6px 0;">' . ($notes ?: '-') . '</td></tr>
              </table>
              <h3 style="color:#171433;font-size:15px;border-top:1px solid #eee;padding-top:14px;">تفکیک هزینه‌ها</h3>
              <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <tr style="background:#EDE9F7;">
                  <th style="padding:8px 10px;border:1px solid #ddd;text-align:right;">شرح هزینه</th>
                  <th style="padding:8px 10px;border:1px solid #ddd;text-align:right;">نرخ</th>
                  <th style="padding:8px 10px;border:1px solid #ddd;text-align:right;">مبلغ</th>
                </tr>
                ' . $rowsHtml . '
              </table>
              <h3 style="color:#171433;font-size:15px;margin-top:18px;">جمع‌بندی</h3>
              <table style="width:100%;border-collapse:collapse;font-size:13px;">' . $totalsHtml . '</table>
              <p style="color:#999;font-size:11px;margin-top:22px;border-top:1px solid #eee;padding-top:12px;">
                این ایمیل به‌صورت خودکار از سامانه محاسبه‌گر هزینه واردات خودرو ناوراکار ارسال شده است.
              </p>
            </div>
          </div>
        </div>';

        $subjectText = 'درخواست استعلام قیمت خودرو: ' . $name . ' — ' . $car;
        $subject = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';
        $fromDomain = $_SERVER['HTTP_HOST'] ?? 'yourdomain.com';
        $headers  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: \xd9\x86\xd8\xa7\xd9\x88\xd8\xb1\xd8\xa7\xda\xa9\xd8\xa7\xd8\xb1 <no-reply@" . $fromDomain . ">\r\n";
        if ($email) { $headers .= "Reply-To: " . $email . "\r\n"; }

        $emailOk = @mail('nezamparvar@gmail.com', $subject, $body, $headers);
        if ($emailOk) {
            $pdo->prepare("UPDATE quote_requests SET email_sent = 1 WHERE id = :id")->execute(['id' => $insertedId]);
        }
    } catch (\Throwable $mailErr) {
        navarakar_log('error', 'ارسال ایمیل درخواست استعلام ناموفق بود', ['error' => $mailErr->getMessage(), 'id' => $insertedId]);
    }

    $msg = $emailOk
        ? 'درخواست با موفقیت ثبت و ارسال شد.'
        : 'درخواست شما ثبت شد؛ اما ارسال ایمیل با تأخیر مواجه شد. کارشناسان از پنل مدیریت آن را می‌بینند.';
    ajax_ok(['id' => $insertedId], $msg);
}, 'send-quote.php');

/*
========================================================================
 در صورتی که mail() روی هاست شما کار نکند یا ایمیل‌ها اسپم شوند، توصیه
 می‌شود از PHPMailer با SMTP واقعی استفاده کنید (composer require phpmailer/phpmailer):

 require 'vendor/autoload.php';
 use PHPMailer\PHPMailer\PHPMailer;
 $mail = new PHPMailer(true);
 $mail->isSMTP();
 $mail->Host       = 'smtp.example.com';
 $mail->SMTPAuth   = true;
 $mail->Username   = 'your-smtp-user@example.com';
 $mail->Password   = 'YOUR_SMTP_PASSWORD';
 $mail->SMTPSecure = 'tls';
 $mail->Port       = 587;
 $mail->CharSet    = 'UTF-8';
 $mail->setFrom('no-reply@example.com', 'ناوراکار');
 $mail->addAddress('nezamparvar@gmail.com');
 $mail->isHTML(true);
 $mail->Subject = $subjectText;
 $mail->Body    = $body;
 $mail->send();
========================================================================
*/
