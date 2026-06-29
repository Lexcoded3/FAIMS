<?php
session_start();
 $required_role = 'farmer'; // Only farmers allowed
require_once __DIR__ . '/../config/auth_check.php';
require_once "weather_engine.php";
require_once __DIR__ . '/../config/db.php';

// Default fallback location
 $lat = -0.3476;
 $lon = 32.5825;
 $display_location = "Kampala, Uganda";

 $crop = "maize"; // can later come from user profile

// If user is logged in, fetch location from database
if(isset($_SESSION['id'])){

    $user_id = $_SESSION['id'];

    $stmt = $conn->prepare("
        SELECT location_lat, location_lon, location
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i",$user_id);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    if(!empty($result['location_lat']) && !empty($result['location_lon'])){

        $lat = (float)$result['location_lat'];
        $lon = (float)$result['location_lon'];

        $display_location = $result['location'] ?? "Your Farm";

    }

}

// Fetch weather
 $data = weatherEngine($lat,$lon,$crop);

if(!$data || empty($data['weather'])){
    $error = "Weather service unavailable for {$display_location}. Please try again later.";
}

 $current = $data['weather']['list'][0] ?? [];

// Safely extract values for the main page and sidebar
 $icon_code = htmlspecialchars($current['weather'][0]['icon'] ?? '01d');
 $desc = htmlspecialchars(ucfirst($current['weather'][0]['description'] ?? 'Clear'));

// Safely extract new structured data for the cards
 $risksData = $data['risks']['risks'] ?? [];
 $riskLevel = $data['risks']['risk_level'] ?? 'low';

 $diseasesData = $data['disease']['diseases'] ?? [];

 $plantingVerdict = $data['planting']['verdict'] ?? 'unknown';
 $plantingReasons = $data['planting']['reasons'] ?? ['No recommendation available.'];

 $irrigationAdvice = $data['irrigation']['advice'] ?? ['No specific advice available.'];

 $forecastDays = $data['forecast']['forecast'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Weather</title>
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
    <!-- App preloader-->
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900">
      <div class="app-preloader-inner relative inline-block size-48"></div>
    </div>

    <!-- Page Wrapper -->
    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak="">
      <!-- Sidebar -->
      <div class="sidebar print:hidden">
        <!-- Main Sidebar -->
        <div class="main-sidebar">
          <div class="flex h-full w-full flex-col items-center border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
            <!-- Application Logo -->
            <div class="flex pt-4">
              <a href="index.php">
                <img class="size-11 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
              </a>
            </div>
            
            <!-- Main Sections Links -->
            <?php include 'sidenav.php';?>

          </div>
        </div>

        <!-- Sidebar Panel -->
        <?php include 'weathersider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include '../farmer/toprightsidenav.php';?>

       <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="flex items-center space-x-4 py-5 lg:py-6">
          <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">
            Weather Intelligence
          </h2>
          <div class="hidden h-full py-1 sm:flex">
            <div class="h-full w-px bg-slate-300 dark:bg-navy-600"></div>
          </div>
          <ul class="hidden flex-wrap items-center space-x-2 sm:flex">
            <li class="flex items-center space-x-2">
              <a class="text-primary transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent" href="#"><?= htmlspecialchars($display_location) ?></a>
              <svg x-ignore="" xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
              </svg>
            </li>
            <li><?= date('l, d M Y • H:i') ?></li>
          </ul>
        </div>
        
        <div class="mt-5 flex items-center justify-between">
          <h3 class="text-lg font-medium text-slate-700 line-clamp-1 dark:text-navy-50">
            Overview
          </h3>
          <div class="flex">
            <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </button>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
          <!-- Precipitation Chart Card -->
          <div class="card group col-span-12 lg:col-span-4">
            <div class="mt-3 flex items-center justify-between px-4 sm:px-5">
              <div class="flex flex-1 items-center justify-between space-x-2 sm:flex-initial">
                <h2 class="text-sm+ font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  <i class="fas fa-cloud-rain text-info mr-1"></i>Forecast
                </h2>
              </div>
              <div class="hidden justify-between space-x-4 text-xs+ sm:flex">
                <span class="font-medium text-slate-500 dark:text-navy-300"><?= count($forecastDays) ?> Days</span>
              </div>
            </div>
            <div class="ax-transparent-gridline pr-2 pb-2">
              <div id="precip-chart"></div>
            </div>
          </div>

          <div class="order-first col-span-12 grid grid-cols-2 gap-4 sm:order-none sm:gap-5 lg:col-span-8 lg:gap-6">
            
            <!-- Current Weather Card -->
            <div class="card row-span-2 justify-between py-5 px-2 text-center relative overflow-hidden">
              <p class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                <?= $desc ?>
              </p>
              <img src="https://openweathermap.org/img/wn/<?= $icon_code ?>@4x.png" 
               alt="<?= $desc ?>" 
               class="rounded-full absolute inset-0 w-66 h-full object-contain opacity-30">
              <div class="absolute inset-0 flex flex-col justify-end p-6 bg-gradient-to-t from-black/50 via-black/20">
                <p class="text-4xl text-primary-focus"><?= round($current['main']['temp'] ?? 0) ?>°C</p>
                <p class="text-2xl font-medium mt-2 capitalize"></p>
                <div class="mt-4 flex gap-6 text-sm opacity-90">
                  <span><i class="fas fa-tint mr-1"></i> <?= $current['main']['humidity'] ?? 0 ?>%</span>
                  <span><i class="fas fa-wind mr-1"></i> <?= $current['wind']['speed'] ?? 0 ?> m/s</span>
                  <span><i class="fas fa-feels-like mr-1"></i> <?= round($current['main']['feels_like'] ?? 0) ?>°C</span>
                </div>
              </div>
            </div>

            <!-- Risks Card -->
            <div class="card justify-center p-4">
              <div class="flex items-center space-x-3">
                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                <div class="grow space-y-6">
                  <div class="flex justify-between">
                    <p class="text-xs+ font-medium text-slate-700 dark:text-navy-100">Crop Risks <?= $riskLevel !== 'low' ? '(' . ucfirst($riskLevel) . ')' : '' ?></p>
                  </div>
                </div>
              </div>
              
              <?php if (empty($risksData)): ?>
                <div class="alert flex overflow-hidden rounded-lg border border-info text-info mt-3">
                  <div class="bg-info p-1 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div class="px-4 py-1 sm:px-5">No crop risks detected.</div>
                </div>
              <?php else: ?>
                <div class="space-y-3">
                  <?php foreach ($risksData as $r): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                      <p class="font-medium text-sm text-red-800"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $r['type']))) ?></p>
                      <p class="text-xs text-red-700 mt-1"><?= htmlspecialchars($r['message']) ?></p>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Disease Card -->
            <div class="card justify-center p-4">
              <div class="flex items-center space-x-3">
                <i class="fas fa-biohazard fa-2x text-error"></i>
                <div class="grow space-y-6">
                  <div class="flex justify-between">
                    <p class="text-xs+ font-medium text-slate-700 dark:text-navy-100">Disease Prediction</p>
                  </div>
                </div>
              </div>
              
              <?php 
              // Check if empty OR if the only disease is "None"
              $hasDisease = !empty($diseasesData) && !(count($diseasesData) === 1 && ($diseasesData[0]['name'] ?? '') === 'None');
              ?>
              
              <?php if (!$hasDisease): ?>
                <div class="alert flex overflow-hidden rounded-lg border border-info text-info mt-3">
                  <div class="bg-info p-1 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div class="px-4 py-1 sm:px-5">No diseases detected.</div>
                </div>
              <?php else: ?>
                <div class="space-y-3 mt-3">
                  <?php foreach ($diseasesData as $d): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                      <p class="font-medium text-sm text-red-800"><?= htmlspecialchars($d['name']) ?></p>
                      <p class="text-xs text-red-700 mt-1"><?= htmlspecialchars($d['advice'] ?? $d['message'] ?? '') ?></p>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Planting Card -->
            <div class="card flex-row overflow-hidden">
              <div class="h-full w-1 shrink-0 bg-success"></div>
              <div class="p-4 font-inter">
                <div class="flex items-center space-x-3">
                  <div class="mask is-squircle flex size-10 items-center justify-center bg-success/10">
                    <i class="fa-solid fa-seedling text-xl text-success"></i>
                  </div>
                  <div class="grow space-y-1">
                    <div class="flex justify-between">
                      <p class="text-xs+ font-medium text-slate-700 dark:text-navy-100">Planting Recommendation</p>
                    </div>
                  </div>
                </div>
                <div class="mt-2 space-y-1">
                  <?php foreach ($plantingReasons as $reason): ?>
                    <p class="text-xs <?= $plantingVerdict === 'favorable' ? 'text-success' : ($plantingVerdict === 'unfavorable' ? 'text-error' : 'text-warning') ?>">
                      <?= htmlspecialchars($reason) ?>
                    </p>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Irrigation Card -->
            <div class="card flex-row overflow-hidden">
              <div class="h-full w-1 shrink-0 bg-info"></div>
              <div class="p-4 font-inter">
                <div class="flex items-center space-x-3">
                  <div class="mask is-squircle flex size-10 items-center justify-center bg-info/10">
                    <i class="fa-solid fas fa-water text-xl text-info"></i>
                  </div>
                  <div class="grow space-y-1">
                    <div class="flex justify-between">
                      <p class="text-xs+ font-medium text-slate-700 dark:text-navy-100">Irrigation Advice</p>
                    </div>
                  </div>
                </div>
                <div class="mt-2 space-y-1">
                  <?php foreach ($irrigationAdvice as $advice): ?>
                    <p class="text-xs"><?= htmlspecialchars($advice) ?></p>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            
          </div>
        </div>

        <!-- 6-Day Forecast Section -->
        <div class="mt-4 sm:mt-5 lg:mt-6">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
              6-Day Farm Forecast
            </h2>
          </div>
          
          <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 lg:gap-6">
            <?php foreach($forecastDays as $day): ?>
            <div class="card space-y-4 p-4 sm:px-5">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-lg font-semibold lowercase text-primary dark:text-accent-light">
                    <?= htmlspecialchars($day['day']) ?>
                  </p>
                </div>
                <div class="text-2xl">
                   <?= $day['icons'] ?? '✅' ?>
                </div>
              </div>
              
              <div>
                <?php foreach($day['actions'] as $a): ?>
                  <p class="text-sm font-medium text-success"><?= htmlspecialchars($a) ?></p>
                <?php endforeach; ?>
              </div>
              
              <div class="flex grow justify-between space-x-2 pt-2 border-t border-slate-100 dark:border-navy-600">
                <div>
                  <p class="text-xs+ text-slate-400 dark:text-navy-300">Date</p>
                  <p class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($day['date']) ?></p>
                </div>
                <div>
                  <p class="text-xs+ text-slate-400 dark:text-navy-300">Temp</p>
                  <p class="font-medium text-slate-700 dark:text-navy-100">
                    <?= $day['temp_high'] ?>° / <?= $day['temp_low'] ?>°
                  </p>
                </div>
                <div>
                  <p class="text-xs+ text-slate-400 dark:text-navy-300">Rain</p>
                  <p class="font-medium text-slate-700 dark:text-navy-100">
                    <?= $day['rain_mm'] ?> mm
                  </p>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </main>
    </div>

    <div id="x-teleport-target"></div>
        <script>
      window.addEventListener("DOMContentLoaded", () => {
        Alpine.start(); // Keep existing Alpine start

        // Initialize Precipitation Chart
        if (typeof ApexCharts !== 'undefined') {
          const chartData = <?= json_encode(array_map(fn($d) => floatval($d['rain_mm']), $forecastDays)) ?>;
          const chartCategories = <?= json_encode(array_map(fn($d) => substr($d['day'], 0, 3) . ' ' . substr($d['date'], 5), $forecastDays)) ?>;

          var options = {
            series: [{
              name: 'Rainfall (mm)',
              data: chartData
            }],
            chart: {
              height: 360,
              type: 'bar',
              fontFamily: 'Inter, sans-serif',
              toolbar: { show: false },
              zoom: { enabled: false }
            },
            plotOptions: {
              bar: {
                borderRadius: 4,
                columnWidth: '60%',
                colors: {
                  ranges: [{
                    from: 0,
                    to: 2,
                    color: '#38bdf8' // light blue for light rain
                  }, {
                    from: 3,
                    to: 10,
                    color: '#3b82f6' // blue for moderate rain
                  }, {
                    from: 11,
                    color: '#ef4444' // red for heavy rain warning
                  }]
                }
              }
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
              categories: chartCategories,
              labels: { style: { fontSize: '11px', colors: '#64748b' } }
            },
            yaxis: {
              title: { text: 'mm', style: { fontSize: '11px', color: '#64748b' } },
              labels: { style: { fontSize: '11px', colors: '#64748b' } }
            },
            fill: { opacity: 1 },
            tooltip: {
              theme: 'dark',
              y: { formatter: function (val) { return val + " mm" } }
            },
            grid: { borderColor: '#e2e8f0', strokeDashArray: 4 }
          };

          var chart = new ApexCharts(document.querySelector("#precip-chart"), options);
          chart.render();
        }
      });
    </script>
  </body>
</html>