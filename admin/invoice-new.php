<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'invoices';
$myId = current_admin_id();

/**
 * دسته‌های خودرو و ضریب سود بازرگانی — دقیقاً مطابق محاسبه‌گر صفحه اول سایت.
 */
$CATEGORIES = [
    'ev'    => ['label' => 'هیبرید / برقی',     'coef' => 1.00],
    'c1500' => ['label' => 'زیر ۱۵۰۰ سی‌سی',     'coef' => 1.10],
    'c2000' => ['label' => '۱۵۰۱ تا ۲۰۰۰',       'coef' => 1.20],
    'c2500' => ['label' => '۲۰۰۱ تا ۲۵۰۰',       'coef' => 1.30],
    'c3000' => ['label' => '۲۵۰۱ تا ۳۰۰۰',       'coef' => 1.45],
    'c3001' => ['label' => 'بالای ۳۰۰۱',         'coef' => 1.65],
];
/** نرخ‌های پیش‌فرض عوارض/مالیات (درصد) — دقیقاً مطابق محاسبه‌گر صفحه اول سایت. */
$RATE_DEFAULTS = [
    'customsFixed' => 4, 'gasoline' => 10, 'fob' => 5, 'vat' => 10, 'advanceTax' => 2,
    'redCrescent' => 1, 'supervision' => 0.5, 'waste' => 0.05, 'standard' => 0.8,
    'scrapCert' => 1.5, 'plateReg' => 10, 'transferTax' => 3, 'municipal' => 1,
    'individual' => 5, 'serviceProfit' => 10,
];
$RATE_LABELS = [
    'customsFixed' => 'حقوق گمرکی ثابت', 'gasoline' => 'عوارض بنزین‌سوز', 'fob' => 'عوارض ۵٪ فوب',
    'vat' => 'مالیات ارزش افزوده (VAT)', 'advanceTax' => 'مالیات علی‌الحساب واردات',
    'redCrescent' => 'عوارض هلال احمر', 'supervision' => 'حق نظارت کارشناسان گمرک',
    'waste' => 'عوارض پسماند کالا', 'standard' => 'هزینه استاندارد',
    'scrapCert' => 'خرید گواهی اسقاط', 'plateReg' => 'عوارض شماره‌گذاری راهور',
    'transferTax' => 'مالیات نقل و انتقال', 'municipal' => 'عوارض سالانه شهرداری',
    'individual' => 'عوارض شخص حقیقی', 'serviceProfit' => 'سود خدمات ناوراکار',
];

/**
 * محاسبه کامل هزینه‌های گمرکی/پلاک — پیاده‌سازی سمت سرور، دقیقاً برابر با
 * تابع calc() در index.html؛ مرجع نهایی برای مبلغ ذخیره‌شده همین تابع است
 * (ورودی سمت کاربر فقط برای پیش‌نمایش زنده در مرورگر استفاده می‌شود).
 */
function navarakar_compute_full($coef, $raw, $rates, $rateLabels) {
    $pct = fn($k) => ($rates[$k] ?? 0) / 100;
    $dec = fn($k, $d = 2) => number_format($rates[$k] ?? 0, $d);

    $CIF = $raw['customsPriceAED'] * $raw['customsRate'];
    $realPriceToman = $raw['realPriceAED'] * $raw['freeRate'];
    $dutyProfit = $coef * $CIF;
    $base9 = $dutyProfit + $CIF;

    $customsRows = [
        ['سود بازرگانی', round($coef * 100) . '٪ از ارزش گمرکی (بر اساس دسته خودرو)', $dutyProfit],
        [$rateLabels['customsFixed'], $dec('customsFixed') . '٪ از ارزش گمرکی', $pct('customsFixed') * $CIF],
        [$rateLabels['gasoline'], $dec('gasoline') . '٪ از ارزش گمرکی', $pct('gasoline') * $CIF],
        [$rateLabels['fob'], $dec('fob') . '٪ از ارزش فوب', $pct('fob') * $CIF],
        [$rateLabels['vat'], $dec('vat') . '٪ از (گمرکی+حقوق ورودی)', $pct('vat') * $base9],
        [$rateLabels['advanceTax'], $dec('advanceTax') . '٪ از (گمرکی+حقوق ورودی)', $pct('advanceTax') * $base9],
        [$rateLabels['redCrescent'], $dec('redCrescent') . '٪ از حقوق ورودی', $pct('redCrescent') * $dutyProfit],
        [$rateLabels['supervision'], $dec('supervision') . '٪ از حقوق ورودی', $pct('supervision') * $dutyProfit],
        [$rateLabels['waste'], $dec('waste', 3) . '٪ از ارزش گمرکی', $pct('waste') * $CIF],
        [$rateLabels['standard'], $dec('standard') . '٪ از ارزش گمرکی', $pct('standard') * $CIF],
    ];
    $sumCustoms10 = array_sum(array_column($customsRows, 2));
    $customsRows[] = ['انبارداری، دموراژ و THC', 'مبلغ دستی وارد شده', $raw['storage']];
    $sumCustomsAll = $sumCustoms10 + $raw['storage'];

    $plateRows = [
        [$rateLabels['scrapCert'], $dec('scrapCert') . '٪ از ارزش گمرکی', $pct('scrapCert') * $CIF],
        [$rateLabels['plateReg'], $dec('plateReg') . '٪ از ارزش گمرکی', $pct('plateReg') * $CIF],
        [$rateLabels['transferTax'], $dec('transferTax') . '٪ از ارزش گمرکی', $pct('transferTax') * $CIF],
        [$rateLabels['municipal'], $dec('municipal') . '٪ از ارزش گمرکی', $pct('municipal') * $CIF],
        [$rateLabels['individual'], $dec('individual') . '٪ از ارزش گمرکی', $pct('individual') * $CIF],
    ];
    $sumPlate = array_sum(array_column($plateRows, 2));

    $seaFreight = $raw['seaFreightAED'] * $raw['freeRate'];
    $permits = $raw['permitsAED'] * $raw['freeRate'];

    $totalNoProfit = $sumCustomsAll + $sumPlate + $realPriceToman + $seaFreight + $permits;
    $serviceProfitAmt = $pct('serviceProfit') * ($sumCustoms10 + $sumPlate + $seaFreight + $permits);
    $totalWithProfit = $totalNoProfit + $serviceProfitAmt;

    $toRow = fn($r) => ['label' => $r[0], 'rate' => $r[1], 'amount' => $r[2]];

    return [
        'realPriceToman' => $realPriceToman,
        'customsRows' => array_map($toRow, $customsRows),
        'plateRows' => array_map($toRow, $plateRows),
        'seaFreight' => $seaFreight, 'permits' => $permits,
        'totalNoProfit' => $totalNoProfit, 'serviceProfitAmt' => $serviceProfitAmt, 'totalWithProfit' => $totalWithProfit,
    ];
}

function navarakar_num($v) { return (float)preg_replace('/[^0-9.]/', '', (string)($v ?? '0')); }

$requestId = (int)($_GET['request_id'] ?? 0);
$editId = (int)($_GET['id'] ?? 0);

$prefill = [
    'name' => '', 'phone' => '', 'email' => '', 'address' => '', 'car' => '',
    'invoice_type' => 'full', 'discount' => 0, 'currency' => 'toman', 'exchange_rate' => '',
    'valid_until' => '', 'payment_terms' => '',
    // فقط برای نوع «خدمت مجزا»
    'breakdown' => [], 'total' => 0,
    // فقط برای نوع «واردات کامل خودرو»
    'category_id' => 'c1500',
    'realPriceAED' => '', 'customsPriceAED' => '', 'freeRate' => '51,000', 'customsRate' => '35,688',
    'seaFreightAED' => '1,500', 'permitsAED' => '60,000', 'storage' => '0',
    'rates' => $RATE_DEFAULTS,
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
        $prefill['discount'] = $inv['discount_amount'] ?? 0;
        $prefill['currency'] = $inv['currency'] ?? 'toman';
        $prefill['exchange_rate'] = $inv['exchange_rate'] ?? '';
        $prefill['valid_until'] = $inv['valid_until'] ?? '';
        $prefill['payment_terms'] = $inv['payment_terms'] ?? '';
        $prefill['invoice_type'] = $inv['invoice_type'] ?? 'full';
        $prefill['total'] = $inv['total_amount'];

        if ($prefill['invoice_type'] === 'single_item') {
            $prefill['breakdown'] = json_decode($inv['breakdown_json'] ?? '[]', true) ?: [];
        } else {
            $ci = json_decode($inv['calc_inputs_json'] ?? '{}', true) ?: [];
            if (isset($CATEGORIES[$ci['category_id'] ?? ''])) $prefill['category_id'] = $ci['category_id'];
            foreach (['realPriceAED', 'customsPriceAED', 'freeRate', 'customsRate', 'seaFreightAED', 'permitsAED', 'storage'] as $k) {
                if (isset($ci[$k])) $prefill[$k] = $ci[$k];
            }
            if (!empty($ci['rates']) && is_array($ci['rates'])) {
                $prefill['rates'] = array_merge($RATE_DEFAULTS, $ci['rates']);
            }
        }
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
        foreach ($CATEGORIES as $cid => $c) {
            if ($c['label'] === $req['category']) { $prefill['category_id'] = $cid; break; }
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
    $currency = in_array($_POST['currency'] ?? '', ['toman', 'aed'], true) ? $_POST['currency'] : 'toman';
    $exchangeRate = trim($_POST['exchange_rate'] ?? '') !== '' ? navarakar_num($_POST['exchange_rate']) : null;
    $validUntilRaw = trim($_POST['valid_until'] ?? '');
    $validUntil = preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntilRaw) ? $validUntilRaw : null;
    $paymentTerms = trim($_POST['payment_terms'] ?? '');
    $discountAmount = navarakar_num($_POST['discount_amount'] ?? '0');
    $invoiceType = ($_POST['invoice_type'] ?? 'full') === 'single_item' ? 'single_item' : 'full';

    if ($customerName === '' || $customerPhone === '') {
        $error = 'نام و تلفن مشتری الزامی است.';
    } else {
        if ($invoiceType === 'full') {
            $categoryId = isset($CATEGORIES[$_POST['category_id'] ?? '']) ? $_POST['category_id'] : 'c1500';
            $coef = $CATEGORIES[$categoryId]['coef'];
            $category = $CATEGORIES[$categoryId]['label'];

            $raw = [
                'realPriceAED' => navarakar_num($_POST['realPriceAED'] ?? '0'),
                'customsPriceAED' => navarakar_num($_POST['customsPriceAED'] ?? '0'),
                'freeRate' => navarakar_num($_POST['freeRate'] ?? '0'),
                'customsRate' => navarakar_num($_POST['customsRate'] ?? '0'),
                'seaFreightAED' => navarakar_num($_POST['seaFreightAED'] ?? '0'),
                'permitsAED' => navarakar_num($_POST['permitsAED'] ?? '0'),
                'storage' => navarakar_num($_POST['storage'] ?? '0'),
            ];
            $rates = [];
            foreach ($RATE_DEFAULTS as $key => $def) {
                $rates[$key] = isset($_POST['r_' . $key]) ? (float)$_POST['r_' . $key] : $def;
            }

            $result = navarakar_compute_full($coef, $raw, $rates, $RATE_LABELS);

            $breakdownData = [
                'car' => ['label' => $carLabel, 'priceAED' => $raw['realPriceAED'], 'priceToman' => $result['realPriceToman']],
                'customs' => $result['customsRows'],
                'plate' => $result['plateRows'],
                'shipping' => ['seaFreight' => $result['seaFreight'], 'permits' => $result['permits']],
                'totals' => ['totalNoProfit' => $result['totalNoProfit'], 'serviceProfit' => $result['serviceProfitAmt'], 'totalWithProfit' => $result['totalWithProfit']],
            ];
            $breakdownJson = json_encode($breakdownData, JSON_UNESCAPED_UNICODE);
            $calcInputsJson = json_encode(['category_id' => $categoryId] + $raw + ['rates' => $rates], JSON_UNESCAPED_UNICODE);
            $totalAmount = $result['totalWithProfit'];
        } else {
            $category = '';
            $labels = $_POST['b_label'] ?? [];
            $rates2 = $_POST['b_rate'] ?? [];
            $amounts = $_POST['b_amount'] ?? [];
            $breakdown = [];
            foreach ($labels as $i => $lbl) {
                if (trim($lbl) === '') continue;
                $breakdown[] = ['label' => trim($lbl), 'rate' => trim($rates2[$i] ?? ''), 'amount' => trim($amounts[$i] ?? '')];
            }
            $breakdownJson = json_encode($breakdown, JSON_UNESCAPED_UNICODE);
            $calcInputsJson = null;
            $totalAmount = navarakar_num($_POST['total_amount'] ?? '0');
        }

        $params = [
            'cn' => $customerName, 'cp' => $customerPhone, 'ce' => $customerEmail, 'ca' => $customerAddress,
            'car' => $carLabel, 'cat' => $category, 'bj' => $breakdownJson, 'cij' => $calcInputsJson,
            'tot' => $totalAmount, 'disc' => $discountAmount, 'cur' => $currency, 'exr' => $exchangeRate,
            'vu' => $validUntil, 'pt' => $paymentTerms, 'it' => $invoiceType,
        ];

        if ($postId > 0) {
            $stmt = $pdo->prepare(
                "UPDATE invoices SET customer_name=:cn, customer_phone=:cp, customer_email=:ce, customer_address=:ca,
                 car_label=:car, category=:cat, breakdown_json=:bj, calc_inputs_json=:cij, total_amount=:tot, discount_amount=:disc,
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
                 car_label, category, breakdown_json, calc_inputs_json, total_amount, discount_amount, currency, exchange_rate, valid_until,
                 payment_terms, invoice_type, status, created_by)
                 VALUES (:rid,'',:cn,:cp,:ce,:ca,:car,:cat,:bj,:cij,:tot,:disc,:cur,:exr,:vu,:pt,:it,'پیش‌نویس',:uid)"
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

$quickRowsSingle = [
    ['صدور مجوز', 'خدمت مجزا', ''],
    ['حمل و نقل', 'خدمت مجزا', ''],
    ['ترخیص گمرکی', 'خدمت مجزا', ''],
    ['مشاوره و پیگیری اداری', 'خدمت مجزا', ''],
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
  .field-grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}
  @media(max-width:640px){.field-grid2,.field-grid3{grid-template-columns:1fr;}}
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
  .calc-fields,#singleFields{display:none;}
  .cat-btns{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;}
  .cat-btns button{border:2px solid var(--border);border-radius:12px;padding:9px 14px;font-family:inherit;font-size:.82rem;font-weight:700;background:#fff;cursor:pointer;color:var(--ink-soft);}
  .cat-btns button.active{border-color:var(--primary);background:var(--primary-light);color:var(--primary-dark);}
  details.adv{margin-bottom:16px;}
  details.adv summary{cursor:pointer;font-weight:700;font-size:.85rem;color:var(--primary-dark);margin-bottom:10px;}
  .rate-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px 14px;}
  @media(max-width:700px){.rate-grid{grid-template-columns:1fr 1fr;}}
  .rate-row{display:flex;align-items:center;justify-content:space-between;gap:8px;background:var(--surface-alt);border-radius:8px;padding:6px 10px;}
  .rate-row span{font-size:.76rem;color:var(--ink-soft);}
  .rate-row input{width:70px;margin:0;padding:6px 8px;font-size:.8rem;}
  table.calc-table{width:100%;border-collapse:collapse;font-size:.82rem;margin-bottom:16px;}
  table.calc-table th{background:var(--surface-alt);text-align:right;padding:8px;font-size:.72rem;color:var(--ink-soft);border-bottom:2px solid var(--border);}
  table.calc-table td{padding:8px;border-bottom:1px solid var(--border);}
  table.calc-table td.amt{text-align:left;font-weight:700;white-space:nowrap;}
  table.calc-table tfoot td{font-weight:900;background:var(--primary-light);color:var(--primary-dark);}
  .calc-summary{background:var(--primary-light);border-radius:12px;padding:14px 18px;margin-bottom:16px;font-size:.86rem;}
  .calc-summary div{display:flex;justify-content:space-between;padding:4px 0;}
  .calc-summary .grand{font-weight:900;font-size:1.05rem;color:var(--primary-dark);border-top:1px dashed var(--border);margin-top:6px;padding-top:8px;}
</style>
</head>
<body>
<?php include 'partials-header.php'; ?>
<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12h6M9 16h6M9 8h6"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>
    <?= $editId ? 'ویرایش پیش‌فاکتور' : 'صدور پیش‌فاکتور جدید' ?>
  </h1>
  <p class="page-sub"><?= $requestId ? 'اطلاعات مشتری از درخواست #' . $requestId . ' پیش‌پر شده — عددهای خام قیمت را وارد کنید تا جدول‌ها محاسبه شود.' : ($editId ? 'در حال ویرایش پیش‌فاکتور موجود؛ هر مقداری را تغییر دهید تا جدول‌ها دوباره محاسبه شود.' : 'اطلاعات مشتری و عددهای خام محاسبه را وارد کنید — دقیقاً مطابق محاسبه‌گر صفحه اول سایت.') ?></p>

  <div class="card">
    <?php if (!empty($error)): ?><div class="error-box"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" class="inv-form" id="invForm">
      <input type="hidden" name="invoice_id" value="<?= $editId ?>">

      <label>نوع پیش‌فاکتور</label>
      <div class="type-toggle">
        <label><input type="radio" name="invoice_type" value="full" id="typeFull" <?= $prefill['invoice_type']!=='single_item'?'checked':'' ?>> واردات کامل خودرو (محاسبه خودکار گمرک/پلاک)</label>
        <label><input type="radio" name="invoice_type" value="single_item" id="typeSingle" <?= $prefill['invoice_type']==='single_item'?'checked':'' ?>> خدمت مجزا (مثلاً فقط مجوز یا فقط حمل)</label>
      </div>

      <div class="field-grid2">
        <div><label>نام مشتری *</label><input type="text" name="customer_name" value="<?= htmlspecialchars($prefill['name']) ?>" required></div>
        <div><label>شماره تماس *</label><input type="text" name="customer_phone" value="<?= htmlspecialchars($prefill['phone']) ?>" required></div>
        <div><label>ایمیل مشتری (اختیاری)</label><input type="email" name="customer_email" value="<?= htmlspecialchars($prefill['email']) ?>"></div>
        <div><label>آدرس مشتری (اختیاری)</label><input type="text" name="customer_address" value="<?= htmlspecialchars($prefill['address']) ?>"></div>
      </div>

      <!-- ===== نوع «واردات کامل خودرو» — ماشین‌حساب کامل، مطابق صفحه اول ===== -->
      <div class="calc-fields" id="calcFields">
        <div class="field-grid2">
          <div><label>خودرو</label><input type="text" name="car_label" id="carLabelInput" value="<?= htmlspecialchars($prefill['car']) ?>"></div>
          <div>
            <label>دسته خودرو (ضریب سود بازرگانی)</label>
            <input type="hidden" name="category_id" id="categoryIdInput" value="<?= htmlspecialchars($prefill['category_id']) ?>">
            <div class="cat-btns" id="catBtns">
              <?php foreach ($CATEGORIES as $cid => $c): ?>
              <button type="button" data-cid="<?= $cid ?>" class="cat-btn <?= $cid===$prefill['category_id']?'active':'' ?>"><?= htmlspecialchars($c['label']) ?> (<?= number_format($c['coef'],2) ?>)</button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="field-grid3">
          <div><label>قیمت واقعی خودرو (درهم)</label><input type="text" inputmode="decimal" id="realPriceAED" name="realPriceAED" value="<?= htmlspecialchars($prefill['realPriceAED']) ?>" placeholder="مثلاً 90,000"></div>
          <div><label>ارزش گمرکی خودرو (درهم)</label><input type="text" inputmode="decimal" id="customsPriceAED" name="customsPriceAED" value="<?= htmlspecialchars($prefill['customsPriceAED']) ?>" placeholder="مثلاً 85,000"></div>
          <div><label>انبارداری/دموراژ/THC (تومان، دستی)</label><input type="text" inputmode="decimal" id="storage" name="storage" value="<?= htmlspecialchars($prefill['storage']) ?>"></div>
          <div><label>نرخ ارز آزاد (تومان)</label><input type="text" inputmode="decimal" id="freeRate" name="freeRate" value="<?= htmlspecialchars($prefill['freeRate']) ?>"></div>
          <div><label>نرخ ارز گمرک (تومان)</label><input type="text" inputmode="decimal" id="customsRate" name="customsRate" value="<?= htmlspecialchars($prefill['customsRate']) ?>"></div>
          <div><label>حمل دریایی (درهم)</label><input type="text" inputmode="decimal" id="seaFreightAED" name="seaFreightAED" value="<?= htmlspecialchars($prefill['seaFreightAED']) ?>"></div>
          <div><label>صدور مجوزها (درهم)</label><input type="text" inputmode="decimal" id="permitsAED" name="permitsAED" value="<?= htmlspecialchars($prefill['permitsAED']) ?>"></div>
        </div>

        <details class="adv">
          <summary>تنظیمات پیشرفته نرخ‌ها و عوارض</summary>
          <div class="rate-grid">
            <?php foreach ($RATE_LABELS as $key => $lbl): ?>
            <div class="rate-row"><span><?= htmlspecialchars($lbl) ?></span><input type="number" step="any" id="r_<?= $key ?>" name="r_<?= $key ?>" value="<?= htmlspecialchars($prefill['rates'][$key] ?? $RATE_DEFAULTS[$key]) ?>"></div>
            <?php endforeach; ?>
          </div>
        </details>

        <h3 class="ps-h3" style="font-size:.92rem;">تفکیک هزینه‌های ترخیص گمرکی</h3>
        <table class="calc-table"><thead><tr><th>شرح هزینه</th><th>نرخ</th><th>مبلغ (تومان)</th></tr></thead><tbody id="invTblCustoms"></tbody><tfoot><tr><td colspan="2">جمع هزینه‌های گمرکی</td><td class="amt" id="invSumCustoms"></td></tr></tfoot></table>

        <h3 class="ps-h3" style="font-size:.92rem;">تفکیک هزینه‌های پلاک انتظامی</h3>
        <table class="calc-table"><thead><tr><th>شرح هزینه</th><th>نرخ</th><th>مبلغ (تومان)</th></tr></thead><tbody id="invTblPlate"></tbody><tfoot><tr><td colspan="2">جمع هزینه‌های پلاک</td><td class="amt" id="invSumPlate"></td></tr></tfoot></table>

        <div class="calc-summary">
          <div><span>قیمت خودرو (اصل کالا)</span><b id="invCarPrice">0</b></div>
          <div><span>حمل دریایی و صدور مجوزها</span><b id="invShipping">0</b></div>
          <div><span>جمع کل بدون سود خدمات</span><b id="invNoProfit">0</b></div>
          <div><span>سود خدمات ناوراکار</span><b id="invServiceProfit">0</b></div>
          <div class="grand"><span>جمع کل نهایی (تومان)</span><b id="invGrandTotal">0</b></div>
        </div>
      </div>

      <!-- ===== نوع «خدمت مجزا» — ردیف‌های آزاد، مثل قبل ===== -->
      <div id="singleFields">
        <label style="margin-top:8px;">اقلام پیش‌فاکتور</label>
        <div class="quick-rows" id="quickRowsSingle">
          <?php foreach ($quickRowsSingle as $qr): ?>
          <button type="button" onclick="addRow('<?= htmlspecialchars($qr[0], ENT_QUOTES) ?>','<?= htmlspecialchars($qr[1], ENT_QUOTES) ?>','')">+ <?= htmlspecialchars($qr[0]) ?></button>
          <?php endforeach; ?>
        </div>
        <div id="breakdownRows"></div>
        <button type="button" class="btn" style="background:#6B6584;margin-bottom:16px;" onclick="addRow('','','')">+ ردیف خالی</button>
        <div><label>جمع کل قبل از تخفیف *</label><input type="text" name="total_amount" id="totalAmount" value="<?= htmlspecialchars($prefill['total']) ?>"></div>
      </div>

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
        <div><label>تخفیف (اختیاری)</label><input type="text" name="discount_amount" id="discountAmount" placeholder="مثلاً 50000000" value="<?= htmlspecialchars($prefill['discount'] ?: 0) ?>"></div>
        <div><label>اعتبار پیش‌فاکتور تا تاریخ (اختیاری)</label><input type="date" name="valid_until" value="<?= htmlspecialchars($prefill['valid_until']) ?>"></div>
      </div>
      <div><label>شرایط پرداخت (اختیاری)</label><input type="text" name="payment_terms" value="<?= htmlspecialchars($prefill['payment_terms']) ?>" placeholder="مثلاً ۵۰٪ پیش‌پرداخت، مابقی هنگام تحویل"></div>

      <button type="submit" class="btn amber"><?= $editId ? 'ذخیره تغییرات' : 'ثبت و مشاهده پیش‌فاکتور' ?></button>
    </form>
  </div>
</main>
<script>
/* ---- نوع «خدمت مجزا»: ردیف‌های آزاد (بدون تغییر نسبت به قبل) ---- */
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
  document.getElementById('calcFields').style.display = isSingle ? 'none' : 'block';
  document.getElementById('singleFields').style.display = isSingle ? 'block' : 'none';
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

/* ---- نوع «واردات کامل خودرو»: ماشین‌حساب زنده، مطابق فرمول صفحه اول ---- */
const CATEGORY_COEF = <?= json_encode(array_map(fn($c)=>$c['coef'], $CATEGORIES), JSON_UNESCAPED_UNICODE) ?>;
const RATE_LABELS = <?= json_encode($RATE_LABELS, JSON_UNESCAPED_UNICODE) ?>;
let activeCategoryId = document.getElementById('categoryIdInput').value;

document.querySelectorAll('.cat-btn').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    activeCategoryId = btn.dataset.cid;
    document.getElementById('categoryIdInput').value = activeCategoryId;
    document.querySelectorAll('.cat-btn').forEach(b=>b.classList.toggle('active', b===btn));
    calcInvoice();
  });
});

['realPriceAED','customsPriceAED','freeRate','customsRate','seaFreightAED','permitsAED','storage'].forEach(id=>{
  document.getElementById(id).addEventListener('input', (e)=>{
    const raw = e.target.value.replace(/[^\d.]/g,'');
    const parts = raw.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    e.target.value = parts.length>1 ? parts[0]+'.'+parts[1] : parts[0];
    calcInvoice();
  });
});
document.querySelectorAll('#calcFields details.adv input').forEach(inp=>inp.addEventListener('input', calcInvoice));

function invNum(id){ const raw=(document.getElementById(id).value||'').replace(/,/g,''); const v=parseFloat(raw); return isNaN(v)||v<0 ? 0 : v; }
function invRate(key){ const v = parseFloat(document.getElementById('r_'+key).value); return isNaN(v) ? 0 : v; }
function invFmt(n){ return Math.round(n).toLocaleString('en-US'); }

function calcInvoice(){
  const coef = CATEGORY_COEF[activeCategoryId] ?? 1;
  const realPriceAED = invNum('realPriceAED');
  const customsPriceAED = invNum('customsPriceAED');
  const freeRate = invNum('freeRate');
  const customsRate = invNum('customsRate');
  const seaFreightAED = invNum('seaFreightAED');
  const permitsAED = invNum('permitsAED');
  const storage = invNum('storage');

  const CIF = customsPriceAED * customsRate;
  const realPriceToman = realPriceAED * freeRate;
  const dutyProfit = coef * CIF;
  const base9 = dutyProfit + CIF;

  const r = {};
  Object.keys(RATE_LABELS).forEach(k=>{ r[k] = invRate(k)/100; });

  const customsRows = [
    ['سود بازرگانی', `${Math.round(coef*100)}٪ از ارزش گمرکی (بر اساس دسته خودرو)`, dutyProfit],
    [RATE_LABELS.customsFixed, `${(r.customsFixed*100).toFixed(2)}٪ از ارزش گمرکی`, r.customsFixed*CIF],
    [RATE_LABELS.gasoline, `${(r.gasoline*100).toFixed(2)}٪ از ارزش گمرکی`, r.gasoline*CIF],
    [RATE_LABELS.fob, `${(r.fob*100).toFixed(2)}٪ از ارزش فوب`, r.fob*CIF],
    [RATE_LABELS.vat, `${(r.vat*100).toFixed(2)}٪ از (گمرکی+حقوق ورودی)`, r.vat*base9],
    [RATE_LABELS.advanceTax, `${(r.advanceTax*100).toFixed(2)}٪ از (گمرکی+حقوق ورودی)`, r.advanceTax*base9],
    [RATE_LABELS.redCrescent, `${(r.redCrescent*100).toFixed(2)}٪ از حقوق ورودی`, r.redCrescent*dutyProfit],
    [RATE_LABELS.supervision, `${(r.supervision*100).toFixed(2)}٪ از حقوق ورودی`, r.supervision*dutyProfit],
    [RATE_LABELS.waste, `${(r.waste*100).toFixed(3)}٪ از ارزش گمرکی`, r.waste*CIF],
    [RATE_LABELS.standard, `${(r.standard*100).toFixed(2)}٪ از ارزش گمرکی`, r.standard*CIF],
  ];
  const sumCustoms10 = customsRows.reduce((s,row)=>s+row[2],0);
  customsRows.push(['انبارداری، دموراژ و THC','مبلغ دستی وارد شده', storage]);
  const sumCustomsAll = sumCustoms10 + storage;

  const plateRows = [
    [RATE_LABELS.scrapCert, `${(r.scrapCert*100).toFixed(2)}٪ از ارزش گمرکی`, r.scrapCert*CIF],
    [RATE_LABELS.plateReg, `${(r.plateReg*100).toFixed(2)}٪ از ارزش گمرکی`, r.plateReg*CIF],
    [RATE_LABELS.transferTax, `${(r.transferTax*100).toFixed(2)}٪ از ارزش گمرکی`, r.transferTax*CIF],
    [RATE_LABELS.municipal, `${(r.municipal*100).toFixed(2)}٪ از ارزش گمرکی`, r.municipal*CIF],
    [RATE_LABELS.individual, `${(r.individual*100).toFixed(2)}٪ از ارزش گمرکی`, r.individual*CIF],
  ];
  const sumPlate = plateRows.reduce((s,row)=>s+row[2],0);

  const seaFreight = seaFreightAED * freeRate;
  const permits = permitsAED * freeRate;

  const totalNoProfit = sumCustomsAll + sumPlate + realPriceToman + seaFreight + permits;
  const serviceProfitAmt = r.serviceProfit * (sumCustoms10 + sumPlate + seaFreight + permits);
  const totalWithProfit = totalNoProfit + serviceProfitAmt;

  const rowHtml = row => `<tr><td>${row[0]}</td><td style="color:var(--ink-soft);font-size:.76rem;">${row[1]}</td><td class="amt">${invFmt(row[2])}</td></tr>`;
  document.getElementById('invTblCustoms').innerHTML = customsRows.map(rowHtml).join('');
  document.getElementById('invSumCustoms').textContent = invFmt(sumCustomsAll);
  document.getElementById('invTblPlate').innerHTML = plateRows.map(rowHtml).join('');
  document.getElementById('invSumPlate').textContent = invFmt(sumPlate);

  document.getElementById('invCarPrice').textContent = invFmt(realPriceToman) + ' تومان';
  document.getElementById('invShipping').textContent = invFmt(seaFreight+permits) + ' تومان';
  document.getElementById('invNoProfit').textContent = invFmt(totalNoProfit) + ' تومان';
  document.getElementById('invServiceProfit').textContent = invFmt(serviceProfitAmt) + ' تومان';
  document.getElementById('invGrandTotal').textContent = invFmt(totalWithProfit) + ' تومان';
}
calcInvoice();
</script>
</body>
</html>
