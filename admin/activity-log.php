<?php
require 'auth-check.php';
require_admin_role();
require '../db-config.php';
$activePage = 'activitylog';

$filter = trim($_GET['level'] ?? '');
$search = trim($_GET['q'] ?? '');

$lines = function_exists('navarakar_log_tail') ? navarakar_log_tail(1000) : [];
$lines = array_reverse($lines); // جدیدترین بالا

if ($filter !== '') {
    $lines = array_filter($lines, fn($l) => stripos($l, '[' . strtoupper($filter) . ']') !== false);
}
if ($search !== '') {
    $lines = array_filter($lines, fn($l) => mb_stripos($l, $search) !== false);
}
$lines = array_slice($lines, 0, 300);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لاگ فعالیت سایت | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
<style>
  .log-line{font-family:'Courier New',monospace;font-size:.78rem;padding:9px 12px;border-radius:8px;margin-bottom:5px;background:var(--surface-alt);white-space:pre-wrap;word-break:break-all;}
  .log-line.err{background:#FEE2E2;color:#991B1B;}
  .log-line.info{background:#EDE9F7;color:var(--primary-dark);}
</style>
</head>
<body>
<?php include 'partials-header.php'; ?>
<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
    لاگ فعالیت سایت
  </h1>
  <p class="page-sub">همه رویدادها و خطاهای مهم سیستم اینجا ثبت می‌شوند — ۳۰۰ رکورد آخر (از فایل logs/activity.log).</p>

  <div class="card">
    <form method="get" class="filter-row">
      <div class="field"><label>جستجو در متن</label><input type="text" name="q" value="<?= htmlspecialchars($search) ?>"></div>
      <div class="field">
        <label>سطح</label>
        <select name="level">
          <option value="">همه</option>
          <option value="error" <?= $filter==='error'?'selected':'' ?>>فقط خطاها</option>
          <option value="info" <?= $filter==='info'?'selected':'' ?>>فقط اطلاعاتی</option>
        </select>
      </div>
      <button type="submit" class="btn">اعمال فیلتر</button>
      <a href="activity-log.php" class="btn" style="background:#6B6584;">پاک کردن</a>
    </form>

    <?php if (!function_exists('navarakar_log_tail')): ?>
      <div class="empty-state">فایل <code>debug-log.php</code> در پوشه اصلی سایت (کنار db-config.php، نه داخل admin/) پیدا نشد. آن را آپلود کنید تا لاگ فعال شود.</div>
    <?php elseif (empty($lines)): ?>
      <div class="empty-state">هنوز رویدادی ثبت نشده — یا فایل logs/activity.log هنوز ساخته نشده (اولین رویداد آن را می‌سازد).</div>
    <?php else: ?>
      <?php foreach ($lines as $l):
        $cls = (stripos($l, '[ERROR]') !== false) ? 'err' : ((stripos($l, '[INFO]') !== false) ? 'info' : '');
      ?>
        <div class="log-line <?= $cls ?>"><?= htmlspecialchars($l) ?></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
