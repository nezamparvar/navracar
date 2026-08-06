<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'requests';
$myId = current_admin_id();
$myRole = current_admin_role();

$q      = trim($_GET['q'] ?? '');
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');
$status = trim($_GET['status'] ?? '');
$assignedFilter = trim($_GET['assigned'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = [];
$params = [];

// دسترسی CRM: کارشناس فروش فقط فرم‌های الحاق‌شده به خودش را می‌بیند
if ($myRole !== 'admin') {
    $where[] = "assigned_to = :myId";
    $params['myId'] = $myId;
} elseif ($assignedFilter === 'unassigned') {
    $where[] = "assigned_to IS NULL";
} elseif ($assignedFilter !== '' && $assignedFilter !== 'all') {
    $where[] = "assigned_to = :assignedFilter";
    $params['assignedFilter'] = (int)$assignedFilter;
}

if ($q !== '') {
    $where[] = "(name LIKE :q1 OR phone LIKE :q2 OR car_label LIKE :q3)";
    $params['q1'] = '%' . $q . '%';
    $params['q2'] = '%' . $q . '%';
    $params['q3'] = '%' . $q . '%';
}
if ($from !== '') { $where[] = "created_at >= :from"; $params['from'] = $from . ' 00:00:00'; }
if ($to !== '')   { $where[] = "created_at <= :to";   $params['to']   = $to . ' 23:59:59'; }
if ($status !== '') { $where[] = "follow_up_status = :status"; $params['status'] = $status; }

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) c FROM quote_requests $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetch()['c'];
$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT qr.*, au.username AS assigned_username, au.full_name AS assigned_name
                        FROM quote_requests qr
                        LEFT JOIN admin_users au ON au.id = qr.assigned_to
                        $whereSql ORDER BY qr.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$staffList = $myRole === 'admin' ? $pdo->query("SELECT id, username, full_name FROM admin_users ORDER BY username")->fetchAll() : [];

function qbuild($extra) { return '?' . http_build_query(array_merge($_GET, $extra)); }
function statusPillClass($s) {
    if ($s === 'فروخته شد') return 'ok';
    if ($s === 'بسته - ناموفق') return 'no';
    return '';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>درخواست‌های استعلام (CRM) | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
</head>
<body>
<?php include 'partials-header.php'; ?>

<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/></svg>
    درخواست‌های استعلام قیمت (CRM)
  </h1>
  <p class="page-sub">
    مجموع <?= $total ?> درخواست<?= $myRole !== 'admin' ? ' الحاق‌شده به شما' : '' ?>.
    <a href="lead-new.php" class="btn amber" style="margin-right:10px;">+ ثبت دستی مشتری تماس‌گرفته</a>
    <a href="../lead-form.php" target="_blank" class="btn" style="margin-right:10px;background:#6B6584;">لینک فرم عمومی فروشنده‌ها ↗</a>
  </p>

  <div class="card">
    <form method="get" class="filter-row">
      <div class="field"><label>جستجو (نام، تلفن، خودرو)</label><input type="text" name="q" value="<?= htmlspecialchars($q) ?>"></div>
      <div class="field"><label>از تاریخ</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></div>
      <div class="field"><label>تا تاریخ</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></div>
      <div class="field">
        <label>وضعیت پیگیری</label>
        <select name="status">
          <option value="">همه</option>
          <?php foreach (['باز','در حال پیگیری','فروخته شد','بسته - ناموفق'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($myRole === 'admin'): ?>
      <div class="field">
        <label>الحاق‌شده به</label>
        <select name="assigned">
          <option value="all">همه</option>
          <option value="unassigned" <?= $assignedFilter==='unassigned'?'selected':'' ?>>بدون الحاق</option>
          <?php foreach ($staffList as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $assignedFilter==(string)$s['id']?'selected':'' ?>><?= htmlspecialchars($s['full_name'] ?: $s['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <button type="submit" class="btn">اعمال فیلتر</button>
      <a href="requests.php" class="btn" style="background:#6B6584;">پاک کردن</a>
      <a href="export.php?type=requests&<?= http_build_query(['q'=>$q,'from'=>$from,'to'=>$to]) ?>" class="btn amber">خروجی اکسل</a>
    </form>

    <?php if (empty($rows)): ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/></svg>
        <div>هیچ درخواستی با این فیلتر یافت نشد.</div>
      </div>
    <?php else: ?>
      <div class="tbl-wrap">
        <table class="data-table">
          <thead><tr><th>تاریخ</th><th>نام</th><th>تلفن</th><th>خودرو</th><th>بودجه</th><th>منبع</th><th>موقعیت</th><th>الحاق به</th><th>وضعیت</th><th>تماس بعدی</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
              <td data-label="تاریخ"><?= htmlspecialchars(substr($r['created_at'],0,16)) ?></td>
              <td data-label="نام"><?= htmlspecialchars($r['name']) ?></td>
              <td data-label="تلفن" class="num-font"><?= htmlspecialchars($r['phone']) ?></td>
              <td data-label="خودرو"><?= htmlspecialchars($r['car_label']) ?></td>
              <td data-label="بودجه" style="font-size:.78rem;"><?= htmlspecialchars($r['budget_range'] ?: '-') ?></td>
              <td data-label="منبع"><span class="pill"><?= htmlspecialchars($r['source'] ?: 'سایت') ?></span></td>
              <td data-label="موقعیت"><?= htmlspecialchars(trim(($r['city']?:'').($r['city']&&$r['country']?'، ':'').($r['country']?:'')) ?: '-') ?></td>
              <td data-label="الحاق به"><?= $r['assigned_name'] ? htmlspecialchars($r['assigned_name'] ?: $r['assigned_username']) : '<span style="color:#B4ADCB;">—</span>' ?></td>
              <td data-label="وضعیت"><span class="pill <?= statusPillClass($r['follow_up_status']) ?>"><?= htmlspecialchars($r['follow_up_status'] ?: 'باز') ?></span></td>
              <td data-label="تماس بعدی"><?= $r['next_call_date'] ? htmlspecialchars($r['next_call_date']) : '-' ?></td>
              <td data-label=""><a class="btn" style="padding:6px 12px;font-size:.74rem;" href="request-view.php?id=<?= (int)$r['id'] ?>">جزئیات</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <?php if ($p == $page): ?><span class="current"><?= $p ?></span>
          <?php else: ?><a href="<?= qbuild(['page'=>$p]) ?>"><?= $p ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
