<?php
session_start();
 $required_role = 'farmer';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once "weather_fetch.php";
require_once "alerts.php"; 
require_once "price_risk_analyzer.php";

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

 $analyzer = new PriceRiskAnalyzer();
 $priceData = $analyzer->forecast($weather, $crop);

 $trend = $priceData['market_trend'] ?? 'stable';
 $drivers = $priceData['drivers'] ?? [];
 $advice = $priceData['advice'] ?? '';

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

    <title>FAIMS - Price Risks</title>
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
                <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">Price Risk Forecast</h2>
                <div class="hidden h-full py-1 sm:flex"><div class="h-full w-px bg-slate-300 dark:bg-navy-600"></div></div>
                <ul class="hidden flex-wrap items-center space-x-2 sm:flex">
                    <li class="flex items-center space-x-2"><span class="text-slate-500"><?= htmlspecialchars($display_location) ?></span></li>
                    <li><?= date('l, d M Y') ?></li>
                </ul>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Main Trend Display -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="card p-8 text-center <?= $trend === 'spike_likely' ? 'bg-red-50 dark:bg-red-900/10' : ($trend === 'drop_likely' ? 'bg-green-50 dark:bg-green-900/10' : 'bg-slate-50 dark:bg-navy-800') ?>">
                        <p class="text-xs+ text-slate-400 dark:text-navy-300 uppercase tracking-wide">5-Day Price Trend for <?= ucfirst($crop) ?></p>
                        
                        <div class="my-6 flex justify-center">
                            <?php if($trend === 'spike_likely'): ?>
                                <div class="flex flex-col items-center text-error">
                                    <svg class="size-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                    <span class="text-2xl font-bold mt-2">PRICE SPIKE LIKELY</span>
                                </div>
                            <?php elseif($trend === 'drop_likely'): ?>
                                <div class="flex flex-col items-center text-success">
                                    <svg class="size-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                                    <span class="text-2xl font-bold mt-2">PRICE DROP LIKELY</span>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-col items-center text-slate-500">
                                    <svg class="size-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                    <span class="text-2xl font-bold mt-2">STABLE MARKET</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="inline-block px-4 py-1 rounded-full bg-white dark:bg-navy-700 shadow text-sm font-medium">
                            Confidence Level: <span class="font-bold"><?= strtoupper($priceData['confidence'] ?? 'LOW') ?></span>
                        </div>
                    </div>

                    <!-- Drivers List -->
                    <div class="card p-6">
                        <h3 class="font-bold text-slate-800 dark:text-navy-100 mb-4">Market Drivers (Why?)</h3>
                        <div class="space-y-4">
                            <?php foreach($drivers as $driver): ?>
                                <div class="flex items-start space-x-3">
                                    <div class="mt-1.5 size-2 rounded-full bg-primary dark:bg-accent shrink-0"></div>
                                    <p class="text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($driver) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Strategy -->
                <div class="space-y-6">
                    <div class="card p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="mask is-squircle flex size-10 items-center justify-center bg-info/10">
                                <i class="fa-solid fa-chess text-xl text-info"></i>
                            </div>
                            <h4 class="font-bold text-slate-800 dark:text-navy-100">Farmer Strategy</h4>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-navy-200 leading-relaxed"><?= htmlspecialchars($advice) ?></p>
                    </div>

                    <div class="card p-5">
                        <h4 class="font-medium text-sm text-slate-700 dark:text-navy-100 mb-3">Disclaimer</h4>
                        <p class="text-xs text-slate-400 dark:text-navy-400 leading-relaxed">
                            This forecast is derived purely from local weather heuristics (Weather -> Yield -> Supply -> Price). It does not account for global commodity markets, currency fluctuations, or import/export policies. Use alongside local market board data.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div id="x-teleport-target"></div>
    <script>window.addEventListener("DOMContentLoaded", () => Alpine.start());</script>
</body>
</html>