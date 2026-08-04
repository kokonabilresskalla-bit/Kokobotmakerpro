<?php
error_reporting(0);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sys.php';
if (!isLoggedIn()) redirect('login.php');
$user = getUser();
checkBlocked();

$userInfo = $_api('get_user_info');
$balance = $userInfo['data']['balance'] ?? 0;
$botsCount = $userInfo['data']['bots_count'] ?? 0;
$chatId = $userInfo['data']['chat_id'] ?? '';
$supportUrl = $_support() ?: $_fallback;

$botsList = $_api('list_bots', ['per_page' => 200]);
$allBots = $botsList['data'] ?? [];

// Filter bots to only show the current user's bots (excluding hidden)
$userBots = loadBots();
$userBotIds = array_map(fn($ub) => $ub['bot_id'],
    array_filter($userBots, fn($ub) => $ub['user_id'] === $user['id'])
);
$bots = array_values(array_filter($allBots, fn($b) => in_array($b['bot_id'], $userBotIds) && !in_array($b['bot_id'], loadHidden())));

$typesList = $_api('get_bot_types');
$allTypes = $typesList['data'] ?? [];
$hiddenTypeCodes = loadHiddenTypes();
$botTypes = array_values(array_filter($allTypes, fn($t) => !in_array($t['code'] ?? '', $hiddenTypeCodes)));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title> — <?=htmlspecialchars($_APP_NAME)?></title>
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
    --red3: #b20710;
    --accent: #e50914;
    --border: rgba(255,255,255,.06);
    --border2: rgba(255,255,255,.1);
    --card: rgba(255,255,255,.02);
    --hover: rgba(229,9,20,.04);
    --shadow: rgba(229,9,20,.08);
    --glow: rgba(229,9,20,.04);
}
[data-theme="light"] {
    --bg: #f5f5f5;
    --bg2: #ffffff;
    --bg3: #e8e8e8;
    --bg4: #d4d4d4;
    --text: #1a1a1a;
    --text2: #555;
    --text3: #888;
    --red: #e50914;
    --red2: #f40612;
    --red3: #c00710;
    --accent: #e50914;
    --border: rgba(0,0,0,.06);
    --border2: rgba(0,0,0,.1);
    --card: #ffffff;
    --hover: rgba(229,9,20,.03);
    --shadow: rgba(229,9,20,.06);
    --glow: rgba(229,9,20,.02);
}

*{margin:0;padding:0;box-sizing:border-box}
body{
    font-family:'Inter',system-ui,sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    transition:background .3s,color .25s;
    -webkit-font-smoothing:antialiased;
}
::selection{background:var(--red);color:#fff}

.wrapper{max-width:1320px;margin:0 auto;padding:24px 32px}

/* ===== NAV ===== */
.nav{
    display:flex;align-items:center;justify-content:space-between;
    padding:12px 0;
    margin-bottom:28px;
    border-bottom:1px solid var(--border);
    flex-wrap:wrap;gap:12px;
}
.nav-l{display:flex;align-items:center;gap:20px}
.nav-brand{
    font-size:1.6rem;font-weight:900;letter-spacing:-.5px;
    color:var(--red);
}
.nav-brand small{
    font-size:.55rem;font-weight:500;color:var(--text3);
    background:var(--bg3);padding:2px 8px;border-radius:4px;
    margin-right:6px;
}
.nav-r{display:flex;align-items:center;gap:8px;flex-wrap:wrap}

.nav-pill{
    display:flex;align-items:center;gap:5px;
    padding:6px 14px;border-radius:999px;
    font-size:.76rem;font-weight:600;
    background:var(--bg3);border:1px solid var(--border2);
    transition:all .15s;color:var(--text2);
}
.nav-pill.gold{color:var(--text);background:rgba(229,9,20,.08);border-color:rgba(229,9,20,.15)}
.nav-pill a{color:var(--text2);text-decoration:none}
.nav-pill a:hover{color:var(--red)}
.nav-pill .label{color:var(--text3);font-weight:400}

.theme-btn{
    width:36px;height:36px;display:flex;align-items:center;justify-content:center;
    background:var(--bg3);border:1px solid var(--border2);border-radius:10px;
    cursor:pointer;font-size:.9rem;transition:all .15s;color:var(--text2);
}
.theme-btn:hover{background:var(--bg4);color:var(--text)}

/* ===== HERO STATS ===== */
.hero{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:28px}
.hero-card{
    position:relative;
    background:var(--card);
    border:1px solid var(--border);
    border-radius:14px;
    padding:18px 20px;
    transition:all .25s;
    overflow:hidden;
}
.hero-card:hover{
    border-color:var(--border2);
    transform:translateY(-3px);
    box-shadow:0 12px 40px rgba(0,0,0,.15);
}
.hero-card .bar{
    position:absolute;top:0;left:0;right:0;height:3px;
    background:linear-gradient(90deg,var(--red),var(--red2));
    opacity:0;transition:opacity .25s;
}
.hero-card:hover .bar{opacity:1}
.hero-card .icon{font-size:1.3rem;margin-bottom:4px}
.hero-card .num{
    font-size:1.7rem;font-weight:800;letter-spacing:-.5px;line-height:1.2;
}
.hero-card .num.red{color:var(--red)}
.hero-card .label{color:var(--text2);font-size:.78rem;font-weight:500;margin-top:1px}
.hero-card .sub{color:var(--text3);font-size:.66rem;margin-top:0}

/* ===== SECTION TITLE ===== */
.sec{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.sec h2{
    font-size:1.1rem;font-weight:700;white-space:nowrap;
    display:flex;align-items:center;gap:8px;
}
.sec h2 span{color:var(--text3);font-weight:400;font-size:.78rem}
.sec-line{flex:1;height:1px;background:var(--border)}

/* ===== CARD ===== */
.card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:16px;
    padding:22px 26px;
    margin-bottom:22px;
    transition:all .25s;
}
.card:hover{border-color:var(--border2)}
.card-sub{color:var(--text2);font-size:.84rem;margin-bottom:16px}

/* ===== KEYBOARD ===== */
.kb-grid{display:flex;flex-direction:column;gap:8px;margin-bottom:16px}
.kb-row{display:flex;gap:8px;flex-wrap:wrap}
.kb-btn{
    flex:1;min-width:100px;padding:14px 12px;border-radius:12px;
    font-size:.84rem;font-weight:600;cursor:pointer;text-align:center;
    transition:all .2s;border:1px solid var(--border2);
    font-family:'Inter',sans-serif;user-select:none;
    background:var(--bg3);color:var(--text2);
}
.kb-btn:hover{background:var(--bg4);transform:translateY(-2px)}
.kb-btn:active{transform:scale(.97)}
.kb-btn.primary.selected{
    background:var(--red);border-color:var(--red);color:#fff;
    box-shadow:0 6px 24px rgba(229,9,20,.2);
}
.kb-btn.danger.selected{
    background:var(--red);border-color:var(--red);color:#fff;
    box-shadow:0 6px 24px rgba(229,9,20,.2);
}
.kb-btn.success.selected{
    background:var(--red);border-color:var(--red);color:#fff;
    box-shadow:0 6px 24px rgba(229,9,20,.2);
}

/* ===== FORM ===== */
.form-row{display:flex;gap:12px;flex-wrap:wrap;align-items:end}
.form-group{flex:1;min-width:200px;display:flex;flex-direction:column;gap:4px}
.form-group label{
    font-size:.7rem;font-weight:600;color:var(--text3);
    text-transform:uppercase;letter-spacing:.3px;
}
.form-group input{
    background:var(--bg3);border:1px solid var(--border2);
    border-radius:10px;padding:12px 14px;
    color:var(--text);font-size:.85rem;font-family:'Inter',sans-serif;
    outline:none;transition:all .2s;width:100%;
}
.form-group input:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(229,9,20,.06)}
.form-group input::placeholder{color:var(--text3)}

.btn{
    background:var(--red);border:none;color:#fff;
    padding:12px 28px;border-radius:10px;
    font-size:.85rem;font-weight:600;cursor:pointer;white-space:nowrap;
    font-family:'Inter',sans-serif;transition:all .2s;
    display:inline-flex;align-items:center;gap:6px;
}
.btn:hover{background:var(--red2);transform:translateY(-2px);box-shadow:0 6px 24px rgba(229,9,20,.15)}
.btn:disabled{opacity:.35;cursor:not-allowed;transform:none;box-shadow:none}

.sel-type{
    display:inline-flex;align-items:center;gap:6px;
    background:rgba(229,9,20,.06);border:1px solid rgba(229,9,20,.12);
    padding:4px 14px;border-radius:999px;
    font-size:.75rem;color:var(--red);
}

/* ===== TABLE ===== */
.tbl{
    border:1px solid var(--border);border-radius:16px;overflow:hidden;
    transition:all .25s;
}
.tbl-hd{
    display:flex;align-items:center;justify-content:space-between;
    padding:16px 22px;border-bottom:1px solid var(--border);
    background:var(--bg2);
}
.tbl-hd h3{font-size:.95rem;font-weight:700;display:flex;align-items:center;gap:8px}
.tbl-hd .ct{color:var(--text3);font-size:.8rem}

table{width:100%;border-collapse:collapse;font-size:.83rem}
th{
    text-align:right;padding:11px 18px;
    background:var(--bg2);color:var(--text3);font-weight:600;font-size:.65rem;
    text-transform:uppercase;letter-spacing:.5px;
    border-bottom:1px solid var(--border);
}
td{padding:11px 18px;border-bottom:1px solid var(--border);transition:background .12s}
tr:last-child td{border-bottom:none}
tbody tr{transition:background .12s}
tbody tr:hover td{background:var(--hover)}

.bot-name{font-weight:700;font-size:.87rem;color:var(--text)}
.bot-user{color:var(--text2);text-decoration:none;font-size:.77rem;direction:ltr}
.bot-user:hover{color:var(--red)}
.bot-id{color:var(--text3);font-size:.58rem;direction:ltr;margin-top:1px}

.tag{
    display:inline-block;padding:1px 10px;border-radius:999px;
    font-size:.68rem;font-weight:500;
    background:var(--bg3);border:1px solid var(--border2);color:var(--text2);
}
.stat-v{font-weight:600;font-size:.87rem}
.stat-v.blue{color:var(--red)}
.status{
    display:inline-flex;align-items:center;gap:4px;
    padding:1px 10px;border-radius:999px;font-size:.68rem;font-weight:500;
}
.status.paid{background:rgba(229,9,20,.06);color:var(--red);border:1px solid rgba(229,9,20,.1)}
.status.free{background:var(--bg3);color:var(--text3);border:1px solid var(--border2)}

.act{display:flex;gap:4px}
.act button{
    width:30px;height:30px;display:flex;align-items:center;justify-content:center;
    background:var(--bg3);border:1px solid var(--border2);
    border-radius:8px;cursor:pointer;font-size:.7rem;color:var(--text3);
    transition:all .12s;
}
.act button:hover{background:var(--bg4);color:var(--text);border-color:var(--text3)}
.act .del:hover{background:rgba(229,9,20,.06);color:var(--red);border-color:var(--red)}

.empty{text-align:center;padding:56px 20px}
.empty .big{font-size:2.8rem;margin-bottom:8px}
.empty h3{font-size:1.05rem;margin-bottom:3px}
.empty p{color:var(--text3);font-size:.8rem}

/* ===== MODAL ===== */
.modal-overlay{
    position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,.6);backdrop-filter:blur(6px);
    z-index:1000;display:none;align-items:center;justify-content:center;
    opacity:0;transition:opacity .2s;
}
.modal-overlay.show{display:flex;opacity:1}
.modal{
    background:var(--bg2);border:1px solid var(--border2);
    border-radius:20px;padding:30px;
    width:90%;max-width:440px;max-height:85vh;overflow-y:auto;
    transform:scale(.92);transition:transform .25s;
    box-shadow:0 60px 120px rgba(0,0,0,.5);
}
.modal-overlay.show .modal{transform:scale(1)}
.modal h3{font-size:1.1rem;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.modal p{color:var(--text2);font-size:.82rem;margin-bottom:14px}
.modal-info{
    background:var(--bg3);border-radius:12px;
    padding:14px 18px;margin-bottom:14px;
    font-size:.82rem;line-height:2.2;border:1px solid var(--border);
}
.modal-info strong{color:var(--text2)}
.modal-info code{background:var(--bg4);padding:2px 6px;border-radius:4px;font-size:.74rem;color:var(--red)}
.modal-actions{display:flex;gap:10px;justify-content:flex-end}
.modal-actions button{
    padding:10px 24px;border-radius:10px;
    font-size:.84rem;cursor:pointer;font-weight:600;
    font-family:'Inter',sans-serif;transition:all .12s;
}
.modal-actions .cancel{background:var(--bg3);border:1px solid var(--border2);color:var(--text2)}
.modal-actions .cancel:hover{background:var(--bg4)}
.modal-actions .confirm{background:var(--red);border:none;color:#fff}
.modal-actions .confirm:hover{box-shadow:0 4px 20px rgba(229,9,20,.15)}
.modal-actions .confirm.danger{background:var(--red3)}
.modal-actions .confirm.danger:hover{background:var(--red);box-shadow:0 4px 20px rgba(229,9,20,.15)}

/* ===== TOAST ===== */
.toast-container{
    position:fixed;bottom:28px;left:50%;transform:translateX(-50%);
    z-index:2000;display:flex;flex-direction:column;gap:8px;align-items:center;pointer-events:none;
}
.toast{
    display:flex;align-items:center;gap:10px;
    background:var(--bg2);border:1px solid var(--border2);
    border-radius:14px;padding:12px 22px;
    font-size:.84rem;font-weight:500;
    transition:all .35s cubic-bezier(.22,1,.36,1);
    opacity:0;transform:translateY(16px);pointer-events:none;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
}
.toast.show{opacity:1;transform:translateY(0);pointer-events:auto}
.toast.success{border-color:rgba(229,9,20,.15)}
.toast.success .toast-icon{color:var(--red)}
.toast.error{border-color:rgba(229,9,20,.15)}
.toast.error .toast-icon{color:var(--red)}
.toast-icon{font-size:1.1rem}

/* ===== SCROLL ===== */
::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--bg4);border-radius:2px}

/* ===== RESPONSIVE ===== */
@media(max-width:1024px){
    .wrapper{padding:20px 24px}
}
@media(max-width:900px){
    .hero{grid-template-columns:repeat(2,1fr)}
    .wrapper{padding:16px 20px}
    .tbl{overflow-x:auto}
    table{font-size:.8rem}
    th,td{padding:10px 14px}
}
@media(max-width:700px){
    .hero{grid-template-columns:1fr 1fr;gap:8px}
    .wrapper{padding:12px 14px}
    .hero-card{padding:12px 14px}
    .hero-card .num{font-size:1.2rem}
    .hero-card .icon{font-size:1.1rem}
    .hero-card .label{font-size:.72rem}
    .kb-btn{min-width:60px;padding:10px 6px;font-size:.75rem}
    .kb-row{gap:5px}
    .kb-grid{gap:5px}
    .form-row{gap:8px;flex-direction:column}
    .form-group{min-width:100%}
    .btn{width:100%;justify-content:center;padding:14px 20px}
    .nav{padding:10px 0;margin-bottom:18px}
    .nav-brand{font-size:1.2rem}
    .nav-pill{padding:4px 10px;font-size:.7rem}
    .nav-r{gap:5px}
    .card{padding:16px 18px}
    .card-sub{font-size:.8rem}
    .sec h2{font-size:.95rem}
    .tbl-hd{padding:12px 16px}
    .tbl-hd h3{font-size:.85rem}
    th,td{padding:8px 12px;font-size:.72rem}
    .bot-name{font-size:.8rem}
    .bot-user{font-size:.72rem}
    .act button{width:26px;height:26px;font-size:.65rem}
    .empty{padding:36px 16px}
    .empty .big{font-size:2.2rem}
    .sel-type{font-size:.7rem;padding:3px 10px}
    .modal{padding:22px;border-radius:16px}
    .modal h3{font-size:1rem}
}
@media(max-width:430px){
    .hero{gap:6px}
    .hero-card{padding:10px 12px}
    .hero-card .num{font-size:1rem}
    .hero-card .icon{font-size:1rem}
    .kb-btn{min-width:50px;padding:8px 4px;font-size:.7rem;border-radius:8px}
    .wrapper{padding:8px 10px}
    .nav{padding:8px 0}
    .nav-brand{font-size:1.1rem}
    .nav-pill{padding:3px 8px;font-size:.65rem}
    .card{padding:12px 14px;margin-bottom:14px}
    .card-sub{font-size:.75rem}
    .form-group input{padding:10px 12px;font-size:.8rem}
    .btn{padding:12px 16px;font-size:.8rem}
    .sec h2{font-size:.85rem}
    .sec{margin-bottom:10px}
    .tbl-hd{padding:10px 12px}
    th,td{padding:6px 8px;font-size:.68rem}
    .bot-name{font-size:.75rem}
    .bot-user{font-size:.65rem}
    .bot-id{font-size:.5rem}
    .tag{font-size:.6rem;padding:0 6px}
    .status{font-size:.6rem;padding:0 6px}
    .stat-v{font-size:.78rem}
    .act{gap:2px}
    .act button{width:24px;height:24px;font-size:.6rem}
}
</style>
</head>
<body>

<div class="wrapper">

<!-- NAV -->
<div class="nav">
<div class="nav-l">
<div class="nav-brand"><?=htmlspecialchars($_APP_NAME)?> <small>▼</small></div>
</div>
<div class="nav-r">
<div class="nav-pill gold">🪙 $<?=number_format($balance,2)?></div>
<div class="nav-pill"><span class="label">ID</span> <?=htmlspecialchars($chatId)?></div>
<div class="nav-pill"><a href="<?=htmlspecialchars($supportUrl)?>" target="_blank">💬 Support</a></div>
<?php if (($user['role'] ?? '') === 'admin'): ?>
<a href="admin.php" class="nav-pill" style="color:var(--red)">🔐 Admin</a>
<?php endif; ?>
<button class="theme-btn" onclick="toggleTheme()" id="themeBtn">🌙</button>
</div>
</div>

<!-- HERO STATS -->
<div class="hero">
<div class="hero-card">
<div class="bar"></div>
<div class="icon">🤖</div>
<div class="num red" id="botsCount"><?=$botsCount?></div>
<div class="label">Total Bots</div>
</div>
<div class="hero-card">
<div class="bar"></div>
<div class="icon">🪙</div>
<div class="num red"><?=number_format($balance,2)?></div>
<div class="label">Balance</div>
<div class="sub">USD</div>
</div>
<div class="hero-card">
<div class="bar"></div>
<div class="icon">📦</div>
<div class="num red"><?=count($botTypes)?></div>
<div class="label">Bot Types</div>
</div>
<div class="hero-card">
<div class="bar"></div>
<div class="icon">🆔</div>
<div class="num red" style="font-size:.95rem;color:var(--text2)"><?=htmlspecialchars($chatId)?></div>
<div class="label">Account</div>
</div>
</div>

<!-- CREATE -->
<div class="sec">
<h2>🎬 <span>Create Bot</span></h2>
<div class="sec-line"></div>
</div>

<div class="card">
<div class="card-sub">Choose a type, then add your <strong style="color:var(--red)">@BotFather</strong> token &amp; admin ID</div>

<div class="kb-grid" id="kbGrid">
<?php
$styles = ['danger', 'primary'];
$i = 0;
$chunks = array_chunk($botTypes, 2);
foreach ($chunks as $chunk):
?>
<div class="kb-row">
<?php foreach ($chunk as $t): ?>
<div class="kb-btn <?=$styles[$i % 2]?>" data-type="<?=htmlspecialchars($t['code'])?>" onclick="selectType(this)"><?=htmlspecialchars($t['name'])?></div>
<?php $i++; endforeach; ?>
</div>
<?php endforeach; ?>
</div>

<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px;min-height:28px">
<div id="selectedTypeDisplay" class="sel-type" style="display:none"></div>
</div>

<div class="form-row">
<div class="form-group">
<label>🤖 Bot Token</label>
<input type="text" id="tokenInput" placeholder="1234567890:AAH..." autocomplete="off" style="direction:ltr;text-align:left">
</div>
<div class="form-group">
<label>👤 Admin ID</label>
<input type="text" id="adminInput" placeholder="e.g. 123456789" autocomplete="off" style="direction:ltr;text-align:left" dir="ltr">
</div>
<button class="btn" id="createBtn" onclick="createBot()">🚀 Deploy</button>
</div>
</div>

<!-- BOTS LIST -->
<div class="sec">
<h2>📋 <span>Your Bots</span></h2>
<div class="sec-line"></div>
</div>

<div class="tbl">
<div class="tbl-hd">
<h3>🤖 Deployed</h3>
<?php if(!empty($bots)): ?><span class="ct"><?=count($bots)?> bot<?=count($bots)>1?'s':''?></span><?php endif; ?>
</div>
<?php if(empty($bots)): ?>
<div class="empty">
<div class="big">🎬</div>
<h3>No bots yet</h3>
<p>Deploy your first bot above</p>
</div>
<?php else: ?>
<table>
<thead>
<tr><th style="width:30px">#</th><th>Bot</th><th>Type</th><th>Members</th><th>Status</th><th style="width:110px">Actions</th></tr>
</thead>
<tbody>
<?php $i=1; foreach($bots as $b): 
$isPremium=$b['is_premium']??false;
$statusText=$isPremium?'Paid':'Free';
$statusClass=$isPremium?'paid':'free';
?>
<tr>
<td style="color:var(--text3);font-weight:600"><?=$i++?></td>
<td>
<div class="bot-name"><?=htmlspecialchars($b['name']?:'Bot')?></div>
<a href="https://t.me/<?=htmlspecialchars($b['username'])?>" target="_blank" class="bot-user">@<?=htmlspecialchars($b['username'])?></a>
<div class="bot-id"><?=htmlspecialchars($b['bot_id'])?></div>
</td>
<td><span class="tag"><?=htmlspecialchars($b['type_name']?:$b['type_code'])?></span></td>
<td class="stat-v blue"><?=htmlspecialchars($b['members']??0)?></td>
<td><span class="status <?=$statusClass?>"><?=$statusText?></span></td>
<td>
<div class="act">
<button onclick="showInfo('<?=$b['bot_id']?>')" title="Info">ℹ️</button>
<button onclick="showTransfer('<?=$b['bot_id']?>')" title="Transfer">↗️</button>
<button class="del" onclick="showDelete('<?=$b['bot_id']?>','<?=htmlspecialchars($b['name']?:'Bot')?>')" title="Delete">🗑️</button>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<div style="height:48px"></div>
</div>

<!-- TOAST -->
<div class="toast-container" id="toastContainer"></div>

<!-- MODAL -->
<div class="modal-overlay" id="modal">
<div class="modal">
<h3 id="modalTitle">Confirm</h3>
<p id="modalDesc">Are you sure?</p>
<div class="modal-info" id="modalInfo"></div>
<div class="modal-actions">
<button class="cancel" onclick="closeModal()">Cancel</button>
<button class="confirm" id="modalConfirm">Confirm</button>
</div>
</div>
</div>

<script>

// THEME
const theme = localStorage.getItem('nf-theme') || 'dark';
document.documentElement.setAttribute('data-theme', theme);
document.getElementById('themeBtn').textContent = theme === 'dark' ? '🌙' : '☀️';

function toggleTheme(){
    const cur = document.documentElement.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('nf-theme', next);
    document.getElementById('themeBtn').textContent = next === 'dark' ? '🌙' : '☀️';
}

// TOAST
function toast(msg,type='success'){
    const c=document.getElementById('toastContainer');
    const t=document.createElement('div');
    t.className=`toast ${type} show`;
    t.innerHTML=`<span class="toast-icon">${type==='success'?'✅':'❌'}</span> ${msg}`;
    c.appendChild(t);
    setTimeout(()=>{t.classList.remove('show');setTimeout(()=>t.remove(),350)},3000);
}

// MODAL
let modalCallback=null;
function showModal(title,desc,info,cb,danger=false){
    document.getElementById('modalTitle').innerHTML=title;
    document.getElementById('modalDesc').textContent=desc;
    document.getElementById('modalInfo').innerHTML=info;
    const btn=document.getElementById('modalConfirm');
    btn.className=`confirm ${danger?'danger':''}`;
    btn.textContent=danger?'🗑️ Delete':'Confirm';
    document.getElementById('modal').classList.add('show');
    modalCallback=cb;
}
function closeModal(){
    document.getElementById('modal').classList.remove('show');
    modalCallback=null;
}
document.getElementById('modalConfirm').onclick=()=>{if(modalCallback)modalCallback();closeModal()};
document.getElementById('modal').onclick=function(e){if(e.target===this)closeModal()};

// TYPE SELECTION
let selectedType='';
function selectType(el){
    document.querySelectorAll('.kb-btn').forEach(b=>b.classList.remove('selected'));
    el.classList.add('selected');
    selectedType=el.dataset.type;
    const name=el.textContent.trim();
    const d=document.getElementById('selectedTypeDisplay');
    d.style.display='inline-flex';
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    d.innerHTML=`✅ <span style="color:var(--text)">${name}</span> <span style="color:var(--text3);font-size:.7rem">(${selectedType})</span>`;
}

// CREATE
function createBot(){
    const token=document.getElementById('tokenInput').value.trim();
    const adminId=document.getElementById('adminInput').value.trim();
    if(!selectedType){toast('❌ Select a bot type','error');return}
    if(!token){toast('❌ Enter bot token','error');return}
    if(!adminId){toast('❌ Enter admin ID','error');return}
    if(!/^\d+$/.test(adminId)){toast('❌ Admin ID must be numeric','error');return}

    const btn=document.getElementById('createBtn');
    btn.disabled=true;btn.innerHTML='⏳ Deploying...';

    fetch(`api.php?action=create_bot&token=${encodeURIComponent(token)}&bot_type=${selectedType}&admin_id=${adminId}`)
        .then(r=>r.json()).then(d=>{
            if(d.success){
                toast(`✅ @${d.data.bot_username} deployed!`);
                setTimeout(()=>location.reload(),1500);
            }else{
                toast(d.error||'❌ Failed','error');
                btn.disabled=false;btn.innerHTML='🚀 Deploy';
            }
        }).catch(()=>{
            toast('❌ Connection error','error');
            btn.disabled=false;btn.innerHTML='🚀 Deploy';
        });
}

// BOT ACTIONS
function showInfo(botId){
    fetch(`api.php?action=get_bot_info&bot_id=${botId}`)
        .then(r=>r.json()).then(d=>{
            if(!d.success){toast(d.error,'error');return}
            const dt=d.data;
            const info=`
                <strong>ID:</strong> ${dt.bot_id}<br>
                <strong>Username:</strong> @${dt.bot_username}<br>
                <strong>Name:</strong> ${dt.bot_name||'—'}<br>
                <strong>Type:</strong> ${dt.type_name} (${dt.type_code})<br>
                <strong>Members:</strong> ${dt.members_count}<br>
                <strong>Status:</strong> ${dt.subscription}<br>
                <strong>Token:</strong> <code style="direction:ltr;display:block;font-size:.74rem;color:var(--red);word-break:break-all">${dt.token_hidden}</code>
            `;
            showModal(`ℹ️ @${dt.bot_username}`,'Full bot info',info,null);
        }).catch(()=>toast('❌ Error','error'));
}

function showTransfer(botId){
    const target=prompt('Target user ID:');
    if(!target||!target.trim())return;
    if(!confirm(`Transfer to ${target.trim()}?`))return;
    fetch(`api.php?action=transfer_bot&bot_id=${botId}&target_chat_id=${target.trim()}`)
        .then(r=>r.json()).then(d=>{
            if(d.success){toast('✅ Transferred');setTimeout(()=>location.reload(),1200)}
            else toast(d.error,'error');
        }).catch(()=>toast('❌ Error','error'));
}

function showDelete(botId,name){
    showModal('🗑️ Delete Bot','This cannot be undone','<strong>Bot:</strong> '+name+'<br><strong>ID:</strong> '+botId,()=>{
        fetch(`api.php?action=delete_bot&bot_id=${botId}`)
            .then(r=>r.json()).then(d=>{
                if(d.success){
                    toast('✅ Deleted');
                    setTimeout(()=>location.reload(),1200);
                }
                else toast(d.error,'error');
            }).catch(()=>toast('❌ Error','error'));
    },true);
}

document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
window.addEventListener('storage',e=>{
    if(e.key==='nf-theme'){
        const v=e.newValue||'dark';
        document.documentElement.setAttribute('data-theme',v);
        document.getElementById('themeBtn').textContent=v==='dark'?'🌙':'☀️';
    }
});
</script>

</body>
</html>
