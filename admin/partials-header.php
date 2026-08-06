<?php
// $activePage باید قبل از include شدن این فایل تعریف شود
$activePage = $activePage ?? '';
$_role = current_admin_role();
$_name = $_SESSION['admin_name'] ?? ($_SESSION['admin_username'] ?? '');
?>
<header class="admin-hd">
  <div class="wrap admin-hd-row">
    <div class="admin-brand">
      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" width="24" height="24"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/><circle cx="7" cy="18.5" r="1.6" fill="#fff" stroke="none"/><circle cx="17" cy="18.5" r="1.6" fill="#fff" stroke="none"/></svg>
      ناوراکار <span class="badge">پنل مدیریت</span>
    </div>
    <nav class="admin-nav">
      <a href="index.php" class="<?= $activePage==='dashboard'?'active':'' ?>">داشبورد</a>
      <a href="kanban.php" class="<?= $activePage==='kanban'?'active':'' ?>">پایپ‌لاین (کانبان)</a>
      <a href="requests.php" class="<?= $activePage==='requests'?'active':'' ?>">درخواست‌ها (لیست)</a>
      <a href="calculations.php" class="<?= $activePage==='calculations'?'active':'' ?>">محاسبات</a>
      <a href="vin-checks.php" class="<?= $activePage==='vin'?'active':'' ?>">شماره‌شاسی‌ها</a>
      <a href="invoices.php" class="<?= $activePage==='invoices'?'active':'' ?>">پیش‌فاکتورها</a>
      <?php if ($_role === 'admin'): ?>
      <a href="templates.php" class="<?= $activePage==='templates'?'active':'' ?>">قالب‌های پیام</a>
      <a href="users.php" class="<?= $activePage==='users'?'active':'' ?>">کاربران</a>
      <a href="activity-log.php" class="<?= $activePage==='activitylog'?'active':'' ?>">لاگ سیستم</a>
      <?php endif; ?>
    </nav>
    <a href="logout.php" class="logout-link">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
      خروج (<?= htmlspecialchars($_name) ?> · <?= $_role==='admin'?'مدیر':'کارشناس فروش' ?>)
    </a>
  </div>
</header>
