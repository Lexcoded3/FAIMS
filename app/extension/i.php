<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') {
    header('Location: /login.php'); exit;
}
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';

// Reports count
$reports_count = $conn->query("SELECT COUNT(*) AS c FROM extension_reports WHERE extension_id=$extension_id")->fetch_assoc()['c'];

// Farmers in same location
$row = $conn->query("SELECT location FROM users WHERE id=$extension_id")->fetch_assoc();
$safe_loc = $conn->real_escape_string($row['location'] ?? '');
$farmers_count = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='farmer' AND location='$safe_loc'")->fetch_assoc()['c'];

// Bulletins
$bulletins_count = $conn->query("SELECT COUNT(*) AS c FROM posts WHERE id=$extension_id")->fetch_assoc()['c'];

// Disease alerts this month
$alerts_count = $conn->query("
    SELECT COUNT(*) AS c FROM extension_reports
    WHERE extension_id=$extension_id
      AND title REGEXP 'disease|pest|blight|worm|virus|fungus|rust|armyworm'
      AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())
")->fetch_assoc()['c'];

// Recent reports
$recent_reports = [];
$res = $conn->query("SELECT id,title,district,created_at FROM extension_reports WHERE extension_id=$extension_id ORDER BY created_at DESC LIMIT 5");
while ($r = $res->fetch_assoc()) $recent_reports[] = $r;

// Market prices — latest per crop
$market_prices = [];
$res = $conn->query("
    SELECT mp.crop, mp.price, mp.date, c.name AS category
    FROM market_prices mp
    LEFT JOIN categories c ON c.id=mp.category_id
    WHERE mp.date=(SELECT MAX(date) FROM market_prices mp2 WHERE mp2.crop=mp.crop)
    ORDER BY mp.crop ASC LIMIT 8
");
while ($r = $res->fetch_assoc()) $market_prices[] = $r;

// Farmer activity
$farmer_activity = [];
$res = $conn->query("
    SELECT u.name, u.location, p.name AS product, p.status, p.created_at
    FROM products p JOIN users u ON u.id=p.farmer_id
    WHERE u.role='farmer' ORDER BY p.created_at DESC LIMIT 5
");
while ($r = $res->fetch_assoc()) $farmer_activity[] = $r;

// Recent bulletins
$recent_bulletins = [];
$res = $conn->query("SELECT id,title,created_at FROM posts WHERE id=$extension_id ORDER BY created_at DESC LIMIT 4");
while ($r = $res->fetch_assoc()) $recent_bulletins[] = $r;

// Weather
$weather = null;
$res = $conn->query("SELECT location,temperature,humidity,weather_desc FROM weather_data ORDER BY created_at DESC LIMIT 1");
if ($res && $res->num_rows) $weather = $res->fetch_assoc();

function detect_tag(string $t): string {
    $t = strtolower($t);
    if (preg_match('/disease|pest|blight|worm|virus|fungus|rust|armyworm/',$t)) return 'disease';
    if (preg_match('/yield|harvest|crop|produce/',$t))  return 'yield';
    if (preg_match('/soil|erosion|degrad|fertility/',$t)) return 'soil';
    if (preg_match('/water|irrigation|flood|drought|rain/',$t)) return 'water';
    return 'general';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Overview — FAIMS Extension</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
body{font-family:'DM Sans',sans-serif}
.mono{font-family:'DM Mono',monospace}
.sidebar-link{transition:all .15s ease}
.sidebar-link:hover{background:rgba(29,158,117,.07)}
.sidebar-link.active{background:rgba(29,158,117,.1);border-right:2px solid #1D9E75;color:#0F6E56}
.stat-card{transition:transform .15s}
.stat-card:hover{transform:translateY(-1px)}
.fade-in{animation:fadeIn .3s ease forwards}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.tag{display:inline-flex;align-items:center;font-size:11px;font-weight:500;padding:2px 8px;border-radius:20px}
.tag-disease{background:#FCEBEB;color:#A32D2D}
.tag-yield{background:#EAF3DE;color:#3B6D11}
.tag-soil{background:#FAEEDA;color:#854F0B}
.tag-water{background:#E6F1FB;color:#185FA5}
.tag-general{background:#F1EFE8;color:#5F5E5A}
.tag-pending{background:#FAEEDA;color:#854F0B}
.tag-approved,.tag-active{background:#E1F5EE;color:#0F6E56}
.tag-rejected{background:#FCEBEB;color:#A32D2D}
.scrollbar-hide::-webkit-scrollbar{display:none}
</style>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="flex h-screen overflow-hidden">

<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base text-gray-800" style="font-weight:500">Overview</h1>
            <p class="text-xs text-gray-400 mt-0.5">
                <?= date('l, d F Y') ?>
                <?php if($weather): ?> &nbsp;·&nbsp;<?= htmlspecialchars($weather['location']) ?> &nbsp;·&nbsp;<?= round($weather['temperature']) ?>°C<?php endif; ?>
            </p>
        </div>
        <a href="submit_report.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs text-white" style="background:#1D9E75;font-weight:500">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="white" stroke-width="1.8"><path d="M6 1v10M1 6h10"/></svg>
            New report
        </a>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-5 space-y-5 fade-in">

        <!-- Stats -->
        <div class="grid grid-cols-4 gap-4">
            <div class="stat-card bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">Reports filed</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $reports_count ?></p>
                <p class="text-xs text-gray-400 mt-1">All time</p>
            </div>
            <div class="stat-card bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">Farmers in district</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $farmers_count ?></p>
                <p class="text-xs text-gray-400 mt-1">Your area</p>
            </div>
            <div class="stat-card bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">Bulletins posted</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $bulletins_count ?></p>
                <p class="text-xs text-gray-400 mt-1">By you</p>
            </div>
            <div class="stat-card bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">Disease alerts</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500;<?= $alerts_count>0?'color:#A32D2D':'' ?>"><?= $alerts_count ?></p>
                <p class="text-xs text-gray-400 mt-1">This month</p>
            </div>
        </div>

        <!-- 3-col -->
        <div class="grid grid-cols-3 gap-4">
            <!-- Recent reports -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="text-xs text-gray-700" style="font-weight:500">Recent reports</p>
                    <a href="reports.php" class="text-xs text-gray-400 hover:text-gray-600">View all →</a>
                </div>
                <div class="divide-y divide-gray-50">
                    <?php if(empty($recent_reports)): ?><div class="px-4 py-6 text-center text-xs text-gray-400">No reports yet</div>
                    <?php else: foreach($recent_reports as $r): $tag=detect_tag($r['title']); ?>
                    <div class="flex items-start justify-between px-4 py-3">
                        <div class="min-w-0 flex-1 pr-2">
                            <p class="text-xs text-gray-700 truncate" style="font-weight:500"><?= htmlspecialchars($r['title']) ?></p>
                            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($r['district']) ?> · <?= date('d M',strtotime($r['created_at'])) ?></p>
                        </div>
                        <span class="tag tag-<?= $tag ?> flex-shrink-0"><?= ucfirst($tag) ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Farmer activity -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="text-xs text-gray-700" style="font-weight:500">Farmer activity</p>
                    <a href="farmers.php" class="text-xs text-gray-400 hover:text-gray-600">View all →</a>
                </div>
                <div class="divide-y divide-gray-50">
                    <?php if(empty($farmer_activity)): ?><div class="px-4 py-6 text-center text-xs text-gray-400">No activity yet</div>
                    <?php else: foreach($farmer_activity as $f): ?>
                    <div class="flex items-center justify-between px-4 py-3">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <div class="w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-xs text-white" style="background:#1D9E75;font-weight:500"><?= strtoupper(substr($f['name'],0,1)) ?></div>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-700 truncate" style="font-weight:500"><?= htmlspecialchars($f['name']) ?></p>
                                <p class="text-xs text-gray-400 truncate">Listed: <?= htmlspecialchars($f['product']) ?></p>
                            </div>
                        </div>
                        <span class="tag tag-<?= strtolower($f['status']) ?> flex-shrink-0 ml-2"><?= ucfirst($f['status']) ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Weather + prices -->
            <div class="flex flex-col gap-4">
                <?php if($weather): ?>
                <div class="bg-white rounded-xl border border-gray-100 p-4">
                    <p class="text-xs text-gray-700 mb-3" style="font-weight:500">Weather — <?= htmlspecialchars($weather['location']) ?></p>
                    <div class="flex items-end gap-3">
                        <p class="mono text-3xl text-gray-800" style="font-weight:500"><?= round($weather['temperature']) ?>°</p>
                        <div class="mb-0.5">
                            <p class="text-xs text-gray-600 capitalize"><?= htmlspecialchars($weather['weather_desc']) ?></p>
                            <p class="text-xs text-gray-400">Humidity <?= $weather['humidity'] ?>%</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden flex-1">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <p class="text-xs text-gray-700" style="font-weight:500">Market prices</p>
                        <a href="prices.php" class="text-xs text-gray-400 hover:text-gray-600">All →</a>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <?php if(empty($market_prices)): ?><div class="px-4 py-6 text-center text-xs text-gray-400">No price data</div>
                        <?php else: foreach($market_prices as $mp): ?>
                        <div class="flex items-center justify-between px-4 py-2">
                            <p class="text-xs text-gray-600"><?= htmlspecialchars($mp['crop']) ?></p>
                            <p class="mono text-xs text-gray-800" style="font-weight:500"><?= number_format((float)$mp['price']) ?> <span class="text-gray-400" style="font-weight:400">UGX/kg</span></p>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulletins + quick actions -->
        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2 bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="text-xs text-gray-700" style="font-weight:500">My recent bulletins</p>
                    <a href="bulletins.php" class="text-xs text-gray-400 hover:text-gray-600">View all →</a>
                </div>
                <?php if(empty($recent_bulletins)): ?>
                <div class="px-4 py-8 text-center">
                    <p class="text-xs text-gray-400 mb-3">No bulletins posted yet</p>
                    <a href="post_bulletin.php" class="text-xs text-white px-3 py-1.5 rounded-lg" style="background:#1D9E75;font-weight:500">Post your first bulletin</a>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-50">
                    <?php foreach($recent_bulletins as $b): ?>
                    <div class="flex items-center justify-between px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:#1D9E75"></div>
                            <p class="text-xs text-gray-700 truncate"><?= htmlspecialchars($b['title']) ?></p>
                        </div>
                        <p class="text-xs text-gray-400 flex-shrink-0 ml-4"><?= date('d M Y',strtotime($b['created_at'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-700 mb-3" style="font-weight:500">Quick actions</p>
                <div class="space-y-2">
                    <a href="submit_report.php" class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all text-xs text-gray-600">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#E1F5EE"><svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="#0F6E56" stroke-width="1.6"><path d="M6.5 1v11M1 6.5h11"/></svg></span>
                        New field report
                    </a>
                    <a href="post_bulletin.php" class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all text-xs text-gray-600">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#FAEEDA"><svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="#854F0B" stroke-width="1.6"><path d="M2 9.5L4.5 2l7 7-7.5 1L2 9.5z"/></svg></span>
                        Post agri bulletin
                    </a>
                    <a href="prices.php" class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all text-xs text-gray-600">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#E6F1FB"><svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="#185FA5" stroke-width="1.6"><path d="M1.5 10l3.5-3.5 2 2 4.5-5"/></svg></span>
                        Check market prices
                    </a>
                    <a href="farmers.php" class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all text-xs text-gray-600">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#F1EFE8"><svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="#5F5E5A" stroke-width="1.6"><circle cx="6.5" cy="4" r="2.5"/><path d="M1.5 12c0-2.8 2.2-5 5-5s5 2.2 5 5"/></svg></span>
                        View farmer activity
                    </a>
                </div>
            </div>
        </div>

    </div>
</main>
</div>
</body>
</html>
