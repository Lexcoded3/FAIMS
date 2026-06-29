<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

// 1. Get all unique crops/categories with latest stats
$market_data = $conn->query("
    SELECT 
        mp.category_id,c.image AS category_image,
        COALESCE(mp.crop, c.name, CONCAT('Category ', mp.category_id)) AS crop_name,
        ROUND(AVG(mp.price), 0) AS avg_price,
        ROUND(MIN(mp.price), 0) AS min_price,
        ROUND(MAX(mp.price), 0) AS max_price,
        COUNT(mp.id) AS data_points,
        MAX(mp.date) AS latest_date,
        (SELECT price FROM market_prices mp2 
         WHERE mp2.category_id = mp.category_id 
         ORDER BY mp2.date DESC LIMIT 1) AS current_price,
        (SELECT price FROM market_prices mp3 
         WHERE mp3.category_id = mp.category_id 
         ORDER BY mp3.date ASC LIMIT 1) AS start_price
    FROM market_prices mp
    LEFT JOIN categories c ON mp.category_id = c.id
    GROUP BY mp.category_id, mp.crop
    ORDER BY current_price DESC
")->fetch_all(MYSQLI_ASSOC);

// 2. Prepare mini-chart data for each crop (last 7 prices or less)
$mini_charts = [];
foreach ($market_data as &$item) {
    $cat_id = $item['category_id'];
    $prices = $conn->query("
        SELECT price, date 
        FROM market_prices 
        WHERE category_id = $cat_id 
        ORDER BY date DESC 
        LIMIT 7
    ")->fetch_all(MYSQLI_ASSOC);

    // Use array_map to ensure every price is a numeric float
    $item['chart_data'] = array_reverse(array_map('floatval', array_column($prices, 'price')));
    $item['price_change'] = round(($item['current_price'] - $item['start_price']) / $item['start_price'] * 100, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>FAIMS - Live</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">
    <link rel="stylesheet" href="../css/app.css">
    <!-- <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> -->
    <script src="../js/app.js" defer=""></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <script>
      localStorage.getItem("_x_darkMode_on") === "true" && document.documentElement.classList.add("dark");
    </script>
  </head>

  <body x-data="" x-bind="$store.global.documentBody" class="is-header-blur">
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900">
      <div class="app-preloader-inner relative inline-block size-48"></div>
    </div>

    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak="">
      <!-- Sidebar -->
      <div class="sidebar print:hidden">
        <div class="main-sidebar">
          <div class="flex h-full w-full flex-col items-center border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
            <div class="flex pt-4">
              <a href="index.php">
                <img class="size-11 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
              </a>
            </div>
            <?php include 'sidenav.php';?>
          </div>
        </div>
        <?php include 'livesider.php';?>
      </div>

      <?php include 'toprightsidenav.php';?>

      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="col-span-12">
            <div class="card mt-4 pb-1 sm:mt-5 lg:mt-6">
              <div class="my-3 flex items-center justify-between px-4 sm:px-5">
                <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Live Market Watchlist
                </h2>
                Updated <?= date('d M Y H:i') ?>
               <!--  <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn -mr-1.5 size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                    </svg>
                  </button>

                  <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                    <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                      <ul>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Action</a>
                        </li>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Another Action</a>
                        </li>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Something else</a>
                        </li>
                      </ul>
                      <div class="my-1 h-px bg-slate-150 dark:bg-navy-500"></div>
                      <ul>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Separated Link</a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div> -->
              </div>
              <div class="scrollbar-sm flex space-x-4 overflow-x-auto overflow-y-hidden px-4 pb-2 sm:px-5">
                <?php foreach ($market_data as $item): 
                  // Determine color based on price change
                  if ($item['price_change'] > 0) {
                      $chart_color = '#10b981'; // Green (Positive)
                  } elseif ($item['price_change'] < 0) {
                      $chart_color = '#ef4444'; // Red (Negative)
                  } else {
                      $chart_color = '#3d5ee1'; // Blue (No Change / Stable)
                  }
              ?>

                <div class="flex w-72 shrink-0 flex-col">
                  <div class="flex items-center space-x-2">
                    <?php 
    $img = $item['category_image'];
    // We check if the string isn't empty and if the file actually exists on the server
    if (!empty($img) && file_exists('../' . $img)): 
  ?>
    <img src="../<?= htmlspecialchars($img) ?>" 
         alt="icon" 
         class="size-6 object-contain">
  <?php else: ?>
    <i class="fas fa-seedling text-sm"></i>
  <?php endif; ?>
                    <div>
                      <span><?= htmlspecialchars($item['crop_name']) ?></span>
                      <span class="text-xs uppercase text-slate-400 dark:text-navy-300">
                        <?= htmlspecialchars($item['category_id'] ? 'Cat ' . $item['category_id'] : 'N/A') ?>
                      </span>
                    </div>
                  </div>

                  <div class="mt-2.5 flex justify-between rounded-lg bg-slate-50 py-3 pr-3 dark:bg-navy-600">
                    <div class="w-32 h-16"> 
              <div x-init="
                setTimeout(() => {
                  new ApexCharts($el, {
                    // Use .map(Number) just in case the PHP strings persist
                    series: [{ data: <?= json_encode($item['chart_data']) ?>.map(Number) }],
                    chart: { 
                      type: 'area', 
                      height: 60, 
                      sparkline: { enabled: true },
                      animations: { enabled: true } 
                    },
                    stroke: { 
                      curve: 'smooth', 
                      width: 3 
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            inverseColors: false,
                            opacityFrom: 0.45,
                            opacityTo: 0.05,
                            stops: [20, 100, 100, 100]
                          },
                      },
                    // Use the color from our PHP logic
                    colors: ['<?= $chart_color ?>'],  
                    tooltip: { enabled: false }
                  }).render();
                }, 150)
              "></div>
            </div>
                    <div class="flex w-36 flex-col items-center rounded-lg bg-slate-100 py-2 font-inter dark:bg-navy-500">
                      <p class="text-xl font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                        UGX <?= number_format($item['current_price'] ?? 0, 0) ?>
                      </p>
                      <p class="mt-1 flex items-center space-x-1 text-xs <?= $item['price_change'] >= 0 ? 'text-success' : 'text-error' ?>">
                  <i class="fas fa-<?= $item['price_change'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                  <span><?= abs($item['price_change']) ?>%</span>
                </p>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <!-- Grid container set to 12 columns -->
            <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
              
              <!-- Row 1: Snapshot Cards (Wrapped to span full width) -->
                            <!-- Row 1: Snapshot Cards -->
              <div class="col-span-12">
                 <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-5 lg:gap-6">
                    
                 </div>
              </div>

              <!-- Row 1: Price Trends (6 columns) -->
              <div class="col-span-12 lg:col-span-6 card px-4 pb-5 sm:px-5">
                <div class="my-3 flex h-8 items-center justify-between">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Market Overview
                  </h2>
                </div>
                <div class="space-y-3.5">
                  <div class="flex cursor-pointer items-center justify-between">
                    <div class="flex items-center space-x-3.5">
                      <!-- <div class="avatar">
                        <img class="rounded-full" src="../../images/avatar/avatar-20.jpg" alt="avatar">
                      </div> -->
                      <div>
                        <p class="font-medium text-slate-700 dark:text-navy-100">
                          Total Crops Tracked
                        </p>
                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                          <?= date('d M Y') ?>
                        </p>
                      </div>
                      <div>
                        
                        <!-- <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                          Dec 21, 2021 - 08:05
                        </p> -->
                      </div>
                    </div>
                    <p class="font-medium text-slate-600 dark:text-navy-100">
                      <?= count($market_data) ?>
                    </p>
                  </div>
                  <div class="flex cursor-pointer items-center justify-between">
                    <div class="flex items-center space-x-3.5">
                      <!-- <div class="avatar">
                        <img class="rounded-full" src="../../images/avatar/avatar-20.jpg" alt="avatar">
                      </div> -->
                      <div>
                        <p class="font-medium text-slate-700 dark:text-navy-100">
                          Avg Price Change (30d)
                        </p>
                        <!-- <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                          Dec 21, 2021 - 08:05
                        </p> -->
                      </div>
                      <div>
                        
                        <!-- <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                          Dec 21, 2021 - 08:05
                        </p> -->
                      </div>
                    </div>
                    <p class="font-medium text-success dark:text-navy-100">
                      +8.4%
                    </p>
                  </div>
              </div>
            </div>

              <!-- Row 2: ReQ Per Crop (3 columns) -->
              <div class="col-span-12 lg:col-span-3 card px-4 pb-5 sm:px-5">
                <div class="my-3 flex h-8 items-center justify-between">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Highest Price Today
                  </h2>
                  <div class="mask is-squircle flex size-10 items-center justify-center bg-warning/10">
                <i class="fa-solid fa-arrow-trend-up text-xl text-warning"></i>
              </div>
                </div>
                <div class="space-y-4">
                  <?php if (!empty($market_data)): $top = $market_data[0]; ?>
                  <div>
                <div class="mt-4 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= htmlspecialchars($top['crop_name']) ?>
                  </p>
                  <p class="text-xs text-success">UGX <?= number_format($top['current_price'] ?? 0, 0) ?></p>
                </div>
                <p class="text-xs+"> Category <?= $top['category_id'] ?></p>
              </div>
                </div>
                <?php else: ?>
          <p class="text-gray-500">No data yet</p>
        <?php endif; ?>
              </div>

               <!-- Row 3: ReQ Per Crop (3 columns) -->
              <div class="col-span-12 lg:col-span-3 card px-4 pb-5 sm:px-5">
                <div class="my-3 flex h-8 items-center justify-between">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Most Volatile Crop
                  </h2>
                  <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                    <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn -mr-1.5 size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                      </svg>
                    </button>
                    <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                      <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                        <ul>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Action</a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              <?php if (!empty($market_data)): $volatile = array_reduce($market_data, function($carry, $item) {
            $vol = ($item['max_price'] - $item['min_price']) / $item['avg_price'] * 100;
            return ($vol > $carry['vol']) ? ['vol' => $vol, 'crop' => $item['crop_name']] : $carry;
        }, ['vol' => 0, 'crop' => 'None']); ?>
              <div class="mt-2">
                <div class="badge h-5 bg-success/10 px-2 text-success dark:bg-success/15">
                  <?= htmlspecialchars($volatile['crop']) ?>
                </div>
                <div class="badge h-5 bg-warning/10 px-2 text-warning dark:bg-warning/15">
                  <?= round($volatile['vol'], 1) ?>%
                </div>
              </div>
              <?php else: ?>
          <p class="text-gray-500">No data yet</p>
        <?php endif; ?>
              </div>

            </div>
          </div>
        </div>
      </main>
    </div>
    
    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
  </body>
</html>