<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'weather.php';

$filter_loc = $conn->real_escape_string(trim($_GET['location'] ?? ''));

$locations = [];
$res = $conn->query("SELECT DISTINCT location FROM weather_data WHERE location IS NOT NULL ORDER BY location");
while ($r = $res->fetch_assoc()) $locations[] = $r['location'];

$loc_cond = $filter_loc ? "AND wd.location='$filter_loc'" : '';

$current = [];
$res = $conn->query("
    SELECT wd.* FROM weather_data wd
    INNER JOIN (SELECT location, MAX(created_at) AS max_at FROM weather_data GROUP BY location) latest
        ON wd.location=latest.location AND wd.created_at=latest.max_at
    WHERE 1=1 $loc_cond ORDER BY wd.location
");
while ($r = $res->fetch_assoc()) $current[] = $r;

$forecast = [];
$res = $conn->query("
    SELECT location,temperature,humidity,wind_speed,weather_main,weather_desc,forecast_time
    FROM weather_data WHERE forecast_time>NOW() $loc_cond
    ORDER BY location,forecast_time ASC LIMIT 30
");
while ($r = $res->fetch_assoc()) $forecast[$r['location']][] = $r;

function wicon(string $m): string {
    $m = strtolower($m);
    if (str_contains($m,'rain'))    return '🌧';
    if (str_contains($m,'cloud'))   return '☁';
    if (str_contains($m,'clear')||str_contains($m,'sun')) return '☀';
    if (str_contains($m,'storm')||str_contains($m,'thunder')) return '⛈';
    if (str_contains($m,'wind'))    return '💨';
    if (str_contains($m,'mist')||str_contains($m,'fog')) return '🌫';
    return '🌤';
}
function farming_tip(string $desc, float $temp, int $hum): string {
    $d = strtolower($desc);
    if (str_contains($d,'rain')||$hum>80) return 'Good for planting. Watch for fungal diseases.';
    if ($temp>32) return 'High heat — advise irrigating early morning or evening.';
    if ((str_contains($d,'clear'))&&$temp>=20&&$temp<=30) return 'Ideal for harvesting and drying produce.';
    if (str_contains($d,'wind')||str_contains($d,'storm')) return 'Warn farmers to secure seedlings and structures.';
    return 'Monitor crops regularly — conditions are moderate.';
}
?>
<!DOCTYPE html><html lang="en"><head>
<title>Weather — FAIMS Extension</title>
<?php include '_head.php'; ?>
<style>.weather-icon{font-size:26px;line-height:1}</style>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base text-gray-800" style="font-weight:500">Weather</h1>
            <p class="text-xs text-gray-400 mt-0.5">Conditions across monitoring locations</p>
        </div>
        <form method="GET">
            <select name="location" onchange="this.form.submit()" style="width:auto">
                <option value="">All locations</option>
                <?php foreach($locations as $loc): ?>
                <option value="<?= htmlspecialchars($loc) ?>" <?= $filter_loc===$loc?'selected':'' ?>><?= htmlspecialchars($loc) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-5 fade-in">
        <?php if(empty($current)): ?>
        <div class="text-center py-16 text-xs text-gray-400">No weather data available</div>
        <?php else: ?>

        <div class="grid grid-cols-3 gap-4 mb-6">
            <?php foreach($current as $w): ?>
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5"><?= htmlspecialchars($w['location']) ?></p>
                        <p class="mono text-3xl text-gray-800" style="font-weight:500"><?= round($w['temperature']) ?>°C</p>
                    </div>
                    <span class="weather-icon"><?= wicon($w['weather_main']??'') ?></span>
                </div>
                <p class="text-xs text-gray-600 capitalize mb-3"><?= htmlspecialchars($w['weather_desc']??'') ?></p>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="rounded-lg px-3 py-2" style="background:#f9fafb">
                        <p class="text-gray-400 mb-0.5" style="font-size:10px">Humidity</p>
                        <p class="mono text-xs text-gray-700" style="font-weight:500"><?= $w['humidity'] ?>%</p>
                    </div>
                    <div class="rounded-lg px-3 py-2" style="background:#f9fafb">
                        <p class="text-gray-400 mb-0.5" style="font-size:10px">Wind</p>
                        <p class="mono text-xs text-gray-700" style="font-weight:500"><?= round($w['wind_speed']??0) ?> m/s</p>
                    </div>
                </div>
                <!-- Farming tip -->
                <div class="flex gap-2 px-3 py-2 rounded-lg" style="background:#E1F5EE22;border:1px solid #9FE1CB40">
                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="#0F6E56" stroke-width="1.4" class="flex-shrink-0 mt-0.5"><path d="M6.5 1v8M4 6.5l2.5 2.5 2.5-2.5"/><path d="M2 11h9"/></svg>
                    <p class="text-xs leading-relaxed" style="color:#0F6E56;font-size:11px"><?= farming_tip($w['weather_desc']??'',(float)$w['temperature'],(int)$w['humidity']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if(!empty($forecast)): ?>
        <p class="text-xs text-gray-400 mb-3" style="font-weight:500">Upcoming forecast</p>
        <div class="space-y-3">
            <?php foreach($forecast as $loc=>$entries): ?>
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <p class="text-xs text-gray-700" style="font-weight:500"><?= htmlspecialchars($loc) ?></p>
                </div>
                <div class="flex overflow-x-auto scrollbar-hide px-4 py-3 gap-5">
                    <?php foreach(array_slice($entries,0,6) as $fc): ?>
                    <div class="flex-shrink-0 text-center" style="min-width:48px">
                        <p class="mono text-gray-400 mb-1" style="font-size:10px"><?= date('H:i',strtotime($fc['forecast_time'])) ?></p>
                        <span style="font-size:18px;line-height:1"><?= wicon($fc['weather_main']??'') ?></span>
                        <p class="mono text-xs text-gray-700 mt-1" style="font-weight:500"><?= round($fc['temperature']) ?>°</p>
                        <p class="text-gray-400" style="font-size:10px"><?= $fc['humidity'] ?>%</p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; endif; ?>
    </div>
</main>
</div>
</body></html>
