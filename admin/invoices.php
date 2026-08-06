<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'invoices';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$total = (int)$pdo->query("SELECT COUNT(*) c FROM invoices")->fetch()['c'];
$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$rows = $pdo->query("SELECT inv.*, au.username FROM invoices inv LEFT JOIN admin_users au ON au.id = inv.created_by ORDER BY inv.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();

function statusPill($s) {
    if ($s === 'تایید شده') return 'ok';
    if ($s === 'پیش‌نویس') return 'no';
    return '';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>پیش‌فاکتورها | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
</head>
<body>
<?php include 'partials-header.php'; ?>
<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12h6M9 16h6M9 8h6"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>
    پیش‌فاکتورها و فروش
  </h1>
  <p class="page-sub">مجموع <?= $total ?> پیش‌فاکتور صادرشده.</p>
  <div class="card">
    <div style="margin-bottom:16px;"><a href="invoice-new.php" class="btn amber">+ صدور پیش‌فاکتور جدید</a></div>
    <?php if (empty($rows)): ?>
      <div class="empty-state">هنوز پیش‌فاکتوری صادر نشده.</div>
    <?php else: ?>
    <div class="tbl-wrap">
      <table class="data-table">
        <thead><tr><th>شماره</th><th>تاریخ</th><th>مشتری</th><th>خودرو</th><th>مبلغ قابل‌پرداخت</th><th>وضعیت</th><th>صادرکننده</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="شماره" class="num-font"><?= htmlspecialchars($r['invoice_number']) ?></td>
            <td data-label="تاریخ"><?= htmlspecialchars(substr($r['created_at'],0,10)) ?></td>
            <td data-label="مشتری"><?= htmlspecialchars($r['customer_name']) ?></td>
            <td data-label="خودرو"><?= htmlspecialchars($r['car_label'] ?: '-') ?></td>
            <td data-label="مبلغ قابل‌پرداخت" class="amt num-font"><?= number_format($r['total_amount'] - ($r['discount_amount'] ?? 0)) ?> <?= ($r['currency'] ?? 'toman') === 'aed' ? 'AED' : 'تومان' ?></td>
            <td data-label="وضعیت"><span class="pill <?= statusPill($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
            <td data-label="صادرکننده"><?= htmlspecialchars($r['username'] ?: '-') ?></td>
            <td data-label=""><a class="btn" style="padding:6px 12px;font-size:.74rem;" href="invoice-view.php?id=<?= (int)$r['id'] ?>">مشاهده / چاپ</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="pagination">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <?php if ($p == $page): ?><span class="current"><?= $p ?></span>
        <?php else: ?><a href="?page=<?= $p ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
