<?php
require 'auth-check.php';
require_admin_role();
require '../db-config.php';
$activePage = 'users';
$myId = current_admin_id();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $role = ($_POST['role'] ?? 'sales') === 'admin' ? 'admin' : 'sales';

        if ($username === '' || strlen($password) < 6) {
            $error = 'نام کاربری الزامی و رمز عبور باید حداقل ۶ کاراکتر باشد.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, full_name, role) VALUES (:u,:h,:n,:r)");
                $stmt->execute(['u' => $username, 'h' => password_hash($password, PASSWORD_DEFAULT), 'n' => $fullName ?: null, 'r' => $role]);
                $message = 'کاربر جدید ساخته شد.';
            } catch (Exception $e) {
                $error = 'این نام کاربری قبلاً استفاده شده است.';
            }
        }
    }

    if ($action === 'update_role' && (int)$_POST['user_id'] !== $myId) {
        $role = ($_POST['role'] ?? 'sales') === 'admin' ? 'admin' : 'sales';
        $pdo->prepare("UPDATE admin_users SET role = :r WHERE id = :id")->execute(['r' => $role, 'id' => (int)$_POST['user_id']]);
        $message = 'نقش کاربر به‌روزرسانی شد.';
    }

    if ($action === 'reset_password' && trim($_POST['new_password'] ?? '') !== '') {
        $np = trim($_POST['new_password']);
        if (strlen($np) < 6) {
            $error = 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.';
        } else {
            $pdo->prepare("UPDATE admin_users SET password_hash = :h WHERE id = :id")
                ->execute(['h' => password_hash($np, PASSWORD_DEFAULT), 'id' => (int)$_POST['user_id']]);
            $message = 'رمز عبور بازنشانی شد.';
        }
    }
}

$users = $pdo->query("SELECT au.*, (SELECT COUNT(*) FROM quote_requests WHERE assigned_to = au.id) AS assigned_count FROM admin_users au ORDER BY au.role='admin' DESC, au.username")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>کاربران پنل | ناوراکار</title>
<?php include 'style.php'; ?>
<style>
  .user-form input,.user-form select{padding:10px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:var(--font);font-size:.88rem;background:var(--surface-alt);}
  .user-form{display:flex;gap:10px;flex-wrap:wrap;align-items:end;}
  .user-form .f{display:flex;flex-direction:column;gap:5px;}
  .user-form label{font-size:.76rem;font-weight:700;color:var(--ink-soft);}
  .msg-ok{background:#DCFCE7;color:#166534;padding:10px 14px;border-radius:10px;font-size:.86rem;margin-bottom:14px;}
  .msg-err{background:#FEE2E2;color:#991B1B;padding:10px 14px;border-radius:10px;font-size:.86rem;margin-bottom:14px;}
  .inline-form{display:inline-flex;gap:6px;align-items:center;}
  .inline-form select,.inline-form input{padding:6px 8px;font-size:.78rem;border-radius:7px;border:1px solid var(--border);}
</style>
</head>
<body>
<?php include 'partials-header.php'; ?>
<main class="wrap">
  <h1 class="page-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    مدیریت کاربران پنل
  </h1>
  <p class="page-sub">کاربر «مدیر» به همه فرم‌ها دسترسی دارد و می‌تواند الحاق کند. کاربر «کارشناس فروش» فقط فرم‌های الحاق‌شده به خودش را می‌بیند.</p>

  <?php if ($message): ?><div class="msg-ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <h2>افزودن کاربر جدید</h2>
    <form method="post" class="user-form">
      <input type="hidden" name="action" value="create">
      <div class="f"><label>نام کاربری</label><input type="text" name="username" required></div>
      <div class="f"><label>رمز عبور</label><input type="password" name="password" required></div>
      <div class="f"><label>نام کامل</label><input type="text" name="full_name"></div>
      <div class="f"><label>نقش</label>
        <select name="role"><option value="sales">کارشناس فروش</option><option value="admin">مدیر</option></select>
      </div>
      <button type="submit" class="btn amber">افزودن کاربر</button>
    </form>
  </div>

  <div class="card">
    <h2>فهرست کاربران</h2>
    <div class="tbl-wrap">
      <table class="data-table">
        <thead><tr><th>نام کاربری</th><th>نام کامل</th><th>نقش</th><th>فرم‌های الحاق‌شده</th><th>تغییر نقش</th><th>بازنشانی رمز</th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td data-label="نام کاربری"><?= htmlspecialchars($u['username']) ?><?= $u['id']==$myId ? ' (شما)':'' ?></td>
            <td data-label="نام کامل"><?= htmlspecialchars($u['full_name'] ?: '-') ?></td>
            <td data-label="نقش"><span class="pill <?= $u['role']==='admin'?'ok':'' ?>"><?= $u['role']==='admin'?'مدیر':'کارشناس فروش' ?></span></td>
            <td data-label="فرم‌های الحاق‌شده" class="num-font"><?= (int)$u['assigned_count'] ?></td>
            <td data-label="تغییر نقش">
              <?php if ($u['id'] != $myId): ?>
              <form method="post" class="inline-form">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <select name="role" onchange="this.form.submit()">
                  <option value="sales" <?= $u['role']==='sales'?'selected':'' ?>>کارشناس فروش</option>
                  <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>مدیر</option>
                </select>
              </form>
              <?php else: echo '—'; endif; ?>
            </td>
            <td data-label="بازنشانی رمز">
              <form method="post" class="inline-form" onsubmit="return this.new_password.value.length>=6;">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <input type="password" name="new_password" placeholder="رمز جدید">
                <button type="submit" class="btn" style="padding:6px 10px;font-size:.72rem;">ثبت</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>
