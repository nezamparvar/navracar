<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'vin';

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = [];
$params = [];
if ($from !== '') { $where[] = "created_at >= :from"; $params['from'] = $from . ' 00:00:00'; }
if ($to !== '')   { $where[] = "created_at <= :to";   $params['to']   = $to . ' 23:59:59'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) c FROM vin_checks $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetch()['c'];
$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM vin_checks $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$usCount = $pdo->query("SELECT COUNT(*) c FROM vin_checks WHERE verdict LIKE '%آمریکا%'")->fetch()['c'];

function qbuild3($extra) { return '?' . http_build_query(array_merge($_GET, $extra)); }
function verdictPill($v) {
    if (strpos($v, 'غیرمجاز') !== false) return 'no';
    if ($v === 'مجاز') return 'ok';
    return '';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>استعلام‌های شماره شاسی | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
</head>
<body>
<?php include 'partials-header.php'; ?>
<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 4v16"/></svg>
    استعلام‌های شماره شاسی (VIN)
  </h1>
  <p class="page-sub">مجموع <?= $total ?> استعلام — از این تعداد <?= (int)$usCount ?> مورد ساخت/وارداتی آمریکا تشخیص داده شده (غیرمجاز).</p>

  <div class="card">
    <form method="get" class="filter-row">
      <div class="field"><label>از تاریخ</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></div>
      <div class="field"><label>تا تاریخ</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></div>
      <button type="submit" class="btn">اعمال فیلتر</button>
      <a href="vin-checks.php" class="btn" style="background:#6B6584;">پاک کردن</a>
    </form>

    <?php if (empty($rows)): ?>
      <div class="empty-state">هنوز استعلامی ثبت نشده.</div>
    <?php else: ?>
    <div class="tbl-wrap">
      <table class="data-table">
        <thead><tr><th>تاریخ</th><th>VIN</th><th>برند</th><th>مدل</th><th>سال</th><th>کشور</th><th>نتیجه</th><th>موقعیت کاربر</th><th>منبع تشخیص</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="تاریخ"><?= htmlspecialchars(substr($r['created_at'],0,16)) ?></td>
            <td data-label="VIN" class="num-font" style="font-size:.76rem;"><?= htmlspecialchars($r['vin']) ?></td>
            <td data-label="برند"><?= htmlspecialchars($r['make'] ?: '-') ?></td>
            <td data-label="مدل"><?= htmlspecialchars($r['model'] ?: '-') ?></td>
            <td data-label="سال"><?= htmlspecialchars($r['model_year'] ?: '-') ?></td>
            <td data-label="کشور"><?= htmlspecialchars($r['plant_country'] ?: '-') ?></td>
            <td data-label="نتیجه"><span class="pill <?= verdictPill($r['verdict']) ?>"><?= htmlspecialchars($r['verdict']) ?></span></td>
            <td data-label="موقعیت کاربر"><?= htmlspecialchars(trim(($r['city']?:'').($r['city']&&$r['country']?'، ':'').($r['country']?:'')) ?: '-') ?></td>
            <td data-label="منبع تشخیص"><?= $r['source']==='nhtsa' ? 'NHTSA' : 'کد شاسی (تخمینی)' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="pagination">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <?php if ($p == $page): ?><span class="current"><?= $p ?></span>
        <?php else: ?><a href="<?= qbuild3(['page'=>$p]) ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
