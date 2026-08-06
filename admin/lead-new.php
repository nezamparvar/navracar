<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'requests';
$myId = current_admin_id();
$myRole = current_admin_role();

// ---- درخواست AJAX (JSON) برای ثبت ----
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($contentType, 'application/json') !== false) {
    header('Content-Type: application/json; charset=utf-8');
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) return false;
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });
    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $car = trim($data['car_label'] ?? '');
    $category = trim($data['category'] ?? '');
    $budget = trim($data['budget_range'] ?? '');
    $city = trim($data['city'] ?? '');
    $notes = trim($data['notes'] ?? '');
    $source = trim($data['source'] ?? 'تماس تلفنی');
    $total = trim($data['total_with_profit'] ?? '');
    $nextCallRaw = trim($data['next_call_date'] ?? '');
    $nextCall = preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextCallRaw) ? $nextCallRaw : null;
    $assignedTo = ($myRole === 'admin' && !empty($data['assigned_to'])) ? (int)$data['assigned_to'] : $myId;

    if ($name === '' || $phone === '') {
        echo json_encode(['success' => false, 'message' => 'نام و شماره تماس الزامی است.']);
        exit;
    }

    try {
        $totalNumeric = $total !== '' ? (float)preg_replace('/[^0-9.]/', '', $total) : 0;
        $stmt = $pdo->prepare(
            "INSERT INTO quote_requests
            (name, phone, email, notes, car_label, category, breakdown_json, totals_json, total_with_profit,
             email_sent, source, budget_range, city, assigned_to, created_by, follow_up_status, next_call_date, ip_address)
            VALUES (:name,:phone,:email,:notes,:car,:cat,'[]','{}',:twp,
             0,:src,:budget,:city,:assigned1,:assigned2,'باز',:nextcall,:ip)"
        );
        $stmt->execute([
            'name' => $name, 'phone' => $phone, 'email' => $email, 'notes' => $notes,
            'car' => $car, 'cat' => $category, 'twp' => $totalNumeric, 'src' => $source,
            'budget' => $budget, 'city' => $city,
            'assigned1' => $assignedTo, 'assigned2' => $myId,
            'nextcall' => $nextCall, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        $newId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO lead_activities (request_id, admin_user_id, activity_type, note) VALUES (:rid,:uid,'note',:note)")
            ->execute(['rid' => $newId, 'uid' => $myId, 'note' => 'ثبت دستی توسط پنل مدیریت (منبع: ' . $source . ')']);

        navarakar_log('info', 'ثبت دستی مشتری در پنل', ['id' => $newId, 'name' => $name, 'phone' => $phone]);
        echo json_encode(['success' => true, 'message' => 'مشتری با موفقیت ثبت شد.', 'id' => $newId]);
    } catch (\Throwable $e) {
        navarakar_log('error', 'lead-new.php insert failed', ['error' => $e->getMessage(), 'name' => $name, 'phone' => $phone]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطا در ثبت در پایگاه داده.']);
    }
    exit;
}

$staffList = $myRole === 'admin' ? $pdo->query("SELECT id, username, full_name FROM admin_users ORDER BY username")->fetchAll() : [];
$cities = ["تهران","کرج","مشهد","اصفهان","شیراز","تبریز","اهواز","قم","کرمانشاه","ارومیه","رشت","زاهدان","کرمان","اراک","یزد","اردبیل","بندرعباس","قزوین","ساری","همدان","سایر"];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ثبت دستی مشتری | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
<style>
  .lead-form .field-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
  @media(max-width:600px){.lead-form .field-grid{grid-template-columns:1fr;}}
  .lead-form label{display:block;font-size:.85rem;font-weight:700;margin-bottom:7px;}
  .lead-form label .req{color:#9C2B2B;}
  .lead-form input,.lead-form select,.lead-form textarea{
    width:100%;padding:12px 14px;border-radius:11px;border:1.5px solid var(--border);
    font-family:var(--font);font-size:.94rem;background:var(--surface-alt);color:var(--ink);
    transition:.15s border-color,.15s background;margin-bottom:16px;
  }
  .lead-form input:focus,.lead-form select:focus,.lead-form textarea:focus{outline:none;border-color:var(--primary);background:#fff;box-shadow:0 0 0 4px var(--primary-light);}
  .error-box{background:#FEE2E2;color:#991B1B;padding:11px 14px;border-radius:10px;font-size:.86rem;margin-bottom:16px;}
  .success-panel{display:none;text-align:center;padding:50px 24px;}
  .success-panel svg{width:60px;height:60px;color:var(--green);margin-bottom:16px;}
  .success-panel h2{color:var(--primary-dark);margin-bottom:6px;}
  .success-panel .actions{display:flex;gap:10px;justify-content:center;margin-top:20px;flex-wrap:wrap;}
</style>
</head>
<body>
<?php include 'partials-header.php'; ?>
<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
    ثبت دستی مشتری تماس‌گرفته
  </h1>
  <p class="page-sub">برای مشتریانی که تلفنی یا حضوری تماس می‌گیرند، اینجا ثبتشان کنید تا وارد چرخه پیگیری CRM شوند.</p>

  <div class="card" id="formCard">
    <div id="errorBox" class="error-box" style="display:none;"></div>
    <form class="lead-form" id="leadForm">
      <div class="field-grid">
        <div><label>نام و نام خانوادگی <span class="req">*</span></label><input type="text" id="f_name" required></div>
        <div><label>شماره تماس <span class="req">*</span></label><input type="text" id="f_phone" required></div>
        <div><label>ایمیل (اختیاری)</label><input type="email" id="f_email"></div>
        <div>
          <label>منبع تماس</label>
          <select id="f_source">
            <option>تماس تلفنی</option><option>حضوری</option><option>سایت</option>
            <option>معرفی مشتری</option><option>شبکه‌های اجتماعی</option><option>سایر</option>
          </select>
        </div>
        <div><label>خودروی مورد نظر</label><input type="text" id="f_car" placeholder="مثلاً Toyota Land Cruiser 300"></div>
        <div>
          <label>دسته خودرو</label>
          <select id="f_category">
            <option value="">— نامشخص —</option>
            <option>هیبرید / برقی</option><option>زیر ۱۵۰۰ سی‌سی</option><option>۱۵۰۱ تا ۲۰۰۰</option>
            <option>۲۰۰۱ تا ۲۵۰۰</option><option>۲۵۰۱ تا ۳۰۰۰</option><option>بالای ۳۰۰۱</option>
          </select>
        </div>
        <div>
          <label>بودجه تقریبی</label>
          <select id="f_budget">
            <option value="">— نامشخص —</option>
            <option>زیر ۱۰ میلیارد تومان</option>
            <option>۱۰ تا ۲۰ میلیارد تومان</option>
            <option>۲۰ تا ۵۰ میلیارد تومان</option>
            <option>۵۰ تا ۱۰۰ میلیارد تومان</option>
            <option>۱۰۰ میلیارد تومان به بالا</option>
            <option>نامشخص</option>
          </select>
        </div>
        <div>
          <label>شهر</label>
          <select id="f_city">
            <option value="">— نامشخص —</option>
            <?php foreach ($cities as $c): ?><option><?= $c ?></option><?php endforeach; ?>
          </select>
        </div>
        <div><label>برآورد جمع کل (تومان — اختیاری)</label><input type="text" id="f_total" placeholder="مثلاً 900000000"></div>
        <div><label>تاریخ تماس بعدی (اختیاری)</label><input type="date" id="f_nextcall"></div>
        <?php if ($myRole === 'admin'): ?>
        <div>
          <label>الحاق به</label>
          <select id="f_assigned">
            <option value="">— خودم —</option>
            <?php foreach ($staffList as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name'] ?: $s['username']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
      </div>
      <label>توضیحات</label>
      <textarea id="f_notes" rows="3" placeholder="جزئیات گفتگو با مشتری..."></textarea>
      <button type="submit" class="btn amber" id="submitBtn">ثبت مشتری</button>
    </form>
  </div>

  <div class="card success-panel" id="successPanel">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
    <h2>مشتری با موفقیت ثبت شد</h2>
    <p class="page-sub" style="margin:0;">می‌توانید فرصت جدید دیگری ثبت کنید یا جزئیات همین یکی را ببینید.</p>
    <div class="actions">
      <button class="btn amber" onclick="resetForm()">ثبت مشتری جدید</button>
      <a class="btn" id="viewLink" href="#">مشاهده جزئیات →</a>
    </div>
  </div>
</main>
<script>
document.getElementById('leadForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const errBox = document.getElementById('errorBox');
  const name = document.getElementById('f_name').value.trim();
  const phone = document.getElementById('f_phone').value.trim();
  if(!name || !phone){
    errBox.textContent = 'نام و شماره تماس الزامی است.';
    errBox.style.display = 'block';
    return;
  }
  errBox.style.display = 'none';
  btn.disabled = true;
  btn.textContent = 'در حال ثبت...';

  const assignedEl = document.getElementById('f_assigned');
  const payload = {
    name, phone,
    email: document.getElementById('f_email').value.trim(),
    source: document.getElementById('f_source').value,
    car_label: document.getElementById('f_car').value.trim(),
    category: document.getElementById('f_category').value,
    budget_range: document.getElementById('f_budget').value,
    city: document.getElementById('f_city').value,
    total_with_profit: document.getElementById('f_total').value.trim(),
    next_call_date: document.getElementById('f_nextcall').value,
    notes: document.getElementById('f_notes').value.trim(),
    assigned_to: assignedEl ? assignedEl.value : '',
  };

  try{
    const res = await fetch('lead-new.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(data.success){
      document.getElementById('formCard').style.display = 'none';
      document.getElementById('successPanel').style.display = 'block';
      document.getElementById('viewLink').href = 'request-view.php?id=' + data.id;
    } else {
      errBox.textContent = data.message || 'ثبت ناموفق بود.';
      errBox.style.display = 'block';
    }
  }catch(err){
    errBox.textContent = 'خطا در ارتباط با سرور.';
    errBox.style.display = 'block';
  } finally {
    btn.disabled = false;
    btn.textContent = 'ثبت مشتری';
  }
});

function resetForm(){
  document.getElementById('leadForm').reset();
  document.getElementById('successPanel').style.display = 'none';
  document.getElementById('formCard').style.display = 'block';
  document.getElementById('f_name').focus();
}
</script>
</body>
</html>
