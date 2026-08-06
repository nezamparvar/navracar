<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'calculations';

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to'] ?? '');
$cat  = trim($_GET['cat'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = [];
$params = [];
if ($from !== '') { $where[] = "created_at >= :from"; $params['from'] = $from . ' 00:00:00'; }
if ($to !== '')   { $where[] = "created_at <= :to";   $params['to']   = $to . ' 23:59:59'; }
if ($cat !== '')  { $where[] = "category = :cat";     $params['cat']  = $cat; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) c FROM calculation_logs $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetch()['c'];
$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM calculation_logs $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM calculation_logs WHERE category != '' ORDER BY category")->fetchAll();

function qbuild2($extra) { return '?' . http_build_query(array_merge($_GET, $extra)); }
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>محاسبات ثبت‌شده | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
</head>
<body>
<?php include 'partials-header.php'; ?>

<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 10h8M8 14h4"/></svg>
    محاسبات ثبت‌شده
  </h1>
  <p class="page-sub">مجموع <?= $total ?> محاسبه — شامل بازدیدکنندگانی که مشخصات تماس ثبت نکرده‌اند.</p>

  <div class="card">
    <form method="get" class="filter-row">
      <div class="field"><label>از تاریخ</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></div>
      <div class="field"><label>تا تاریخ</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></div>
      <div class="field">
        <label>دسته خودرو</label>
        <select name="cat">
          <option value="">همه</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= htmlspecialchars($c['category']) ?>" <?= $c['category']===$cat?'selected':'' ?>><?= htmlspecialchars($c['category']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn">اعمال فیلتر</button>
      <a href="calculations.php" class="btn" style="background:#5B6478;">پاک کردن</a>
      <a href="export.php?type=calculations&<?= http_build_query(['from'=>$from,'to'=>$to,'cat'=>$cat]) ?>" class="btn amber">خروجی اکسل همین لیست</a>
    </form>

    <?php if (empty($rows)): ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="2" width="16" height="20" rx="2"/></svg>
        <div>محاسبه‌ای با این فیلتر یافت نشد.</div>
      </div>
    <?php else: ?>
      <div class="tbl-wrap">
        <table class="data-table">
          <thead><tr><th>تاریخ</th><th>خودرو</th><th>دسته</th><th>موقعیت</th><th>جمع بدون سود</th><th>سود خدمات</th><th>جمع کل نهایی</th><th>IP</th></tr></thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
              <td data-label="تاریخ"><?= htmlspecialchars(substr($r['created_at'],0,16)) ?></td>
              <td data-label="خودرو"><?= htmlspecialchars($r['car_label'] ?: '-') ?></td>
              <td data-label="دسته"><span class="pill"><?= htmlspecialchars($r['category']) ?></span></td>
              <td data-label="موقعیت"><?= htmlspecialchars(trim(($r['city']?:'').($r['city']&&$r['country']?'، ':'').($r['country']?:'')) ?: '-') ?></td>
              <td data-label="جمع بدون سود" class="num-font"><?= number_format($r['total_no_profit']) ?></td>
              <td data-label="سود خدمات" class="num-font"><?= number_format($r['service_profit']) ?></td>
              <td data-label="جمع کل نهایی" class="amt num-font"><?= number_format($r['total_with_profit']) ?></td>
              <td data-label="IP" class="num-font" style="font-size:.74rem;color:var(--ink-soft);"><?= htmlspecialchars($r['ip_address']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <?php if ($p == $page): ?><span class="current"><?= $p ?></span>
          <?php else: ?><a href="<?= qbuild2(['page'=>$p]) ?>"><?= $p ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
