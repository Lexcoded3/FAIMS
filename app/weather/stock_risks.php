<?php
session_start();
 $required_role = 'farmer';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once "weather_fetch.php";
require_once "alerts.php"; 
require_once "stock_risk_analyzer.php";

 $lat = -0.3476; $lon = 32.5825; $crop = "maize"; $display_location = "Kampala, Uganda";

if(isset($_SESSION['id'])){
    $stmt = $conn->prepare("SELECT location_lat, location_lon, location FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if(!empty($result['location_lat']) && !empty($result['location_lon'])){
        $lat = (float)$result['location_lat']; $lon = (float)$result['location_lon'];
        $display_location = $result['location'] ?? "Your Farm";
    }
}

 $weather = fetchWeather($lat, $lon);
if (!$weather) {
    $weather = []; // Fallback to empty so the page doesn't crash
}

 $analyzer = new StockRiskAnalyzer();
 $stockData = $analyzer->analyze($weather, $crop);
 $risks = $stockData['risks'] ?? [];

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

    <title>FAIMS - Stock Risks</title>
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
                <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">Stock & Inventory Risks</h2>
                <div class="hidden h-full py-1 sm:flex"><div class="h-full w-px bg-slate-300 dark:bg-navy-600"></div></div>
                <ul class="hidden flex-wrap items-center space-x-2 sm:flex">
                    <li class="flex items-center space-x-2"><span class="text-slate-500"><?= htmlspecialchars($display_location) ?></span></li>
                    <li><?= date('l, d M Y') ?></li>
                </ul>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Risk Feed -->
                <div class="lg:col-span-2 space-y-6">
                    <h3 class="text-lg font-medium text-slate-700 dark:text-navy-50">Active Risk Assessments</h3>
                    
                    <?php foreach($risks as $risk): ?>
                        <?php 
                            $colorMap = [
                                'high' => 'border-l-error bg-red-50 dark:bg-red-900/10',
                                'medium' => 'border-l-warning bg-yellow-50 dark:bg-yellow-900/10',
                                'low' => 'border-l-success bg-green-50 dark:bg-green-900/10'
                            ];
                            $iconMap = [
                                'storage_loss' => 'fas fa-warehouse',
                                'supply_disruption' => 'fas fa-truck',
                                'yield_shortfall' => 'fas fa-seedling',
                                'none' => 'fas fa-check-circle'
                            ];
                            $cardColor = $colorMap[$risk['severity']] ?? $colorMap['low'];
                            $icon = $iconMap[$risk['type']] ?? 'fas fa-exclamation-triangle';
                        ?>
                        <div class="card border-l-4 <?= $cardColor ?> p-6">
                            <div class="flex items-start space-x-4">
                                <div class="mt-1 text-2xl <?= $risk['severity'] === 'high' ? 'text-error' : 'text-success' ?>">
                                    <i class="<?= $icon ?>"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-bold text-slate-800 dark:text-navy-100"><?= htmlspecialchars($risk['title']) ?></h4>
                                        <span class="text-xs font-bold uppercase <?= $risk['severity'] === 'high' ? 'text-error' : 'text-success' ?>">
                                            <?= $risk['severity'] ?> risk
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-600 dark:text-navy-200 mb-4"><?= htmlspecialchars($risk['reason']) ?></p>
                                    
                                    <div class="bg-white dark:bg-navy-800 rounded p-3 border border-slate-200 dark:border-navy-600">
                                        <p class="text-xs font-bold text-slate-800 dark:text-navy-100 mb-1">RECOMMENDED ACTION:</p>
                                        <p class="text-sm text-info dark:text-accent-light"><?= htmlspecialchars($risk['action']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sidebar Summary -->
                <div class="space-y-6">
                    <div class="card p-6 text-center">
    					<p class="text-xs+ text-slate-400 dark:text-navy-300 uppercase tracking-wide">Overall Stock Risk</p>
    
						    <!-- Added mx-auto here to center the inline-flex block -->
						    <div class="mt-4 mx-auto flex items-center justify-center size-22rounded-full <?= $stockData['overall_risk'] === 'high' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-green-100 dark:bg-green-900/30' ?>">
						        <i class="fas fa-shield-alt text-3xl <?= $stockData['overall_risk'] === 'high' ? 'text-error' : 'text-success' ?>"></i>
						    </div>
						    
						    <p class="mt-3 text-xl font-bold <?= $stockData['overall_risk'] === 'high' ? 'text-error' : 'text-success' ?>">
						        <?= strtoupper($stockData['overall_risk'] ?? 'LOW') ?>
						    </p>
						    <p class="text-xs text-slate-500 mt-1">Based on <?= ucfirst($crop) ?> profile</p>
						</div>


                    <div class="card p-5">
                        <h4 class="font-medium text-sm text-slate-700 dark:text-navy-100 mb-3">How this works</h4>
                        <!-- <ul class="text-xs text-slate-500 dark:text-navy-300 space-y-2">
                            <li>• Analyzes humidity for storage rot.</li>
                            <li>• Tracks rain/wind for transport issues.</li>
                            <li>• Monitors dry spells for yield drops.</li>
                        </ul> -->
                        <div>
						    <ol class="steps is-vertical line-space">
						      <li
						        class="step space-x-4 pb-12 before:bg-slate-200 dark:before:bg-navy-500"
						      >
						        <div
						          class="step-header rounded-full bg-slate-200 text-slate-800 dark:bg-navy-500 dark:text-white"
						        >
						          1
						        </div>
						        <h3 class="text-slate-600 dark:text-navy-100 text-left">Analyzes humidity for storage rot.</h3>
						      </li>
						      <li
						        class="step space-x-4 pb-12 before:bg-slate-200 dark:before:bg-navy-500"
						      >
						        <div
						          class="step-header rounded-full bg-slate-200 text-slate-800 dark:bg-navy-500 dark:text-white"
						        >
						          2
						        </div>
						        <h3 class="text-slate-600 dark:text-navy-100 text-left">Tracks rain/wind for transport issues.</h3>
						      </li>
						      <li
						        class="step space-x-4 pb-12 before:bg-slate-200 dark:before:bg-navy-500"
						      >
						        <div
						          class="step-header rounded-full bg-slate-200 text-slate-800 dark:bg-navy-500 dark:text-white"
						        >
						          3
						        </div>
						        <h3 class="text-slate-600 dark:text-navy-100 text-left">Monitors dry spells for yield drops.</h3>
						      </li>
						      <!-- <li class="step space-x-4 before:bg-slate-200 dark:before:bg-navy-500">
						        <div
						          class="step-header rounded-full bg-slate-200 text-slate-800 dark:bg-navy-500 dark:text-white"
						        >
						          4
						        </div>
						        <h3 class="text-slate-600 dark:text-navy-100">Step 4</h3>
						      </li> -->
						    </ol>
						  </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div id="x-teleport-target"></div>
    <script>window.addEventListener("DOMContentLoaded", () => Alpine.start());</script>
</body>
</html>