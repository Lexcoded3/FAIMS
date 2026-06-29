<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'buyer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$buyer_id = $_SESSION['id'] ?? 0;  // ← make sure this is set during login

$sql = "SELECT 
            COUNT(*) as order_count, 
            COALESCE(SUM(amount), 0) as total_value 
        FROM orders 
        WHERE buyer_id = ? AND status IN ('pending', 'cancelled', 'completed')";  // adjust statuses as needed

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$activeOrdersCount = $row['order_count'] ?? 0;
$activeOrdersValue = $row['total_value'] ?? 0;

// You can add more queries for pending negotiations, wallet, etc.
// Example for pending negotiations (assuming you have negotiations table)
$sql_neg = "SELECT COUNT(*) as count FROM negotiations WHERE buyer_id = ? AND status = 'pending'";
$stmt_neg = $conn->prepare($sql_neg);
$stmt_neg->bind_param("i", $buyer_id);
$stmt_neg->execute();
$result_neg = $stmt_neg->get_result();
$row_neg = $result_neg->fetch_assoc();
$pendingNegotiations = $row_neg['count'] ?? 0;

// Fetch wallet data
$sql_wallet = "SELECT balance, held_balance FROM wallets WHERE user_id = ?";
$stmt_wallet = $conn->prepare($sql_wallet);
$stmt_wallet->bind_param("i", $buyer_id);
$stmt_wallet->execute();
$result_wallet = $stmt_wallet->get_result();
$row_wallet = $result_wallet->fetch_assoc();

$walletBalance = (float) ($row_wallet['balance'] ?? 0.00);
// Optional: $heldBalance = (float) ($row_wallet['held_balance'] ?? 0.00);

$stmt_wallet->close();

// Now pass to HTML
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
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="col-span-12 space-y-4 sm:space-y-5 lg:col-span-12 lg:space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5 lg:gap-6" x-data="{ 
           activeOrders: <?= $activeOrdersCount ?>, 
           activeValue: <?= $activeOrdersValue ?>,
           pendingNeg: <?= $pendingNegotiations ?>,
           wallet: <?= $walletBalance ?? 0 ?>,
           newMatches: <?= $newListingsToday ?? 0 ?>
         }">
              <div class="card bg-gradient-to-r from-blue-500 to-indigo-600 px-5 pb-5">
                <div>
                  <div class="ax-transparent-gridline mt-5 w-1/2">
                    <div x-init="$nextTick(() => { $el._x_chart = new ApexCharts($el,pages.charts.earningWhite); $el._x_chart.render() });"></div>
                  </div>
                  <p class="mt-3 text-base font-medium tracking-wide text-indigo-100">
                    Wallet Balance
                  </p>
                  <p class="mt-4 font-inter text-2xl font-semibold">
                    <span class="text-indigo-100">UGX</span>
                    <span class="text-white" x-text="wallet.toLocaleString()"></span>
                  </p>
                  <div class="badge mt-2 rounded-full bg-black/20 text-indigo-50">
                    13 Members
                  </div>
                </div>
                <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                  <img class="w-24 translate-x-1/4 translate-y-1/4 opacity-50" src="../images/illustrations/the-dollar.svg" alt="image">
                </div>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:col-span-2 sm:grid-cols-2 sm:gap-5 lg:gap-6">
                <div class="card justify-center p-4.5">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-base font-semibold text-slate-700 dark:text-navy-100" x-text=" activeOrders.toLocaleString()">
                        
                      </p>
                      <p class="text-xs+ line-clamp-1">Active Orders</p>
                    </div>
                    <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-success">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                      </svg>
                    </div>
                  </div>
                  <div>
                    <div class="badge mt-2 space-x-1 bg-success/10 py-1 px-1.5 text-success dark:bg-success/15">
                      <span>10%</span>
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewbox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                      </svg>
                    </div>
                  </div>
                </div>
                <div class="card justify-center p-4.5">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-base font-semibold text-slate-700 dark:text-navy-100" x-text="pendingNeg">
                        
                      </p>
                      <p class="text-xs+ line-clamp-1">Pending Negotiations</p>
                    </div>
                    <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-info">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                    </div>
                  </div>
                  <div>
                    <div class="badge mt-2 space-x-1 bg-success/10 py-1 px-1.5 text-success dark:bg-success/15">
                      <span>6%</span>
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewbox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                      </svg>
                    </div>
                  </div>
                </div>
                <div class="card justify-center p-4.5">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-base font-semibold text-slate-700 dark:text-navy-100" x-text="'UGX ' + activeValue.toLocaleString()">
                        
                      </p>
                      <p class="text-xs+ line-clamp-1">Active Order Value</p>
                    </div>
                    <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-secondary">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                      </svg>
                    </div>
                  </div>
                  <div>
                    <div class="badge mt-2 space-x-1 bg-success/10 py-1 px-1.5 text-success dark:bg-success/15">
                      <span>9%</span>
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewbox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                      </svg>
                    </div>
                  </div>
                </div>
                <div class="card justify-center p-4.5">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-base font-semibold text-slate-700 dark:text-navy-100" x-text="newMatches">
                        
                      </p>
                      <p class="text-xs+ line-clamp-1">New Matches Today</p>
                    </div>
                    <div class="mask is-star flex size-10 shrink-0 items-center justify-center bg-warning">
                      <svg class="size-5 text-white" viewbox="0 0 24 24" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.5293 18L20.9999 8.40002" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M3 13.2L7.23529 18L17.8235 6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                      </svg>
                    </div>
                  </div>
                  <div>
                    <div class="badge mt-2 space-x-1 bg-error/10 py-1 px-1.5 text-error dark:bg-error/15">
                      <span>6%</span>
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewbox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                      </svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- <div>
              <div class="flex items-center justify-between">
                <h2 class="text-sm+ font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Ongoing Projects
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
              <div class="mt-3 space-y-3.5">
                <div class="card p-3">
                  <div class="flex items-center space-x-3">
                    <img class="size-10 rounded-lg object-cover object-center" src="../../images/illustrations/lms-ui.svg" alt="image">
                    <div class="flex-1">
                      <div class="flex justify-between">
                        <p class="font-medium text-slate-700 dark:text-navy-100">
                          LMS App Design
                        </p>
                      </div>
                      <div class="mt-0.5 flex text-xs text-slate-400 dark:text-navy-300">
                        <p>Updated at 7 Sep</p>
                        <div class="mx-2 my-1 hidden w-px bg-slate-200 dark:bg-navy-500 sm:flex"></div>

                        <p class="hidden sm:flex">Deadline: 25.08.2020</p>
                      </div>
                    </div>
                  </div>
                  <p class="-mt-3 text-right text-xs font-medium text-primary dark:text-accent-light">
                    24%
                  </p>
                  <div class="progress mt-2 h-1.5 bg-slate-150 dark:bg-navy-500">
                    <div class="is-active relative w-4/12 overflow-hidden rounded-full bg-primary dark:bg-accent"></div>
                  </div>
                </div>
                <div class="card p-3">
                  <div class="flex items-center space-x-3">
                    <img class="size-10 rounded-lg object-cover object-center" src="../../images/illustrations/store-ui.svg" alt="image">
                    <div class="flex-1">
                      <div class="flex justify-between">
                        <p class="font-medium text-slate-700 dark:text-navy-100">
                          Store Dashboard
                        </p>
                      </div>
                      <div class="mt-0.5 flex text-xs text-slate-400 dark:text-navy-300">
                        <p>Updated a hour ago</p>
                        <div class="mx-2 my-1 hidden w-px bg-slate-200 dark:bg-navy-500 sm:flex"></div>

                        <p class="hidden sm:flex">Deadline: 21.08.2020</p>
                      </div>
                    </div>
                  </div>
                  <p class="-mt-3 text-right text-xs font-medium text-secondary dark:text-secondary-light">
                    56%
                  </p>

                  <div class="progress mt-2 h-1.5 bg-secondary/15 dark:bg-secondary-light/25">
                    <div class="w-6/12 rounded-full bg-secondary"></div>
                  </div>
                </div>
                <div class="card p-3">
                  <div class="flex items-center space-x-3">
                    <img class="size-10 rounded-lg object-cover object-center" src="../../images/illustrations/chat-ui.svg" alt="image">
                    <div class="flex-1">
                      <div class="flex justify-between">
                        <p class="font-medium text-slate-700 dark:text-navy-100">
                          Chat Mobile App
                        </p>
                      </div>
                      <div class="mt-0.5 flex text-xs text-slate-400 dark:text-navy-300">
                        <p>Updated 3 days ago</p>
                        <div class="mx-2 my-1 hidden w-px bg-slate-200 dark:bg-navy-500 sm:flex"></div>

                        <p class="hidden sm:flex">Deadline: 16.09.2020</p>
                      </div>
                    </div>
                  </div>
                  <p class="-mt-3 text-right text-xs font-medium text-warning">
                    64%
                  </p>

                  <div class="progress mt-2 h-1.5 bg-warning/15 dark:bg-warning/25">
                    <div class="w-7/12 rounded-full bg-warning"></div>
                  </div>
                </div>
                <div class="card p-3">
                  <div class="flex items-center space-x-3">
                    <img class="size-10 rounded-lg object-cover object-center" src="../../images/illustrations/nft.svg" alt="image">
                    <div class="flex-1">
                      <div class="flex justify-between">
                        <p class="font-medium text-slate-700 dark:text-navy-100">
                          NFT Marketplace App
                        </p>
                      </div>
                      <div class="mt-0.5 flex text-xs text-slate-400 dark:text-navy-300">
                        <p>Updated a week ago</p>
                        <div class="mx-2 my-1 hidden w-px bg-slate-200 dark:bg-navy-500 sm:flex"></div>

                        <p class="hidden sm:flex">Deadline: 26.11.2020</p>
                      </div>
                    </div>
                  </div>
                  <p class="-mt-3 text-right text-xs font-medium text-info">
                    14%
                  </p>

                  <div class="progress mt-2 h-1.5 bg-info/15 dark:bg-info/25">
                    <div class="w-2/12 rounded-full bg-info"></div>
                  </div>
                </div>
              </div>
            </div> -->

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
