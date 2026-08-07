<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'invoices';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id");
$stmt->execute(['id' => $id]);
$inv = $stmt->fetch();
if (!$inv) { header('Location: invoices.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = trim($_POST['status']);
    if (in_array($newStatus, ['پیش‌نویس','ارسال‌شده','تایید شده'], true)) {
        $pdo->prepare("UPDATE invoices SET status = :s WHERE id = :id")->execute(['s' => $newStatus, 'id' => $id]);
        $inv['status'] = $newStatus;
    }
}

$isSingleItem = ($inv['invoice_type'] ?? 'full') === 'single_item';
$decoded = json_decode($inv['breakdown_json'] ?? ($isSingleItem ? '[]' : '{}'), true) ?: [];
$breakdown = $isSingleItem ? $decoded : [];
$customsRows = !$isSingleItem ? ($decoded['customs'] ?? []) : [];
$plateRows = !$isSingleItem ? ($decoded['plate'] ?? []) : [];
$carInfo = !$isSingleItem ? ($decoded['car'] ?? []) : [];
$shipping = !$isSingleItem ? ($decoded['shipping'] ?? []) : [];
$calcTotals = !$isSingleItem ? ($decoded['totals'] ?? []) : [];
$discount = (float)($inv['discount_amount'] ?? 0);
$grandTotal = (float)$inv['total_amount'];
$payable = $grandTotal - $discount;
$currency = $inv['currency'] ?? 'toman';
$unitLabel = $currency === 'aed' ? 'درهم (AED)' : 'تومان';
$exRate = (float)($inv['exchange_rate'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>پیش‌فاکتور <?= htmlspecialchars($inv['invoice_number']) ?> | ناوراکار</title>
<style>
  :root{--ink:#1A1730;--ink-soft:#6B6584;--primary:#2D2657;--primary-dark:#171433;--amber:#C9A227;--amber-dark:#9C7726;--border:#E4DFD3;--primary-light:#EDE9F7;--surface-alt:#F5F7FC;--font:'Vazirmatn','Tahoma','Segoe UI',Arial,sans-serif;}
  *{box-sizing:border-box;}
  html{overflow-x:hidden;}
  body{margin:0;overflow-x:hidden;max-width:100vw;background:#EEEAE2;font-family:var(--font);color:var(--ink);padding:24px 12px;}
  .toolbar{max-width:800px;margin:0 auto 16px;display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;}
  .toolbar button,.toolbar a{border:none;border-radius:10px;padding:10px 16px;font-family:inherit;font-weight:700;font-size:.85rem;cursor:pointer;text-decoration:none;}
  .btn-print{background:var(--primary);color:#fff;}
  .btn-edit{background:var(--amber);color:#1A1200;}
  .btn-back{background:#fff;color:var(--ink-soft);border:1px solid var(--border) !important;}

  .print-only{display:block;max-width:800px;margin:0 auto;}
  .ps-header{background:linear-gradient(120deg,var(--primary-dark),var(--primary));color:#fff;padding:28px 32px;border-radius:18px 18px 0 0;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;}
  .ps-brand{font-size:1.5rem;font-weight:900;}
  .ps-brand span{display:block;font-size:.82rem;font-weight:500;color:#D6CFF0;margin-top:3px;}
  .ps-meta{text-align:left;font-size:.85rem;line-height:1.9;}
  .ps-meta b{color:var(--amber);}
  .ps-body{background:#fff;padding:28px 32px;border:1px solid var(--border);border-top:none;border-radius:0 0 18px 18px;}
  .ps-car-box{background:var(--primary-light);border-radius:12px;padding:16px 18px;margin-bottom:22px;font-size:.92rem;}
  .ps-car-title{font-weight:900;color:var(--primary-dark);font-size:1rem;margin-bottom:10px;}
  .ps-car-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;}
  .ps-car-grid div span{display:block;color:var(--ink-soft);font-size:.74rem;margin-bottom:2px;}
  .ps-h3{font-size:.98rem;font-weight:800;color:var(--primary-dark);margin:22px 0 8px;border-bottom:2px solid var(--primary-light);padding-bottom:6px;}
  table.ps-table{width:100%;border-collapse:collapse;font-size:.88rem;}
  table.ps-table th{background:var(--surface-alt);text-align:right;padding:10px;font-size:.78rem;color:var(--ink-soft);border-bottom:2px solid var(--border);}
  table.ps-table td{padding:10px;border-bottom:1px solid var(--border);}
  table.ps-table td.amt{text-align:left;font-weight:700;white-space:nowrap;}
  table.ps-table tr.discount-row td{color:#B45309;font-weight:700;}
  table.ps-table tr.total-row td{font-weight:900;font-size:1.05rem;background:var(--primary-light);color:var(--primary-dark);}
  .status-badge{display:inline-block;padding:5px 14px;border-radius:999px;font-size:.78rem;font-weight:800;background:var(--amber);color:#3a2c00;}
  .type-badge{display:inline-block;padding:4px 12px;border-radius:999px;font-size:.72rem;font-weight:800;background:#DBEAFE;color:#1E40AF;margin-inline-start:8px;}
  .ps-contact{background:var(--surface-alt);border-radius:12px;padding:16px 18px;margin-top:22px;font-size:.85rem;line-height:2.1;}
  .ps-contact .ps-contact-title{font-weight:800;color:var(--primary-dark);margin-bottom:6px;}
  .ps-terms{font-size:.8rem;color:var(--ink-soft);margin-top:14px;line-height:1.9;}
  .sign-area{display:flex;justify-content:space-between;margin-top:36px;padding-top:18px;border-top:1px dashed var(--border);font-size:.82rem;color:var(--ink-soft);}
  .disclaimer{font-size:.74rem;color:var(--ink-soft);margin-top:22px;border-top:1px solid var(--border);padding-top:14px;line-height:1.8;}
  @media print{ body{background:#fff;padding:0;} .toolbar{display:none;} .ps-header,.ps-body{border-radius:0;} }
</style>
</head>
<body>
  <div class="toolbar">
    <a href="invoices.php" class="btn-back">← بازگشت به لیست</a>
    <a href="invoice-new.php?id=<?= $id ?>" class="btn-edit">✏️ ویرایش پیش‌فاکتور</a>
    <form method="post" style="display:inline-flex;gap:8px;">
      <select name="status" onchange="this.form.submit()" style="border-radius:10px;border:1px solid var(--border);padding:9px 12px;font-family:inherit;">
        <option value="پیش‌نویس" <?= $inv['status']==='پیش‌نویس'?'selected':'' ?>>پیش‌نویس</option>
        <option value="ارسال‌شده" <?= $inv['status']==='ارسال‌شده'?'selected':'' ?>>ارسال‌شده</option>
        <option value="تایید شده" <?= $inv['status']==='تایید شده'?'selected':'' ?>>تایید شده (فروخته شد)</option>
      </select>
    </form>
    <button class="btn-print" onclick="window.print()">چاپ / ذخیره PDF</button>
  </div>

  <div class="print-only">
    <div class="ps-header">
      <div class="ps-brand">ناوراکار<span>پیش‌فاکتور رسمی <?= $isSingleItem ? '— خدمت مجزا' : 'هزینه واردات خودرو' ?></span></div>
      <div class="ps-meta">
        شماره: <b><?= htmlspecialchars($inv['invoice_number']) ?></b><br>
        تاریخ: <?= htmlspecialchars(substr($inv['created_at'],0,10)) ?><br>
        <?php if ($inv['valid_until']): ?>اعتبار تا: <b><?= htmlspecialchars($inv['valid_until']) ?></b><br><?php endif; ?>
        وضعیت: <span class="status-badge"><?= htmlspecialchars($inv['status']) ?></span>
      </div>
    </div>
    <div class="ps-body">
      <div class="ps-car-box">
        <div class="ps-car-title">مشخصات مشتری<?= $isSingleItem ? '' : ' و خودرو' ?></div>
        <div class="ps-car-grid">
          <div><span>نام مشتری</span><?= htmlspecialchars($inv['customer_name']) ?></div>
          <div><span>شماره تماس</span><?= htmlspecialchars($inv['customer_phone']) ?></div>
          <?php if (!empty($inv['customer_email'])): ?><div><span>ایمیل</span><?= htmlspecialchars($inv['customer_email']) ?></div><?php endif; ?>
          <?php if (!$isSingleItem): ?>
          <div><span>خودرو</span><?= htmlspecialchars($inv['car_label'] ?: '-') ?></div>
          <div><span>دسته خودرو</span><?= htmlspecialchars($inv['category'] ?: '-') ?></div>
          <?php if (!empty($carInfo['priceAED'])): ?>
          <div><span>قیمت خودرو (درهم)</span><?= number_format($carInfo['priceAED']) ?> AED</div>
          <div><span>قیمت خودرو (معادل تومانی)</span><?= number_format($carInfo['priceToman'] ?? 0) ?> تومان</div>
          <?php endif; ?>
          <?php endif; ?>
          <?php if ($inv['customer_address']): ?><div style="grid-column:1/-1;"><span>آدرس</span><?= htmlspecialchars($inv['customer_address']) ?></div><?php endif; ?>
        </div>
      </div>

      <?php if ($isSingleItem): ?>
      <div class="ps-h3">تفکیک هزینه‌ها <span style="font-size:.72rem;color:var(--ink-soft);font-weight:600;">(واحد: <?= $unitLabel ?>)</span></div>
      <table class="ps-table">
        <thead><tr><th>شرح</th><th>نرخ / توضیح</th><th>مبلغ</th></tr></thead>
        <tbody>
          <?php foreach ($breakdown as $row): ?>
          <tr><td><?= htmlspecialchars($row['label'] ?? '') ?></td><td style="color:var(--ink-soft);font-size:.8rem;"><?= htmlspecialchars($row['rate'] ?? '') ?></td><td class="amt"><?= htmlspecialchars($row['amount'] ?? '') ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="ps-h3">تفکیک هزینه‌های ترخیص گمرکی</div>
      <table class="ps-table">
        <thead><tr><th>شرح</th><th>نرخ / توضیح</th><th>مبلغ (تومان)</th></tr></thead>
        <tbody>
          <?php foreach ($customsRows as $row): ?>
          <tr><td><?= htmlspecialchars($row['label'] ?? '') ?></td><td style="color:var(--ink-soft);font-size:.8rem;"><?= htmlspecialchars($row['rate'] ?? '') ?></td><td class="amt"><?= number_format($row['amount'] ?? 0) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="ps-h3">تفکیک هزینه‌های پلاک انتظامی</div>
      <table class="ps-table">
        <thead><tr><th>شرح</th><th>نرخ / توضیح</th><th>مبلغ (تومان)</th></tr></thead>
        <tbody>
          <?php foreach ($plateRows as $row): ?>
          <tr><td><?= htmlspecialchars($row['label'] ?? '') ?></td><td style="color:var(--ink-soft);font-size:.8rem;"><?= htmlspecialchars($row['rate'] ?? '') ?></td><td class="amt"><?= number_format($row['amount'] ?? 0) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="ps-h3">حمل و مجوزها</div>
      <table class="ps-table">
        <tbody>
          <tr><td colspan="2">حمل دریایی و صدور مجوزها</td><td class="amt"><?= number_format($shipping['seaFreight'] ?? 0) ?> + <?= number_format($shipping['permits'] ?? 0) ?> تومان</td></tr>
        </tbody>
      </table>

      <div class="ps-h3">جمع‌بندی محاسبه</div>
      <table class="ps-table">
        <tbody>
          <tr><td colspan="2">جمع کل بدون سود خدمات</td><td class="amt"><?= number_format($calcTotals['totalNoProfit'] ?? 0) ?> تومان</td></tr>
          <tr><td colspan="2">سود خدمات ناوراکار</td><td class="amt"><?= number_format($calcTotals['serviceProfit'] ?? 0) ?> تومان</td></tr>
          <tr class="total-row"><td colspan="2">جمع کل نهایی</td><td class="amt"><?= number_format($calcTotals['totalWithProfit'] ?? 0) ?> تومان</td></tr>
        </tbody>
      </table>
      <?php endif; ?>

      <div class="ps-h3">جمع‌بندی نهایی پیش‌فاکتور</div>
      <table class="ps-table">
        <tbody>
          <tr><td colspan="2">جمع کل قبل از تخفیف</td><td class="amt"><?= number_format($grandTotal) ?> <?= $unitLabel ?></td></tr>
          <?php if ($discount > 0): ?>
          <tr class="discount-row"><td colspan="2">تخفیف</td><td class="amt">− <?= number_format($discount) ?> <?= $unitLabel ?></td></tr>
          <?php endif; ?>
          <tr class="total-row"><td colspan="2">مبلغ قابل‌پرداخت</td><td class="amt"><?= number_format($payable) ?> <?= $unitLabel ?></td></tr>
          <?php if ($currency === 'aed' && $exRate > 0): ?>
          <tr><td colspan="2" style="color:var(--ink-soft);font-size:.78rem;">معادل تقریبی به تومان (نرخ <?= number_format($exRate) ?>)</td><td class="amt" style="color:var(--ink-soft);font-size:.85rem;"><?= number_format($payable * $exRate) ?> تومان</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if (!empty($inv['payment_terms'])): ?>
      <div class="ps-terms"><b>شرایط پرداخت:</b> <?= htmlspecialchars($inv['payment_terms']) ?></div>
      <?php endif; ?>

      <div class="ps-contact">
        <div class="ps-contact-title">📞 ارتباط با ما</div>
        🇮🇷 +98 912 051 2149 (واتس‌اپ | بله | تلگرام)<br>
        🇦🇪 +971 50 515 8484 (واتس‌اپ | تلگرام)<br>
        ☎️ +98 21 8887 0878 (دفتر تهران)<br>
        🌐 navaracar.com
      </div>

      <div class="sign-area">
        <div>مهر و امضای ناوراکار</div>
        <div>امضای مشتری</div>
      </div>

      <div class="disclaimer">
        این پیش‌فاکتور بر اساس اطلاعات و نرخ‌های ثبت‌شده در تاریخ صدور تنظیم شده و ممکن است با تغییر مقررات گمرکی یا نرخ ارز به‌روزرسانی شود.
        این سند صرفاً جنبه برآوردی دارد و برای تعیین قطعی، قرارداد نهایی با کارشناسان ناوراکار ملاک عمل است.
      </div>
    </div>
  </div>
</body>
</html>
