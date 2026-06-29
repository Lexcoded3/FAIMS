<?php
session_start();
 $required_role = 'farmer';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once "weather_fetch.php";
require_once "alerts.php"; // Contains _aggregateDaily
require_once "disease_page_analyzer.php";

 $lat = -0.3476; $lon = 32.5825; $crop = "maize"; $display_location = "Kampala, Uganda";

if(isset($_SESSION['id'])){
    $stmt = $conn->prepare("SELECT location_lat, location_lon, location FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if(!empty($result['location_lat']) && !empty($result['location_lon'])){
        $lat = (float)$result['location_lat'];
        $lon = (float)$result['location_lon'];
        $display_location = $result['location'] ?? "Your Farm";
    }
}

 $weather = fetchWeather($lat, $lon);
if (!$weather) {
    $weather = []; // Fallback to empty so the page doesn't crash
}

 $analyzer = new DiseasePageAnalyzer();
 $diseaseData = $analyzer->analyzeFullTimeline($weather, $crop);

 $timeline = $diseaseData['timeline'] ?? [];
 $summary = $diseaseData['summary'] ?? [];

 // Safely extract values for the main page and sidebar
 $icon_code = htmlspecialchars($current['weather'][0]['icon'] ?? '01d');
 $desc = htmlspecialchars(ucfirst($current['weather'][0]['description'] ?? 'Clear'));
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Disease</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="../css/app.css">

    <!-- Javascript Assets -->
    <script src="../js/app.js" defer=""></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <script>
      localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>
  </head>
<body x-data="" class="is-header-blur is-sidebar-open" x-bind="$store.global.documentBody">
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900"><div class="app-preloader-inner relative inline-block size-48"></div></div>
    
    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak>
        <div class="sidebar print:hidden">
            <div class="main-sidebar"><div class="flex h-full w-full flex-col items-center border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
                <div class="flex pt-4"><a href="index.php"><img class="size-11" src="../images/app-logo.png" alt="logo"></a></div>
                <?php include 'sidenav.php';?>
            </div></div>
            <?php include 'weathersider.php';?>
        </div>
        <?php include '../farmer/toprightsidenav.php';?>

        <main class="main-content w-full px-[var(--margin-x)] pb-8">
            <div class="flex items-center space-x-4 py-5 lg:py-6">
                <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">Disease Prediction</h2>
                <div class="hidden h-full py-1 sm:flex"><div class="h-full w-px bg-slate-300 dark:bg-navy-600"></div></div>
                <ul class="hidden flex-wrap items-center space-x-2 sm:flex">
                    <li class="flex items-center space-x-2"><span class="text-slate-500"><?= htmlspecialchars($display_location) ?></span></li>
                    <li><?= date('l, d M Y') ?></li>
                </ul>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="card p-5">
                    <p class="text-xs+ text-slate-400 dark:text-navy-300">Overall Status</p>
                    <p class="text-2xl font-bold mt-2 <?= $summary['overall_status'] === 'critical' ? 'text-error' : ($summary['overall_status'] === 'warning' ? 'text-warning' : 'text-success') ?>">
                        <?= strtoupper($summary['overall_status'] ?? 'N/A') ?>
                    </p>
                </div>
                <div class="card p-5">
                    <p class="text-xs+ text-slate-400 dark:text-navy-300">High Risk Days (Next 5)</p>
                    <p class="text-2xl font-bold mt-2 text-slate-800 dark:text-navy-100"><?= $summary['high_risk_days'] ?? 0 ?> Days</p>
                </div>
                <div class="card p-5">
                    <p class="text-xs+ text-slate-400 dark:text-navy-300">Top Threats for <?= ucfirst($crop) ?></p>
                    <p class="text-sm font-medium mt-2 text-slate-800 dark:text-navy-100">
                        <?= !empty($summary['top_threats']) ? implode(', ', $summary['top_threats']) : 'None detected' ?>
                    </p>
                </div>
            </div>

            <h3 class="text-lg font-medium text-slate-700 dark:text-navy-50 mb-4">5-Day Disease Timeline</h3>

            <!-- Timeline Loop -->
            <div class="space-y-4">
                <?php if(empty($timeline)): ?>
                    <div class="card p-6 text-center text-slate-500 dark:text-navy-300">No weather data available to generate timeline.</div>
                <?php else: ?>
                    <?php foreach($timeline as $day): ?>
                        <?php 
                            $borderColor = $day['risk_level'] === 'high' ? 'border-l-error' : ($day['risk_level'] === 'medium' ? 'border-l-warning' : 'border-l-success');
                        ?>
                        <div class="card border-l-4 <?= $borderColor ?> p-5">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3">
                                <h4 class="font-semibold text-slate-800 dark:text-navy-100"><?= htmlspecialchars($day['day'] . ', ' . $day['date']) ?></h4>
                                <span class="text-xs font-medium px-2 py-1 rounded mt-2 sm:mt-0 <?= $day['risk_level'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' ?>">
                                    Risk: <?= ucfirst($day['risk_level']) ?>
                                </span>
                            </div>
                            
                            <div class="flex gap-6 text-xs text-slate-500 dark:text-navy-400 mb-3">
                                <span>🌡️ Avg Temp: <?= $day['trigger_data']['temp_avg'] ?>°C</span>
                                <span>💧 Humidity: <?= $day['trigger_data']['humidity_avg'] ?>%</span>
                                <span>🌧️ Rain: <?= $day['trigger_data']['rain_mm'] ?>mm</span>
                                <span>⏳ High Humid Streak: <?= $day['trigger_data']['consecutive_humid_days'] ?> days</span>
                            </div>

                            <?php if(!empty($day['diseases'])): ?>
                                <div class="space-y-2">
                                    <?php foreach($day['diseases'] as $d): ?>
                                        <div class="bg-slate-50 dark:bg-navy-700/50 rounded p-3 text-sm">
                                            <span class="font-bold text-slate-800 dark:text-navy-100"><?= htmlspecialchars($d['name']) ?></span> 
                                            <span class="text-xs text-slate-500">(Severity: <?= ucfirst($d['severity']) ?>)</span>
                                            <p class="text-slate-600 dark:text-navy-200 mt-1">→ <?= htmlspecialchars($d['action']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-slate-500">Conditions not favorable for active <?= ucfirst($crop) ?> diseases on this day.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <div id="x-teleport-target"></div>
    <script>window.addEventListener("DOMContentLoaded", () => Alpine.start());</script>
</body>
</html>