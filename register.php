<?php
error_reporting(0);
require_once __DIR__ . '/config.php';
if (isLoggedIn()) redirect('dashboard.php');

$error = '';
$success = '';

$regAllowed = isset($_ALLOW_REGISTRATION) ? $_ALLOW_REGISTRATION : true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$regAllowed) {
    $error = 'التسجيل مغلق حالياً';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $regAllowed) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$username || !$email || !$password || !$confirm) {
        $error = 'جميع الحقول مطلوبة';
    } elseif (strtolower($username) === 'admin') {
        $error = 'اسم المستخدم هذا محجوز';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صالح';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif ($password !== $confirm) {
        $error = 'كلمة المرور غير متطابقة';
    } else {
        $users = loadUsers();
        foreach ($users as $u) {
            if ($u['username'] === $username) {
                $error = 'اسم المستخدم موجود بالفعل';
                break;
            }
            if ($u['email'] === $email) {
                $error = 'البريد الإلكتروني موجود بالفعل';
                break;
            }
        }
        if (!$error) {
            $users[] = [
                'id'       => uniqid('user_', true),
                'username' => $username,
                'email'    => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'created'  => date('Y-m-d H:i:s'),
            ];
            saveUsers($users);
            $success = 'تم إنشاء الحساب بنجاح! يمكنك تسجيل الدخول الآن.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إنشاء حساب - <?=htmlspecialchars($_APP_NAME)?></title>
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
.success{
    background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.12);
    color:#22c55e;padding:10px 14px;border-radius:10px;
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
<h2>إنشاء حساب جديد</h2>
<p class="sub">أدخل بياناتك للتسجيل في المنصة</p>

<?php if ($error): ?>
<div class="error"><?=htmlspecialchars($error)?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="success"><?=htmlspecialchars($success)?></div>
<?php endif; ?>

<?php if (!$regAllowed): ?>
<div class="error" style="background:rgba(234,179,8,.08);border-color:rgba(234,179,8,.12);color:var(--yellow)">🚫 التسجيل مغلق حالياً — تواصل مع المدير</div>
<?php else: ?>
<form method="POST">
<div class="form-group">
<label>👤 اسم المستخدم</label>
<input type="text" name="username" placeholder="اسم المستخدم" required autocomplete="username">
</div>
<div class="form-group">
<label>📧 البريد الإلكتروني</label>
<input type="email" name="email" placeholder="email@example.com" required autocomplete="email">
</div>
<div class="form-group">
<label>🔑 كلمة المرور</label>
<input type="password" name="password" placeholder="أدخل كلمة المرور" required autocomplete="new-password">
</div>
<div class="form-group">
<label>🔒 تأكيد كلمة المرور</label>
<input type="password" name="confirm" placeholder="أعد إدخال كلمة المرور" required autocomplete="new-password">
</div>
<button type="submit" class="btn">🚀 إنشاء حساب</button>
</form>
<?php endif; ?>

<div class="auth-link">
لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>
</div>
</div>
</div>
</body>
</html>
