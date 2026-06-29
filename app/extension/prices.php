<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'prices.php';

$filter_cat    = (int)($_GET['category'] ?? 0);
$filter_search = $conn->real_escape_string(trim($_GET['search'] ?? ''));

$categories = [];
$res = $conn->query("SELECT * FROM categories ORDER BY name");
while ($r = $res->fetch_assoc()) $categories[] = $r;

$where = "WHERE 1=1";
if ($filter_cat > 0)       $where .= " AND mp.category_id=$filter_cat";
if ($filter_search !== '') $where .= " AND mp.crop LIKE '%$filter_search%'";

$prices = [];
$res = $conn->query("
    SELECT mp.crop, mp.price, mp.date, c.name AS category
    FROM market_prices mp
    LEFT JOIN categories c ON c.id=mp.category_id
    $where
    AND mp.date=(SELECT MAX(date) FROM market_prices mp2 WHERE mp2.crop=mp.crop)
    ORDER BY mp.crop ASC
");
while ($r = $res->fetch_assoc()) $prices[] = $r;

// 14-day history for chart
$history_data = [];
$res = $conn->query("SELECT crop,price,date FROM market_prices WHERE date>=DATE_SUB(CURDATE(),INTERVAL 14 DAY) ORDER BY crop,date ASC");
while ($r = $res->fetch_assoc()) $history_data[$r['crop']][] = ['date'=>$r['date'],'price'=>(float)$r['price']];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Prices</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="../css/app.css">

    <!-- Javascript Assets -->
    <script src="../js/app.js" defer=""></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
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
        .mono{font-family:'DM Mono',monospace}
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
        <?php include 'pricessider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="flex items-center justify-between space-x-2 py-5">
          <h3 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">
            Market Prices
          </h3>

          <div>
            <a href="#" class="border-b border-dashed border-current pb-0.5 font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">Latest — <?= date('d M Y') ?></a>
          </div>
        </div>
        <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
          <div class="col-span-12 lg:col-span-8">
            <div class="flex items-center justify-between space-x-3 sm:space-x-5">
              <div class="flex w-full max-w-lg">
                <label class="relative flex w-full">
                  <select name="category"
                    class="form-select mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:bg-navy-700 dark:hover:border-navy-400 dark:focus:border-accent"
                  >
                    <option value="0">All categories</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filter_cat===(int)$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                  </select>
                </label>
              </div>
                <form method="GET" action="">
                <div class="flex w-full max-w-lg">
                <label class="relative flex w-50">
                  <input class="form-input peer h-9 w-full rounded-l-lg bg-white px-3 py-2 shadow-soft ring-primary/50 placeholder:text-slate-400 focus:ring dark:bg-navy-700 dark:shadow-none dark:ring-accent/50 dark:placeholder:text-navy-300 lg:pl-9"  type="text" name="search" value="<?= htmlspecialchars($_GET['search']??'') ?>" placeholder="Search crop…">
                  <span class="pointer-events-none absolute hidden h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent lg:flex">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 transition-colors duration-200" fill="currentColor" viewbox="0 0 24 24">
                      <path d="M3.316 13.781l.73-.171-.73.171zm0-5.457l.73.171-.73-.171zm15.473 0l.73-.171-.73.171zm0 5.457l.73.171-.73-.171zm-5.008 5.008l-.171-.73.171.73zm-5.457 0l-.171.73.171-.73zm0-15.473l-.171-.73.171.73zm5.457 0l.171-.73-.171.73zM20.47 21.53a.75.75 0 101.06-1.06l-1.06 1.06zM4.046 13.61a11.198 11.198 0 010-5.115l-1.46-.342a12.698 12.698 0 000 5.8l1.46-.343zm14.013-5.115a11.196 11.196 0 010 5.115l1.46.342a12.698 12.698 0 000-5.8l-1.46.343zm-4.45 9.564a11.196 11.196 0 01-5.114 0l-.342 1.46c1.907.448 3.892.448 5.8 0l-.343-1.46zM8.496 4.046a11.198 11.198 0 015.115 0l.342-1.46a12.698 12.698 0 00-5.8 0l.343 1.46zm0 14.013a5.97 5.97 0 01-4.45-4.45l-1.46.343a7.47 7.47 0 005.568 5.568l.342-1.46zm5.457 1.46a7.47 7.47 0 005.568-5.567l-1.46-.342a5.97 5.97 0 01-4.45 4.45l.342 1.46zM13.61 4.046a5.97 5.97 0 014.45 4.45l1.46-.343a7.47 7.47 0 00-5.568-5.567l-.342 1.46zm-5.457-1.46a7.47 7.47 0 00-5.567 5.567l1.46.342a5.97 5.97 0 014.45-4.45l-.343-1.46zm8.652 15.28l3.665 3.664 1.06-1.06-3.665-3.665-1.06 1.06z"></path>
                    </svg>
                  </span>
                </label>
                <button type="submit" class="btn h-9 rounded-l-none bg-primary px-3 font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90 lg:px-5">
                  <span class="hidden lg:inline-flex">Search</span>
                  <svg class="size-4.5 lg:hidden" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                </button>
                </form>
              </div>
            </div>
            <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
              <div class="card mt-3 col-span-12">
              <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <table class="is-hoverable w-full text-left">
                  <thead>
                    <tr>
                      <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                        Crop
                      </th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                        Category
                      </th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                        Price
                      </th>
                      <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">Updated</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if(empty($prices)): ?>
                            <tr><td colspan="4" class="px-4 py-10 text-center text-xs text-gray-400">No price data</td></tr>
                        <?php else: foreach($prices as $p): ?>
                    <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                      <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                        <?= htmlspecialchars($p['crop']) ?>
                      </td>
                      <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                        <?= htmlspecialchars($p['category']??'—') ?>
                        
                      </td>
                      <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                       <?= number_format((float)$p['price']) ?>
                      </td>
                      <td class="mono whitespace-nowrap px-4 py-3 sm:px-5">
                        <?= date('d M Y',strtotime($p['date'])) ?>
                      </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
              </div>

            </div>
            </div>
          </div>
          <div class="col-span-12 lg:col-span-4">
            <div class="card px-4 pb-5 sm:px-5">
              <div class="flex items-center justify-between py-3">
                <h2 class="text-sm+ font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  14-day trend
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

              <div class="space-y-4">
                <!-- <div class="flex items-center justify-between space-x-2">
                  <div class="flex items-center space-x-4">
                    <img class="mask is-squircle size-12 object-cover object-center" src="images/travel/hotel-3.jpg" alt="image">
                    <div class="space-y-1">
                      <a href="#" class="font-medium text-slate-600 line-clamp-1 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Crowne Plaza</a>
                      <div class="flex items-center space-x-3 text-xs">
                        <p class="flex items-center space-x-1">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          </svg>
                          <span class="line-clamp-1">French</span>
                        </p>
                        <p class="flex shrink-0 items-center space-x-1">
                          <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" class="size-3.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.948 4.29l1.643 3.169c.224.44.82.864 1.325.945l2.977.477c1.905.306 2.353 1.639.98 2.953l-2.314 2.233c-.392.378-.607 1.107-.486 1.63l.663 2.763c.523 2.188-.681 3.034-2.688 1.89l-2.791-1.593c-.504-.288-1.335-.288-1.848 0l-2.791 1.594c-1.997 1.143-3.21.288-2.688-1.89l.663-2.765c.12-.522-.094-1.251-.486-1.63l-2.315-2.232c-1.362-1.314-.924-2.647.98-2.953l2.978-.477c.495-.081 1.092-.504 1.316-.945l1.643-3.17c.896-1.719 2.352-1.719 3.239 0z"></path>
                          </svg>
                          <span>4.9</span>
                        </p>
                      </div>
                    </div>
                  </div>
                  <p class="shrink-0">
                    <span class="text-base font-medium text-slate-700 dark:text-navy-100">$100</span>
                    <span class="text-xs text-slate-400 dark:text-navy-300">/day</span>
                  </p>
                </div>
                <div class="flex items-center justify-between space-x-2">
                  <div class="flex items-center space-x-4">
                    <img class="mask is-squircle size-12 object-cover object-center" src="images/travel/hotel-5.jpg" alt="image">
                    <div class="space-y-1">
                      <a href="#" class="font-medium text-slate-600 line-clamp-1 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Emerald Bay Inn.</a>
                      <div class="flex items-center space-x-3 text-xs">
                        <p class="flex items-center space-x-1">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          </svg>
                          <span class="line-clamp-1">Italy</span>
                        </p>
                        <p class="flex shrink-0 items-center space-x-1">
                          <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" class="size-3.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.948 4.29l1.643 3.169c.224.44.82.864 1.325.945l2.977.477c1.905.306 2.353 1.639.98 2.953l-2.314 2.233c-.392.378-.607 1.107-.486 1.63l.663 2.763c.523 2.188-.681 3.034-2.688 1.89l-2.791-1.593c-.504-.288-1.335-.288-1.848 0l-2.791 1.594c-1.997 1.143-3.21.288-2.688-1.89l.663-2.765c.12-.522-.094-1.251-.486-1.63l-2.315-2.232c-1.362-1.314-.924-2.647.98-2.953l2.978-.477c.495-.081 1.092-.504 1.316-.945l1.643-3.17c.896-1.719 2.352-1.719 3.239 0z"></path>
                          </svg>
                          <span>4.6</span>
                        </p>
                      </div>
                    </div>
                  </div>
                  <p class="shrink-0">
                    <span class="text-base font-medium text-slate-700 dark:text-navy-100">$80</span>
                    <span class="text-xs text-slate-400 dark:text-navy-300">/day</span>
                  </p>
                </div>
                <div class="flex items-center justify-between space-x-2">
                  <div class="flex items-center space-x-4">
                    <img class="mask is-squircle size-12 object-cover object-center" src="images/travel/hotel-7.jpg" alt="image">
                    <div class="space-y-1">
                      <a href="#" class="font-medium text-slate-600 line-clamp-1 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Hotel Bliss.</a>
                      <div class="flex items-center space-x-3 text-xs">
                        <p class="flex items-center space-x-1">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          </svg>
                          <span class="line-clamp-1">Room</span>
                        </p>
                        <p class="flex shrink-0 items-center space-x-1">
                          <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" class="size-3.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.948 4.29l1.643 3.169c.224.44.82.864 1.325.945l2.977.477c1.905.306 2.353 1.639.98 2.953l-2.314 2.233c-.392.378-.607 1.107-.486 1.63l.663 2.763c.523 2.188-.681 3.034-2.688 1.89l-2.791-1.593c-.504-.288-1.335-.288-1.848 0l-2.791 1.594c-1.997 1.143-3.21.288-2.688-1.89l.663-2.765c.12-.522-.094-1.251-.486-1.63l-2.315-2.232c-1.362-1.314-.924-2.647.98-2.953l2.978-.477c.495-.081 1.092-.504 1.316-.945l1.643-3.17c.896-1.719 2.352-1.719 3.239 0z"></path>
                          </svg>
                          <span>4.4</span>
                        </p>
                      </div>
                    </div>
                  </div>
                  <p class="shrink-0">
                    <span class="text-base font-medium text-slate-700 dark:text-navy-100">$110</span>
                    <span class="text-xs text-slate-400 dark:text-navy-300">/day</span>
                  </p>
                </div>
                <div class="flex items-center justify-between space-x-2">
                  <div class="flex items-center space-x-4">
                    <img class="mask is-squircle size-12 object-cover object-center" src="images/travel/hotel-4.jpg" alt="image">
                    <div class="space-y-1">
                      <a href="#" class="font-medium text-slate-600 line-clamp-1 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Sunset Lodge.</a>
                      <div class="flex items-center space-x-3 text-xs">
                        <p class="flex items-center space-x-1">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          </svg>
                          <span class="line-clamp-1">Sydney</span>
                        </p>
                        <p class="flex shrink-0 items-center space-x-1">
                          <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" class="size-3.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.948 4.29l1.643 3.169c.224.44.82.864 1.325.945l2.977.477c1.905.306 2.353 1.639.98 2.953l-2.314 2.233c-.392.378-.607 1.107-.486 1.63l.663 2.763c.523 2.188-.681 3.034-2.688 1.89l-2.791-1.593c-.504-.288-1.335-.288-1.848 0l-2.791 1.594c-1.997 1.143-3.21.288-2.688-1.89l.663-2.765c.12-.522-.094-1.251-.486-1.63l-2.315-2.232c-1.362-1.314-.924-2.647.98-2.953l2.978-.477c.495-.081 1.092-.504 1.316-.945l1.643-3.17c.896-1.719 2.352-1.719 3.239 0z"></path>
                          </svg>
                          <span>4.7</span>
                        </p>
                      </div>
                    </div>
                  </div>
                  <p class="shrink-0">
                    <span class="text-base font-medium text-slate-700 dark:text-navy-100">$180</span>
                    <span class="text-xs text-slate-400 dark:text-navy-300">/day</span>
                  </p>
                </div>
                <div class="flex items-center justify-between space-x-2">
                  <div class="flex items-center space-x-4">
                    <img class="mask is-squircle size-12 object-cover object-center" src="images/travel/hotel-2.jpg" alt="image">
                    <div class="space-y-1">
                      <a href="#" class="font-medium text-slate-600 line-clamp-1 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Hotel Elite.</a>
                      <div class="flex items-center space-x-3 text-xs">
                        <p class="flex items-center space-x-1">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          </svg>
                          <span class="line-clamp-1">New York</span>
                        </p>
                        <p class="flex shrink-0 items-center space-x-1">
                          <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" class="size-3.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.948 4.29l1.643 3.169c.224.44.82.864 1.325.945l2.977.477c1.905.306 2.353 1.639.98 2.953l-2.314 2.233c-.392.378-.607 1.107-.486 1.63l.663 2.763c.523 2.188-.681 3.034-2.688 1.89l-2.791-1.593c-.504-.288-1.335-.288-1.848 0l-2.791 1.594c-1.997 1.143-3.21.288-2.688-1.89l.663-2.765c.12-.522-.094-1.251-.486-1.63l-2.315-2.232c-1.362-1.314-.924-2.647.98-2.953l2.978-.477c.495-.081 1.092-.504 1.316-.945l1.643-3.17c.896-1.719 2.352-1.719 3.239 0z"></path>
                          </svg>
                          <span>4.6</span>
                        </p>
                      </div>
                    </div>
                  </div>
                  <p class="shrink-0">
                    <span class="text-base font-medium text-slate-700 dark:text-navy-100">$220</span>
                    <span class="text-xs text-slate-400 dark:text-navy-300">/day</span>
                  </p>
                </div> -->
                  <div id="priceChart"></div>
                <!-- </div> -->

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
document.addEventListener("DOMContentLoaded", function () {
    // 1. Check if the element exists to avoid errors
    const chartElement = document.querySelector("#priceChart");
    if (!chartElement) return;

    // 2. Safely parse PHP data
    const historyData = <?= json_encode($history_data) ?>;
    if (!historyData || Object.keys(historyData).length === 0) {
        chartElement.innerHTML = "<p class='p-4 text-center'>No data available for chart</p>";
        return;
    }

    const colors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

    // 3. Process data
    const sorted = Object.entries(historyData)
        .map(([crop, pts]) => ({
            crop,
            pts,
            max: Math.max(...pts.map(p => p.price))
        }))
        .sort((a, b) => b.max - a.max)
        .slice(0, 5);

    const allDates = [...new Set(sorted.flatMap(c => c.pts.map(p => p.date)))].sort();

    const series = sorted.map((c, i) => ({
        name: c.crop,
        data: allDates.map(d => {
            const f = c.pts.find(p => p.date === d);
            return f ? f.price : null;
        })
    }));

    // 4. Chart Configuration
    const options = {
        series: series,
        chart: {
            height: 280,
            type: 'line',
            fontFamily: 'Inter, sans-serif',
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        colors: colors,
        stroke: { curve: 'smooth', width: 2 },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
        xaxis: {
            categories: allDates.map(d => d.slice(5)),
            labels: { style: { colors: '#64748b', fontSize: '11px' } }
        },
        yaxis: {
            labels: {
                formatter: (val) => val ? val.toLocaleString() : 0,
                style: { colors: '#64748b', fontSize: '11px' }
            }
        },
        legend: { position: 'top', horizontalAlign: 'right' },
        tooltip: { shared: true }
    };

    // 5. Render
    const chart = new ApexCharts(chartElement, options);
    chart.render();
});
</script>

  </body>
</html>
