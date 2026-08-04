<?php
error_reporting(0);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sys.php';
if (!isLoggedIn()) redirect('login.php');
$user = getUser();
checkBlocked();

// Fetch dynamic data from encrypted core
$statsData = $_stats();
$sUser = $statsData['user'];
$sBots = array_values(array_filter($statsData['bots'], fn($b) => !in_array($b['bot_id'] ?? '', loadHidden())));
$sTypes = $statsData['types'];

$balance = number_format($sUser['balance'] ?? 0, 2);
$totalBots = $sUser['bots_count'] ?? count($sBots);
$chatId = $sUser['chat_id'] ?? '—';
$typesCount = count($sTypes);
$activeBots = count(array_filter($sBots, fn($b) => !empty($b['is_active'] ?? true)));
$paidBots = count(array_filter($sBots, fn($b) => !empty($b['is_premium'])));
$supportUrl = $_support() ?: $_fallback;
$totalMembers = array_sum(array_map(fn($b) => (int)($b['members'] ?? 0), $sBots));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لوحة التحكم - <?=htmlspecialchars($_APP_NAME)?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #0f0f0f; --bg2: #1a1a1a; --bg3: #252525; --bg4: #333;
    --text: #f5f5f5; --text2: #aaa; --text3: #666;
    --red: #e50914; --red2: #f40612; --red3: #b20710;
    --accent: #e50914; --border: rgba(255,255,255,.06); --border2: rgba(255,255,255,.1);
    --card: rgba(255,255,255,.02); --hover: rgba(229,9,20,.04);
    --shadow: rgba(229,9,20,.08); --glow: rgba(229,9,20,.04);
    --green: #22c55e; --yellow: #eab308; --blue: #3b82f6; --purple: #a855f7;
}
[data-theme="light"] {
    --bg: #f5f5f5; --bg2: #ffffff; --bg3: #e8e8e8; --bg4: #d4d4d4;
    --text: #1a1a1a; --text2: #555; --text3: #888;
    --red: #e50914; --red2: #f40612; --border: rgba(0,0,0,.06); --border2: rgba(0,0,0,.1);
    --card: #ffffff; --hover: rgba(229,9,20,.03); --shadow: rgba(229,9,20,.06); --glow: rgba(229,9,20,.02);
    --bg3: #e8e8e8; --bg4: #d4d4d4;
}
*{margin:0;padding:0;box-sizing:border-box}
body{
    font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);
    min-height:100vh;transition:background .3s,color .25s;-webkit-font-smoothing:antialiased;
}
::selection{background:var(--red);color:#fff}
.wrapper{max-width:1320px;margin:0 auto;padding:24px 32px}
.nav{display:flex;align-items:center;justify-content:space-between;padding:12px 0;margin-bottom:28px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:12px}
.nav-l{display:flex;align-items:center;gap:20px}
.nav-brand{font-size:1.6rem;font-weight:900;letter-spacing:-.5px;color:var(--red);text-decoration:none}
.nav-brand small{font-size:.55rem;font-weight:500;color:var(--text3);background:var(--bg3);padding:2px 8px;border-radius:4px;margin-right:6px}
.nav-r{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.nav-pill{display:flex;align-items:center;gap:5px;padding:6px 14px;border-radius:999px;font-size:.76rem;font-weight:600;background:var(--bg3);border:1px solid var(--border2);transition:all .15s;color:var(--text2);text-decoration:none}
.nav-pill:hover{background:var(--bg4);color:var(--text)}
.nav-pill.gold{color:var(--text);background:rgba(229,9,20,.08);border-color:rgba(229,9,20,.15)}
.nav-pill a{color:var(--text2);text-decoration:none}
.nav-pill a:hover{color:var(--red)}
.theme-btn{width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:var(--bg3);border:1px solid var(--border2);border-radius:10px;cursor:pointer;font-size:.9rem;transition:all .15s;color:var(--text2)}
.theme-btn:hover{background:var(--bg4);color:var(--text)}
.hero{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:24px}
.hero-card{position:relative;background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 20px;transition:all .25s;overflow:hidden}
.hero-card:hover{border-color:var(--border2);transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.15)}
.hero-card .bar{position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--red),var(--red2));opacity:0;transition:opacity .25s}
.hero-card:hover .bar{opacity:1}
.hero-card .icon{font-size:1.3rem;margin-bottom:4px}
.hero-card .num{font-size:1.7rem;font-weight:800;letter-spacing:-.5px;line-height:1.2}
.hero-card .num.red{color:var(--red)}
.hero-card .num.green{color:var(--green)}
.hero-card .num.purple{color:var(--purple)}
.hero-card .num.blue{color:var(--blue)}
.hero-card .label{color:var(--text2);font-size:.78rem;font-weight:500;margin-top:1px}
.hero-card .sub{color:var(--text3);font-size:.66rem;margin-top:0}

.sec{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.sec h2{font-size:1.1rem;font-weight:700;white-space:nowrap;display:flex;align-items:center;gap:8px}
.sec h2 span{color:var(--text3);font-weight:400;font-size:.78rem}
.sec-line{flex:1;height:1px;background:var(--border)}

.card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:22px 26px;margin-bottom:22px;transition:all .25s}
.card:hover{border-color:var(--border2)}
.card-sub{color:var(--text2);font-size:.84rem;margin-bottom:16px}

.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:22px}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:22px}
@media(max-width:900px){.hero{grid-template-columns:repeat(2,1fr)}.grid-2,.grid-3{grid-template-columns:1fr}}

.info-table{width:100%}
.info-table td{padding:10px 0;border-bottom:1px solid var(--border);font-size:.84rem}
.info-table tr:last-child td{border-bottom:none}
.info-table .lbl{color:var(--text3);font-weight:500;width:140px}
.info-table .val{color:var(--text);font-weight:600}
.info-table .val .tag{display:inline-block;padding:1px 10px;border-radius:999px;font-size:.68rem;font-weight:500;background:var(--bg3);border:1px solid var(--border2);color:var(--text2)}

.actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:22px}
.action-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 20px;text-align:center;transition:all .25s;text-decoration:none;color:var(--text);cursor:pointer}
.action-card:hover{border-color:var(--border2);transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.15)}
.action-card .icon{font-size:1.6rem;margin-bottom:6px}
.action-card h3{font-size:.85rem;font-weight:700;margin-bottom:2px}
.action-card p{color:var(--text2);font-size:.72rem}

.tbl{border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:22px}
.tbl-hd{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);background:var(--bg2)}
.tbl-hd h3{font-size:.9rem;font-weight:700;display:flex;align-items:center;gap:8px}
.tbl-hd .ct{color:var(--text3);font-size:.78rem}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{text-align:right;padding:10px 16px;background:var(--bg2);color:var(--text3);font-weight:600;font-size:.64rem;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border)}
td{padding:10px 16px;border-bottom:1px solid var(--border);transition:background .12s}
tr:last-child td{border-bottom:none}
tbody tr:hover td{background:var(--hover)}
.bot-name{font-weight:700;font-size:.84rem;color:var(--text)}
.bot-user{color:var(--text2);text-decoration:none;font-size:.75rem;direction:ltr}
.bot-status{display:inline-flex;align-items:center;gap:4px;padding:1px 10px;border-radius:999px;font-size:.66rem;font-weight:500}
.bot-status.active{background:rgba(34,197,94,.08);color:var(--green);border:1px solid rgba(34,197,94,.12)}
.bot-status.inactive{background:var(--bg3);color:var(--text3);border:1px solid var(--border2)}
.stat-num{font-weight:700;font-size:.9rem}
.stat-num.green{color:var(--green)}

.empty{text-align:center;padding:40px 20px}
.empty .big{font-size:2.4rem;margin-bottom:6px}
.empty h3{font-size:1rem;margin-bottom:2px}
.empty p{color:var(--text3);font-size:.78rem}

/* Progress rings */
.p-ring{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.p-ring-item{text-align:center;flex:1;min-width:100px}
.p-ring-item canvas{display:block;margin:0 auto 6px}
.p-ring-item .p-label{font-size:.72rem;color:var(--text2)}
.p-ring-item .p-val{font-size:1.4rem;font-weight:800;color:var(--red)}

::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--bg4);border-radius:2px}

@media(max-width:700px){
    .wrapper{padding:12px 14px}
    .hero{grid-template-columns:1fr 1fr;gap:8px}
    .hero-card{padding:12px 14px}
    .hero-card .num{font-size:1.2rem}
    .nav-brand{font-size:1.2rem}
    .card{padding:14px 16px}
    .actions{grid-template-columns:1fr 1fr}
}
@media(max-width:430px){
    .hero{gap:6px}
    .hero-card{padding:10px 12px}
    .hero-card .num{font-size:1rem}
    .actions{grid-template-columns:1fr}
}
</style>
</head>
<body>
<div class="wrapper">

<!-- NAV -->
<div class="nav">
<div class="nav-l">
<a href="dashboard.php" class="nav-brand"> <small>DASHBOARD</small></a>
</div>
<div class="nav-r">
<div class="nav-pill gold">🪙 $<?=$balance?></div>
<div class="nav-pill"><span class="label">ID</span> <?=htmlspecialchars($chatId)?></div>
<a href="<?=htmlspecialchars($supportUrl)?>" target="_blank" class="nav-pill">💬 دعم</a>
<a href="index.php" class="nav-pill">⚙️ البوتات</a>
<?php if (($user['role'] ?? '') === 'admin'): ?>
<a href="admin.php" class="nav-pill" style="color:var(--red)">🔐 أدمن</a>
<?php endif; ?>
<a href="logout.php" class="nav-pill" style="color:var(--red)">🚪 خروج</a>
<button class="theme-btn" onclick="t()" id="tb">🌙</button>
</div>
</div>

<!-- HERO STATS -->
<div class="hero">
<div class="hero-card">
<div class="bar"></div>
<div class="icon">🤖</div>
<div class="num red" id="bc"><?=$totalBots?></div>
<div class="label">إجمالي البوتات</div>
<div class="sub"><?=$activeBots?> نشط</div>
</div>
<div class="hero-card">
<div class="bar"></div>
<div class="icon">🪙</div>
<div class="num green">$<?=$balance?></div>
<div class="label">الرصيد</div>
<div class="sub">دولار أمريكي</div>
</div>
<div class="hero-card">
<div class="bar"></div>
<div class="icon">👥</div>
<div class="num blue"><?=number_format($totalMembers)?></div>
<div class="label">إجمالي الأعضاء</div>
<div class="sub">في جميع البوتات</div>
</div>
<div class="hero-card">
<div class="bar"></div>
<div class="icon">📦</div>
<div class="num purple"><?=$typesCount?></div>
<div class="label">أنواع البوتات</div>
<div class="sub"><?=$paidBots?> مدفوع</div>
</div>
</div>

<!-- QUICK ACTIONS -->
<div class="sec">
<h2>⚡ إجراءات سريعة</h2>
<div class="sec-line"></div>
</div>

<div class="actions">
<a href="index.php" class="action-card">
<div class="icon">🤖</div>
<h3>إنشاء بوت</h3>
<p>استوديو إنشاء البوتات</p>
</a>
<a href="index.php#bots" class="action-card">
<div class="icon">📋</div>
<h3>إدارة البوتات</h3>
<p>عرض وتعديل البوتات</p>
</a>
<a href="<?=htmlspecialchars($supportUrl)?>" target="_blank" class="action-card">
<div class="icon">💬</div>
<h3>تواصل مع الدعم</h3>
<p>مساعدة فنية ودعم</p>
</a>
<a href="logout.php" class="action-card">
<div class="icon">🔒</div>
<h3>تسجيل خروج</h3>
<p>خروج آمن من الحساب</p>
</a>
</div>

<!-- STATS GRID -->
<div class="sec">
<h2>📊 إحصائيات متقدمة</h2>
<div class="sec-line"></div>
</div>

<div class="grid-2">
<div class="card">
<h3>🔄 نشاط البوتات</h3>
<div class="p-ring">
<div class="p-ring-item">
<canvas id="ringActive" width="80" height="80"></canvas>
<div class="p-val" style="color:var(--green)"><?=$activeBots?></div>
<div class="p-label">نشط</div>
</div>
<div class="p-ring-item">
<canvas id="ringPaid" width="80" height="80"></canvas>
<div class="p-val" style="color:var(--yellow)"><?=$paidBots?></div>
<div class="p-label">مدفوع</div>
</div>
<div class="p-ring-item">
<canvas id="ringTypes" width="80" height="80"></canvas>
<div class="p-val" style="color:var(--purple)"><?=$typesCount?></div>
<div class="p-label">أنواع</div>
</div>
</div>
</div>

<div class="card">
<h3>👤 معلومات الحساب</h3>
<table class="info-table">
<tr><td class="lbl">المعرف</td><td class="val"><?=htmlspecialchars($chatId)?></td></tr>
<tr><td class="lbl">المستخدم</td><td class="val"><?=htmlspecialchars($user['username'] ?? '—')?></td></tr>
<tr><td class="lbl">البريد</td><td class="val"><?=htmlspecialchars($user['email'] ?? '—')?></td></tr>
<tr><td class="lbl">التسجيل</td><td class="val"><?=htmlspecialchars($user['created'] ?? '—')?></td></tr>
<tr><td class="lbl">الرصيد</td><td class="val" style="color:var(--green)">$<?=$balance?></td></tr>
<tr><td class="lbl">البوتات</td><td class="val"><?=$totalBots?> (<?=$activeBots?> نشط)</td></tr>
</table>
</div>
</div>

<!-- BOTS LIST -->
<div class="sec" id="bots">
<h2>📋 أحدث البوتات <span>آخر <?=min(5,count($sBots))?> من <?=count($sBots)?></span></h2>
<div class="sec-line"></div>
</div>

<div class="tbl">
<div class="tbl-hd">
<h3>🤖 البوتات الم deployed</h3>
<span class="ct"><?=count($sBots)?> بوت<?=count($sBots)!==1?'ات':''?></span>
</div>
<?php if(empty($sBots)): ?>
<div class="empty">
<div class="big">🎬</div>
<h3>لا توجد بوتات بعد</h3>
<p>قم بإنشاء بوتك الأول من صفحة البوتات</p>
</div>
<?php else: ?>
<table>
<thead>
<tr><th>#</th><th>البوت</th><th>النوع</th><th>الأعضاء</th><th>الحالة</th></tr>
</thead>
<tbody>
<?php $i=1; foreach(array_slice($sBots,0,5) as $b): 
$isP = !empty($b['is_premium']);
$isA = !empty($b['is_active'] ?? true);
?>
<tr>
<td style="color:var(--text3);font-weight:600"><?=$i++?></td>
<td>
<div class="bot-name"><?=htmlspecialchars($b['name']?:'بوت')?></div>
<a href="https://t.me/<?=htmlspecialchars($b['username'])?>" target="_blank" class="bot-user">@<?=htmlspecialchars($b['username'])?></a>
</td>
<td><span class="tag"><?=htmlspecialchars($b['type_name']?:$b['type_code'])?></span></td>
<td class="stat-num green"><?=htmlspecialchars($b['members']??0)?></td>
<td><span class="bot-status <?=$isA?'active':'inactive'?>"><?=$isA?'نشط':'متوقف'?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

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

// Ring charts
function drawRing(id,pct,color){
    const c=document.getElementById(id);
    if(!c)return;
    const ctx=c.getContext('2d');
    const cx=c.width/2,cy=c.height/2,r=30,s=1.5*Math.PI;
    ctx.clearRect(0,0,c.width,c.height);
    ctx.beginPath();
    ctx.arc(cx,cy,r,s,2*Math.PI+s);
    ctx.strokeStyle=color+'20';
    ctx.lineWidth=6;ctx.lineCap='round';ctx.stroke();
    ctx.beginPath();
    ctx.arc(cx,cy,r,s,s+2*Math.PI*Math.min(pct/100,1));
    ctx.strokeStyle=color;ctx.lineWidth=6;ctx.lineCap='round';ctx.stroke();
}
const at=<?=$totalBots?>,pt=<?=$paidBots?>,tt=<?=$typesCount?>;
const mx=Math.max(at,pt,tt,1);
drawRing('ringActive',(at/mx*100),'#22c55e');
drawRing('ringPaid',(pt/mx*100),'#eab308');
drawRing('ringTypes',(tt/mx*100),'#a855f7');
</script>
</body>
</html>
