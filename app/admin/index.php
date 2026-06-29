<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';
$admin_id = $_SESSION['id'];

// --- Stats Queries using MySQLi ---
$totalFarmers = $conn->query("SELECT COUNT(*) FROM users WHERE role='farmer'")->fetch_row()[0];
$totalBuyers  = $conn->query("SELECT COUNT(*) FROM users WHERE role='buyer'")->fetch_row()[0];
$totalExtension  = $conn->query("SELECT COUNT(*) FROM users WHERE role='extension'")->fetch_row()[0];
$totalProducts= $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$totalOrders  = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];


// --- Recent Orders ---
$recentOrders = [];
$res = $conn->query("
    SELECT o.id, o.order_code, o.amount, o.status,
       u1.name AS farmer_name, u2.name AS buyer_name,
       p.name AS product_name
FROM orders o
JOIN users u1 ON o.farmer_id = u1.id
JOIN users u2 ON o.buyer_id = u2.id
JOIN order_items oi ON oi.order_id = o.id
JOIN products p ON oi.product_id = p.id
GROUP BY o.id
ORDER BY o.created_at DESC
LIMIT 5;
");
while($row = $res->fetch_assoc()){
    $recentOrders[] = $row;
}

$recentOrders = [];
$res = $conn->query("
    SELECT o.id, o.order_code, o.amount, o.status,
           u1.name AS farmer_name, u2.name AS buyer_name
    FROM orders o
    JOIN users u1 ON o.farmer_id = u1.id
    JOIN users u2 ON o.buyer_id = u2.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
while ($order = $res->fetch_assoc()) {
    // Get all products for this order
    $prodRes = $conn->query("
        SELECT p.name 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = {$order['id']}
    ");
    $products = [];
    while ($p = $prodRes->fetch_assoc()) {
        $products[] = $p['name'];
    }
    $order['product_names'] = implode(', ', $products);
    $recentOrders[] = $order;
}
$currentMonth = date('m');
$currentYear  = date('Y');

$lastMonth = date('m', strtotime('-1 month'));
$lastMonthYear = date('Y', strtotime('-1 month'));

// Orders this month
$currentMonthOrders = $conn->query("
    SELECT COUNT(*) as total 
    FROM orders 
    WHERE MONTH(created_at) = '$currentMonth'
    AND YEAR(created_at) = '$currentYear'
")->fetch_assoc()['total'];

// Orders last month
$lastMonthOrders = $conn->query("
    SELECT COUNT(*) as total 
    FROM orders 
    WHERE MONTH(created_at) = '$lastMonth'
    AND YEAR(created_at) = '$lastMonthYear'
")->fetch_assoc()['total'];

// Prevent division by zero
if ($lastMonthOrders > 0) {
    $growth = (($currentMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100;
} else {
    $growth = 100; // if no orders last month
}

$growthFormatted = number_format($growth, 1);

// --- 1. FETCH TOP DISTRICTS ---
 $topDistricts = [];
 $districtSql = "
    SELECT 
        COALESCE(NULLIF(u.location_name, ''), u.location) AS district,
        SUM(p.quantity) AS total_volume,
        COUNT(p.id) AS product_count
    FROM products p
    JOIN users u ON p.farmer_id = u.id
    WHERE p.status IN ('active', 'approved') 
      AND YEAR(p.created_at) = YEAR(CURRENT_DATE)
    GROUP BY district
    HAVING total_volume > 0
    ORDER BY total_volume DESC
    LIMIT 4
";
 $distResult = $conn->query($districtSql);
while ($row = $distResult->fetch_assoc()) {
    $topDistricts[] = $row;
}

// --- 2. FETCH SUPPLY VS BUYING MONTHLY ---
 $supplyData = [];
 $buyingData = [];

// Supply Query
 $sSql = "SELECT MONTH(p.created_at) AS m, SUM(p.quantity) AS vol 
         FROM products p WHERE p.status IN ('active', 'approved', 'pending') AND YEAR(p.created_at) = YEAR(CURRENT_DATE) GROUP BY m";
 $sRes = $conn->query($sSql);
while ($r = $sRes->fetch_assoc()) $supplyData[$r['m']] = (int)$r['vol'];

// Buying Query
 $bSql = "SELECT MONTH(o.created_at) AS m, SUM(oi.quantity) AS vol 
         FROM orders o JOIN order_items oi ON o.id = oi.order_id 
         WHERE o.status IN ('completed', 'confirmed', 'processing') AND YEAR(o.created_at) = YEAR(CURRENT_DATE) GROUP BY m";
 $bRes = $conn->query($bSql);
while ($r = $bRes->fetch_assoc()) $buyingData[$r['m']] = (int)$r['vol'];

// Build 12-month arrays
 $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
 $chartLabels = $chartSupply = $chartBuying = [];
for ($i = 1; $i <= 12; $i++) {
    $chartLabels[] = $months[$i - 1];
    $chartSupply[] = $supplyData[$i] ?? 0;
    $chartBuying[] = $buyingData[$i] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Dashboard</title>
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
      /**
       * THIS SCRIPT REQUIRED FOR PREVENT FLICKERING IN SOME BROWSERS
       */
      localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>
  </head>

  <body x-data="" class="is-header-blur" x-bind="$store.global.documentBody">
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
        <?php include 'dashboardsider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 px-[var(--margin-x)] transition-all duration-[.25s] sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div x-data="{activeTab:'thisYear'}"class="col-span-12 lg:col-span-8">
            <div class="flex items-center justify-between space-x-2">
              <h2 class="text-base font-medium tracking-wide text-slate-800 line-clamp-1 dark:text-navy-100">
                Supply vs. Buying
              </h2>
              <div  class="is-scrollbar-hidden overflow-x-auto rounded-lg bg-slate-200 text-slate-600 dark:bg-navy-800 dark:text-navy-200">
                <div class="tabs-list flex p-1">
                  <!-- <button @click="activeTab = 'tabRecent'" :class="activeTab === 'tabRecent' ? 'bg-white shadow dark:bg-navy-500 dark:text-navy-100' : 'hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'" class="btn shrink-0 px-3 py-1 text-xs+ font-medium">
                    This year
                  </button> -->
                  <button @click="activeTab = 'thisYear'"
                    :class="activeTab === 'thisYear' ? 'bg-white shadow dark:bg-navy-500 dark:text-navy-100' : 'hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                    class="btn shrink-0 px-3 py-1 text-xs+ font-medium transition-colors duration-200">
                    This year
                </button>
                  <button @click="activeTab = 'lastYear'" 
                  :class="activeTab === 'lastYear' ? 'bg-white shadow dark:bg-navy-500 dark:text-navy-100' : 'hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'" class="btn shrink-0 px-3 py-1 text-xs+ font-medium">
                    Last year
                  </button>
                </div>
              </div>
            </div>
              <?php
              $totalRevenue = $conn->query("
                  SELECT SUM(amount) as revenue 
                  FROM orders 
                  WHERE status='completed'
              ")->fetch_assoc()['revenue'];

              $totalRevenue = $totalRevenue ?? 0;
              ?>

            <div class="flex flex-col sm:flex-row sm:space-x-7">
              <div class="mt-4 flex shrink-0 flex-col items-center sm:items-start">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                </svg>
                <div class="mt-4">
                  <div class="flex items-center space-x-1">
                    <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                      UGX <?= $totalRevenue ?>
                    </p>
                    <button class="btn size-6 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                      </svg>
                    </button>
                  </div>
                  <p class="text-xs text-slate-400 dark:text-navy-300">
                    total revenue
                  </p>
                </div>
                <div class="mt-3 flex items-center space-x-2">
                  <div class="ax-transparent-gridline w-28">
                    <div x-init="$nextTick(() => { $el._x_chart = new ApexCharts($el,pages.charts.analyticsSalesThisMonth); $el._x_chart.render() });"></div>
                  </div>
                  <div class="flex items-center space-x-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg>
                    <p class="text-sm+ text-slate-800 dark:text-navy-100">
                      3.2%
                    </p>
                  </div>
                </div>
                <button class="btn mt-8 space-x-2 rounded-full border border-slate-300 px-3 text-xs+ font-medium text-slate-700 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-100 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"></path>
                  </svg>
                  <span> Download Report</span>
                </button>
              </div>

              <div x-show="activeTab === 'thisYear'" div
                      x-show="activeTab === 'tabHome'"
                      x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                      class="ax-transparent-gridline grid w-full grid-cols-1">
                <!-- ApexCharts Container -->
                 <div x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                      id="supplyBuyingChart"></div>
              </div>
              <div x-show="activeTab === 'lastYear'"
              div
                      x-show="activeTab === 'tabHome'"
                      x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                      class="ax-transparent-gridline grid w-full grid-cols-1">
                <!-- ApexCharts Container -->
                 <div id="supplyBuyingChart"></div>
              </div>
            </div>
          </div>
          <div class="col-span-12 lg:col-span-4">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-2">
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="mt-1 hidden sm:block">Farmers</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= $totalFarmers ?>
                  </p>
                  <!-- <p class="text-xs text-success">12%</p> -->
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-warning/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-secondary" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-users translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-20 text-secondary translate-x-1/4 translate-y-1/4 text-5xl opacity-15" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="mt-1 hidden sm:block">Buyers</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= $totalBuyers ?>
                  </p>
                  <!-- <p class="text-xs text-success">12%</p> -->
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-warning/10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6 text-warning">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-users translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-20 text-warning translate-x-1/4 translate-y-1/4 text-5xl opacity-15" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                  </svg>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="mt-1 hidden sm:block">Extension</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= $totalExtension ?>
                  </p>
                  <!-- <p class="text-xs text-success">12%</p> -->
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-primary/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-primary" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                  </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-users translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-20 text-primary translate-x-1/4 translate-y-1/4 text-5xl opacity-15" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                  </svg>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="mt-1 hidden sm:block">Products</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= $totalProducts ?>
                  </p>
                  <!-- <p class="text-xs text-success">12%</p> -->
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-success/10">
                <!-- <i class="fa-solid fa-users text-xl text-warning"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M20.87,8.009a9.67,9.67,0,0,0-5.236,2.306A7.676,7.676,0,0,0,16,8a9.463,9.463,0,0,0-3.375-6.781,1,1,0,0,0-1.25,0A9.463,9.463,0,0,0,8,8a7.681,7.681,0,0,0,.366,2.315A9.673,9.673,0,0,0,3.13,8.009,1,1,0,0,0,2.011,9.148C2.7,13.871,7.6,18,11,18v4a1,1,0,0,0,2,0V18c3.419,0,8.218-4.029,8.989-8.852A1,1,0,0,0,20.87,8.009ZM12,3.391A7.075,7.075,0,0,1,14,8a7.08,7.08,0,0,1-2,4.61A7.08,7.08,0,0,1,10,8,7.075,7.075,0,0,1,12,3.391ZM4.408,10.33a8.215,8.215,0,0,1,5.183,5.248A8.764,8.764,0,0,1,4.408,10.33Zm10,5.248a8.218,8.218,0,0,1,5.183-5.248A8.767,8.767,0,0,1,14.409,15.578Z"></path>

                  </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-users translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-20 text-success translate-x-1/4 translate-y-1/4 text-5xl opacity-15" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M20.87,8.009a9.67,9.67,0,0,0-5.236,2.306A7.676,7.676,0,0,0,16,8a9.463,9.463,0,0,0-3.375-6.781,1,1,0,0,0-1.25,0A9.463,9.463,0,0,0,8,8a7.681,7.681,0,0,0,.366,2.315A9.673,9.673,0,0,0,3.13,8.009,1,1,0,0,0,2.011,9.148C2.7,13.871,7.6,18,11,18v4a1,1,0,0,0,2,0V18c3.419,0,8.218-4.029,8.989-8.852A1,1,0,0,0,20.87,8.009ZM12,3.391A7.075,7.075,0,0,1,14,8a7.08,7.08,0,0,1-2,4.61A7.08,7.08,0,0,1,10,8,7.075,7.075,0,0,1,12,3.391ZM4.408,10.33a8.215,8.215,0,0,1,5.183,5.248A8.764,8.764,0,0,1,4.408,10.33Zm10,5.248a8.218,8.218,0,0,1,5.183-5.248A8.767,8.767,0,0,1,14.409,15.578Z"></path>
                  </svg>
              </div>
            </div>
            </div>
          </div>
          <div class="card col-span-12 lg:col-span-6">
            <div class="flex items-center justify-between py-3 px-4">
              <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                Latest forum posts
              </h2>
              <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
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
              </div>
            </div>
            <div class="grid grid-cols-1 gap-y-4 pb-3 sm:grid-cols-3">
              <?php
              $recentTopics = $conn->query("
                  SELECT ft.id, ft.title, ft.content, ft.views, ft.created_at, u.name AS author
                  FROM forum_topics ft
                  JOIN users u ON ft.user_id = u.id
                  ORDER BY ft.created_at DESC
                  LIMIT 3
              ");
              ?>
              <?php 
              $colors = ['#4f46e5','#22c55e','#facc15','#0ea5e9','#ef4444'];
              $i = 0;

              while($topic = $recentTopics->fetch_assoc()): 
              $borderColor = $colors[$i % count($colors)];
              $i++;
              ?>
              <div class="flex flex-col justify-between border-4 border-transparent px-4" style="border-left:4px solid <?php echo $borderColor; ?>;">
                <div>
                  <p class="text-base font-medium text-slate-600 dark:text-navy-100">
                    <?= htmlspecialchars(substr($topic['title'],0 ,40)) ?>
                  </p>
                  <p class="text-xs text-slate-400 dark:text-navy-300">
                    <?= htmlspecialchars(substr($topic['content'] ?? '', 0, 50)) . (strlen($topic['content'] ?? '') > 50 ? '...' : '') ?>
                  </p>
                  <!-- <div class="badge mt-2 bg-info/10 text-info dark:bg-info/15">
                    UI/UX Design
                  </div> -->
                </div>
                <div>
                  <div class="mt-8">
                    <p class="font-inter">
                      <!-- <span class="text-2xl font-medium text-slate-600 dark:text-navy-100">%55.</span> -->
                      <div>
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                  <span><?= $topic['views'] ?></span>
                </div>
                    </p>
                    <p class="mt-1 text-xs"><?= date("d M Y", strtotime($topic['created_at'])) ?></p>
                  </div>
                  <!-- <div class="mt-8 flex items-center justify-between space-x-2">
                    <div class="flex -space-x-3">
                      <div class="avatar size-8 hover:z-10">
                        <img class="rounded-full ring ring-white dark:ring-navy-700" src="../images/avatar/avatar-16.jpg" alt="avatar">
                      </div>
                      <div class="avatar size-8 hover:z-10">
                        <div class="is-initial rounded-full bg-info text-xs+ uppercase text-white ring ring-white dark:ring-navy-700">
                          jd
                        </div>
                      </div>
                      <div class="avatar size-8 hover:z-10">
                        <img class="rounded-full ring ring-white dark:ring-navy-700" src="../images/avatar/avatar-20.jpg" alt="avatar">
                      </div>
                    </div>
                    <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      </svg>
                    </button>
                  </div> -->
                </div>
              </div>
               <?php endwhile; ?>
            </div>
          </div>
          <div class="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:col-span-7 lg:gap-6 xl:col-span-6">
            <?php
            $recentOrders = $conn->query("
                SELECT o.order_code, o.amount, o.status, u.name as buyer
                FROM orders o
                JOIN users u ON o.buyer_id = u.id
                ORDER BY o.id DESC
                LIMIT 5
            ");
            ?>

            <div class="card px-4 pb-5 sm:px-5">
              <div class="flex items-center justify-between py-3">
                <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Recent Orders
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
                </div>
              </div>
              <div>
                <p>
                  <span class="text-2xl text-slate-700 dark:text-navy-100"><?= number_format($currentMonthOrders) ?></span>
                  <span class="text-xs <?= $growth >= 0 ? 'text-success' : 'text-error' ?>">
                    <?= $growth >= 0 ? '+' : '' ?><?= $growthFormatted ?>%
                  </span>
                </p>
                <p class="text-xs+">Orders in this month</p>
              </div>
              <div class="mt-5 space-y-4">
                <?php while($row = $recentOrders->fetch_assoc()): ?>
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <!-- <img class="size-6" src="../images/logos/instagram-round.svg" alt="flag"> -->
                    <p 
                      x-tooltip.secondary="'<?= htmlspecialchars($row['order_code']) ?>'" 
                      class="cursor-pointer font-medium text-slate-700 dark:text-navy-100"
                    >
                      <?= htmlspecialchars(substr($row['order_code'], 0, 6)) ?>
                    </p>

                  </div>
                  <div class="flex items-center space-x-2">
                    <p class="text-sm+ text-slate-800 dark:text-navy-100">
                      <?= $row['amount'] ?>
                    </p>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg>
                  </div>
                </div>
                <?php endwhile; ?>
              </div>
            </div>

            <div class="card px-4 pb-5 sm:px-5">
              <div class="my-3 flex h-8 items-center justify-between">
                <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Top Districts
                </h2>
                <a href="#" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">
                  View All
                </a>
              </div>
              <div>
                <p>
                  <span class="text-2xl text-slate-700 dark:text-navy-100">64</span>
                </p>
                <p class="text-xs+">Total Districts</p>
              </div>
              <div class="mt-5 space-y-4">
                <?php if (empty($topDistricts)): ?>
                <p class="text-sm text-slate-500 dark:text-navy-300">No production data available yet.</p>
            <?php else: ?>
              <?php foreach ($topDistricts as $index => $dist): ?>
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <img class="size-6" src="../images/awards/award-1.svg" alt="flag">
                    <p x-tooltip.primary="'<?php echo $dist['product_count']; ?> Products'"><?php echo htmlspecialchars($dist['district']); ?></p>
                  </div>
                  <div class="flex items-center space-x-2">
                    <p class="text-sm+ text-slate-800 dark:text-navy-100">
                      <?php echo $dist['product_count']; ?>
                    </p>
                    <!-- <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg> -->
                  </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
              </div>
            </div>
          </div>

          
        </div>
      </main>
    </div>
    <!-- 
        This is a place for Alpine.js Teleport feature 
        @see https://alpinejs.dev/directives/teleport
      -->
    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
    <script>
document.addEventListener("DOMContentLoaded", function() {
    
    // Check dark mode for ApexCharts theme
    const isDark = document.documentElement.classList.contains("dark");

    var options = {
        series: [{
            name: 'Supply (Listed)',
            data: <?php echo json_encode($chartSupply); ?>
        }, {
            name: 'Buying (Ordered)',
            data: <?php echo json_encode($chartBuying); ?>
        }],
        chart: {
            type: 'bar', // Vertical Bar Graph
            height: 240,
            background: 'transparent',
            fontFamily: 'Inter, Poppins, sans-serif',
            toolbar: { show: false }, // Hides the default download/zoom menu
            zoom: { enabled: false }
        },
        plotOptions: {
            bar: {
                horizontal: false, // true would make it horizontal
                columnWidth: '55%',
                borderRadius: 4,
                borderRadiusApplication: 'end' // Rounds only the top corners
            }
        },
        dataLabels: {
            enabled: false // Hides numbers on top of bars (keeps it clean)
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent'] // Creates a slight border effect
        },
        colors: ['#10b981', '#3b82f6'], // Emerald Green and Blue to match your Tailwind setup
        xaxis: {
            categories: <?php echo json_encode($chartLabels); ?>,
            labels: {
                style: { colors: isDark ? '#94a3b8' : '#64748b' } // slate-400 / slate-500
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            title: {
                text: 'Volume (KG)',
                style: { color: isDark ? '#94a3b8' : '#64748b', fontWeight: 500 }
            },
            labels: {
                style: { colors: isDark ? '#94a3b8' : '#64748b' },
                formatter: function (val) {
                    return val >= 1000 ? (val / 1000) + 'k' : val; // Formats 15000 to 15k
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            fontWeight: 500,
            labels: { colors: isDark ? '#cbd5e1' : '#334155' } // slate-300 / slate-700
        },
        grid: {
            borderColor: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
            strokeDashArray: 4 // Dotted grid lines look cleaner
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " kg";
                }
            }
        },
        theme: {
            mode: isDark ? 'dark' : 'light'
        }
    };

    // Render the chart into the div
    var chart = new ApexCharts(document.querySelector("#supplyBuyingChart"), options);
    chart.render();
    
    // OPTIONAL BUT RECOMMENDED: If you have an Alpine.js dark mode toggle, 
    // this tells ApexCharts to update its colors instantly when toggled without reloading
    window.addEventListener('darkmode:toggled', function() {
        chart.updateOptions({
            theme: { mode: document.documentElement.classList.contains("dark") ? 'dark' : 'light' },
            xaxis: { labels: { style: { colors: document.documentElement.classList.contains("dark") ? '#94a3b8' : '#64748b' }}},
            yaxis: { title: { style: { color: document.documentElement.classList.contains("dark") ? '#94a3b8' : '#64748b' }}, labels: { style: { colors: document.documentElement.classList.contains("dark") ? '#94a3b8' : '#64748b' }}},
            legend: { labels: { colors: document.documentElement.classList.contains("dark") ? '#cbd5e1' : '#334155' }},
            grid: { borderColor: document.documentElement.classList.contains("dark") ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        });
    });
});
</script>
    <div x-data 
     x-init="
        const params = new URLSearchParams(window.location.search);
        if(params.get('status') === 'success') {
            // Fire the notification
            $notification({text:'Logged In Successfully', variant:'success', position:'right-top'});
            
            // Clean the URL so it doesn't trigger again on refresh
            const url = new URL(window.location);
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname);
        }
     ">
</div>
  </body>
</html>
