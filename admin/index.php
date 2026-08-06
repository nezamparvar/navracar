<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'dashboard';

// ---------- KPI ها ----------
$todayRequests = $pdo->query("SELECT COUNT(*) c FROM quote_requests WHERE DATE(created_at)=CURDATE()")->fetch()['c'];
$todayCalcs    = $pdo->query("SELECT COUNT(*) c FROM calculation_logs WHERE DATE(created_at)=CURDATE()")->fetch()['c'];
$todayVin      = $pdo->query("SELECT COUNT(*) c FROM vin_checks WHERE DATE(created_at)=CURDATE()")->fetch()['c'];
$unassignedCount = (current_admin_role()==='admin') ? $pdo->query("SELECT COUNT(*) c FROM quote_requests WHERE assigned_to IS NULL")->fetch()['c'] : 0;
$hotLeads = 0;
try {
    if (current_admin_role() === 'admin') {
        $callsToday = $pdo->query("SELECT COUNT(*) c FROM quote_requests WHERE next_call_date = CURDATE()")->fetch()['c'];
        $hotLeads = $pdo->query("SELECT COUNT(*) c FROM quote_requests WHERE temperature = 'hot' AND (current_stage_id IS NULL OR current_stage_id NOT IN (SELECT id FROM pipeline_stages WHERE slug IN ('delivered','lost')))")->fetch()['c'];
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) c FROM quote_requests WHERE next_call_date = CURDATE() AND assigned_to = :id");
        $stmt->execute(['id' => current_admin_id()]);
        $callsToday = $stmt->fetch()['c'];
        $stmt = $pdo->prepare("SELECT COUNT(*) c FROM quote_requests WHERE temperature = 'hot' AND assigned_to = :id AND (current_stage_id IS NULL OR current_stage_id NOT IN (SELECT id FROM pipeline_stages WHERE slug IN ('delivered','lost')))");
        $stmt->execute(['id' => current_admin_id()]);
        $hotLeads = $stmt->fetch()['c'];
    }
} catch (\Throwable $e) {
    $callsToday = $callsToday ?? 0;
}
$weekRequests  = $pdo->query("SELECT COUNT(*) c FROM quote_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch()['c'];
$monthRequests = $pdo->query("SELECT COUNT(*) c FROM quote_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch()['c'];
$totalRequests = $pdo->query("SELECT COUNT(*) c FROM quote_requests")->fetch()['c'];
$totalCalcs    = $pdo->query("SELECT COUNT(*) c FROM calculation_logs")->fetch()['c'];
$avgTotal      = $pdo->query("SELECT AVG(total_with_profit) a FROM quote_requests WHERE total_with_profit > 0")->fetch()['a'];

// ---------- نمودار روزانه (۱۴ روز اخیر) ----------
$daily = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $daily[$d] = ['date' => $d, 'requests' => 0, 'calcs' => 0];
}
$reqRows = $pdo->query("SELECT DATE(created_at) d, COUNT(*) c FROM quote_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d")->fetchAll();
foreach ($reqRows as $r) { if (isset($daily[$r['d']])) $daily[$r['d']]['requests'] = (int)$r['c']; }
$calcRows = $pdo->query("SELECT DATE(created_at) d, COUNT(*) c FROM calculation_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d")->fetchAll();
foreach ($calcRows as $r) { if (isset($daily[$r['d']])) $daily[$r['d']]['calcs'] = (int)$r['c']; }

// ---------- توزیع دسته خودرو ----------
$catDist = $pdo->query("SELECT category, COUNT(*) c FROM calculation_logs WHERE category IS NOT NULL AND category != '' GROUP BY category ORDER BY c DESC")->fetchAll();

// ---------- پرتکرارترین خودروهای درخواستی ----------
$topCars = $pdo->query("SELECT car_label, COUNT(*) c FROM calculation_logs WHERE car_label IS NOT NULL AND car_label NOT IN ('','مشخص نشده') GROUP BY car_label ORDER BY c DESC LIMIT 8")->fetchAll();

// ---------- آخرین درخواست‌های استعلام ----------
if (current_admin_role() === 'admin') {
    $recentRequests = $pdo->query("SELECT id, created_at, name, phone, car_label, total_with_profit, email_sent FROM quote_requests ORDER BY created_at DESC LIMIT 8")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT id, created_at, name, phone, car_label, total_with_profit, email_sent FROM quote_requests WHERE assigned_to = :id ORDER BY created_at DESC LIMIT 8");
    $stmt->execute(['id' => current_admin_id()]);
    $recentRequests = $stmt->fetchAll();
}

$palette = ['#2952E0','#FF8A1E','#8B5CF6','#16A34A','#5B6478','#9FB2FF','#D9690A','#0EA5E9'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>داشبورد | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
</head>
<body>
<?php include 'partials-header.php'; ?>

<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
    داشبورد مدیریت
  </h1>
  <p class="page-sub">نمای کلی از درخواست‌های استعلام قیمت و محاسبات انجام‌شده روی سایت.</p>

  <div class="card" style="background:linear-gradient(120deg,var(--primary-dark),var(--primary));color:#fff;">
    <h2 style="color:#fff;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg> فرم عمومی ثبت تماس فروش</h2>
    <p style="color:#D6CFF0;font-size:.88rem;margin:0 0 14px;">این لینک را برای فروشنده‌ها بفرستید تا بدون نیاز به ورود، گزارش تماس‌های جدید را ثبت کنند.</p>
    <a href="../lead-form.php" target="_blank" class="btn amber">مشاهده فرم ↗</a>
  </div>

  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">درخواست‌های امروز</span>
        <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/></svg></div>
      </div>
      <div class="kpi-val num-font"><?= (int)$todayRequests ?></div>
      <div class="kpi-note">درخواست با مشخصات تماس</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">محاسبات امروز</span>
        <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 10h8M8 14h4"/></svg></div>
      </div>
      <div class="kpi-val num-font"><?= (int)$todayCalcs ?></div>
      <div class="kpi-note">با یا بدون ثبت مشخصات</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">درخواست‌های ۷ روز اخیر</span>
        <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
      </div>
      <div class="kpi-val num-font"><?= (int)$weekRequests ?></div>
      <div class="kpi-note">۳۰ روز اخیر: <?= (int)$monthRequests ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">میانگین جمع کل هزینه</span>
        <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
      </div>
      <div class="kpi-val num-font"><?= number_format($avgTotal ?? 0) ?></div>
      <div class="kpi-note">تومان — بر اساس درخواست‌های ثبت‌شده</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">استعلام شاسی امروز</span>
        <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 4v16"/></svg></div>
      </div>
      <div class="kpi-val num-font"><?= (int)$todayVin ?></div>
      <div class="kpi-note"><?= current_admin_role()==='admin' ? ((int)$unassignedCount . ' درخواست بدون الحاق') : 'گزارش کامل در صفحه شماره‌شاسی‌ها' ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">تماس‌های امروز</span>
        <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><circle cx="12" cy="15" r="2"/></svg></div>
      </div>
      <div class="kpi-val num-font"><?= (int)$callsToday ?></div>
      <div class="kpi-note"><a href="requests.php" style="color:var(--primary);font-weight:700;">مشاهده لیست</a></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">سرنخ‌های داغ 🔴</span>
        <div class="kpi-icon" style="background:#FEE2E2;color:#DC2626;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg></div>
      </div>
      <div class="kpi-val num-font"><?= (int)$hotLeads ?></div>
      <div class="kpi-note"><a href="kanban.php?temp=hot" style="color:var(--primary);font-weight:700;">مشاهده در کانبان</a></div>
    </div>
  </div>

  <div class="two-col">
    <div class="card">
      <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><path d="M7 15l4-6 3 3 5-8"/></svg> روند ۱۴ روز اخیر</h2>
      <div class="chart-box">
        <div class="bar-rows">
          <?php $maxVal = max(1, max(array_map(fn($d)=>max($d['requests'],$d['calcs']), $daily))); ?>
          <?php foreach ($daily as $d): $jd = jalali_lite($d['date']); ?>
          <div class="bar-row">
            <div class="bar-top"><span class="bname"><?= $jd ?></span><span class="bval num-font">درخواست: <?= $d['requests'] ?> | محاسبه: <?= $d['calcs'] ?></span></div>
            <div style="display:flex;gap:4px;">
              <div class="bar-track" style="flex:1;"><div class="bar-fill" style="width:<?= max(($d['requests']/$maxVal)*100,2) ?>%;background:#FF8A1E;"></div></div>
              <div class="bar-track" style="flex:1;"><div class="bar-fill" style="width:<?= max(($d['calcs']/$maxVal)*100,2) ?>%;background:#2952E0;"></div></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="legend" style="flex-direction:row;justify-content:center;margin-top:14px;">
          <div class="legend-row"><span class="legend-dot" style="background:#FF8A1E;"></span><span class="legend-name">درخواست استعلام</span></div>
          <div class="legend-row"><span class="legend-dot" style="background:#2952E0;"></span><span class="legend-name">محاسبه انجام‌شده</span></div>
        </div>
      </div>
    </div>

    <div class="card">
      <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3a9 9 0 1 0 9 9h-9V3Z"/></svg> توزیع دسته خودرو</h2>
      <div class="chart-box">
        <?php if (empty($catDist)): ?>
          <div class="empty-state">هنوز داده‌ای ثبت نشده.</div>
        <?php else: $totalCat = array_sum(array_column($catDist,'c')); ?>
          <div class="legend">
            <?php foreach ($catDist as $i => $row): $pct = $totalCat ? round($row['c']/$totalCat*100,1) : 0; ?>
            <div class="legend-row">
              <span class="legend-dot" style="background:<?= $palette[$i % count($palette)] ?>"></span>
              <span class="legend-name"><?= htmlspecialchars($row['category']) ?></span>
              <span class="legend-val num-font"><?= $row['c'] ?> (<?= $pct ?>٪)</span>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="two-col">
    <div class="card">
      <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/></svg> آخرین درخواست‌های استعلام</h2>
      <?php if (empty($recentRequests)): ?>
        <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/></svg><div>هنوز درخواستی ثبت نشده است.</div></div>
      <?php else: ?>
      <div class="tbl-wrap">
        <table class="data-table">
          <thead><tr><th>تاریخ</th><th>نام</th><th>تلفن</th><th>خودرو</th><th>جمع کل</th><th>ایمیل</th></tr></thead>
          <tbody>
            <?php foreach ($recentRequests as $r): ?>
            <tr>
              <td data-label="تاریخ"><?= jalali_lite(substr($r['created_at'],0,10)) ?></td>
              <td data-label="نام"><?= htmlspecialchars($r['name']) ?></td>
              <td data-label="تلفن" class="num-font"><?= htmlspecialchars($r['phone']) ?></td>
              <td data-label="خودرو"><?= htmlspecialchars($r['car_label']) ?></td>
              <td data-label="جمع کل" class="amt num-font"><?= number_format($r['total_with_profit']) ?></td>
              <td data-label="ایمیل"><span class="pill <?= $r['email_sent']?'ok':'no' ?>"><?= $r['email_sent']?'ارسال شد':'نامشخص' ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div style="margin-top:14px;"><a href="requests.php" class="btn">مشاهده همه درخواست‌ها</a></div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/></svg> پرتکرارترین خودروها</h2>
      <?php if (empty($topCars)): ?>
        <div class="empty-state">داده‌ای موجود نیست.</div>
      <?php else: ?>
        <div class="bar-rows">
          <?php $maxCar = max(array_column($topCars,'c')); foreach ($topCars as $i => $c): ?>
          <div class="bar-row">
            <div class="bar-top"><span class="bname"><?= htmlspecialchars($c['car_label']) ?></span><span class="bval num-font"><?= $c['c'] ?></span></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= max(($c['c']/$maxCar)*100,3) ?>%;background:<?= $palette[$i % count($palette)] ?>"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 15V3M8 7l4-4 4 4"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg> خروجی اکسل گزارش‌ها</h2>
    <p class="page-sub" style="margin-bottom:14px;">خروجی فایل اکسل (CSV) از تعداد و جزئیات درخواست‌ها و محاسبات، برای بازه دلخواه.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a class="btn amber" href="export.php?type=requests"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3M8 7l4-4 4 4"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg> خروجی همه درخواست‌های استعلام</a>
      <a class="btn amber" href="export.php?type=calculations"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3M8 7l4-4 4 4"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg> خروجی همه محاسبات ثبت‌شده</a>
      <a class="btn" href="export.php?type=requests&range=today">خروجی درخواست‌های امروز</a>
      <a class="btn" href="export.php?type=requests&range=month">خروجی درخواست‌های ۳۰ روز اخیر</a>
    </div>
  </div>

</main>
</body>
</html>
<?php
/** تبدیل ساده تاریخ میلادی به نمایش کوتاه (برای سادگی از فرمت میلادی خوانا استفاده می‌شود) */
function jalali_lite($ymd) {
    $ts = strtotime($ymd);
    $months = ['','ژانویه','فوریه','مارس','آوریل','مه','ژوئن','ژوئیه','اوت','سپتامبر','اکتبر','نوامبر','دسامبر'];
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)];
}
