<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'farmer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$farmer_id = $_SESSION['id'];

// Total products
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products WHERE farmer_id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

// Total earnings (optional)
$stmt = $conn->prepare("SELECT SUM(price*quantity) AS money FROM products WHERE farmer_id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$earn = $stmt->get_result()->fetch_assoc();

// Earnings this month
$stmt = $conn->prepare("
SELECT SUM(amount) AS total
FROM orders
WHERE farmer_id = ?
AND status='completed'
AND MONTH(created_at)=MONTH(CURRENT_DATE())
AND YEAR(created_at)=YEAR(CURRENT_DATE())
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$earningsResult = $stmt->get_result();
$row = $earningsResult->fetch_assoc();

$monthlyEarnings = $row['total'] ?? 0;

//Customers
$stmt = $conn->prepare("
SELECT COUNT(DISTINCT buyer_id) AS customers
FROM orders
WHERE farmer_id = ?
AND status='completed'
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$customerResult = $stmt->get_result();
$row = $customerResult->fetch_assoc();
$totalCustomers = $row['customers'] ?? 0;

//Pending
$stmt = $conn->prepare("
SELECT COUNT(*) AS pending
FROM orders
WHERE farmer_id = ?
AND status='pending'
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$pendingResult = $stmt->get_result();
$row = $pendingResult->fetch_assoc();
$pendingOrders = $row['pending'] ?? 0;

//Completed
$stmt = $conn->prepare("
SELECT COUNT(*) AS completed
FROM orders
WHERE farmer_id = ?
AND status='completed'
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$completedResult = $stmt->get_result();
$row = $completedResult->fetch_assoc();
$completedOrders = $row['completed'] ?? 0;
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
        <?php include 'indexsider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full pb-8">
        <!-- Outer 12-Column Grid -->
  <div class="mt-4 grid grid-cols-12 gap-4 px-[var(--margin-x)] transition-all duration-[.25s] sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
        <!-- ========================================== -->
        <!-- ROW 1: FARM OVERVIEW (Spans 8 columns)    -->
        <!-- ========================================== -->
    <div class="col-span-12 lg:col-span-8" x-data="{ activeTab: 'today' }">

    <!-- HEADER & TABS -->
      <div class="flex items-center justify-between space-x-2">
        <h2 class="text-base font-medium tracking-wide text-slate-800 line-clamp-1 dark:text-navy-100">
            Farm Overview
        </h2>
        
        <!-- Tab Buttons -->
        <div class="is-scrollbar-hidden overflow-x-auto rounded-lg bg-slate-200 text-slate-600 dark:bg-navy-800 dark:text-navy-200">
            <div class="tabs-list flex p-1">
                <button @click="activeTab = 'today'"
                    :class="activeTab === 'today' ? 'bg-white shadow dark:bg-navy-500 dark:text-navy-100' : 'hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                    class="btn shrink-0 px-3 py-1 text-xs+ font-medium transition-colors duration-200">
                    Today
                </button>
                <button @click="activeTab = 'forecast'"
                    :class="activeTab === 'forecast' ? 'bg-white shadow dark:bg-navy-500 dark:text-navy-100' : 'hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                    class="btn shrink-0 px-3 py-1 text-xs+ font-medium transition-colors duration-200">
                    3 Day Forecast
                </button>
            </div>
        </div>
      </div>
            
    <!-- INTERNAL LAYOUT GRID: 4 Columns Total -->
      <!-- Flex-1 replaced with grid to ensure strict column control -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-4">
         <div class="lg:col-span-1 flex flex-col items-center sm:items-start text-center sm:text-left">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
            </svg>
            <div class="mt-4 flex shrink-0 flex-col items-center sm:items-start w-full sm:max-w-xs">
                <div class="flex items-center space-x-1">
                    <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    <?php echo number_format($monthlyEarnings); ?>
                    </p>
                    <button class="btn size-6 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-slate-400 dark:text-navy-300">UGX this month</p>
            </div>
           <?php
$stmt = $conn->prepare("
    SELECT SUM(amount) as total, DATE(created_at) as sales_date
    FROM orders
    WHERE farmer_id = ? AND status='completed'
    GROUP BY DATE(created_at)
    ORDER BY sales_date DESC 
    LIMIT 2
"); // We only need the last 2 days for a single growth calculation
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();

$earnings = [];
while($row = $result->fetch_assoc()){
    $earnings[] = (float)$row['total'];
}

// Default values
$growth = 0;
$growth_display = "0%";
$growth_color = 'text-gray-500'; // Neutral if no data

if (count($earnings) >= 2) {
    // Array is DESC, so [0] is today, [1] is yesterday
    $today = $earnings[0];
    $yesterday = $earnings[1];

    if ($yesterday > 0) {
        $growth = (($today - $yesterday) / $yesterday) * 100;
        
        // Handle the "Abnormal" high percentages
        if ($growth > 100) {
            $growth_display = "99";
        } elseif ($growth < -100) {
            $growth_display = "-99";
        } else {
            $growth_display = number_format($growth, 1) . "%";
        }
    } else {
        // If yesterday was 0 and today is > 0, it's 100% new growth
        $growth = 100;
        $growth_display = "New";
    }
}

// Determine color based on growth value
if ($growth > 0) {
    $growth_color = 'text-success';
    $growth_display = '+' . $growth_display; // Add plus sign for positive
} elseif ($growth < 0) {
    $growth_color = 'text-error';
}
?>         <div class="mt-3 flex items-center space-x-2">
            <!-- Chart container -->
            <div id="farmerEarningsSpark" class="w-20 h-12"></div>

                <!-- Growth indicator -->
                <div class="flex items-center space-x-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 <?php echo $growth >= 0 ? 'text-success' : 'text-error'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <?php if($growth >=0): ?>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                <?php else: ?>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V1"></path>
                <?php endif; ?>
            </svg>
                    <p class="text-sm+ <?php echo $growth_color; ?>">
                <?php echo $growth_display; ?>%
                    </p>
                </div>
            </div>
            <button class="btn mt-8 space-x-2 rounded-full border border-slate-300 px-3 text-xs+ font-medium text-slate-700 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-100 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"></path>
                </svg>
                <span>Report</span>
            </button>
        </div>

              <!-- RIGHT COLUMN: WEATHER SECTION (Span 3 of 4) -->
          <div class="lg:col-span-3 w-full min-w-0 relative">

            <!-- VIEW 1: TODAY (Complex Grid) -->
            <div x-show="activeTab === 'today'" div
                      x-show="activeTab === 'tabHome'"
                      x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                    >
                <div class="grid grid-cols gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                    <!-- Big Weather Card -->
                    <div class="card bg-gradient-to-r from-blue-500 to-indigo-600 px-10 pb-8 justify-between relative">
                        <div class="flex items-center space-x-3">
                            <div>
                                <img class="size-10 rounded-lg object-cover object-center" id="weather-icon" src="" alt="--">
                            </div>             
                            <div>
                                <p class="font-medium leading-snug text-white" id="time-display">-:- ---</p>
                            </div>
                        </div>
                        <div>
                            <p class="mt-4 font-inter text-2xl font-semibold text-white">
                                <span class="temp-value" id="temperature">--</span><span>°</span><span class="temp-unit">C</span>
                            </p>
                            <p class="text-xs text-white/80" id="weather-description">-- --</p>
                            <p class="text-xs text-white/80" id="date-display">--</p>
                            
                            <div class="badge mt-2 inline-flex items-center space-x-1 rounded-full bg-black/20 text-indigo-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4.5 text-white">
                                    <path d="M5 14.2864C3.14864 15.1031 2 16.2412 2 17.5C2 19.9853 6.47715 22 12 22C17.5228 22 22 19.9853 22 17.5C22 16.2412 20.8514 15.1031 19 14.2864M18 8C18 12.0637 13.5 14 12 17C10.5 14 6 12.0637 6 8C6 4.68629 8.68629 2 12 2C15.3137 2 18 4.68629 18 8ZM13 8C13 8.55228 12.5523 9 12 9C11.4477 9 11 8.55228 11 8C11 7.44772 11.4477 7 12 7C12.5523 7 13 7.44772 13 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                <span id="location-name" class="text-xs">Loading...</span>
                            </div>
                            
                            <div class="mt-4 flex items-center justify-between">
                                <div class="flex space-x-2">
                                    <button class="btn bg-success/20 hover:bg-success/30 text-white size-7 rounded-full backdrop-blur-sm" x-tooltip.success="'°C'"><span class="text-xs font-bold">C</span></button>
                                    <button class="btn bg-error/20 hover:bg-error/30 text-white size-7 rounded-full backdrop-blur-sm" x-tooltip.error="'°F'"><span class="text-xs font-bold">F</span></button>
                                </div>
                            </div>
                        </div>
                        <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg opacity-30 pointer-events-none">
                            <img class="w-24 translate-x-1/4 translate-y-1/4" src="../images/illustrations/globe2.svg" alt="image">
                        </div>
                    </div>

                    <!-- 4 Small Stats Grid -->
                    <div class="grid grid-cols- gap-4 sm:col-span-2 sm:grid-cols-2 sm:gap-5 lg:col-span-3 lg:grid-cols-2 lg:gap-6">
                        <!-- Wind -->
                        <div class="card justify-center p-2 text-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100" id="wind-speed">-- km/h</p>
                                    <p class="text-xs+ line-clamp-1">Wind Speed</p>
                                </div>
                                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                                        <path d="M16.7639 6.5C17.3132 5.88625 18.1115 5.5 19 5.5C20.6569 5.5 22 6.84315 22 8.5C22 10.1569 20.6569 11.5 19 11.5H13M6.7639 4C7.31322 3.38625 8.1115 3 9 3C10.6569 3 12 4.34315 12 6C12 7.65685 10.6569 9 9 9H2M10.7639 20C11.3132 20.6137 12.1115 21 13 21C14.6569 21 16 19.6569 16 18C16 16.3431 14.6569 15 13 15H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <!-- Feels Like -->
                        <div class="card justify-center p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100" id="feels-like">--°C</p>
                                    <p class="text-xs+ line-clamp-1">Feels like</p>
                                </div>
                                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                                        <path d="M21 3L15 3M21 7L15 7M21 11L15 11M5.5 13.7578V4.5C5.5 3.11929 6.61929 2 8 2C9.38071 2 10.5 3.11929 10.5 4.5V13.7578C11.706 14.565 12.5 15.9398 12.5 17.5C12.5 19.9853 10.4853 22 8 22C5.51472 22 3.5 19.9853 3.5 17.5C3.5 15.9398 4.29401 14.565 5.5 13.7578ZM9 17.5C9 18.0523 8.55228 18.5 8 18.5C7.44772 18.5 7 18.0523 7 17.5C7 16.9477 7.44772 16.5 8 16.5C8.55228 16.5 9 16.9477 9 17.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <!-- Humidity -->
                        <div class="card justify-center p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100" id="humidity">--%</p>
                                    <p class="text-xs+ line-clamp-1">Humidity</p>
                                </div>
                                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                                        <path d="M12.56 6.08C13.2478 4.98112 13.7353 3.76904 14 2.5C14.5 5 16 7.4 18 9C20 10.6 21 12.5 21 14.5C21.0057 15.8823 20.6009 17.2352 19.8368 18.3871C19.0727 19.539 17.9838 20.4382 16.7081 20.9705C15.4324 21.5028 14.0274 21.6444 12.6712 21.3773C11.3149 21.1101 10.0685 20.4463 9.09 19.47M7 15.78C9.2 15.78 11 13.95 11 11.73C11 10.57 10.43 9.47 9.29 8.54C8.15 7.61 7.29 6.23 7 4.78C6.71 6.23 5.86 7.62 4.71 8.54C3.56 9.46 3 10.58 3 11.73C3 13.95 4.8 15.78 7 15.78Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <!-- UV -->
                        <div class="card justify-center p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-base font-semibold text-slate-700 dark:text-navy-100" id="uv-index">--</p>
                                    <p class="text-xs+ line-clamp-1">UV index</p>
                                </div>
                                <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-warning">
                                    <svg class="size-5 text-white" viewbox="0 0 24 24" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Weather Overlay Loader -->
            <div id="weather-loading"
                 class="absolute inset-0 flex items-center justify-center 
                        bg-white/40 dark:bg-navy-900/40 
                        backdrop-blur-sm 
                        z-50 transition-opacity duration-300">

            <div
                  class="spinner size-6 animate-spin rounded-full border-4 border-primary border-r-transparent dark:border-accent dark:border-r-transparent"
                ></div>

            </div>
            </div>

            <!-- VIEW 2: 3 DAY FORECAST (Simple Grid) -->
            <div x-show="activeTab === 'forecast'" class="w-full mt-4" div
                      x-show="activeTab === 'tabHome'"
                      x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                    >
                <!-- This grid ensures 3 cards fit nicely side-by-side on desktop, or stack on mobile -->
                <div id="forecast-cards" class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                    
                    <!-- Forecast Card 1 -->
                    <div class="card flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-navy-800 border border-slate-200 dark:border-navy-700 hover:border-blue-400 dark:hover:border-blue-400 transition-colors">
                        <p class="text-sm font-semibold text-slate-500 dark:text-navy-200">Tomorrow</p>
                        <img src="https://openweathermap.org/img/wn/10d@2x.png" class="w-16 h-16 my-2" alt="icon">
                        <p class="text-xl font-bold text-slate-800 dark:text-navy-100">24° <span class="text-sm font-normal text-slate-400">/ 18°</span></p>
                        <p class="text-xs text-slate-500 mt-1">Light Rain</p>
                    </div>

                    <!-- Forecast Card 2 -->
                    <div class="card flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-navy-800 border border-slate-200 dark:border-navy-700 hover:border-blue-400 dark:hover:border-blue-400 transition-colors">
                        <p class="text-sm font-semibold text-slate-500 dark:text-navy-200">Wednesday</p>
                        <img src="https://openweathermap.org/img/wn/01d@2x.png" class="w-16 h-16 my-2" alt="icon">
                        <p class="text-xl font-bold text-slate-800 dark:text-navy-100">28° <span class="text-sm font-normal text-slate-400">/ 20°</span></p>
                        <p class="text-xs text-slate-500 mt-1">Sunny</p>
                    </div>

                    <!-- Forecast Card 3 -->
                    <div class="card flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-navy-800 border border-slate-200 dark:border-navy-700 hover:border-blue-400 dark:hover:border-blue-400 transition-colors">
                        <p class="text-sm font-semibold text-slate-500 dark:text-navy-200">Thursday</p>
                        <img src="https://openweathermap.org/img/wn/03d@2x.png" class="w-16 h-16 my-2" alt="icon">
                        <p class="text-xl font-bold text-slate-800 dark:text-navy-100">26° <span class="text-sm font-normal text-slate-400">/ 19°</span></p>
                        <p class="text-xs text-slate-500 mt-1">Cloudy</p>
                    </div>

                </div>
            </div>
          </div>
        </div>
      </div>

    

          <div class="col-span-12 lg:col-span-4">
            <div class="flex items-center justify-between space-x-2">
              <!-- <h2 class="text-base font-medium tracking-wide text-slate-800 line-clamp-1 dark:text-navy-100">
                Overview
              </h2> -->
                <div class="tabs-list flex p-1">
                   <h2 class="text-base font-medium tracking-wide text-slate-800 line-clamp-1 dark:text-navy-100">
                    Order Overview
                  </h2>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-2">
              <div class="rounded-lg bg-slate-150 p-4 dark:bg-navy-700">
                <div class="flex justify-between space-x-1">
                  <p class="text-xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= number_format($earn['money']); ?>
                  </p>
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary dark:text-accent" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <p class="mt-1 text-xs+">Income</p>
              </div>
              <div class="rounded-lg bg-slate-150 p-4 dark:bg-navy-700">
                <div class="flex justify-between">
                  <p class="text-xl font-semibold text-slate-700 dark:text-navy-100">
                    <?php echo $completedOrders; ?>
                  </p>
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                  </svg>
                </div>
                <p class="mt-1 text-xs+">Completed</p>
              </div>
              <div class="rounded-lg bg-slate-150 p-4 dark:bg-navy-700">
                <div class="flex justify-between">
                  <p class="text-xl font-semibold text-slate-700 dark:text-navy-100">
                    <?php echo $pendingOrders; ?>
                  </p>
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-warning" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                   <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"></path>
                  </svg>
                </div>
                <p class="mt-1 text-xs+">Pending</p>
              </div>
              <div class="rounded-lg bg-slate-150 p-4 dark:bg-navy-700">
                <div class="flex justify-between">
                  <p class="text-xl font-semibold text-slate-700 dark:text-navy-100">
                    651
                  </p>
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                  </svg>
                </div>
                <p class="mt-1 text-xs+">Dispatch</p>
              </div>
              <div class="rounded-lg bg-slate-150 p-4 dark:bg-navy-700">
                <div class="flex justify-between space-x-1">
                  <p class="text-xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= $product['total']; ?>
                  </p>
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-secondary" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
                </div>
                <p class="mt-1 text-xs+">Products</p>
              </div>
              <div class="rounded-lg bg-slate-150 p-4 dark:bg-navy-700">
                <div class="flex justify-between">
                  <p class="text-xl font-semibold text-slate-700 dark:text-navy-100">
                    <?php echo $totalCustomers; ?>
                  </p>
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-error" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                  </svg>
                </div>
                <p class="mt-1 text-xs+">Customers</p>
              </div>
            </div>
          </div>

          <div class="card col-span-12 lg:col-span-8">
            <div class="flex items-center justify-between py-3 px-4">
              <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                Your latest Offers
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
            <?php
                $stmt = $conn->prepare("
                    SELECT 
                        n.*,
                        neg.proposed_price,
                        neg.proposed_quantity,
                        neg.proposed_unit
                    FROM notifications n
                    LEFT JOIN negotiations neg 
                        ON n.reference_id = neg.id
                        AND n.reference_type = 'negotiation'
                    WHERE n.user_id = ?
                    ORDER BY n.created_at DESC
                ");

                $stmt->bind_param("i", $farmer_id);
                $stmt->execute();

                $result = $stmt->get_result();

                $notifications = [];

                while($row = $result->fetch_assoc()){
                    $notifications[] = $row;
                }
                ?>
            <div class="grid grid-cols-1 gap-y-4 pb-3 sm:grid-cols-3">
                <?php foreach($notifications as $note): ?>
              <div class="flex flex-col justify-between border-4 border-transparent border-l-info px-4">
                <div>
                  <p class="text-base font-medium text-slate-600 dark:text-navy-100">
                   <?php echo htmlspecialchars($note['title']); ?>
                  </p>
                  <p class="text-xs text-slate-400 dark:text-navy-300">
                    <?php echo htmlspecialchars($note['message']); ?>
                  </p>
                  <div class="badge mt-2 bg-info/10 text-info dark:bg-info/15">
                    UGX <?php echo number_format($note['proposed_price']); ?>
                  </div>
                </div>
                <div>
                  <div class="mt-6">
                    <p class="font-inter">
                      <span class="text-2xl font-medium text-slate-600 dark:text-navy-100"><?php echo rtrim(rtrim($note['proposed_quantity'], '0'), '.');?>.</span><span class="text-xs"><?php echo $note['proposed_unit']; ?></span>
                    </p>
                    <p class="mt-1 text-xs"><?php echo date("d M Y H:i", strtotime($note['created_at'])); ?></p>
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"></path>
                      </svg>
                    </button>
                  </div> -->
                </div>
              </div>
               <?php endforeach; ?>
            </div>
          </div>
          <?php

        // Fetch satisfaction data
        $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN status='accepted' THEN 1 ELSE 0 END) as excellent,
            SUM(CASE WHEN status='countered' THEN 1 ELSE 0 END) as very_good,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as good,
            SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as poor,
            SUM(CASE WHEN status='withdrawn' THEN 1 ELSE 0 END) as very_poor
        FROM negotiations
        WHERE farmer_id = ?
        ");

        $stmt->bind_param("i", $farmer_id);
        $stmt->execute();

        $data = $stmt->get_result()->fetch_assoc();

        // Assign values
        $excellent = $data['excellent'] ?? 0;
        $very_good = $data['very_good'] ?? 0;
        $good = $data['good'] ?? 0;
        $poor = $data['poor'] ?? 0;
        $very_poor = $data['very_poor'] ?? 0;

        $total = $excellent + $very_good + $good + $poor + $very_poor;

        // helper function
        function percent($value, $total){
            return $total > 0 ? round(($value / $total) * 100) : 0;
        }

        // score
        $score = $total > 0 
            ? (($excellent*5 + $very_good*4 + $good*3 + $poor*2 + $very_poor*1) / $total)
            : 0;

        ?>
          <div class="col-span-12 lg:col-span-4">
            <div class="flex items-center justify-between">
              <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                Buyer Satisfaction
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
            <div class="mt-3">
              <p>
                <span class="text-2xl text-slate-700 dark:text-navy-100"><?php echo number_format($score,1); ?></span>
                <!-- <span class="text-xs text-success">
                    2
                </span> -->
              </p>
              <p class="text-xs+">Performance score</p>
            </div>
            <div class="mt-4 flex h-2 space-x-1">
                <!-- Excellent -->
                <div class="rounded-full bg-primary dark:bg-accent" 
                     style="width: <?= percent($excellent, $total) ?>%" 
                     x-tooltip.primary="'Excellent (<?= percent($excellent, $total) ?>%)'"></div>
                
                <!-- Very Good -->
                <div class="rounded-full bg-success" 
                     style="width: <?= percent($very_good, $total) ?>%" 
                     x-tooltip.success="'Very Good (<?= percent($very_good, $total) ?>%)'"></div>

                <!-- Good -->
                <div class="rounded-full bg-info" 
                     style="width: <?= percent($good, $total) ?>%" 
                     x-tooltip.info="'Good (<?= percent($good, $total) ?>%)'"></div>

                <!-- Poor -->
                <div class="rounded-full bg-warning" 
                     style="width: <?= percent($poor, $total) ?>%" 
                     x-tooltip.warning="'Poor (<?= percent($poor, $total) ?>%)'"></div>

                <!-- Very Poor -->
                <div class="rounded-full bg-error" 
                     style="width: <?= percent($very_poor, $total) ?>%" 
                     x-tooltip.error="'Very Poor (<?= percent($very_poor, $total) ?>%)'"></div>
            </div>

            <div class="is-scrollbar-hidden mt-4 min-w-full overflow-x-auto">
              <table class="w-full font-inter">
                <tbody>
                  <tr>
                    <td class="whitespace-nowrap py-2">
                      <div class="flex items-center space-x-2">
                        <div class="size-3.5 rounded-full border-2 border-primary dark:border-accent"></div>
                        <p class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                          Exellent
                        </p>
                      </div>
                    </td>
                    <td class="whitespace-nowrap py-2 text-right">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        <?php echo $excellent; ?>
                      </p>
                    </td>
                    <td class="whitespace-nowrap py-2 text-right"><?php echo percent($excellent, $total); ?>%</td>
                  </tr>
                  <tr>
                    <td class="whitespace-nowrap py-2">
                      <div class="flex items-center space-x-2">
                        <div class="size-3.5 rounded-full border-2 border-success"></div>
                        <p class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                          Very Good
                        </p>
                      </div>
                    </td>
                    <td class="whitespace-nowrap py-2 text-right">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        <?php echo $very_good; ?>
                      </p>
                    </td>
                    <td class="whitespace-nowrap py-2 text-right"><?php echo percent($very_good, $total); ?>%</td>
                  </tr>
                  <tr>
                    <td class="whitespace-nowrap py-2">
                      <div class="flex items-center space-x-2">
                        <div class="size-3.5 rounded-full border-2 border-info"></div>
                        <p class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                          Good
                        </p>
                      </div>
                    </td>
                    <td class="whitespace-nowrap py-2 text-right">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        <?php echo $good; ?>
                      </p>
                    </td>
                    <td class="whitespace-nowrap py-2 text-right"><?php echo percent($good, $total); ?>%</td>
                  </tr>
                  <tr>
                    <td class="whitespace-nowrap py-2">
                      <div class="flex items-center space-x-2">
                        <div class="size-3.5 rounded-full border-2 border-warning"></div>
                        <p class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                          Poor
                        </p>
                      </div>
                    </td>
                    <td class="whitespace-nowrap py-2 text-right">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        <?php echo $poor; ?>
                      </p>
                    </td>
                    <td class="whitespace-nowrap py-2 text-right"><?php echo percent($poor, $total); ?>%</td>
                  </tr>
                  <tr>
                    <td class="whitespace-nowrap py-2">
                      <div class="flex items-center space-x-2">
                        <div class="size-3.5 rounded-full border-2 border-error"></div>
                        <p class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                          Very Poor
                        </p>
                      </div>
                    </td>
                    <td class="whitespace-nowrap py-2 text-right">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        <?php echo $very_poor; ?>
                      </p>
                    </td>
                    <td class="whitespace-nowrap py-2 text-right"><?php echo percent($very_poor, $total); ?>%</td>
                  </tr>
                </tbody>
              </table>
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
document.addEventListener("DOMContentLoaded", function () {

    const loading = document.getElementById("weather-loading");

    fetch("api/get_weather.php")
        .then(res => {
            if (!res.ok) throw new Error("Failed to fetch weather");
            return res.json();
        })
        .then(data => {

         

                if (loading) {
                    loading.classList.add("opacity-0");
                    setTimeout(() => loading.remove(), 300);
                }
            // MAIN CARD
            document.getElementById("temperature").textContent = data.temp;
            document.getElementById("weather-description").textContent = data.description;
            document.getElementById("date-display").textContent = data.date;
            document.getElementById("time-display").textContent = data.time;
            document.getElementById("location-name").textContent = data.location;

            // Weather icon
            const iconUrl = `https://openweathermap.org/img/wn/${data.icon}@2x.png`;
            const iconEl = document.getElementById("weather-icon");
            iconEl.src = iconUrl;
            iconEl.alt = data.description;

            // SMALL CARDS
            document.getElementById("wind-speed").textContent = data.wind_speed + " km/h";
            document.getElementById("feels-like").textContent = data.feels_like + "°C";
            document.getElementById("humidity").textContent = data.humidity + "%";
            document.getElementById("uv-index").textContent = data.uv;
            // FORECAST CARDS
            const forecastContainer = document.getElementById("forecast-cards");

            // Clear previous cards if any
            forecastContainer.innerHTML = "";

            // Make sure forecast data exists
            if (data.forecast && data.forecast.length > 0) {
                // Get next 3 days
                // Each item: { date, temp_max, temp_min, description, icon }
                data.forecast.slice(0,3).forEach(day => {

                    const card = document.createElement("div");
                    card.className = "card flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-navy-800 border border-slate-200 dark:border-navy-700 hover:border-blue-400 dark:hover:border-blue-400 transition-colors";

                    card.innerHTML = `
                        <p class="text-sm font-semibold text-slate-500 dark:text-navy-200">${day.dayName}</p>
                        <img src="https://openweathermap.org/img/wn/${day.icon}@2x.png" class="w-16 h-16 my-2" alt="${day.description}">
                        <p class="text-xl font-bold text-slate-800 dark:text-navy-100">${day.temp_max}° <span class="text-sm font-normal text-slate-400">/ ${day.temp_min}°</span></p>
                        <p class="text-xs text-slate-500 mt-1">${day.description}</p>
                    `;

                    forecastContainer.appendChild(card);
                });
            }

        })
        .catch(err => {
            console.error(err);
            if (loading) {
                loading.innerHTML = 
                `<div class="space-y-4">
    <div
      x-data="{isShow:true}"
      :class="!isShow && 'opacity-0 transition-opacity duration-300'"
      class="alert flex items-center justify-between overflow-hidden rounded-lg border border-error text-error"
    >
      <div class="flex">
        <div class="bg-error p-3 text-white">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="size-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>
        <div class="px-4 py-3 sm:px-5">Failed to load weather data.</div>
      </div>
      <div class="px-2">
        <button
          @click="isShow = false; setTimeout(()=>$root.remove(),300)"
          class="btn size-7 rounded-full p-0 font-medium text-error hover:bg-error/20 focus:bg-error/20 active:bg-error/25"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="size-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </div>
</div>`;
            }
        });

});
</script>
<script>
const earningsData = <?php echo json_encode($earnings); ?>;

var options = {
    chart: {
        type: 'line',
        height: 48, // small mini-chart
        sparkline: {
            enabled: true // no axes/labels
        }
    },
    stroke: {
        curve: 'smooth',
        width: 2
    },
    series: [{
        data: earningsData
    }],
    tooltip: {
        enabled: true,
        y: {
            formatter: function(val) { return "UGX " + val; }
        }
    }
};
var chart = new ApexCharts(document.querySelector("#farmerEarningsSpark"), options);
chart.render();
</script>
<!-- Login Success Notification Trigger -->
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
