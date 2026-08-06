<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'invoices';
$myId = current_admin_id();

$requestId = (int)($_GET['request_id'] ?? 0);
$editId = (int)($_GET['id'] ?? 0);
$prefill = [
    'name' => '', 'phone' => '', 'email' => '', 'address' => '', 'car' => '', 'category' => '',
    'breakdown' => [], 'total' => 0, 'discount' => 0, 'currency' => 'toman', 'exchange_rate' => '',
    'valid_until' => '', 'payment_terms' => '', 'invoice_type' => 'full',
];

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $inv = $stmt->fetch();
    if ($inv) {
        $prefill['name'] = $inv['customer_name'];
        $prefill['phone'] = $inv['customer_phone'];
        $prefill['email'] = $inv['customer_email'] ?? '';
        $prefill['address'] = $inv['customer_address'];
        $prefill['car'] = $inv['car_label'];
        $prefill['category'] = $inv['category'];
        $prefill['breakdown'] = json_decode($inv['breakdown_json'] ?? '[]', true) ?: [];
        $prefill['total'] = $inv['total_amount'];
        $prefill['discount'] = $inv['discount_amount'] ?? 0;
        $prefill['currency'] = $inv['currency'] ?? 'toman';
        $prefill['exchange_rate'] = $inv['exchange_rate'] ?? '';
        $prefill['valid_until'] = $inv['valid_until'] ?? '';
        $prefill['payment_terms'] = $inv['payment_terms'] ?? '';
        $prefill['invoice_type'] = $inv['invoice_type'] ?? 'full';
    }
} elseif ($requestId) {
    $stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = :id");
    $stmt->execute(['id' => $requestId]);
    $req = $stmt->fetch();
    if ($req) {
        $prefill['name'] = $req['name'];
        $prefill['phone'] = $req['phone'];
        $prefill['email'] = $req['email'] ?? '';
        $prefill['car'] = $req['car_label'];
        $prefill['category'] = $req['category'];
        $prefill['breakdown'] = json_decode($req['breakdown_json'] ?? '[]', true) ?: [];
        $prefill['total'] = $req['total_with_profit'];

        // سود خدمات ناوراکار را از totals_json به‌صورت یک ردیف مجزا اضافه کن تا در پیش‌فاکتور شفاف دیده شود
        $totals = json_decode($req['totals_json'] ?? '{}', true) ?: [];
        foreach ($totals as $label => $val) {
            if (mb_strpos((string)$label, 'سود خدمات') !== false) {
                $prefill['breakdown'][] = ['label' => $label, 'rate' => 'طبق نرخ سود خدمات ناوراکار', 'amount' => $val];
            }
        }
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int)($_POST['invoice_id'] ?? 0);
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $customerEmailRaw = trim($_POST['customer_email'] ?? '');
    $customerEmail = filter_var($customerEmailRaw, FILTER_VALIDATE_EMAIL) ? $customerEmailRaw : '';
    $customerAddress = trim($_POST['customer_address'] ?? '');
    $carLabel = trim($_POST['car_label'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $totalAmount = (float)preg_replace('/[^0-9.]/', '', $_POST['total_amount'] ?? '0');
    $discountAmount = (float)preg_replace('/[^0-9.]/', '', $_POST['discount_amount'] ?? '0');
    $currency = in_array($_POST['currency'] ?? '', ['toman', 'aed'], true) ? $_POST['currency'] : 'toman';
    $exchangeRate = trim($_POST['exchange_rate'] ?? '') !== '' ? (float)preg_replace('/[^0-9.]/', '', $_POST['exchange_rate']) : null;
    $validUntilRaw = trim($_POST['valid_until'] ?? '');
    $validUntil = preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntilRaw) ? $validUntilRaw : null;
    $paymentTerms = trim($_POST['payment_terms'] ?? '');
    $invoiceType = ($_POST['invoice_type'] ?? 'full') === 'single_item' ? 'single_item' : 'full';

    $labels = $_POST['b_label'] ?? [];
    $rates = $_POST['b_rate'] ?? [];
    $amounts = $_POST['b_amount'] ?? [];
    $breakdown = [];
    foreach ($labels as $i => $lbl) {
        if (trim($lbl) === '') continue;
        $breakdown[] = ['label' => trim($lbl), 'rate' => trim($rates[$i] ?? ''), 'amount' => trim($amounts[$i] ?? '')];
    }

    if ($customerName === '' || $customerPhone === '') {
        $error = 'نام و تلفن مشتری الزامی است.';
    } else {
        $params = [
            'cn' => $customerName, 'cp' => $customerPhone, 'ce' => $customerEmail, 'ca' => $customerAddress,
            'car' => $carLabel, 'cat' => $category, 'bj' => json_encode($breakdown, JSON_UNESCAPED_UNICODE),
            'tot' => $totalAmount, 'disc' => $discountAmount, 'cur' => $currency, 'exr' => $exchangeRate,
            'vu' => $validUntil, 'pt' => $paymentTerms, 'it' => $invoiceType,
        ];

        if ($postId > 0) {
            $stmt = $pdo->prepare(
                "UPDATE invoices SET customer_name=:cn, customer_phone=:cp, customer_email=:ce, customer_address=:ca,
                 car_label=:car, category=:cat, breakdown_json=:bj, total_amount=:tot, discount_amount=:disc,
                 currency=:cur, exchange_rate=:exr, valid_until=:vu, payment_terms=:pt, invoice_type=:it
                 WHERE id=:id"
            );
            $params['id'] = $postId;
            $stmt->execute($params);
            $newId = $postId;
        } else {
            $params['rid'] = $requestId ?: null;
            $params['uid'] = $myId;
            $stmt = $pdo->prepare(
                "INSERT INTO invoices (request_id, invoice_number, customer_name, customer_phone, customer_email, customer_address,
                 car_label, category, breakdown_json, total_amount, discount_amount, currency, exchange_rate, valid_until,
                 payment_terms, invoice_type, status, created_by)
                 VALUES (:rid,'',:cn,:cp,:ce,:ca,:car,:cat,:bj,:tot,:disc,:cur,:exr,:vu,:pt,:it,'پیش‌نویس',:uid)"
            );
            $stmt->execute($params);
            $newId = $pdo->lastInsertId();
            $invoiceNumber = 'NVK-' . date('Y') . '-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE invoices SET invoice_number = :n WHERE id = :id")->execute(['n' => $invoiceNumber, 'id' => $newId]);
        }
        header('Location: invoice-view.php?id=' . $newId);
        exit;
    }
}

$quickRows = [
    'full' => [
        ['سود بازرگانی', 'بر اساس دسته خودرو', ''],
        ['حقوق گمرکی ثابت', '۴٪ از ارزش گمرکی', ''],
        ['عوارض و مالیات گمرکی', '', ''],
        ['مالیات ارزش افزوده', '۱۰٪', ''],
        ['هزینه پلاک انتظامی', '', ''],
        ['صدور مجوزها', '', ''],
        ['حمل دریایی', '', ''],
        ['سود خدمات ناوراکار', '', ''],
    ],
    'single_item' => [
        ['صدور مجوز', 'خدمت مجزا', ''],
        ['حمل و نقل', 'خدمت مجزا', ''],
        ['ترخیص گمرکی', 'خدمت مجزا', ''],
        ['مشاوره و پیگیری اداری', 'خدمت مجزا', ''],
    ],
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $editId ? 'ویرایش پیش‌فاکتور' : 'صدور پیش‌فاکتور' ?> | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
<style>
  .inv-form label{display:block;font-size:.85rem;font-weight:700;margin-bottom:6px;}
  .inv-form input,.inv-form select,.inv-form textarea{width:100%;padding:11px 13px;border-radius:10px;border:1.5px solid var(--border);font-family:var(--font);font-size:.92rem;background:var(--surface-alt);margin-bottom:14px;}
  .field-grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
  @media(max-width:600px){.field-grid2{grid-template-columns:1fr;}}
  .b-row{display:grid;grid-template-columns:2fr 2fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;}
  @media(max-width:640px){.b-row{grid-template-columns:1fr;}}
  .b-row input{margin-bottom:0;}
  .rm-row{background:#FEE2E2;color:#991B1B;border:none;border-radius:8px;padding:8px 10px;cursor:pointer;font-family:inherit;font-weight:800;}
  .error-box{background:#FEE2E2;color:#991B1B;padding:10px 14px;border-radius:10px;font-size:.86rem;margin-bottom:14px;}
  .type-toggle{display:flex;gap:10px;margin-bottom:16px;}
  .type-toggle label{flex:1;display:flex;align-items:center;gap:8px;border:2px solid var(--border);border-radius:12px;padding:12px 14px;cursor:pointer;font-weight:700;font-size:.86rem;margin-bottom:0;}
  .type-toggle input{width:auto;margin:0;}
  .quick-rows{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;}
  .quick-rows button{background:var(--primary-light);color:var(--primary-dark);border:none;border-radius:999px;padding:7px 14px;font-family:inherit;font-size:.76rem;font-weight:700;cursor:pointer;}
  .car-fields, #singleFields{display:none;}
</style>
</head>
<body>
<?php include 'partials-header.php'; ?>
<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12h6M9 16h6M9 8h6"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>
    <?= $editId ? 'ویرایش پیش‌فاکتور' : 'صدور پیش‌فاکتور جدید' ?>
  </h1>
  <p class="page-sub"><?= $requestId ? 'اطلاعات از درخواست #' . $requestId . ' پیش‌پر شده است.' : ($editId ? 'در حال ویرایش پیش‌فاکتور موجود.' : 'اطلاعات مشتری و اقلام هزینه را وارد کنید.') ?></p>

  <div class="card">
    <?php if (!empty($error)): ?><div class="error-box"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" class="inv-form" id="invForm">
      <input type="hidden" name="invoice_id" value="<?= $editId ?>">

      <label>نوع پیش‌فاکتور</label>
      <div class="type-toggle">
        <label><input type="radio" name="invoice_type" value="full" id="typeFull" <?= $prefill['invoice_type']!=='single_item'?'checked':'' ?>> واردات کامل خودرو</label>
        <label><input type="radio" name="invoice_type" value="single_item" id="typeSingle" <?= $prefill['invoice_type']==='single_item'?'checked':'' ?>> خدمت مجزا (مثلاً فقط مجوز یا فقط حمل)</label>
      </div>

      <div class="field-grid2">
        <div><label>نام مشتری *</label><input type="text" name="customer_name" value="<?= htmlspecialchars($prefill['name']) ?>" required></div>
        <div><label>شماره تماس *</label><input type="text" name="customer_phone" value="<?= htmlspecialchars($prefill['phone']) ?>" required></div>
        <div><label>ایمیل مشتری (اختیاری)</label><input type="email" name="customer_email" value="<?= htmlspecialchars($prefill['email']) ?>"></div>
        <div><label>آدرس مشتری (اختیاری)</label><input type="text" name="customer_address" value="<?= htmlspecialchars($prefill['address']) ?>"></div>
      </div>

      <div class="field-grid2 car-fields" id="carFields">
        <div><label>خودرو</label><input type="text" name="car_label" value="<?= htmlspecialchars($prefill['car']) ?>"></div>
        <div>
          <label>دسته خودرو</label>
          <select name="category">
            <option value="">— نامشخص —</option>
            <?php foreach (['هیبرید / برقی','زیر ۱۵۰۰ سی‌سی','۱۵۰۱ تا ۲۰۰۰','۲۰۰۱ تا ۲۵۰۰','۲۵۰۱ تا ۳۰۰۰','بالای ۳۰۰۱'] as $c): ?>
            <option <?= $prefill['category']===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <label style="margin-top:8px;">اقلام پیش‌فاکتور</label>
      <div class="quick-rows" id="quickRowsFull">
        <?php foreach ($quickRows['full'] as $qr): ?>
        <button type="button" onclick="addRow('<?= htmlspecialchars($qr[0], ENT_QUOTES) ?>','<?= htmlspecialchars($qr[1], ENT_QUOTES) ?>','')">+ <?= htmlspecialchars($qr[0]) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="quick-rows" id="quickRowsSingle" style="display:none;">
        <?php foreach ($quickRows['single_item'] as $qr): ?>
        <button type="button" onclick="addRow('<?= htmlspecialchars($qr[0], ENT_QUOTES) ?>','<?= htmlspecialchars($qr[1], ENT_QUOTES) ?>','')">+ <?= htmlspecialchars($qr[0]) ?></button>
        <?php endforeach; ?>
      </div>
      <div id="breakdownRows"></div>
      <button type="button" class="btn" style="background:#6B6584;margin-bottom:16px;" onclick="addRow('','','')">+ ردیف خالی</button>

      <div class="field-grid2">
        <div>
          <label>واحد پول صدور</label>
          <select name="currency" id="currencySelect">
            <option value="toman" <?= $prefill['currency']==='toman'?'selected':'' ?>>تومان</option>
            <option value="aed" <?= $prefill['currency']==='aed'?'selected':'' ?>>درهم امارات (AED)</option>
          </select>
        </div>
        <div id="exchangeRateField">
          <label>نرخ ارز (تومان به ازای هر درهم — برای نمایش معادل)</label>
          <input type="text" name="exchange_rate" value="<?= htmlspecialchars($prefill['exchange_rate']) ?>" placeholder="مثلاً 51000">
        </div>
      </div>

      <div class="field-grid2">
        <div><label>جمع کل قبل از تخفیف *</label><input type="text" name="total_amount" id="totalAmount" value="<?= htmlspecialchars($prefill['total']) ?>" required></div>
        <div><label>تخفیف (اختیاری)</label><input type="text" name="discount_amount" id="discountAmount" placeholder="مثلاً 50000000" value="<?= htmlspecialchars($prefill['discount'] ?: 0) ?>"></div>
      </div>

      <div class="field-grid2">
        <div><label>اعتبار پیش‌فاکتور تا تاریخ (اختیاری)</label><input type="date" name="valid_until" value="<?= htmlspecialchars($prefill['valid_until']) ?>"></div>
        <div><label>شرایط پرداخت (اختیاری)</label><input type="text" name="payment_terms" value="<?= htmlspecialchars($prefill['payment_terms']) ?>" placeholder="مثلاً ۵۰٪ پیش‌پرداخت، مابقی هنگام تحویل"></div>
      </div>

      <button type="submit" class="btn amber"><?= $editId ? 'ذخیره تغییرات' : 'ثبت و مشاهده پیش‌فاکتور' ?></button>
    </form>
  </div>
</main>
<script>
const prefillBreakdown = <?= json_encode($prefill['breakdown'], JSON_UNESCAPED_UNICODE) ?>;
const rowsWrap = document.getElementById('breakdownRows');
function addRow(label, rate, amount){
  const div = document.createElement('div');
  div.className = 'b-row';
  div.innerHTML = `
    <input type="text" name="b_label[]" placeholder="شرح هزینه" value="${String(label).replace(/"/g,'&quot;')}">
    <input type="text" name="b_rate[]" placeholder="نرخ / توضیح" value="${String(rate).replace(/"/g,'&quot;')}">
    <input type="text" name="b_amount[]" placeholder="مبلغ" value="${String(amount).replace(/"/g,'&quot;')}">
    <button type="button" class="rm-row" onclick="this.parentElement.remove()">حذف</button>
  `;
  rowsWrap.appendChild(div);
}
if(prefillBreakdown.length){
  prefillBreakdown.forEach(r=>addRow(r.label||'', r.rate||'', r.amount||''));
} else {
  addRow('','','');
}

function applyInvoiceType(){
  const isSingle = document.getElementById('typeSingle').checked;
  document.getElementById('carFields').style.display = isSingle ? 'none' : 'grid';
  document.getElementById('quickRowsFull').style.display = isSingle ? 'none' : 'flex';
  document.getElementById('quickRowsSingle').style.display = isSingle ? 'flex' : 'none';
}
document.getElementById('typeFull').addEventListener('change', applyInvoiceType);
document.getElementById('typeSingle').addEventListener('change', applyInvoiceType);
applyInvoiceType();

function applyCurrency(){
  const isAed = document.getElementById('currencySelect').value === 'aed';
  document.getElementById('exchangeRateField').style.display = isAed ? 'block' : 'none';
}
document.getElementById('currencySelect').addEventListener('change', applyCurrency);
applyCurrency();
</script>
</body>
</html>
