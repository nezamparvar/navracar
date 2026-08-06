<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'requests';
$myId = current_admin_id();
$myRole = current_admin_role();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = :id");
$stmt->execute(['id' => $id]);
$r = $stmt->fetch();

if (!$r) { header('Location: requests.php'); exit; }

// دسترسی CRM: کارشناس فروش فقط فرم الحاق‌شده به خودش را می‌بیند
if ($myRole !== 'admin' && (int)$r['assigned_to'] !== $myId) {
    http_response_code(403);
    die('این درخواست به شما الحاق نشده است.');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign' && $myRole === 'admin') {
        $newAssignee = $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : null;
        $pdo->prepare("UPDATE quote_requests SET assigned_to = :a WHERE id = :id")->execute(['a' => $newAssignee, 'id' => $id]);
        $assigneeName = '—';
        if ($newAssignee) {
            $s = $pdo->prepare("SELECT username, full_name FROM admin_users WHERE id = :id");
            $s->execute(['id' => $newAssignee]);
            $u = $s->fetch();
            $assigneeName = $u ? ($u['full_name'] ?: $u['username']) : '—';
        }
        $pdo->prepare("INSERT INTO lead_activities (request_id, admin_user_id, activity_type, note) VALUES (:rid,:uid,'assign',:note)")
            ->execute(['rid' => $id, 'uid' => $myId, 'note' => 'الحاق به: ' . $assigneeName]);
        $message = 'الحاق با موفقیت انجام شد.';
    }

    if ($action === 'temperature') {
        $newTemp = trim($_POST['temperature'] ?? '');
        if (in_array($newTemp, ['hot', 'warm', 'cold'], true)) {
            $pdo->prepare("UPDATE quote_requests SET temperature = :t WHERE id = :id")->execute(['t' => $newTemp, 'id' => $id]);
            $labels = ['hot' => 'داغ', 'warm' => 'معمولی', 'cold' => 'سرد'];
            $pdo->prepare("INSERT INTO lead_activities (request_id, admin_user_id, activity_type, note) VALUES (:rid,:uid,'note',:note)")
                ->execute(['rid' => $id, 'uid' => $myId, 'note' => 'دمای سرنخ به «' . $labels[$newTemp] . '» تغییر کرد']);
            $message = 'دمای سرنخ به‌روزرسانی شد.';
        }
    }

    if ($action === 'status') {
        $newStatus = trim($_POST['follow_up_status'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $newNextCall = trim($_POST['next_call_date'] ?? '');
        $newNextCall = preg_match('/^\d{4}-\d{2}-\d{2}$/', $newNextCall) ? $newNextCall : null;

        if ($newStatus !== '') {
            $pdo->prepare("UPDATE quote_requests SET follow_up_status = :s WHERE id = :id")->execute(['s' => $newStatus, 'id' => $id]);
            $pdo->prepare("INSERT INTO lead_activities (request_id, admin_user_id, activity_type, note) VALUES (:rid,:uid,'status_change',:note)")
                ->execute(['rid' => $id, 'uid' => $myId, 'note' => 'تغییر وضعیت به «' . $newStatus . '»' . ($note ? ' — ' . $note : '')]);
        } elseif ($note !== '') {
            $pdo->prepare("INSERT INTO lead_activities (request_id, admin_user_id, activity_type, note) VALUES (:rid,:uid,'note',:note)")
                ->execute(['rid' => $id, 'uid' => $myId, 'note' => $note]);
        }
        $pdo->prepare("UPDATE quote_requests SET next_call_date = :d WHERE id = :id")->execute(['d' => $newNextCall, 'id' => $id]);
        if ($newNextCall && $newNextCall !== ($r['next_call_date'] ?? null)) {
            $pdo->prepare("INSERT INTO lead_activities (request_id, admin_user_id, activity_type, note) VALUES (:rid,:uid,'note',:note)")
                ->execute(['rid' => $id, 'uid' => $myId, 'note' => 'تاریخ تماس بعدی به ' . $newNextCall . ' تنظیم شد']);
        }
        $message = 'به‌روزرسانی ثبت شد.';
    }

    // reload
    $stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $r = $stmt->fetch();
}

$breakdown = json_decode($r['breakdown_json'] ?? '[]', true) ?: [];
$totals = json_decode($r['totals_json'] ?? '{}', true) ?: [];
$staffList = $myRole === 'admin' ? $pdo->query("SELECT id, username, full_name FROM admin_users ORDER BY username")->fetchAll() : [];
$templates = $pdo->query("SELECT * FROM message_templates WHERE is_active = 1 ORDER BY category, id")->fetchAll();

$creatorName = '';
if (!empty($r['created_by'])) {
    $cs = $pdo->prepare("SELECT username, full_name FROM admin_users WHERE id = :id");
    $cs->execute(['id' => $r['created_by']]);
    $cu = $cs->fetch();
    if ($cu) { $creatorName = $cu['full_name'] ?: $cu['username']; }
}

$activities = $pdo->prepare("SELECT la.*, au.username, au.full_name FROM lead_activities la LEFT JOIN admin_users au ON au.id = la.admin_user_id WHERE la.request_id = :id ORDER BY la.created_at DESC");
$activities->execute(['id' => $id]);
$activities = $activities->fetchAll();

$statuses = ['باز','در حال پیگیری','فروخته شد','بسته - ناموفق'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>جزئیات درخواست #<?= $id ?> | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
<style>
  .activity-item{border-right:3px solid var(--primary-light);padding:8px 14px;margin-bottom:10px;}
  .activity-item .a-note{font-size:.86rem;color:var(--ink);}
  .activity-item .a-meta{font-size:.72rem;color:var(--ink-soft);margin-top:2px;}
  .status-form select,.status-form textarea,.status-form input{width:100%;padding:10px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:var(--font);font-size:.88rem;background:var(--surface-alt);margin-bottom:10px;}
  .msg-ok{background:#DCFCE7;color:#166534;padding:10px 14px;border-radius:10px;font-size:.86rem;margin-bottom:14px;}
</style>
</head>
<body>
<?php include 'partials-header.php'; ?>

<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/></svg>
    جزئیات درخواست #<?= $id ?>
  </h1>
  <p class="page-sub"><a href="requests.php" style="color:var(--primary);font-weight:700;">← بازگشت به لیست</a></p>
  <?php if ($message): ?><div class="msg-ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>

  <div class="two-col">
    <div>
      <div class="card">
        <h2>تفکیک هزینه‌های ارسال‌شده</h2>
        <div class="tbl-wrap">
          <table class="data-table">
            <thead><tr><th>شرح</th><th>نرخ</th><th>مبلغ</th></tr></thead>
            <tbody>
              <?php foreach ($breakdown as $row): ?>
              <tr>
                <td data-label="شرح"><?= htmlspecialchars($row['label'] ?? '') ?></td>
                <td data-label="نرخ" style="font-size:.78rem;color:var(--ink-soft);"><?= htmlspecialchars($row['rate'] ?? '') ?></td>
                <td data-label="مبلغ" class="amt num-font"><?= htmlspecialchars($row['amount'] ?? '') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <h2 style="margin-top:22px;">جمع‌بندی</h2>
        <table class="data-table">
          <tbody>
            <?php foreach ($totals as $label => $val): ?>
            <tr><td data-label=""><b><?= htmlspecialchars($label) ?></b></td><td data-label="" class="amt num-font"><?= htmlspecialchars($val) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="margin-top:16px;">
          <a class="btn amber" href="invoice-new.php?request_id=<?= $id ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6M9 16h6M9 8h6"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>
            صدور پیش‌فاکتور از این درخواست
          </a>
        </div>
      </div>

      <div class="card">
        <h2>تاریخچه پیگیری (CRM)</h2>
        <?php if (empty($activities)): ?>
          <div class="empty-state">هنوز فعالیتی ثبت نشده.</div>
        <?php else: foreach ($activities as $a): ?>
          <div class="activity-item">
            <div class="a-note"><?= htmlspecialchars($a['note']) ?></div>
            <div class="a-meta"><?= htmlspecialchars($a['username'] ?: 'سیستم') ?> — <?= htmlspecialchars(substr($a['created_at'],0,16)) ?></div>
          </div>
        <?php endforeach; endif; ?>

        <form method="post" class="status-form" style="margin-top:16px;border-top:1px solid var(--border);padding-top:16px;">
          <input type="hidden" name="action" value="status">
          <label style="font-size:.82rem;font-weight:700;">تغییر وضعیت پیگیری</label>
          <select name="follow_up_status">
            <option value="">— بدون تغییر —</option>
            <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $r['follow_up_status']===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <label style="font-size:.82rem;font-weight:700;">تاریخ تماس بعدی</label>
          <input type="date" name="next_call_date" value="<?= htmlspecialchars($r['next_call_date'] ?? '') ?>">
          <label style="font-size:.82rem;font-weight:700;">یادداشت پیگیری (اختیاری)</label>
          <textarea name="note" rows="3" placeholder="مثلاً: با مشتری تماس گرفته شد، قرار است فردا پاسخ بدهد..."></textarea>
          <button type="submit" class="btn">ثبت به‌روزرسانی</button>
        </form>
      </div>
    </div>

    <div>
      <div class="card">
        <h2>مشخصات مشتری</h2>
        <form method="post" style="margin-bottom:14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <input type="hidden" name="action" value="temperature">
          <label style="font-size:.82rem;font-weight:700;">دمای سرنخ:</label>
          <?php $curTemp = $r['temperature'] ?: 'warm'; ?>
          <?php foreach (['hot'=>['داغ 🔴','#FEE2E2','#DC2626'],'warm'=>['معمولی 🟠','#FEF3C7','#D97706'],'cold'=>['سرد 🔵','#DBEAFE','#2563EB']] as $tk => $tv): ?>
          <button type="submit" name="temperature" value="<?= $tk ?>" class="btn" style="background:<?= $curTemp===$tk ? $tv[2] : '#E5E7EB' ?>;color:<?= $curTemp===$tk ? '#fff' : '#374151' ?>;padding:7px 13px;font-size:.78rem;"><?= $tv[0] ?></button>
          <?php endforeach; ?>
        </form>
        <table class="data-table">
          <tbody>
            <tr><td data-label="">نام</td><td data-label="" style="font-weight:800;"><?= htmlspecialchars($r['name']) ?></td></tr>
            <tr><td data-label="">شماره تماس</td><td data-label="" class="num-font"><?= htmlspecialchars($r['phone']) ?></td></tr>
            <tr><td data-label="">ایمیل</td><td data-label=""><?= htmlspecialchars($r['email'] ?: '-') ?></td></tr>
            <tr><td data-label="">خودرو</td><td data-label=""><?= htmlspecialchars($r['car_label']) ?></td></tr>
            <tr><td data-label="">دسته</td><td data-label=""><span class="pill"><?= htmlspecialchars($r['category']) ?></span></td></tr>
            <tr><td data-label="">بودجه تقریبی</td><td data-label=""><?= htmlspecialchars($r['budget_range'] ?: '-') ?></td></tr>
            <tr><td data-label="">منبع</td><td data-label=""><span class="pill"><?= htmlspecialchars($r['source'] ?: 'سایت') ?></span></td></tr>
            <tr><td data-label="">موقعیت</td><td data-label=""><?= htmlspecialchars(trim(($r['city']?:'').($r['city']&&$r['country']?'، ':'').($r['country']?:'')) ?: 'نامشخص') ?></td></tr>
            <tr><td data-label="">تماس بعدی</td><td data-label=""><?= $r['next_call_date'] ? htmlspecialchars($r['next_call_date']) : '-' ?></td></tr>
            <tr><td data-label="">ثبت‌کننده</td><td data-label=""><?= htmlspecialchars($creatorName ?: '-') ?></td></tr>
            <tr><td data-label="">تاریخ ثبت</td><td data-label=""><?= htmlspecialchars($r['created_at']) ?></td></tr>
            <tr><td data-label="">وضعیت ایمیل</td><td data-label=""><span class="pill <?= $r['email_sent']?'ok':'no' ?>"><?= $r['email_sent']?'ارسال شد':'نامشخص/ناموفق' ?></span></td></tr>
            <tr><td data-label="">IP</td><td data-label="" class="num-font"><?= htmlspecialchars($r['ip_address']) ?></td></tr>
          </tbody>
        </table>
        <?php if ($r['notes']): ?>
        <div class="detail-box"><b>توضیحات مشتری:</b><br><?= nl2br(htmlspecialchars($r['notes'])) ?></div>
        <?php endif; ?>
      </div>

      <?php if ($myRole === 'admin'): ?>
      <div class="card">
        <h2>الحاق به کارشناس (فقط مدیر)</h2>
        <form method="post" class="status-form">
          <input type="hidden" name="action" value="assign">
          <select name="assigned_to">
            <option value="">— بدون الحاق —</option>
            <?php foreach ($staffList as $s): ?>
            <option value="<?= $s['id'] ?>" <?= (int)$r['assigned_to']===(int)$s['id']?'selected':'' ?>><?= htmlspecialchars($s['full_name'] ?: $s['username']) ?> (<?= $s['username'] ?>)</option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn amber">ثبت الحاق</button>
        </form>
      </div>
      <?php else: ?>
      <div class="card">
        <h2>الحاق</h2>
        <p class="page-sub">این درخواست به شما الحاق شده است. برای تغییر الحاق، با مدیر سیستم هماهنگ کنید.</p>
      </div>
      <?php endif; ?>

      <div class="card contact-card">
        <h2>قالب‌های پیام آماده</h2>
        <p class="sub" style="color:#B7E8CE;">یکی را انتخاب کنید تا متن با اطلاعات همین سرنخ پر شود، سپس «کپی متن» را بزنید و در واتساپ/تلگرام/بله بفرستید.</p>
        <select id="tplSelect" style="width:100%;padding:11px 13px;border-radius:10px;border:1.5px solid rgba(255,255,255,.25);background:rgba(255,255,255,.08);color:#fff;font-family:inherit;font-size:.88rem;margin-bottom:10px;">
          <option value="">— انتخاب قالب —</option>
          <?php foreach ($templates as $t): ?>
          <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
          <?php endforeach; ?>
        </select>
        <textarea id="tplPreview" rows="6" readonly style="-webkit-appearance:none;appearance:none;-webkit-text-fill-color:#fff;opacity:1;width:100%;padding:12px 13px;border-radius:10px;border:1.5px solid rgba(255,255,255,.25);background:rgba(255,255,255,.08);color:#fff;font-family:inherit;font-size:.86rem;margin-bottom:10px;"></textarea>
        <button type="button" id="tplCopyBtn" class="btn amber" style="width:100%;justify-content:center;">📋 کپی متن</button>
        <?php if (empty($templates)): ?>
          <p class="hint" style="color:#93A2D9;margin-top:8px;">هنوز قالبی تعریف نشده — از منوی «قالب‌های پیام» بسازید.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<div class="toast-wrap" id="toastWrap"></div>

<script>
const templatesData = <?= json_encode(array_map(fn($t)=>['id'=>$t['id'],'body'=>$t['body']], $templates), JSON_UNESCAPED_UNICODE) ?>;
const leadVars = {
  customer_name: <?= json_encode($r['name'], JSON_UNESCAPED_UNICODE) ?>,
  phone: <?= json_encode($r['phone'], JSON_UNESCAPED_UNICODE) ?>,
  car_model: <?= json_encode($r['car_label'] ?: 'خودروی مدنظر', JSON_UNESCAPED_UNICODE) ?>,
  total_price: <?= json_encode($r['total_with_profit'] ? number_format($r['total_with_profit']) : 'برآورد اولیه', JSON_UNESCAPED_UNICODE) ?>,
  salesperson_name: <?= json_encode($_SESSION['admin_name'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
  official_channels: 'navaracar.com — واتساپ +971505158484',
  company_name: 'ناوراکار',
};

function renderTemplate(body){
  return body.replace(/\{\{(\w+)\}\}/g, (m, key) => leadVars[key] !== undefined ? leadVars[key] : m);
}

const tplSelect = document.getElementById('tplSelect');
if (tplSelect) {
  tplSelect.addEventListener('change', () => {
    const t = templatesData.find(x => x.id == tplSelect.value);
    document.getElementById('tplPreview').value = t ? renderTemplate(t.body) : '';
  });

  document.getElementById('tplCopyBtn').addEventListener('click', async () => {
    const text = document.getElementById('tplPreview').value;
    if (!text) { showToast('اول یک قالب انتخاب کنید.', 'err'); return; }
    try {
      await navigator.clipboard.writeText(text);
      showToast('متن با موفقیت کپی شد', 'ok');
      if (tplSelect.value) {
        fetch('log-template-use.php', {
          method: 'POST', headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ leadId: <?= (int)$id ?>, templateId: tplSelect.value })
        }).catch(()=>{});
      }
    } catch (e) {
      showToast('کپی خودکار پشتیبانی نشد — متن را دستی انتخاب و کپی کنید.', 'err');
    }
  });
}

function showToast(msg, type){
  const wrap = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className = 'toast ' + (type||'');
  t.textContent = msg;
  wrap.appendChild(t);
  setTimeout(()=>t.remove(), 3000);
}
</script>
</body>
</html>
