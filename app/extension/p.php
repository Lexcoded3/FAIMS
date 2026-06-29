<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'prices.php';

$filter_cat    = (int)($_GET['category'] ?? 0);
$filter_search = $conn->real_escape_string(trim($_GET['search'] ?? ''));

$categories = [];
$res = $conn->query("SELECT * FROM categories ORDER BY name");
while ($r = $res->fetch_assoc()) $categories[] = $r;

$where = "WHERE 1=1";
if ($filter_cat > 0)       $where .= " AND mp.category_id=$filter_cat";
if ($filter_search !== '') $where .= " AND mp.crop LIKE '%$filter_search%'";

$prices = [];
$res = $conn->query("
    SELECT mp.crop, mp.price, mp.date, c.name AS category
    FROM market_prices mp
    LEFT JOIN categories c ON c.id=mp.category_id
    $where
    AND mp.date=(SELECT MAX(date) FROM market_prices mp2 WHERE mp2.crop=mp.crop)
    ORDER BY mp.crop ASC
");
while ($r = $res->fetch_assoc()) $prices[] = $r;

// 14-day history for chart
$history_data = [];
$res = $conn->query("SELECT crop,price,date FROM market_prices WHERE date>=DATE_SUB(CURDATE(),INTERVAL 14 DAY) ORDER BY crop,date ASC");
while ($r = $res->fetch_assoc()) $history_data[$r['crop']][] = ['date'=>$r['date'],'price'=>(float)$r['price']];
?>
<!DOCTYPE html><html lang="en"><head>
<title>Market Prices — FAIMS Extension</title>
<?php include '_head.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex-shrink-0">
        <h1 class="text-base text-gray-800" style="font-weight:500">Market prices</h1>
        <p class="text-xs text-gray-400 mt-0.5">Latest prices — <?= date('d M Y') ?></p>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-5 fade-in">
        <div class="grid grid-cols-3 gap-4">

            <!-- Table (2/3) -->
            <div class="col-span-2">
                <form method="GET" class="flex items-center gap-3 mb-4">
                    <div class="relative flex-1 max-w-xs">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="5.5" cy="5.5" r="4"/><path d="M9 9l2.5 2.5"/></svg>
                        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search']??'') ?>" placeholder="Search crop…" style="padding-left:32px">
                    </div>
                    <select name="category" style="width:auto">
                        <option value="0">All categories</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filter_cat===(int)$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-ghost">Filter</button>
                    <?php if($filter_cat||$filter_search): ?><a href="prices.php" class="text-xs text-gray-400 hover:text-gray-600">Clear</a><?php endif; ?>
                </form>

                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <table class="w-full">
                        <thead><tr class="border-b border-gray-100">
                            <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Crop</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Category</th>
                            <th class="text-right px-4 py-3 text-xs text-gray-400" style="font-weight:500">Price (UGX/kg)</th>
                            <th class="text-right px-4 py-3 text-xs text-gray-400" style="font-weight:500">Updated</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                        <?php if(empty($prices)): ?>
                            <tr><td colspan="4" class="px-4 py-10 text-center text-xs text-gray-400">No price data</td></tr>
                        <?php else: foreach($prices as $p): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-xs text-gray-700" style="font-weight:500"><?= htmlspecialchars($p['crop']) ?></td>
                                <td class="px-4 py-3 text-xs text-gray-500"><?= htmlspecialchars($p['category']??'—') ?></td>
                                <td class="px-4 py-3 text-right mono text-xs text-gray-800" style="font-weight:500"><?= number_format((float)$p['price']) ?></td>
                                <td class="px-4 py-3 text-right mono text-xs text-gray-400"><?= date('d M Y',strtotime($p['date'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Chart (1/3) -->
            <div>
                <div class="bg-white rounded-xl border border-gray-100 p-4">
                    <p class="text-xs text-gray-700 mb-1" style="font-weight:500">14-day trend</p>
                    <p class="text-xs text-gray-400 mb-3">Top crops by price</p>
                    <canvas id="priceChart" height="260"></canvas>
                </div>
            </div>
        </div>
    </div>
</main>
</div>

<script>
const historyData = <?= json_encode($history_data) ?>;
const colors = ['#1D9E75','#378ADD','#D85A30','#BA7517','#993556'];
const sorted = Object.entries(historyData)
    .map(([crop,pts])=>({crop,pts,max:Math.max(...pts.map(p=>p.price))}))
    .sort((a,b)=>b.max-a.max).slice(0,5);
const allDates = [...new Set(sorted.flatMap(c=>c.pts.map(p=>p.date)))].sort();
const datasets = sorted.map((c,i)=>({
    label:c.crop,
    data:allDates.map(d=>{const f=c.pts.find(p=>p.date===d);return f?f.price:null;}),
    borderColor:colors[i%colors.length],backgroundColor:'transparent',
    borderWidth:1.5,pointRadius:2,tension:.3,spanGaps:true
}));
new Chart(document.getElementById('priceChart'),{
    type:'line',
    data:{labels:allDates.map(d=>d.slice(5)),datasets},
    options:{responsive:true,plugins:{legend:{labels:{font:{size:11},boxWidth:10,padding:8}}},
    scales:{x:{ticks:{font:{size:10},maxTicksLimit:6},grid:{color:'#f3f4f6'}},
            y:{ticks:{font:{size:10},callback:v=>v.toLocaleString()},grid:{color:'#f3f4f6'}}}}
});
</script>
</body></html>
