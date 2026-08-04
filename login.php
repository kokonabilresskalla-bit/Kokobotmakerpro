<?php
error_reporting(0);
require_once __DIR__ . '/config.php';
if (isLoggedIn()) redirect('dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        $users = loadUsers();
        $found = false;
        foreach ($users as $u) {
            if ($u['username'] === $username && password_verify($password, $u['password'])) {
                setAuthCookie($u);
                $found = true;
                $dest = ($u['role'] ?? '') === 'admin' ? 'admin.php' : 'dashboard.php';
                redirect($dest);
            }
        }
        if (!$found) {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول - <?=htmlspecialchars($_APP_NAME)?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #0f0f0f;
    --bg2: #1a1a1a;
    --bg3: #252525;
    --bg4: #333;
    --text: #f5f5f5;
    --text2: #aaa;
    --text3: #666;
    --red: #e50914;
    --red2: #f40612;
    --border: rgba(255,255,255,.06);
    --border2: rgba(255,255,255,.1);
    --card: rgba(255,255,255,.02);
}
*{margin:0;padding:0;box-sizing:border-box}
body{
    font-family:'Inter',system-ui,sans-serif;
    background:var(--bg);color:var(--text);
    min-height:100vh;display:flex;align-items:center;justify-content:center;
    -webkit-font-smoothing:antialiased;
}
::selection{background:var(--red);color:#fff}
.auth-wrap{
    width:100%;max-width:420px;padding:24px;
}
.auth-card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:20px;padding:36px 32px;
    backdrop-filter:blur(10px);
}
.auth-card .logo{
    text-align:center;margin-bottom:24px;
}
.auth-card .logo .brand{
    font-size:1.8rem;font-weight:900;color:var(--red);
    letter-spacing:-.5px;
}
.auth-card .logo p{color:var(--text3);font-size:.82rem;margin-top:4px}
.auth-card h2{
    font-size:1.3rem;font-weight:800;margin-bottom:4px;
}
.auth-card .sub{
    color:var(--text2);font-size:.82rem;margin-bottom:22px;
}
.form-group{
    margin-bottom:16px;
}
.form-group label{
    display:block;font-size:.72rem;font-weight:600;color:var(--text3);
    text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;
}
.form-group input{
    width:100%;
    background:var(--bg3);border:1px solid var(--border2);
    border-radius:10px;padding:12px 14px;
    color:var(--text);font-size:.85rem;font-family:'Inter',sans-serif;
    outline:none;transition:all .2s;
}
.form-group input:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(229,9,20,.06)}
.form-group input::placeholder{color:var(--text3)}
.btn{
    width:100%;
    background:var(--red);border:none;color:#fff;
    padding:13px 28px;border-radius:10px;
    font-size:.9rem;font-weight:600;cursor:pointer;
    font-family:'Inter',sans-serif;transition:all .2s;
    margin-top:6px;
}
.btn:hover{background:var(--red2);transform:translateY(-2px);box-shadow:0 6px 24px rgba(229,9,20,.15)}
.auth-link{
    text-align:center;margin-top:18px;font-size:.84rem;color:var(--text2);
}
.auth-link a{color:var(--red);text-decoration:none;font-weight:600}
.auth-link a:hover{text-decoration:underline}
.error{
    background:rgba(229,9,20,.08);border:1px solid rgba(229,9,20,.12);
    color:var(--red);padding:10px 14px;border-radius:10px;
    font-size:.82rem;margin-bottom:16px;text-align:center;
}
</style>
</head>
<body>
<div class="auth-wrap">
<div class="auth-card">
<div class="logo">
<div class="brand"><?=htmlspecialchars($_APP_NAME)?></div>
<p>منصة إنشاء البوتات</p>
</div>
<h2>تسجيل الدخول</h2>
<p class="sub">أهلاً بك مرة أخرى! قم بتسجيل الدخول إلى حسابك</p>

<?php if ($error): ?>
<div class="error"><?=htmlspecialchars($error)?></div>
<?php endif; ?>

<form method="POST">
<div class="form-group">
<label>👤 اسم المستخدم</label>
<input type="text" name="username" placeholder="اسم المستخدم" required autocomplete="username">
</div>
<div class="form-group">
<label>🔑 كلمة المرور</label>
<input type="password" name="password" placeholder="أدخل كلمة المرور" required autocomplete="current-password">
</div>
<button type="submit" class="btn">🔓 تسجيل الدخول</button>
</form>

<div class="auth-link">
<?php $regOpen = isset($_ALLOW_REGISTRATION) ? $_ALLOW_REGISTRATION : true; ?>
<?php if ($regOpen): ?>
ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a>
<?php endif; ?>
</div>
</div>
</div>
</body>
</html>
