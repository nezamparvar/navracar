<?php
/**
 * lead-form.php — فرم عمومی ثبت گزارش تماس فروش
 * این صفحه بدون نیاز به ورود در دسترس است؛ لینکش را به فروشنده‌ها بدهید.
 * هر فروشنده نام خودش را از لیست انتخاب می‌کند و گزارش تماس/مشتری جدید ثبت می‌کند.
 */
require 'db-config.php';

$staff = $pdo->query("SELECT id, username, full_name FROM admin_users ORDER BY full_name IS NULL, full_name, username")->fetchAll();

$carBrands = ["Mercedes-Benz","BMW","Acura","Volkswagen","Audi","Toyota","Lexus","Hyundai","Kia","Genesis","Honda","Peugeot","Nissan","Infiniti","Mazda","Mitsubishi","Suzuki","Land Rover","Jaguar","Volvo","Cupra","Skoda","Subaru","Mini","Dacia","BYD","Fangchengbao","MG","Changan","Haval","NIO","Tank","Voyah","Dongfeng","Xpeng","Alfa Romeo","Avatar","Xiaomi","Opel","Geely","SsangYong","LiAuto","Yangwang","Fiat","Maextro","M-Hero","ORA","Denza","Citroen","Renault"];

$cities = ["تهران","کرج","مشهد","اصفهان","شیراز","تبریز","اهواز","قم","کرمانشاه","ارومیه","رشت","زاهدان","کرمان","اراک","یزد","اردبیل","بندرعباس","قزوین","ساری","همدان","سایر"];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ثبت گزارش تماس فروش | ناوراکار</title>
<style>
  :root{
    --ink:#1A1730;--ink-soft:#6B6584;--bg:#F7F5F1;--surface:#FFFFFF;--surface-alt:#F1EDE6;
    --border:#E4DFD3;--primary:#2D2657;--primary-dark:#171433;--primary-light:#EDE9F7;
    --amber:#C9A227;--amber-dark:#9C7D14;--green:#16A34A;
    --font:'Vazirmatn','Vazir','Tahoma','Segoe UI',Arial,sans-serif;
  }
  *{box-sizing:border-box;}
  html{overflow-x:hidden;}
  body{margin:0;overflow-x:hidden;max-width:100vw;background:radial-gradient(140% 100% at 100% 0%, #F1EDFF 0%, var(--bg) 45%);color:var(--ink);font-family:var(--font);font-size:16px;line-height:1.8;}
  .wrap{max-width:640px;margin:0 auto;padding:0 16px 60px;}
  header{background:linear-gradient(120deg,var(--primary-dark),var(--primary));color:#fff;padding:32px 16px;text-align:center;margin-bottom:22px;border-radius:0 0 24px 24px;box-shadow:0 10px 30px -14px rgba(23,20,51,.4);}
  header .badge-icon{width:52px;height:52px;border-radius:16px;background:linear-gradient(160deg,var(--amber),var(--amber-dark));display:flex;align-items:center;justify-content:center;margin:0 auto 12px;box-shadow:0 8px 18px -6px rgba(156,125,20,.5);}
  header .badge-icon svg{width:26px;height:26px;color:#1A1200;}
  header .brand{font-size:1.4rem;font-weight:900;}
  header .sub{font-size:.86rem;color:#D6CFF0;margin-top:5px;}
  .required-note{color:#9C2B2B;font-size:.8rem;margin-bottom:16px;}
  @keyframes cardIn{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px;margin-bottom:14px;box-shadow:0 10px 26px -16px rgba(23,20,51,.25);animation:cardIn .3s ease both;transition:.2s box-shadow, .2s transform;}
  .card:hover{box-shadow:0 14px 32px -16px rgba(23,20,51,.3);}
  .card label{display:flex;align-items:center;gap:8px;font-weight:800;font-size:.95rem;margin-bottom:10px;}
  .card label svg{width:17px;height:17px;color:var(--primary);flex-shrink:0;}
  .card label .req{color:#9C2B2B;}
  .card input, .card select, .card textarea{
    width:100%;padding:13px 14px;border-radius:10px;border:1.5px solid var(--border);
    font-family:var(--font);font-size:16px;background:var(--surface-alt);color:var(--ink);
  }
  .card input:focus, .card select:focus, .card textarea:focus{outline:none;border-color:var(--primary);background:#fff;box-shadow:0 0 0 4px var(--primary-light);}
  .hint{font-size:.76rem;color:var(--ink-soft);margin-top:6px;}
  button#submitBtn{
    width:100%;background:linear-gradient(150deg,var(--amber),var(--amber-dark));color:#1A1200;border:none;
    padding:16px;border-radius:14px;font-family:inherit;font-weight:900;font-size:1rem;cursor:pointer;
    box-shadow:0 10px 22px -8px rgba(156,125,20,.5);
  }
  button#submitBtn:disabled{opacity:.6;}
  #statusMsg{margin-top:14px;text-align:center;font-weight:700;font-size:.9rem;}
  #statusMsg.ok{color:var(--green);}
  #statusMsg.err{color:#9C2B2B;}
  .success-box{display:none;text-align:center;padding:40px 20px;}
  .success-box svg{width:56px;height:56px;color:var(--green);margin-bottom:14px;}
  .success-box h2{color:var(--primary-dark);}
  .success-box button{margin-top:16px;background:var(--primary);color:#fff;border:none;padding:12px 22px;border-radius:10px;font-family:inherit;font-weight:700;cursor:pointer;}
  .lead-form .card:nth-child(1){animation-delay:.02s;}
  .lead-form .card:nth-child(2){animation-delay:.05s;}
  .lead-form .card:nth-child(3){animation-delay:.08s;}
  .lead-form .card:nth-child(4){animation-delay:.11s;}
  .lead-form .card:nth-child(5){animation-delay:.14s;}
  .lead-form .card:nth-child(6){animation-delay:.17s;}
  .lead-form .card:nth-child(7){animation-delay:.2s;}
  .lead-form .card:nth-child(8){animation-delay:.23s;}
  .lead-form .card:nth-child(9){animation-delay:.26s;}
  .lead-form .card:nth-child(10){animation-delay:.29s;}</style>
</head>
<body>
<header>
  <div class="badge-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/><circle cx="7" cy="18.5" r="1.6" fill="currentColor" stroke="none"/><circle cx="17" cy="18.5" r="1.6" fill="currentColor" stroke="none"/></svg></div>
  <div class="brand">ناوراکار</div>
  <div class="sub">فرم ثبت گزارش تماس فروش (فرصت جدید)</div>
</header>

<div class="wrap">
  <div class="required-note">* فیلدهای الزامی</div>

  <form id="leadForm">
    <input type="text" id="website" name="website" autocomplete="off" tabindex="-1" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;" aria-hidden="true">
    <input type="hidden" id="formLoadedAt" value="">

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>نام کارشناس <span class="req">*</span></label>
      <select id="userId" required>
        <option value="">— انتخاب کنید —</option>
        <?php foreach ($staff as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name'] ?: $s['username']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>نام مشتری <span class="req">*</span></label>
      <input type="text" id="custName" required>
    </div>

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>شماره تماس <span class="req">*</span></label>
      <input type="text" id="custPhone" inputmode="tel" required>
    </div>

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>ایمیل مشتری (اختیاری)</label>
      <input type="email" id="custEmail">
    </div>

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>بودجه تقریبی <span class="req">*</span></label>
      <select id="budget" required>
        <option value="">— انتخاب کنید —</option>
        <option>زیر ۱۰ میلیارد تومان</option>
        <option>۱۰ تا ۲۰ میلیارد تومان</option>
        <option>۲۰ تا ۵۰ میلیارد تومان</option>
        <option>۵۰ تا ۱۰۰ میلیارد تومان</option>
        <option>۱۰۰ میلیارد تومان به بالا</option>
        <option>نامشخص</option>
      </select>
    </div>

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/><circle cx="7" cy="18.5" r="1.6"/><circle cx="17" cy="18.5" r="1.6"/></svg>خودروی مورد نظر <span class="req">*</span></label>
      <input type="text" id="carInterest" list="carBrandsList" placeholder="مثلاً Toyota Land Cruiser یا Lexus LX" required>
      <datalist id="carBrandsList">
        <?php foreach ($carBrands as $b): ?><option value="<?= htmlspecialchars($b) ?>"><?php endforeach; ?>
      </datalist>
    </div>

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>منبع مشتری <span class="req">*</span></label>
      <select id="source" required>
        <option value="">— انتخاب کنید —</option>
        <option>تماس تلفنی</option>
        <option>حضوری</option>
        <option>سایت</option>
        <option>معرفی مشتری</option>
        <option>شبکه‌های اجتماعی</option>
        <option>سایر</option>
      </select>
    </div>

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 12.5l2 2 5-5"/><circle cx="12" cy="12" r="9"/></svg>وضعیت <span class="req">*</span></label>
      <select id="status" required>
        <option value="">— انتخاب کنید —</option>
        <option>باز</option>
        <option>در حال پیگیری</option>
        <option>فروخته شد</option>
        <option>بسته - ناموفق</option>
      </select>
    </div>

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>شهر <span class="req">*</span></label>
      <select id="city" required>
        <option value="">— انتخاب کنید —</option>
        <?php foreach ($cities as $c): ?><option><?= $c ?></option><?php endforeach; ?>
      </select>
    </div>

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>تاریخ تماس بعدی (اختیاری)</label>
      <input type="date" id="nextCall">
      <div class="hint">اگر قرار است دوباره با مشتری تماس بگیرید، تاریخش را اینجا بگذارید.</div>
    </div>

    <div class="card">
      <label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>توضیحات</label>
      <textarea id="notes" rows="4" placeholder="جزئیات گفتگو با مشتری..."></textarea>
    </div>

    <button type="submit" id="submitBtn">ثبت گزارش</button>
    <div id="statusMsg"></div>
  </form>

  <div class="success-box" id="successBox">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
    <h2>گزارش با موفقیت ثبت شد</h2>
    <p>می‌توانید یک فرصت جدید دیگر ثبت کنید.</p>
    <button onclick="location.reload()">ثبت گزارش جدید</button>
  </div>
</div>

<script>
document.getElementById('formLoadedAt').value = Date.now();

document.getElementById('leadForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const statusEl = document.getElementById('statusMsg');
  const payload = {
    website: document.getElementById('website').value,
    formLoadedAt: document.getElementById('formLoadedAt').value,
    userId: document.getElementById('userId').value,
    name: document.getElementById('custName').value.trim(),
    phone: document.getElementById('custPhone').value.trim(),
    email: document.getElementById('custEmail').value.trim(),
    budget: document.getElementById('budget').value,
    carInterest: document.getElementById('carInterest').value.trim(),
    source: document.getElementById('source').value,
    status: document.getElementById('status').value,
    city: document.getElementById('city').value,
    nextCall: document.getElementById('nextCall').value,
    notes: document.getElementById('notes').value.trim(),
  };
  if(!payload.userId || !payload.name || !payload.phone || !payload.budget || !payload.carInterest || !payload.source || !payload.status || !payload.city){
    statusEl.textContent = 'لطفاً همه فیلدهای الزامی (*) را پر کنید.';
    statusEl.className = 'err';
    return;
  }
  btn.disabled = true;
  statusEl.textContent = 'در حال ثبت...';
  statusEl.className = '';
  try{
    const res = await fetch('submit-lead-form.php', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(data.success){
      document.getElementById('leadForm').style.display = 'none';
      document.getElementById('successBox').style.display = 'block';
    } else {
      statusEl.textContent = data.message || 'ثبت ناموفق بود، دوباره تلاش کنید.';
      statusEl.className = 'err';
      btn.disabled = false;
    }
  }catch(err){
    statusEl.textContent = 'خطا در ارتباط با سرور.';
    statusEl.className = 'err';
    btn.disabled = false;
  }
});
</script>
</body>
</html>
