<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') {
    header("Location: ../auth");
    exit;
}
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';

// Reports count
$reports_count = $conn->query("SELECT COUNT(*) AS c FROM extension_reports WHERE extension_id=$extension_id")->fetch_assoc()['c'];

// Farmers in same location
$row = $conn->query("SELECT location FROM users WHERE id=$extension_id")->fetch_assoc();
$safe_loc = $conn->real_escape_string($row['location'] ?? '');
$farmers_count = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='farmer' AND location='$safe_loc'")->fetch_assoc()['c'];

// Bulletins
$bulletins_count = $conn->query("SELECT COUNT(*) AS c FROM posts WHERE id=$extension_id")->fetch_assoc()['c'];

// Disease alerts this month
$alerts_count = $conn->query("
    SELECT COUNT(*) AS c FROM extension_reports
    WHERE extension_id=$extension_id
      AND title REGEXP 'disease|pest|blight|worm|virus|fungus|rust|armyworm'
      AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())
")->fetch_assoc()['c'];

// Recent reports
$recent_reports = [];
$res = $conn->query("SELECT id,title,district,created_at FROM extension_reports WHERE extension_id=$extension_id ORDER BY created_at DESC LIMIT 4");
while ($r = $res->fetch_assoc()) $recent_reports[] = $r;

// Market prices — latest per crop
$market_prices = [];
$res = $conn->query("
    SELECT mp.crop, mp.price, mp.date, c.name AS category
    FROM market_prices mp
    LEFT JOIN categories c ON c.id=mp.category_id
    WHERE mp.date=(SELECT MAX(date) FROM market_prices mp2 WHERE mp2.crop=mp.crop)
    ORDER BY mp.crop ASC LIMIT 6
");
while ($r = $res->fetch_assoc()) $market_prices[] = $r;

// Farmer activity
$farmer_activity = [];
$res = $conn->query("
    SELECT u.name, u.location, p.name AS product, p.status, p.created_at
    FROM products p JOIN users u ON u.id=p.farmer_id
    WHERE u.role='farmer' ORDER BY p.created_at DESC LIMIT 5
");
while ($r = $res->fetch_assoc()) $farmer_activity[] = $r;

// Recent bulletins
$recent_bulletins = [];
$res = $conn->query("SELECT id,title,created_at FROM posts WHERE id=$extension_id ORDER BY created_at DESC LIMIT 4");
while ($r = $res->fetch_assoc()) $recent_bulletins[] = $r;

// Weather
$weather = null;
$res = $conn->query("SELECT location,temperature,humidity,weather_desc FROM weather_data ORDER BY created_at DESC LIMIT 1");
if ($res && $res->num_rows) $weather = $res->fetch_assoc();

function detect_tag(string $t): string {
    $t = strtolower($t);
    if (preg_match('/disease|pest|blight|worm|virus|fungus|rust|armyworm/',$t)) return 'disease';
    if (preg_match('/yield|harvest|crop|produce/',$t))  return 'yield';
    if (preg_match('/soil|erosion|degrad|fertility/',$t)) return 'soil';
    if (preg_match('/water|irrigation|flood|drought|rain/',$t)) return 'water';
    return 'general';
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
    <style>
      body
      {
        .stat-card{transition:transform .15s}
.stat-card:hover{transform:translateY(-1px)}
.fade-in{animation:fadeIn .3s ease forwards}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.tag{display:inline-flex;align-items:center;font-size:11px;font-weight:500;padding:2px 8px;border-radius:20px}
.tag-disease{background:#FCEBEB;color:#A32D2D}
.tag-yield{background:#EAF3DE;color:#3B6D11}
.tag-soil{background:#FAEEDA;color:#854F0B}
.tag-water{background:#E6F1FB;color:#185FA5}
.tag-general{background:#F1EFE8;color:#5F5E5A}
.tag-pending{background:#FAEEDA;color:#854F0B}
.tag-approved,.tag-active{background:#E1F5EE;color:#0F6E56}
.tag-rejected{background:#FCEBEB;color:#A32D2D}
      }
    </style>
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
        <?php include 'dashboardsider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 px-[var(--margin-x)] transition-all duration-[.25s] sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 col-span-12 lg:col-span-12">
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">Farmers in District</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="mono text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= $farmers_count ?>
                  </p>
                  <p class="text-xs text-success">Your area</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-warning/10">
                <i class="fa-solid fa-user text-xl text-warning"></i>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <i class="fa-solid fa-user translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">Reports Filed</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= $reports_count ?>
                  </p>
                  <p class="text-xs text-success">All time</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-info/10">
                <!-- <i class="fa-solid fa-eye text-xl text-info"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-info">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-eye translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-14 translate-x-1/4 translate-y-1/4 text-5xl opacity-15">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                </svg>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">Bulletins Posted</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="mono text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= $bulletins_count ?>
                  </p>
                  <p class="text-xs text-success">By you</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-success/10">
                <!-- <i class="fa-solid fa-thumbs-up text-xl text-success"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-success">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-thumbs-up translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"  class="size-14 translate-x-1/4 translate-y-1/4 text-5xl opacity-15">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                </svg>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">Disease Alerts</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100" style="font-weight:500;<?= $alerts_count>0?'color:#A32D2D':'' ?>"><?= $alerts_count ?>
                    
                  </p>
                  <p class="text-xs text-error">This Month</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-error/10">
                <i class="fa-solid fa-bug text-xl text-error"></i>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <i class="fa-solid fa-bug translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i>
              </div>
            </div>
          </div>
          
          <div class="card col-span-12 lg:col-span-4">
            <div class="card px-4 pb-5 sm:px-5">
              <div class="my-3 flex h-8 items-center justify-between">
                <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Recent Reports
                </h2>
                <a href="reports.php" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">
                  View All
                </a>
              </div>
              <div>
                <p>
                  <span class="text-2xl text-slate-700 dark:text-navy-100"><?= $reports_count ?></span>
                </p>
                <p class="text-xs+">Total Reports</p>
              </div>
              <div class="mt-5 space-y-4">
                <?php if(empty($recent_reports)): ?><div class="px-4 py-6 text-center text-xs text-gray-400">No reports yet</div>
                <?php else: foreach($recent_reports as $r): $tag=detect_tag($r['title']); ?>
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="mask is-squircle flex size-5 items-center justify-center bg-error/10">
                      <i class="fa-solid fa-bug text-xs text-error"></i>
                    </div>
                    
                    <div class="min-w-0 flex-1 pr-2">
                            <p class="text-xs text-gray-700 truncate" style="font-weight:500"><?= htmlspecialchars($r['title']) ?></p>
                            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($r['district']) ?> · <?= date('d M',strtotime($r['created_at'])) ?></p>
                        </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <div class="badge bg-error/10 text-error dark:bg-error/15"><?= ucfirst($tag) ?></div>
                  </div>
                </div>
                <?php endforeach; endif; ?>
              </div>
            </div>
          </div>
          <div class="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:col-span-8 lg:gap-8 xl:col-span-8">
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
                  Farmer Activity
                </h2>
                <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn -mr-1.5 size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 rotate-100" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 4.5 15 15m0 0V8.25m0 11.25H8.25" ></path>
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
              <div class="mt-5 space-y-4">
                <?php if(empty($farmer_activity)): ?><div class="px-4 py-6 text-center text-xs text-gray-400">No activity yet</div>
                    <?php else: foreach($farmer_activity as $f): ?>
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-xs text-white" style="background:#1D9E75;font-weight:500"><?= strtoupper(substr($f['name'],0,1)) ?></div>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-700 truncate" style="font-weight:500"><?= htmlspecialchars($f['name']) ?></p>
                                <p class="text-xs text-gray-400 truncate">Listed: <?= htmlspecialchars($f['product']) ?></p>
                            </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="tag tag-<?= strtolower($f['status']) ?> flex-shrink-0 ml-2"><?= ucfirst($f['status']) ?></span>
                  </div>
                </div>
                <?php endforeach; endif; ?>
              </div>
            </div>

            <div class="card px-4 pb-5 sm:px-5">
              <div class="my-3 flex h-8 items-center justify-between">
                <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Market Prices
                </h2>
                <a href="prices.php" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">
                  View All
                </a>
              </div>
              <div class="mt-5 space-y-4">
                <?php if(empty($market_prices)): ?><div class="px-4 py-6 text-center text-xs text-gray-400">No price data</div>
                        <?php else: foreach($market_prices as $mp): ?>
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="mask is-squircle flex size-5 items-center justify-center bg-success/10">
                      <i class="fa-solid fa-seedling text-xs text-success"></i>
                    </div>
                    <p><?= htmlspecialchars($mp['crop']) ?></p>
                  </div>
                  <div class="flex items-center space-x-2">
                    <p class="text-xs+ text-slate-800 dark:text-navy-100">
                      <?= number_format((float)$mp['price']) ?> <span class="text-gray-400" style="font-weight:400">UGX/kg</span>
                    </p>
                  </div>
                </div>
                <?php endforeach; endif; ?>
              </div>
            </div>
          </div>

          <div class="card col-span-12 lg:col-span-8">
            <div class="card px-4 pb-5 sm:px-5">
              <div class="my-3 flex h-8 items-center justify-between">
                <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  My Recent bulletins
                </h2>
                <a href="bulletins.php" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">
                  View All
                </a>
              </div>
              <div class="mt-5 space-y-4">
                <?php if(empty($recent_bulletins)): ?>
                <div class="px-4 py-8 text-center">
                    <p class="text-xs text-gray-400 mb-3">No bulletins posted yet</p>
                    <a href="post_bulletin.php" class="text-xs text-white px-3 py-1.5 rounded-lg" style="background:#1D9E75;font-weight:500">Post your first bulletin</a>
                </div>
                <?php else: ?>
                <?php foreach($recent_bulletins as $b): ?>
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="badge space-x-2.5 text-warning">
                        <div class="size-2 rounded-full bg-current"></div>
                                              </div>
                    
                    <div class="min-w-0 flex-1 pr-2">
                            <p class="text-xs text-gray-700 truncate" style="font-weight:500"><?= htmlspecialchars($b['title']) ?></p>
                        </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <p class="text-xs text-gray-400 mt-0.5">
                      <?= date('d M Y',strtotime($b['created_at'])) ?>
                    </p>
                  </div>
                </div>
                 <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-1 sm:gap-5 lg:col-span-4 lg:gap-4 xl:col-span-4">
            <div class="card px-4 pb-5 sm:px-5">
              <div class="my-3 flex h-8 items-center justify-between">
                <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Quick Actions
                </h2>
                <a href="#" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">
                  View All
                </a>
              </div>
              <div class="mt-5 space-y-2">
               
              </div>
               <div class="space-y-2">
                    <a href="reports.php" class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all text-xs text-gray-600">
                        <!-- <span class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#E1F5EE"><svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="#0F6E56" stroke-width="1.6"><path d="M6.5 1v11M1 6.5h11"/></svg></span> -->
                        <div class="badge bg-success text-white">
                       <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="size-4"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke-width="1.5" stroke="currentColor"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round" 
                            d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"
                            clip-rule="evenodd"
                          ></path>
                        </svg>
                      </div>
                        New field report
                    </a>
                    <a href="bulletins.php" class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all text-xs text-gray-600">
                        <!-- <span class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#FAEEDA"><svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="#854F0B" stroke-width="1.6"><path d="M2 9.5L4.5 2l7 7-7.5 1L2 9.5z"/></svg></span> -->
                        <div class="badge bg-warning text-white">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="size-4"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke-width="1.5" stroke="currentColor"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"                           
                            d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"
                            
                          ></path>
                        </svg>
                      </div>
                        Post agri bulletin
                    </a>
                    <a href="prices.php" class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all text-xs text-gray-600">
                        <!-- <span class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#E6F1FB"><svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="#185FA5" stroke-width="1.6"><path d="M1.5 10l3.5-3.5 2 2 4.5-5"/></svg></span> -->
                        <div class="badge bg-secondary text-white">
                       <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="size-4"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke-width="1.5" stroke="currentColor"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round" 
                            d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z"
                            clip-rule="evenodd"
                          ></path>
                        </svg>
                      </div>
                        Check market prices
                    </a>
                    <a href="farmers.php" class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all text-xs text-gray-600">
                        <!-- <span class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#F1EFE8"><svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="#5F5E5A" stroke-width="1.6"><circle cx="6.5" cy="4" r="2.5"/><path d="M1.5 12c0-2.8 2.2-5 5-5s5 2.2 5 5"/></svg></span> -->
                        <div class="badge bg-primary text-white">
                       <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="size-4"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke-width="1.5" stroke="currentColor"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round" 
                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            clip-rule="evenodd"
                          ></path>
                        </svg>
                      </div>
                        View farmer activity
                    </a>
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
