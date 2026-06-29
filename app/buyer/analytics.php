<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Market Snapshot - ADDED c.image to query
 $market_data = $conn->query("
    SELECT 
        COALESCE(mp.crop, c.name, CONCAT('Category #', mp.category_id)) AS crop_name,
        c.image, -- Fetching the image path
        ROUND(AVG(mp.price), 0) AS avg_price,
        ROUND(MIN(mp.price), 0) AS min_price,
        ROUND(MAX(mp.price), 0) AS max_price,
        COUNT(mp.id) AS data_points,
        MAX(mp.date) AS latest_date
    FROM market_prices mp
    LEFT JOIN categories c ON mp.category_id = c.id
    GROUP BY mp.category_id, mp.crop
    ORDER BY latest_date DESC, avg_price DESC
    LIMIT 4
")->fetch_all(MYSQLI_ASSOC);

// 2. Trend Data (3 Months)
 $trend_raw = $conn->query("
    SELECT 
        mp.date,
        COALESCE(mp.crop, c.name, CONCAT('Cat-', mp.category_id)) AS crop_name,
        ROUND(AVG(mp.price), 0) AS avg_price
    FROM market_prices mp
    LEFT JOIN categories c ON mp.category_id = c.id
    WHERE mp.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
    GROUP BY mp.category_id, mp.crop, mp.date
    ORDER BY mp.date ASC
")->fetch_all(MYSQLI_ASSOC);

// 3. Prepare Chart Data
 $chart_series_map = [];
foreach ($trend_raw as $row) {
    $crop_name = $row['crop_name'];
    $timestamp = strtotime($row['date']) * 1000; 
    $price = (int)$row['avg_price'];
    if (!isset($chart_series_map[$crop_name])) {
        $chart_series_map[$crop_name] = [];
    }
    $chart_series_map[$crop_name][] = [$timestamp, $price];
}

 $chart_series = [];
foreach ($chart_series_map as $name => $data) {
    $chart_series[] = ['name' => $name, 'data' => $data];
}

 $chart_json = htmlspecialchars(json_encode($chart_series), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>FAIMS - Insights</title>
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

  <body x-data="" x-bind="$store.global.documentBody" class="is-header-blur is-sidebar-open">
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
        <?php include 'analyticssider.php';?>
      </div>

      <?php include 'toprightsidenav.php';?>

      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="col-span-12">
            
            <!-- Grid container set to 12 columns -->
            <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
              
              <!-- Row 1: Snapshot Cards (Wrapped to span full width) -->
                            <!-- Row 1: Snapshot Cards -->
              <div class="col-span-12">
                 <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-5 lg:gap-6">
                    <?php foreach ($market_data as $item): ?>
                    <div class="card p-4 sm:p-5">
                      <div class="flex size-12 items-center justify-center rounded-xl bg-primary shadow-xl shadow-primary/50 dark:bg-accent dark:shadow-accent/50">
                        
                        <?php if (!empty($item['image'])): ?>
                            <!-- Display Image if exists -->
                            <!-- Assuming path is like images/categories/tuber.svg, we prepend ../ to go up one level from buyer/ -->
                            <img src="../<?= htmlspecialchars($item['image']) ?>" alt="icon" class="size-7 object-contain">
                        <?php else: ?>
                            <!-- Fallback Icon -->
                            <i class="fa fa-dollar-sign text-xl text-white"></i>
                        <?php endif; ?>

                      </div>
                      <p class="mt-16"><?= htmlspecialchars($item['crop_name']) ?></p>
                      <p class="mt-2 font-medium text-slate-700 dark:text-navy-100">
                        <span class="text-2xl">UGX <?= number_format($item['avg_price']) ?></span><span class="text-base">.00</span>
                      </p>
                      <p class="mt-1 flex items-center text-xs text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" ></path>
                        </svg>
                        <span> Updated <?= date('d M', strtotime($item['latest_date'] ?? date('Y-m-d'))) ?></span>
                      </p>
                    </div>
                    <?php endforeach; ?>
                 </div>
              </div>

              <!-- Row 2: Price Trends (8 columns) -->
              <div class="col-span-12 lg:col-span-8 card px-4 pb-5 sm:px-5">
                <div class="my-3 flex h-8 items-center justify-between">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Price Trends (Last 3 Months)
                  </h2>
                </div>
                <div class="space-y-4">
                  <?php if (empty($chart_series)): ?>
                    <div class="h-96 flex items-center justify-center text-gray-500">
                      Not enough historical data to show trends yet.
                    </div>
                  <?php else: ?>
                    <div 
                      class="h-96 cursor-crosshair" 
                      style="min-height: 380px;" 
                      x-data="{ seriesData: <?= $chart_json ?> }"
                      x-init="
                        $nextTick(() => {
                          const options = {
                            series: seriesData,
                            chart: {
                              type: 'line',
                              height: 380,
                              toolbar: { show: false },
                              zoom: { enabled: false }
                            },
                            dataLabels: { enabled: false },
                            stroke: {
                              curve: 'smooth',
                              width: 3
                            },
                            colors: ['#b45309', '#854d0e', '#a36d2c', '#78350f', '#f59e0b', '#fbbf24', '#fef3c7'],
                            
                            // CONFIGURATION FOR CROSSHAIRS
                            tooltip: {
                              enabled: true,
                              shared: true,
                              intersect: false,
                              theme: 'dark' // or 'light'
                            },
                            
                            xaxis: {
                              type: 'datetime',
                              labels: { format: 'dd MMM' },
                              crosshairs: {
                                show: true,
                                width: 1,
                                position: 'back',
                                stroke: {
                                  color: '#94a3b8',
                                  width: 1,
                                  dashArray: 0
                                }
                              }
                            },
                            
                            yaxis: {
                              title: { text: 'UGX per kg' },
                              labels: {
                                formatter: (value) => value.toLocaleString()
                              },
                              crosshairs: {
                                show: true,
                                width: 1,
                                position: 'back',
                                stroke: {
                                  color: '#94a3b8',
                                  width: 1,
                                  dashArray: 0
                                }
                              }
                            }
                            // END CROSSHAIR CONFIG

                          };
                          $el._x_chart = new ApexCharts($el, options);
                          $el._x_chart.render();
                        });
                      ">
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Row 2: ReQ Per Crop (4 columns) -->
              <div class="col-span-12 lg:col-span-4 card px-4 pb-5 sm:px-5">
                <div class="my-3 flex h-8 items-center justify-between">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    ReQ Per Crop
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
                <div class="space-y-4">
                  <div id="priceTrendChart" class="flex cursor-pointer items-center justify-between">
                     <!-- Content for ReQ Per Crop -->
                     <p class="text-sm text-slate-400">Analytics data will appear here.</p>
                  </div>
                </div>
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