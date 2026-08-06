<?php
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $isHttps,
    ]);
    session_start();
}
require '../db-config.php';

$error = '';

// --- محدودسازی تلاش‌های ورود (محافظت ساده در برابر حدس‌زدن رمز) ---
$maxAttempts = 6;
$lockSeconds = 300; // ۵ دقیقه
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
$_SESSION['login_locked_until'] = $_SESSION['login_locked_until'] ?? 0;
$isLocked = time() < $_SESSION['login_locked_until'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLocked) {
    $error = 'به‌دلیل تلاش‌های ناموفق زیاد، چند دقیقه صبر کنید و دوباره امتحان کنید.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'نام کاربری و رمز عبور را وارد کنید.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = :u LIMIT 1');
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts'] = 0;
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'] ?? 'admin';
            $_SESSION['admin_name'] = $user['full_name'] ?: $user['username'];
            $_SESSION['admin_last_activity'] = time();
            if (function_exists('navarakar_log')) {
                navarakar_log('info', 'ورود موفق به پنل مدیریت', ['username' => $username, 'role' => $_SESSION['admin_role']]);
            }
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['login_attempts']++;
            if (function_exists('navarakar_log')) {
                navarakar_log('error', 'تلاش ناموفق ورود به پنل', ['username' => $username]);
            }
            if ($_SESSION['login_attempts'] >= $maxAttempts) {
                $_SESSION['login_locked_until'] = time() + $lockSeconds;
                $_SESSION['login_attempts'] = 0;
                if (function_exists('navarakar_log')) {
                    navarakar_log('error', 'قفل موقت ورود به‌دلیل تلاش‌های ناموفق زیاد', ['username' => $username]);
                }
                $error = 'به‌دلیل تلاش‌های ناموفق زیاد، چند دقیقه صبر کنید و دوباره امتحان کنید.';
            } else {
                $error = 'نام کاربری یا رمز عبور اشتباه است.';
            }
        }
    }
}

$expired = isset($_GET['expired']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ورود به پنل مدیریت | ناوراکار</title>
<style>
  :root{
    --primary:#2952E0; --primary-dark:#122C7A; --amber:#FF8A1E; --amber-dark:#D9690A;
    --font:'Vazirmatn','IRANYekanX','IRANSansX','Yekan','Tahoma','Segoe UI',Arial,sans-serif;
  }
  *{box-sizing:border-box;}
  html{overflow-x:hidden;}
  body{
    margin:0;min-height:100vh;overflow-x:hidden;max-width:100vw;display:flex;align-items:center;justify-content:center;
    background:radial-gradient(120% 140% at 20% 0%, #1C2A5E 0%, #0A0F24 60%);
    font-family:var(--font);padding:20px;
  }
  .login-box{background:#fff;border-radius:20px;padding:36px 30px;width:100%;max-width:380px;box-shadow:0 24px 60px -20px rgba(0,0,0,.5);}
  .brand{display:flex;flex-direction:column;align-items:center;gap:10px;margin-bottom:26px;text-align:center;}
  .brand-mark{width:56px;height:56px;border-radius:16px;background:linear-gradient(160deg,var(--amber),var(--amber-dark));display:flex;align-items:center;justify-content:center;box-shadow:0 8px 18px -6px rgba(217,105,10,.5);}
  .brand-mark svg{width:28px;height:28px;}
  .brand h1{font-size:1.15rem;margin:0;color:var(--primary-dark);font-weight:900;}
  .brand p{font-size:.8rem;color:#5B6478;margin:2px 0 0;}
  .field{margin-bottom:14px;}
  .field label{display:block;font-size:.82rem;font-weight:700;color:#101828;margin-bottom:6px;}
  .field input{
    width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid #DFE4F2;
    font-family:inherit;font-size:.95rem;background:#F5F7FC;
  }
  .field input:focus{outline:none;border-color:var(--primary);background:#fff;box-shadow:0 0 0 4px #E3EAFE;}
  button{
    width:100%;margin-top:8px;background:linear-gradient(150deg,var(--primary),var(--primary-dark));
    color:#fff;border:none;padding:13px;border-radius:12px;font-family:inherit;font-weight:800;
    font-size:.95rem;cursor:pointer;
  }
  .error-msg{background:#FEE2E2;color:#991B1B;padding:10px 12px;border-radius:10px;font-size:.82rem;margin-bottom:14px;text-align:center;}
  .note{background:#FFF7ED;color:#9C7726;padding:10px 12px;border-radius:10px;font-size:.78rem;margin-bottom:14px;text-align:center;}
  .foot{text-align:center;margin-top:18px;font-size:.72rem;color:#9AA3B8;}
</style>
</head>
<body>
  <div class="login-box">
    <div class="brand">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="#1A1200" stroke-width="1.9"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/><circle cx="7" cy="18.5" r="1.6" fill="#1A1200" stroke="none"/><circle cx="17" cy="18.5" r="1.6" fill="#1A1200" stroke="none"/></svg>
      </div>
      <h1>پنل مدیریت ناوراکار</h1>
      <p>این بخش فقط برای مدیر سامانه است</p>
    </div>

    <?php if ($expired): ?><div class="note">نشست شما منقضی شد، دوباره وارد شوید.</div><?php endif; ?>
    <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post">
      <div class="field">
        <label>نام کاربری</label>
        <input type="text" name="username" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label>رمز عبور</label>
        <input type="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit">ورود به پنل</button>
    </form>
    <div class="foot">حساب مدیر را از بخش «کاربران» در پنل مدیریت کنید</div>
  </div>
</body>
</html>
