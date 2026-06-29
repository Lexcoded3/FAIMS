<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';
$sqlTotal = "SELECT COUNT(*) AS total_requests
             FROM buyer_requests
             WHERE status = 'open'";
$resTotal = mysqli_query($conn, $sqlTotal);
$total = mysqli_fetch_assoc($resTotal)['total_requests'];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./style.css">
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Marketplace</title>
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

  <!-- <body x-data="" class="is-header-blur" x-bind="$store.global.documentBody is-sidebar-open"> -->
    <body x-data="" x-bind="$store.global.documentBody" class="is-header-blur is-sidebar-open">
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
        <?php include 'marketplacesider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="col-span-12 lg:order-last lg:col-span-4">
            <div class="card col-span-2 px-4 pb-5 sm:px-5">
                <div x-data="{activeTab:'tabProfile'}" class="tabs flex flex-col">
                  <div class="is-scrollbar-hidden overflow-x-auto">
                    <div class="border-b-2 border-slate-150 dark:border-navy-500">
                      <div class="tabs-list -mb-0.5 flex">
                        <button
                          @click="activeTab = 'tabHome'"
                          :class="activeTab === 'tabHome' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                          class="btn shrink-0 space-x-2 rounded-none border-b-2 px-3 py-2 font-medium"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="size-4.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            />
                          </svg>
                          <span>Home</span>
                        </button>
                        <button
                          @click="activeTab = 'tabProfile'"
                          :class="activeTab === 'tabProfile' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                          class="btn shrink-0 space-x-2 rounded-none border-b-2 px-3 py-2 font-medium"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="size-4.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                          </svg>
                          <span>Profile</span>
                        </button>
                        <button
                          @click="activeTab = 'tabMessages'"
                          :class="activeTab === 'tabMessages' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                          class="btn shrink-0 space-x-2 rounded-none border-b-2 px-3 py-2 font-medium"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="size-4.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
                          </svg>
                          <span> Messages </span>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="tab-content pt-4">
                    <div
                      x-show="activeTab === 'tabHome'"
                      x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                    >
                      <div>
                        <p id="marketPrices">
                          Pellentesque pulvinar, sapien eget fermentum sodales, felis lacus
                          viverra magna, id pulvinar odio metus non enim. Ut id augue
                          interdum, ultrices felis eu, tincidunt libero.
                        </p>
                        <div class="flex space-x-2 pt-3">
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 1
                          </a>
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 2
                          </a>
                        </div>

                        <p class="pt-3 text-xs text-slate-400 dark:text-navy-300">
                          Lorem ipsum dolor sit amet consectetur adipisicing elit. Tempore
                          dolore non atque?
                        </p>
                      </div>
                    </div>
                    <div
                      x-show="activeTab === 'tabProfile'"
                      x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                    >
                      <div>
                        <p>
                          Cras iaculis ipsum quis lectus faucibus, in mattis nulla molestie.
                          Vestibulum vel tristique libero. Morbi vulputate odio at viverra
                          sodales. Curabitur accumsan justo eu libero porta ultrices vitae eu
                          leo.
                        </p>
                        <div class="flex space-x-2 pt-3">
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 1
                          </a>
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 2
                          </a>
                        </div>

                        <p class="pt-3 text-xs text-slate-400 dark:text-navy-300">
                          Lorem ipsum dolor sit amet consectetur adipisicing elit. Tempore
                          dolore non atque?
                        </p>
                      </div>
                    </div>
                    <div
                      x-show="activeTab === 'tabMessages'"
                      x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                    >
                      <div>
                        <p>
                          Etiam nec ante eget lacus vulputate egestas non iaculis tellus.
                          Suspendisse tempus ex in tortor venenatis malesuada. Aenean
                          consequat dui vitae nibh lobortis condimentum. Duis vel risus est.
                        </p>
                        <div class="flex space-x-2 pt-3">
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 1
                          </a>
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 2
                          </a>
                        </div>

                        <p class="pt-3 text-xs text-slate-400 dark:text-navy-300">
                          Lorem ipsum dolor sit amet consectetur adipisicing elit. Tempore
                          dolore non atque?
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
           
          </div>
          <div class="col-span-12 lg:col-span-8">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-5 lg:gap-6">
              <div class="card p-4 sm:p-5">
                <div class="flex size-12 items-center justify-center rounded-xl bg-primary shadow-xl shadow-primary/50 dark:bg-accent dark:shadow-accent/50">
                  <i class="fa fa-dollar-sign text-xl text-white"></i>
                </div>
                <p class="mt-16">Top Crop</p>
                <p class="mt-2 font-medium text-slate-700 dark:text-navy-100">
                  <span class="text-2xl">$35</span><span class="text-base">.3k</span>
                </p>
                <p class="mt-1 flex items-center text-xs text-success">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                  </svg>
                  <span>4.3%</span>
                </p>
              </div>
              <div class="card p-4 sm:p-5">
                <div class="flex size-12 items-center justify-center rounded-xl bg-warning shadow-xl shadow-warning/50">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                  </svg>
                </div>
                <p class="mt-16">Av. Price</p>
                <p class="mt-2 font-medium text-slate-700 dark:text-navy-100">
                  <span class="text-2xl">$7</span><span class="text-base">.14k</span>
                </p>
                <p class="mt-1 flex items-center text-xs text-error">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-error" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"></path>
                  </svg>
                  <span>1.9%</span>
                </p>
              </div>
              <div class="card p-4 sm:p-5">
                <div class="flex size-12 items-center justify-center rounded-xl bg-info shadow-xl shadow-info/50">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5"></path>
                  </svg>
                </div>
                <p class="mt-16">Buyer ReQ.</p>
                <p class="mt-2 font-medium text-slate-700 dark:text-navy-100">
                  <span class="text-2xl"><?= $total ?></span><span class="text-base"></span>
                </p>
                <p class="mt-1 flex items-center text-xs text-success">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                  </svg>
                  <span>7.11%</span>
                </p>
              </div>
              <div class="card p-4 sm:p-5">
                <div class="flex size-12 items-center justify-center rounded-xl bg-secondary shadow-xl shadow-secondary/50">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                  </svg>
                </div>
                <p class="mt-16">Saving</p>
                <p class="mt-2 font-medium text-slate-700 dark:text-navy-100">
                  <span class="text-2xl">$2</span><span class="text-base">.44k</span>
                </p>
                <p class="mt-1 flex items-center text-xs text-success">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                  </svg>
                  <span>3.47%</span>
                </p>
              </div>
              <div class="card col-span-2 px-4 pb-5 sm:px-5">
                <div class="my-3 flex h-8 items-center justify-between">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Buyer ReQ.
                  </h2>
                  <a href="#" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">
                    View All
                  </a>
                </div>
                <div class="space-y-4">
                  <div id="buyerRequests" class="flex cursor-pointer items-center justify-between">
                   
                  </div>
                </div>
              </div>


              <div class="card col-span-2 px-4 pb-5 sm:px-5">
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
                  <div id="percrop" class="flex cursor-pointer items-center justify-between">
                   
                  </div>
                </div>
               <!--  <div class="pr-3 sm:pl-2">
                  <div x-init="$nextTick(() => { $el._x_chart = new ApexCharts($el,pages.charts.historyTransactions); $el._x_chart.render() });"></div>
                </div> -->
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
<script>
function loadPrices(period = 'daily') {
    fetch(`ajax/market_prices.php?period=${period}`)
        .then(res => res.text())
        .then(data => {
            document.getElementById("marketPrices").innerHTML = data;
        });
}
loadPrices();
</script>
<script>
function loadRequests() {
    fetch('ajax/buyer_requests.php')
        .then(res => res.text())
        .then(data => {
            document.getElementById("buyerRequests").innerHTML = data;
        });
}
loadRequests();
</script>
<script>
function percrop() {
    fetch('ajax/requestpercrop.php')
        .then(res => res.text())
        .then(data => {
            document.getElementById("percrop").innerHTML = data;
        });
}
percrop();
</script>

    <!-- 
        This is a place for Alpine.js Teleport feature 
        @see https://alpinejs.dev/directives/teleport
      -->
    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
  </body>
</html>
