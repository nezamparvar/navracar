<?php
require 'auth-check.php';
require '../db-config.php';
$activePage = 'kanban';
$myId = current_admin_id();
$myRole = current_admin_role();

$stages = $pdo->query("SELECT * FROM pipeline_stages WHERE is_active = 1 ORDER BY sort_order")->fetchAll();

// --- فیلترها (ساده، بدون رفرش کامل امکان‌پذیر نیست چون سمت PHP فیلتر می‌شود، اما سبک و سریع است) ---
$fTemp = trim($_GET['temp'] ?? '');
$fSource = trim($_GET['source'] ?? '');
$fSearch = trim($_GET['q'] ?? '');
$fSales = trim($_GET['sales'] ?? '');

$where = [];
$params = [];
if ($myRole !== 'admin') {
    $where[] = "qr.assigned_to = :myId";
    $params['myId'] = $myId;
} elseif ($fSales !== '' && $fSales !== 'all') {
    $where[] = "qr.assigned_to = :fsales";
    $params['fsales'] = (int)$fSales;
}
if ($fTemp !== '') { $where[] = "qr.temperature = :ftemp"; $params['ftemp'] = $fTemp; }
if ($fSource !== '') { $where[] = "qr.source = :fsource"; $params['fsource'] = $fSource; }
if ($fSearch !== '') {
    $where[] = "(qr.name LIKE :q1 OR qr.phone LIKE :q2)";
    $params['q1'] = '%' . $fSearch . '%';
    $params['q2'] = '%' . $fSearch . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT qr.*, au.username AS assigned_username, au.full_name AS assigned_name
                        FROM quote_requests qr LEFT JOIN admin_users au ON au.id = qr.assigned_to
                        $whereSql ORDER BY qr.created_at DESC");
$stmt->execute($params);
$allLeads = $stmt->fetchAll();

$leadsByStage = [];
foreach ($stages as $s) { $leadsByStage[$s['id']] = []; }
foreach ($allLeads as $l) {
    $sid = (int)($l['current_stage_id'] ?: 1);
    if (isset($leadsByStage[$sid])) $leadsByStage[$sid][] = $l;
}

$staffList = $myRole === 'admin' ? $pdo->query("SELECT id, username, full_name FROM admin_users ORDER BY username")->fetchAll() : [];
$lossReasons = $pdo->query("SELECT * FROM loss_reasons WHERE is_active = 1")->fetchAll();
$sources = $pdo->query("SELECT DISTINCT source FROM quote_requests WHERE source IS NOT NULL AND source != ''")->fetchAll();

function tempInfo($t) {
    if ($t === 'hot') return ['داغ', '#DC2626', '#FEE2E2'];
    if ($t === 'cold') return ['سرد', '#2563EB', '#DBEAFE'];
    return ['معمولی', '#D97706', '#FEF3C7'];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>پایپ‌لاین فروش (کانبان) | پنل مدیریت ناوراکار</title>
<?php include 'style.php'; ?>
<style>
  .kanban-scroll{overflow-x:auto;padding-bottom:14px;width:100%;max-width:100%;}
  .kanban-board{display:flex;gap:14px;min-width:max-content;align-items:flex-start;}
  .kanban-col{width:270px;flex-shrink:0;background:var(--surface-alt);border:1px solid var(--border);border-radius:16px;padding:12px;}
  .kanban-col-head{display:flex;justify-content:space-between;align-items:center;padding:4px 6px 10px;border-bottom:2px solid var(--border);margin-bottom:10px;}
  .kanban-col-head h3{font-size:.86rem;font-weight:800;color:var(--primary-dark);margin:0;}
  .kanban-count{background:var(--primary-light);color:var(--primary-dark);font-size:.72rem;font-weight:800;padding:2px 9px;border-radius:999px;}
  .kanban-col.drag-over{background:var(--primary-light);border-color:var(--primary);}
  .kanban-cards{display:flex;flex-direction:column;gap:9px;min-height:40px;}
  .kanban-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:11px 12px;cursor:grab;box-shadow:0 4px 10px -6px rgba(16,22,60,.15);transition:.15s transform,.15s box-shadow;}
  .kanban-card:hover{transform:translateY(-2px);box-shadow:var(--shadow);}
  .kanban-card.dragging{opacity:.4;}
  .kanban-card .kc-top{display:flex;justify-content:space-between;align-items:flex-start;gap:6px;margin-bottom:6px;}
  .kanban-card .kc-name{font-weight:800;font-size:.86rem;color:var(--ink);}
  .kanban-card .kc-temp{font-size:.66rem;font-weight:800;padding:2px 8px;border-radius:999px;white-space:nowrap;}
  .kanban-card .kc-phone{font-size:.78rem;color:var(--ink-soft);font-family:monospace;}
  .kanban-card .kc-meta{display:flex;justify-content:space-between;align-items:center;margin-top:8px;font-size:.7rem;color:var(--ink-soft);}
  .kanban-card .kc-car{font-size:.76rem;color:var(--ink-soft);margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  .kanban-empty{font-size:.76rem;color:var(--ink-soft);text-align:center;padding:16px 4px;}
  .kanban-filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;align-items:end;}
  .kanban-filters .field{display:flex;flex-direction:column;gap:5px;}
  .kanban-filters label{font-size:.76rem;font-weight:700;color:var(--ink-soft);}
  .kanban-filters input,.kanban-filters select{padding:9px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:var(--font);font-size:.86rem;background:var(--surface-alt);}
</style>
</head>
<body>
<?php include 'partials-header.php'; ?>
<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="18" rx="1.5"/><rect x="14" y="3" width="7" height="10" rx="1.5"/></svg>
    پایپ‌لاین فروش (کانبان)
  </h1>
  <p class="page-sub">کارت‌ها را بکشید و بین مراحل جابه‌جا کنید — تغییر بلافاصله ذخیره می‌شود.</p>

  <form method="get" class="kanban-filters">
    <div class="field"><label>جستجو (نام/تلفن)</label><input type="text" name="q" value="<?= htmlspecialchars($fSearch) ?>"></div>
    <div class="field">
      <label>دما</label>
      <select name="temp">
        <option value="">همه</option>
        <option value="hot" <?= $fTemp==='hot'?'selected':'' ?>>داغ 🔴</option>
        <option value="warm" <?= $fTemp==='warm'?'selected':'' ?>>معمولی 🟠</option>
        <option value="cold" <?= $fTemp==='cold'?'selected':'' ?>>سرد 🔵</option>
      </select>
    </div>
    <div class="field">
      <label>منبع</label>
      <select name="source">
        <option value="">همه</option>
        <?php foreach ($sources as $s): ?>
        <option value="<?= htmlspecialchars($s['source']) ?>" <?= $fSource===$s['source']?'selected':'' ?>><?= htmlspecialchars($s['source']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($myRole === 'admin'): ?>
    <div class="field">
      <label>کارشناس</label>
      <select name="sales">
        <option value="all">همه</option>
        <?php foreach ($staffList as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $fSales==(string)$s['id']?'selected':'' ?>><?= htmlspecialchars($s['full_name'] ?: $s['username']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <button type="submit" class="btn">اعمال فیلتر</button>
    <a href="kanban.php" class="btn" style="background:#5B6478;">پاک کردن</a>
  </form>

  <div class="kanban-scroll">
    <div class="kanban-board" id="kanbanBoard">
      <?php foreach ($stages as $stage): $leads = $leadsByStage[$stage['id']]; ?>
      <div class="kanban-col" data-stage-id="<?= $stage['id'] ?>">
        <div class="kanban-col-head">
          <h3><?= htmlspecialchars($stage['name']) ?></h3>
          <span class="kanban-count"><?= count($leads) ?></span>
        </div>
        <div class="kanban-cards" data-stage-id="<?= $stage['id'] ?>">
          <?php if (empty($leads)): ?>
            <div class="kanban-empty">کارتی نیست</div>
          <?php endif; ?>
          <?php foreach ($leads as $l): $t = tempInfo($l['temperature']); ?>
          <div class="kanban-card" draggable="true" data-lead-id="<?= (int)$l['id'] ?>" onclick="if(!window.__dragging) location.href='request-view.php?id=<?= (int)$l['id'] ?>'">
            <div class="kc-top">
              <span class="kc-name"><?= htmlspecialchars($l['name']) ?></span>
              <span class="kc-temp" style="background:<?= $t[2] ?>;color:<?= $t[1] ?>;"><?= $t[0] ?></span>
            </div>
            <div class="kc-phone num-font"><?= htmlspecialchars($l['phone']) ?></div>
            <?php if ($l['car_label']): ?><div class="kc-car">🚗 <?= htmlspecialchars($l['car_label']) ?></div><?php endif; ?>
            <div class="kc-meta">
              <span><?= htmlspecialchars($l['assigned_name'] ?: ($l['assigned_username'] ?: 'بدون الحاق')) ?></span>
              <span><?= htmlspecialchars(substr($l['created_at'],5,11)) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<div class="toast-wrap" id="toastWrap"></div>

<script>
const lossReasons = <?= json_encode(array_column($lossReasons, 'reason'), JSON_UNESCAPED_UNICODE) ?>;

function showToast(msg, type){
  const wrap = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className = 'toast ' + (type||'');
  t.textContent = msg;
  wrap.appendChild(t);
  setTimeout(()=>t.remove(), 3000);
}

let draggedCard = null;
document.querySelectorAll('.kanban-card').forEach(card=>{
  card.addEventListener('dragstart', (e)=>{
    draggedCard = card;
    window.__dragging = true;
    card.classList.add('dragging');
  });
  card.addEventListener('dragend', ()=>{
    card.classList.remove('dragging');
    setTimeout(()=>{ window.__dragging = false; }, 50);
  });
});

document.querySelectorAll('.kanban-col').forEach(col=>{
  col.addEventListener('dragover', (e)=>{ e.preventDefault(); col.classList.add('drag-over'); });
  col.addEventListener('dragleave', ()=>{ col.classList.remove('drag-over'); });
  col.addEventListener('drop', async (e)=>{
    e.preventDefault();
    col.classList.remove('drag-over');
    if (!draggedCard) return;
    const leadId = draggedCard.dataset.leadId;
    const newStageId = col.dataset.stageId;
    const cardsWrap = col.querySelector('.kanban-cards');

    let lossReason = '';
    const stageName = col.querySelector('h3').textContent;
    if (stageName === 'از دست رفته') {
      lossReason = prompt('دلیل از دست رفتن سرنخ را انتخاب/وارد کنید:\n' + lossReasons.join('، '));
      if (!lossReason) { showToast('برای انتقال به «از دست رفته» دلیل الزامی است.', 'err'); return; }
    }

    try{
      const res = await fetch('change-stage.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ leadId, stageId: newStageId, lossReason })
      });
      const data = await res.json();
      if(data.success){
        cardsWrap.appendChild(draggedCard);
        showToast('مرحله سرنخ به‌روزرسانی شد.', 'ok');
        setTimeout(()=>location.reload(), 700);
      } else {
        showToast(data.message || 'خطا در به‌روزرسانی.', 'err');
      }
    }catch(err){
      showToast('خطا در ارتباط با سرور.', 'err');
    }
  });
});
</script>
</body>
</html>
