<?php
error_reporting(0);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sys.php';
ensureAdmin();

$cookie = getAuthCookie();
$isAdmin = ($cookie['role'] ?? '') === 'admin';
$adminUser = $cookie ? getUser() : null;

// Handle actions
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'block_user') {
        $uid = $_POST['user_id'] ?? '';
        $list = loadBlocked();
        if (!in_array($uid, $list)) $list[] = $uid;
        saveBlocked($list);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'unblock_user') {
        $uid = $_POST['user_id'] ?? '';
        $list = loadBlocked();
        $list = array_values(array_filter($list, fn($id) => $id !== $uid));
        saveBlocked($list);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'hide_bot') {
        $bid = $_POST['bot_id'] ?? '';
        $list = loadHidden();
        if (!in_array($bid, $list)) $list[] = $bid;
        saveHidden($list);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'show_bot') {
        $bid = $_POST['bot_id'] ?? '';
        $list = loadHidden();
        $list = array_values(array_filter($list, fn($id) => $id !== $bid));
        saveHidden($list);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'hide_bot_type') {
        $code = $_POST['type_code'] ?? '';
        $list = loadHiddenTypes();
        if (!in_array($code, $list)) $list[] = $code;
        saveHiddenTypes($list);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'show_bot_type') {
        $code = $_POST['type_code'] ?? '';
        $list = loadHiddenTypes();
        $list = array_values(array_filter($list, fn($id) => $id !== $code));
        saveHiddenTypes($list);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'change_user_password') {
        $uid = $_POST['user_id'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        if (!$uid || !$newPass) {
            echo json_encode(['success' => false, 'error' => 'البيانات ناقصة']);
            exit;
        }
        $users = loadUsers();
        foreach ($users as &$u) {
            if ($u['id'] === $uid && ($u['role'] ?? '') !== 'admin') {
                $u['password'] = password_hash($newPass, PASSWORD_DEFAULT);
                saveUsers($users);
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'المستخدم غير موجود']);
        exit;
    }
    
    if ($_POST['action'] === 'change_password') {
        $old = $_POST['old'] ?? '';
        $new = $_POST['new'] ?? '';
        if (!password_verify($old, $adminUser['password'])) {
            echo json_encode(['success' => false, 'error' => 'كلمة المرور القديمة غير صحيحة']);
            exit;
        }
        $users = loadUsers();
        foreach ($users as &$u) {
            if ($u['id'] === $adminUser['id']) {
                $u['password'] = password_hash($new, PASSWORD_DEFAULT);
                break;
            }
        }
        saveUsers($users);
        echo json_encode(['success' => true]);
        exit;
    }
}

// Load data for dashboard
$allUsers = loadUsers();
$allBots = $_api('list_bots', ['per_page' => 200]);
$allBotsData = $allBots['data'] ?? [];
$blockedIds = loadBlocked();
$hiddenIds = loadHidden();

if (!$isAdmin):
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>صلاحية مرفوضة - <?=htmlspecialchars($_APP_NAME)?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0f0f;--bg2:#1a1a1a;--bg3:#252525;--bg4:#333;--text:#f5f5f5;--text2:#aaa;--text3:#666;--red:#e50914;--border:rgba(255,255,255,.06);--border2:rgba(255,255,255,.1);--card:rgba(255,255,255,.02)}*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;-webkit-font-smoothing:antialiased;text-align:center}.card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:36px 32px;max-width:400px;width:90%}.card .icon{font-size:3rem;margin-bottom:12px}.card h2{font-size:1.2rem;margin-bottom:6px}.card p{color:var(--text2);font-size:.84rem;margin-bottom:6px}.btn{display:inline-block;background:var(--red);color:#fff;padding:12px 28px;border-radius:10px;font-size:.85rem;font-weight:600;text-decoration:none;font-family:'Inter',sans-serif;margin-top:14px}.btn:hover{opacity:.9}
</style>
</head>
<body>
<div class="card">
<div class="icon">🔒</div>
<h2>صلاحية المشرف فقط</h2>
<p>يجب تسجيل الدخول بحساب مشرف</p>
<a href="login.php" class="btn">🔓 تسجيل الدخول</a>
</div>
</body>
</html>
<?php else:
$statsData = $_stats();
$sUser = $statsData['user'];
$totalUsers = count($allUsers);
$totalBotsAll = count($allBotsData);
$blockedCount = count($blockedIds);
$hiddenCount = count($hiddenIds);
$allTypes = $_api('get_bot_types');
$allTypesData = $allTypes['data'] ?? [];
$hiddenTypeIds = loadHiddenTypes();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - <?=htmlspecialchars($_APP_NAME)?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0f0f;--bg2:#1a1a1a;--bg3:#252525;--bg4:#333;--text:#f5f5f5;--text2:#aaa;--text3:#666;--red:#e50914;--red2:#f40612;--green:#22c55e;--yellow:#eab308;--border:rgba(255,255,255,.06);--border2:rgba(255,255,255,.1);--card:rgba(255,255,255,.02);--hover:rgba(229,9,20,.04)}
[data-theme="light"]{--bg:#f5f5f5;--bg2:#fff;--bg3:#e8e8e8;--bg4:#d4d4d4;--text:#1a1a1a;--text2:#555;--text3:#888;--red:#e50914;--border:rgba(0,0,0,.06);--border2:rgba(0,0,0,.1);--card:#fff;--hover:rgba(229,9,20,.03)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;-webkit-font-smoothing:antialiased;transition:background .3s,color .25s}
::selection{background:var(--red);color:#fff}
.wrapper{max-width:1320px;margin:0 auto;padding:24px 32px}
.nav{display:flex;align-items:center;justify-content:space-between;padding:12px 0;margin-bottom:24px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:10px}
.nav-brand{font-size:1.3rem;font-weight:900;color:var(--red);text-decoration:none;display:flex;align-items:center;gap:6px}
.nav-brand small{font-size:.5rem;font-weight:500;color:var(--text3);background:var(--bg3);padding:2px 7px;border-radius:4px}
.nav-r{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.nav-pill{display:flex;align-items:center;gap:4px;padding:5px 12px;border-radius:999px;font-size:.74rem;font-weight:600;background:var(--bg3);border:1px solid var(--border2);transition:all .15s;color:var(--text2);text-decoration:none}
.nav-pill:hover{background:var(--bg4);color:var(--text)}
.nav-pill.gold{color:var(--text);background:rgba(229,9,20,.08);border-color:rgba(229,9,20,.15)}
.nav-pill.danger{color:var(--red)}
.theme-btn{width:34px;height:34px;display:flex;align-items:center;justify-content:center;background:var(--bg3);border:1px solid var(--border2);border-radius:10px;cursor:pointer;font-size:.85rem;transition:all .15s;color:var(--text2)}
.theme-btn:hover{background:var(--bg4);color:var(--text)}
.hero{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:22px}
.hero-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:14px 16px;transition:all .25s;overflow:hidden}
.hero-card:hover{border-color:var(--border2);transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.15)}
.hero-card .num{font-size:1.4rem;font-weight:800;letter-spacing:-.5px}
.hero-card .num.red{color:var(--red)}
.hero-card .num.green{color:var(--green)}
.hero-card .num.yellow{color:var(--yellow)}
.hero-card .label{color:var(--text2);font-size:.74rem;font-weight:500;margin-top:2px}
.sec{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.sec h2{font-size:1rem;font-weight:700;display:flex;align-items:center;gap:8px;white-space:nowrap}
.sec h2 span{color:var(--text3);font-weight:400;font-size:.74rem}
.sec-line{flex:1;height:1px;background:var(--border)}
.tbl{border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px}
.tbl-hd{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid var(--border);background:var(--bg2)}
.tbl-hd h3{font-size:.88rem;font-weight:700;display:flex;align-items:center;gap:8px}
.tbl-hd .ct{color:var(--text3);font-size:.74rem}
table{width:100%;border-collapse:collapse;font-size:.8rem}
th{text-align:right;padding:9px 14px;background:var(--bg2);color:var(--text3);font-weight:600;font-size:.62rem;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)}
td{padding:9px 14px;border-bottom:1px solid var(--border);transition:background .12s}
tr:last-child td{border-bottom:none}
tbody tr:hover td{background:var(--hover)}
.btn-sm{display:inline-flex;align-items:center;gap:3px;padding:4px 10px;border-radius:7px;font-size:.7rem;font-weight:600;cursor:pointer;border:none;font-family:'Inter',sans-serif;transition:all .12s;color:#fff}
.btn-sm.green{background:var(--green)}
.btn-sm.red{background:var(--red)}
.btn-sm.blue{background:#3b82f6}
.btn-sm:hover{opacity:.85}
.tag{display:inline-block;padding:1px 9px;border-radius:999px;font-size:.66rem;font-weight:500;background:var(--bg3);border:1px solid var(--border2);color:var(--text2)}
.tag.blocked{background:rgba(229,9,20,.08);color:var(--red);border-color:rgba(229,9,20,.1)}
.tag.hidden{background:rgba(234,179,8,.08);color:var(--yellow);border-color:rgba(234,179,8,.1)}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 22px;margin-bottom:20px}
.card h3{font-size:.92rem;font-weight:700;margin-bottom:6px}
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:var(--bg2);border:1px solid var(--border2);border-radius:12px;padding:10px 20px;font-size:.82rem;font-weight:500;z-index:2000;box-shadow:0 20px 60px rgba(0,0,0,.3);display:none;text-align:center;max-width:90%}
.empty{text-align:center;padding:36px 20px;color:var(--text3)}
::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--bg4);border-radius:2px}
@media(max-width:1024px){.wrapper{padding:20px 24px}}
@media(max-width:900px){
    .wrapper{padding:16px 20px}
    .hero{grid-template-columns:repeat(2,1fr)}
    .tbl{overflow-x:auto}
    table{font-size:.78rem}
    th,td{padding:8px 12px}
}
@media(max-width:700px){
    .wrapper{padding:12px 14px}
    .hero{grid-template-columns:1fr 1fr;gap:8px}
    .hero-card{padding:10px 12px}
    .hero-card .num{font-size:1.1rem}
    .nav-brand{font-size:1.1rem}
    .nav-pill{padding:4px 10px;font-size:.68rem}
    .card{padding:14px 16px}
    .tbl-hd{padding:10px 14px}
    th,td{padding:6px 10px;font-size:.72rem}
    .btn-sm{font-size:.65rem;padding:3px 8px}
    .tag{font-size:.6rem}
    .sec h2{font-size:.88rem}
}
@media(max-width:430px){
    .hero{gap:6px}
    .hero-card{padding:8px 10px}
    .hero-card .num{font-size:.95rem}
    .hero-card .label{font-size:.66rem}
    .wrapper{padding:8px 10px}
    .nav{padding:8px 0}
    .nav-brand{font-size:.95rem}
    .nav-pill{font-size:.62rem;padding:3px 8px}
    .card{padding:10px 12px}
    .tbl-hd{padding:8px 10px}
    .tbl-hd h3{font-size:.78rem}
    th,td{padding:5px 7px;font-size:.65rem}
    .sec h2{font-size:.78rem}
    .btn-sm{font-size:.6rem;padding:2px 6px}
    .tag{font-size:.55rem}
}
</style>
</head>
<body>
<div class="wrapper">

<!-- NAV -->
<div class="nav">
<div class="nav-brand">🔐 <small>ADMIN</small></div>
<div class="nav-r">
<div class="nav-pill gold">👤 <?=htmlspecialchars($adminUser['username'] ?? '')?></div>
<a href="index.php" class="nav-pill">⚙️ البوتات</a>
<a href="dashboard.php" class="nav-pill">📊 الإحصائيات</a>
<a href="admin.php?logout=1" class="nav-pill danger">🚪 خروج</a>
<button class="theme-btn" onclick="t()" id="tb">🌙</button>
</div>
</div>

<?php if (isset($_GET['logout'])): clearAuthCookie(); redirect('login.php'); endif; ?>

<!-- HERO -->
<div class="hero">
<div class="hero-card"><div class="num red"><?=$totalUsers?></div><div class="label">إجمالي المستخدمين</div></div>
<div class="hero-card"><div class="num green"><?=$totalBotsAll?></div><div class="label">إجمالي البوتات</div></div>
<div class="hero-card"><div class="num yellow"><?=$blockedCount?></div><div class="label">محظورين</div></div>
<div class="hero-card"><div class="num" style="color:var(--text2)"><?=$hiddenCount?></div><div class="label">بوتات مخفية</div></div>
</div>

<!-- USERS -->
<div class="sec"><h2>👤 إدارة المستخدمين</h2><div class="sec-line"></div></div>
<div class="tbl">
<div class="tbl-hd"><h3>👥 جميع المستخدمين</h3><span class="ct"><?=$totalUsers?></span></div>
<?php if (empty($allUsers)): ?><div class="empty">لا يوجد مستخدمين</div>
<?php else: ?>
<table>
<thead><tr><th>#</th><th>المستخدم</th><th>البريد</th><th>التسجيل</th><th>الدور</th><th>الحالة</th><th>إجراء</th></tr></thead>
<tbody>
<?php $i=1; foreach ($allUsers as $u): 
$isBlocked = in_array($u['id'], $blockedIds);
?>
<tr>
<td style="color:var(--text3);font-weight:600"><?=$i++?></td>
<td><?=htmlspecialchars($u['username'])?></td>
<td><?=htmlspecialchars($u['email'] ?? '—')?></td>
<td style="font-size:.72rem;color:var(--text3)"><?=htmlspecialchars($u['created'] ?? '—')?></td>
<td><span class="tag <?=($u['role']??'')==='admin'?'blocked':''?>"><?=htmlspecialchars($u['role'] ?? 'user')?></span></td>
<td><span class="tag <?=$isBlocked?'blocked':''?>"><?=$isBlocked?'محظور':'نشط'?></span></td>
<td>
<?php if (($u['role']??'') !== 'admin'): ?>
<div style="display:flex;gap:4px;flex-wrap:wrap">
<?php if ($isBlocked): ?>
<button class="btn-sm green" onclick="act('unblock_user','<?=$u['id']?>')">✓ إلغاء</button>
<?php else: ?>
<button class="btn-sm red" onclick="act('block_user','<?=$u['id']?>')">🔨 حظر</button>
<?php endif; ?>
<button class="btn-sm blue" onclick="changeUserPass('<?=$u['id']?>')">🔑</button>
</div>
<?php else: ?>
<span style="color:var(--text3);font-size:.72rem">—</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<!-- BOTS -->
<div class="sec"><h2>🤖 إدارة البوتات</h2><div class="sec-line"></div></div>
<div class="tbl">
<div class="tbl-hd"><h3>📋 جميع البوتات</h3><span class="ct"><?=$totalBotsAll?></span></div>
<?php if (empty($allBotsData)): ?><div class="empty">لا توجد بوتات</div>
<?php else: ?>
<table>
<thead><tr><th>#</th><th>البوت</th><th>النوع</th><th>المستخدم</th><th>الأعضاء</th><th>الحالة</th><th>إجراء</th></tr></thead>
<tbody>
<?php $i=1; foreach ($allBotsData as $b): 
$isHidden = in_array($b['bot_id'], $hiddenIds);
?>
<tr>
<td style="color:var(--text3);font-weight:600"><?=$i++?></td>
<td>
<div style="font-weight:600;font-size:.82rem"><?=htmlspecialchars($b['name']?:'بوت')?></div>
<div style="font-size:.7rem;color:var(--text3);direction:ltr">@<?=htmlspecialchars($b['username'])?></div>
</td>
<td><span class="tag"><?=htmlspecialchars($b['type_name']?:$b['type_code'])?></span></td>
<td style="font-size:.72rem;color:var(--text2)"><?=htmlspecialchars($b['chat_id'] ?? '—')?></td>
<td style="font-weight:600"><?=htmlspecialchars($b['members']??0)?></td>
<td><span class="tag <?=$isHidden?'hidden':''?>"><?=$isHidden?'مخفي':'ظاهر'?></span></td>
<td>
<?php if ($isHidden): ?>
<button class="btn-sm green" onclick="act('show_bot','<?=$b['bot_id']?>')">👁 إظهار</button>
<?php else: ?>
<button class="btn-sm" style="background:var(--yellow)" onclick="act('hide_bot','<?=$b['bot_id']?>')">🙈 إخفاء</button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<!-- BOT TYPES -->
<div class="sec"><h2>📦 أنواع البوتات</h2><div class="sec-line"></div></div>
<div class="tbl">
<div class="tbl-hd"><h3>🧩 جميع الأنواع</h3><span class="ct"><?=count($allTypesData)?></span></div>
<?php if (empty($allTypesData)): ?><div class="empty">لا توجد أنواع</div>
<?php else: ?>
<table>
<thead><tr><th>#</th><th>النوع</th><th>الكود</th><th>الحالة</th><th>إجراء</th></tr></thead>
<tbody>
<?php $i=1; foreach ($allTypesData as $t): 
$isTypeHidden = in_array($t['code'] ?? '', $hiddenTypeIds);
?>
<tr>
<td style="color:var(--text3);font-weight:600"><?=$i++?></td>
<td><?=htmlspecialchars($t['name'] ?? '—')?></td>
<td><span class="tag"><?=htmlspecialchars($t['code'] ?? '—')?></span></td>
<td><span class="tag <?=$isTypeHidden?'blocked':''?>"><?=$isTypeHidden?'مخفي':'ظاهر'?></span></td>
<td>
<?php if ($isTypeHidden): ?>
<button class="btn-sm green" onclick="act('show_bot_type','<?=htmlspecialchars($t['code'])?>')">👁 إظهار</button>
<?php else: ?>
<button class="btn-sm" style="background:var(--yellow)" onclick="act('hide_bot_type','<?=htmlspecialchars($t['code'])?>')">🙈 إخفاء</button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<!-- PASSWORD -->
<div class="sec"><h2>🔑 تغيير كلمة المرور</h2><div class="sec-line"></div></div>
<div class="card">
<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
<div class="form-group" style="flex:1;min-width:150px">
<label style="display:block;font-size:.7rem;font-weight:600;color:var(--text3);margin-bottom:4px">🔒 القديمة</label>
<input type="password" id="oldPass" style="width:100%;background:var(--bg3);border:1px solid var(--border2);border-radius:10px;padding:10px 12px;color:var(--text);font-family:'Inter',sans-serif;font-size:.85rem;outline:none">
</div>
<div class="form-group" style="flex:1;min-width:150px">
<label style="display:block;font-size:.7rem;font-weight:600;color:var(--text3);margin-bottom:4px">🔑 الجديدة</label>
<input type="password" id="newPass" style="width:100%;background:var(--bg3);border:1px solid var(--border2);border-radius:10px;padding:10px 12px;color:var(--text);font-family:'Inter',sans-serif;font-size:.85rem;outline:none">
</div>
<button class="btn-sm green" onclick="changePass()" style="padding:10px 24px;font-size:.82rem;cursor:pointer">تحديث</button>
</div>
</div>

<div class="toast" id="toast"></div>

</div>

<script>
const T=localStorage.getItem('nt')||'dark';
document.documentElement.setAttribute('data-theme',T);
document.getElementById('tb').textContent=T==='dark'?'🌙':'☀️';
function t(){
    const c=document.documentElement.getAttribute('data-theme');
    const n=c==='dark'?'light':'dark';
    document.documentElement.setAttribute('data-theme',n);
    localStorage.setItem('nt',n);
    document.getElementById('tb').textContent=n==='dark'?'🌙':'☀️';
}

function toast(m,t){
    const el=document.getElementById('toast');
    el.textContent=m;
    el.style.display='block';
    el.style.borderColor=t==='error'?'rgba(229,9,20,.15)':'rgba(34,197,94,.15)';
    el.style.color=t==='error'?'var(--red)':'var(--green)';
    setTimeout(()=>el.style.display='none',3000);
}

function act(action,id){
    const fd=new FormData();
    fd.append('action',action);
    if(action==='block_user'||action==='unblock_user') fd.append('user_id',id);
    else if(action==='hide_bot_type'||action==='show_bot_type') fd.append('type_code',id);
    else fd.append('bot_id',id);
    fetch('admin.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
        if(d.success){toast('✅ تم');setTimeout(()=>location.reload(),800)}
        else toast(d.error||'❌ خطأ','error');
    }).catch(()=>toast('❌ خطأ في الاتصال','error'));
}

function changeUserPass(uid){
    const p=prompt('🔑 أدخل كلمة المرور الجديدة للمستخدم:');
    if(!p||p.trim().length<4){toast('❌ كلمة المرور قصيرة','error');return}
    const fd=new FormData();
    fd.append('action','change_user_password');
    fd.append('user_id',uid);
    fd.append('new_password',p.trim());
    fetch('admin.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
        if(d.success){toast('✅ تم تغيير كلمة المرور');setTimeout(()=>location.reload(),800)}
        else toast('❌ '+(d.error||'خطأ'),'error');
    }).catch(()=>toast('❌ خطأ في الاتصال','error'));
}

function changePass(){
    const old=document.getElementById('oldPass').value.trim();
    const nw=document.getElementById('newPass').value.trim();
    if(!old||!nw){toast('❌ أدخل كلمة المرور القديمة والجديدة','error');return}
    if(nw.length<4){toast('❌ كلمة المرور الجديدة قصيرة','error');return}
    const fd=new FormData();
    fd.append('action','change_password');
    fd.append('old',old);
    fd.append('new',nw);
    fetch('admin.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
        if(d.success){toast('✅ تم تغيير كلمة المرور');document.getElementById('oldPass').value='';document.getElementById('newPass').value=''}
        else toast('❌ '+d.error,'error');
    }).catch(()=>toast('❌ خطأ','error'));
}
</script>
</body>
</html>
<?php endif; ?>