<?php
require 'auth-check.php';
require_admin_role();
require '../db-config.php';
$activePage = 'templates';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'custom');
        $body = trim($_POST['body'] ?? '');

        if ($title === '' || $body === '') {
            $error = 'عنوان و متن قالب الزامی است.';
        } elseif ($id > 0) {
            $pdo->prepare("UPDATE message_templates SET title=:t, category=:c, body=:b WHERE id=:id")
                ->execute(['t' => $title, 'c' => $category, 'b' => $body, 'id' => $id]);
            $message = 'قالب به‌روزرسانی شد.';
        } else {
            $pdo->prepare("INSERT INTO message_templates (title, category, body, created_by) VALUES (:t,:c,:b,:uid)")
                ->execute(['t' => $title, 'c' => $category, 'b' => $body, 'uid' => current_admin_id()]);
            $message = 'قالب جدید ساخته شد.';
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE message_templates SET is_active = 1 - is_active WHERE id = :id")->execute(['id' => $id]);
        $message = 'وضعیت قالب تغییر کرد.';
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM message_templates WHERE id = :id")->execute(['id' => $id]);
        $message = 'قالب حذف شد.';
    }
}

$templates = $pdo->query("SELECT * FROM message_templates ORDER BY category, id")->fetchAll();
$categories = [
    'initial_response' => 'پاسخ اولیه',
    'car_list' => 'لیست خودرو',
    'cost_breakdown' => 'برآورد هزینه',
    'contract_draft' => 'پیش‌نویس قرارداد',
    'follow_up_hot' => 'پیگیری داغ',
    'follow_up_warm' => 'پیگیری معمولی',
    'follow_up_cold' => 'پیگیری سرد',
    'custom' => 'سفارشی',
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>قالب‌های پیام | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
<style>
  .tpl-form textarea{width:100%;min-height:110px;padding:12px 14px;border-radius:11px;border:1.5px solid var(--border);font-family:var(--font);font-size:.9rem;background:var(--surface-alt);}
  .tpl-form input,.tpl-form select{width:100%;padding:11px 13px;border-radius:10px;border:1.5px solid var(--border);font-family:var(--font);font-size:.9rem;background:var(--surface-alt);margin-bottom:12px;}
  .tpl-vars{font-size:.74rem;color:var(--ink-soft);background:var(--surface-alt);border-radius:9px;padding:9px 12px;margin-bottom:12px;line-height:1.9;}
  .tpl-card{border:1px solid var(--border);border-radius:14px;padding:14px 16px;margin-bottom:12px;background:var(--surface);}
  .tpl-card.inactive{opacity:.55;}
  .tpl-card-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;}
  .tpl-card .tpl-title{font-weight:800;font-size:.92rem;}
  .tpl-card .tpl-body{font-size:.82rem;color:var(--ink-soft);white-space:pre-wrap;background:var(--surface-alt);border-radius:8px;padding:10px 12px;margin:8px 0;}
  .tpl-actions{display:flex;gap:8px;flex-wrap:wrap;}
  .msg-ok{background:#DCFCE7;color:#166534;padding:10px 14px;border-radius:10px;font-size:.86rem;margin-bottom:14px;}
  .msg-err{background:#FEE2E2;color:#991B1B;padding:10px 14px;border-radius:10px;font-size:.86rem;margin-bottom:14px;}
</style>
</head>
<body>
<?php include 'partials-header.php'; ?>
<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    قالب‌های پیام (فقط مدیر)
  </h1>
  <p class="page-sub">این قالب‌ها در صفحه جزئیات هر سرنخ، آماده کپی برای کارشناسان فروش نمایش داده می‌شوند.</p>

  <?php if ($message): ?><div class="msg-ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <h2>افزودن قالب جدید</h2>
    <form method="post" class="tpl-form">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="0">
      <div class="tpl-vars">
        متغیرهای قابل‌استفاده: <code>{{customer_name}}</code> <code>{{phone}}</code> <code>{{car_model}}</code>
        <code>{{total_price}}</code> <code>{{salesperson_name}}</code> <code>{{official_channels}}</code> <code>{{company_name}}</code>
      </div>
      <input type="text" name="title" placeholder="عنوان قالب" required>
      <select name="category">
        <?php foreach ($categories as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
      </select>
      <textarea name="body" placeholder="متن پیام..." required></textarea>
      <button type="submit" class="btn amber" style="margin-top:12px;">ثبت قالب</button>
    </form>
  </div>

  <div class="card">
    <h2>قالب‌های موجود (<?= count($templates) ?>)</h2>
    <?php if (empty($templates)): ?>
      <div class="empty-state">هنوز قالبی ثبت نشده.</div>
    <?php else: foreach ($templates as $t): ?>
      <div class="tpl-card <?= $t['is_active'] ? '' : 'inactive' ?>">
        <div class="tpl-card-top">
          <span class="tpl-title"><?= htmlspecialchars($t['title']) ?></span>
          <span class="pill"><?= htmlspecialchars($categories[$t['category']] ?? $t['category']) ?></span>
        </div>
        <div class="tpl-body"><?= nl2br(htmlspecialchars($t['body'])) ?></div>
        <div class="tpl-actions">
          <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <button type="submit" class="btn" style="background:#5B6478;"><?= $t['is_active'] ? 'غیرفعال کردن' : 'فعال کردن' ?></button>
          </form>
          <form method="post" style="display:inline;" onsubmit="return confirm('حذف این قالب مطمئنید؟');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <button type="submit" class="btn" style="background:#DC2626;">حذف</button>
          </form>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</main>
</body>
</html>
