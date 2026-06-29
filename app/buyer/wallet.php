<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'buyer'; // Only buyer allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}
$buyer_id = $_SESSION['id'];

// Get or create wallet
$sql_wallet = "SELECT * FROM wallets WHERE user_id = ?";
$stmt_wallet = $conn->prepare($sql_wallet);
$stmt_wallet->bind_param("i", $buyer_id);
$stmt_wallet->execute();
$wallet = $stmt_wallet->get_result()->fetch_assoc();
$stmt_wallet->close();

if (!$wallet) {
    // Create wallet
    $sql_create = "INSERT INTO wallets (user_id, balance, held_balance) VALUES (?, 0.00, 0.00)";
    $stmt_create = $conn->prepare($sql_create);
    $stmt_create->bind_param("i", $buyer_id);
    $stmt_create->execute();
    $wallet_id = $conn->insert_id;
    $stmt_create->close();

    // Reload wallet
    $sql_wallet = "SELECT * FROM wallets WHERE user_id = ?";
    $stmt_wallet = $conn->prepare($sql_wallet);
    $stmt_wallet->bind_param("i", $buyer_id);
    $stmt_wallet->execute();
    $wallet = $stmt_wallet->get_result()->fetch_assoc();
    $stmt_wallet->close();
}

$balance = $wallet['balance'] ?? 0.00;
$held = $wallet['held_balance'] ?? 0.00;
$available = $balance - $held;  // For display

// Recent transactions
$sql_tx = "
    SELECT * FROM wallet_transactions 
    WHERE wallet_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
";
$stmt_tx = $conn->prepare($sql_tx);
$stmt_tx->bind_param("i", $wallet['id']);
$stmt_tx->execute();
$transactions = $stmt_tx->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_tx->close();

// Pending/unpaid orders (for quick pay)
$sql_pending = "
    SELECT id, order_code, amount, payment_status, created_at
    FROM orders 
    WHERE buyer_id = ? AND payment_status != 'paid'
    ORDER BY created_at DESC
";
$stmt_pending = $conn->prepare($sql_pending);
$stmt_pending->bind_param("i", $buyer_id);
$stmt_pending->execute();
$pending_orders = $stmt_pending->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_pending->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>Agriconnect - Wallet & Payments</title>
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
        <?php include 'walletsider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="col-span-12 grid grid-cols-12 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 py-5 sm:py-6">
            <div class="col-span-12 sm:col-span-6 lg:col-span-4">
              <div class="px-4 text-white sm:px-5">
                <div class="-mt-1 flex items-center space-x-2">
                  <h2 class="text-base font-medium tracking-wide">Balance</h2>
                  <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                    <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-white/20 focus:bg-white/20 active:bg-white/25">
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
                  <p class="text-2xl font-semibold">UGX <?= number_format($balance, 0) ?></p>
                  <p class="text-xs">+ 3.5%</p>
                </div>

                <div class="mt-4 flex space-x-7">
                  <div>
                    <p class="text-indigo-100">Available</p>
                    <div class="mt-1 flex items-center space-x-2">
                      <div class="flex size-7 items-center justify-center rounded-full bg-black/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                        </svg>
                      </div>
                      <p class="text-base font-medium"><?= number_format($available, 0) ?> </p>
                    </div>
                  </div>
                  <div>
                    <p class="text-indigo-100">Held</p>
                    <div class="mt-1 flex items-center space-x-2">
                      <div class="flex size-7 items-center justify-center rounded-full bg-black/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                        </svg>
                      </div>
                      <p class="text-base font-medium"><?= number_format($held, 0) ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
                        <?php
              // Fetch recent deposits only (latest first, limit 10)
              $sql_deposits = "
                  SELECT id, amount, provider, created_at, type
                  FROM wallet_transactions
                  WHERE wallet_id = ? AND type = 'deposit'
                  ORDER BY created_at DESC
                  LIMIT 10
              ";
              $stmt_deposits = $conn->prepare($sql_deposits);
              $stmt_deposits->bind_param("i", $wallet['id']);
              $stmt_deposits->execute();
              $deposits = $stmt_deposits->get_result()->fetch_all(MYSQLI_ASSOC);
              $stmt_deposits->close();
              ?>
            <div class="col-span-12 mt-5 sm:col-span-6 sm:mt-0 lg:col-span-6">
              <div class="swiper px-5 sm:pl-0" x-init="$nextTick(()=>new Swiper($el,{  slidesPerView: 'auto', spaceBetween: 16}))">
                <div class="-mt-1 flex items-center space-x-2">
                  <h2 class="text-base font-medium tracking-wide">Recent Deposits</h2>
                  <!-- <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                    <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-white/20 focus:bg-white/20 active:bg-white/25">
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
                <div class="swiper-wrapper">
                   <?php foreach ($deposits as $dep): ?>
                    <?php
                      $provider = $dep['provider'] ?? 'other';
                      switch ($provider) {
                        case 'mtn_momo':
                          $display = 'MTN MoMo';
                          $color = 'bg-warning';
                          $icon = 'fa-mobile-alt';
                          break;
                        case 'airtel_momo':
                          $display = 'Airtel MoMo';
                          $color = 'bg-error';
                          $icon = 'fa-mobile-alt';
                          break;
                        case 'bank_transfer':
                          $display = 'Bank Transfer';
                          $color = 'bg-gradient-to-br from-blue-500 to-blue-600';
                          $icon = 'fa-university';
                          break;
                        default:
                          $display = 'Deposit';
                          $color = 'bg-gradient-to-br from-gray-500 to-gray-600';
                          $icon = 'fa-money-bill-wave';
                      }
                    ?>

                  <div class="swiper-slide relative h-40 w-64 shrink-0 rounded-lg <?= $color ?>">
                    <div class="absolute inset-0 flex flex-col justify-between rounded-lg border border-white/10 p-5">
                      <div class="flex items-center justify-between">
                        <i class="fas <?= $icon ?> text-2xl text-white"></i>
                        <span class="text-xs text-white"><?= $display ?></span>
                      </div>
                      <div class="text-white">
                        <p class="text-lg font-semibold tracking-wide">
                          + UGX <?= number_format($dep['amount'], 0) ?>
                        </p>
                        <p class="mt-1 text-xs font-medium">
                          <?= date('d M Y • H:i', strtotime($dep['created_at'])) ?>
                        </p>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="card group col-span-12 pb-5 lg:col-span-8">
            <div class="my-3 flex flex-col justify-between px-4 sm:flex-row sm:items-center sm:px-5">
              <div class="flex flex-1 items-center justify-between space-x-2 sm:flex-initial">
                <h2 class="text-sm+ font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  History
                </h2>
                <div x-data="usePopper({placement:'bottom-start',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" :class="!isShowPopper && 'sm:opacity-0'" class="inline-flex focus-within:opacity-100 group-hover:opacity-100">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
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
              <!-- <div class="flex items-center space-x-4">
                <div class="flex cursor-pointer items-center space-x-2">
                  <div class="size-3 rounded-full bg-accent"></div>
                  <p>Sales</p>
                </div>
                <div class="flex cursor-pointer items-center space-x-2">
                  <div class="size-3 rounded-full bg-info"></div>
                  <p>Profit</p>
                </div>
              </div> -->
            </div>

            <div class="grid grid-cols-12 gap-4 px-4 sm:gap-5 sm:px-5 lg:gap-6 lg:px-5">
              <div class="col-span-12 sm:order-last sm:col-span-4 sm:mt-2 xl:col-span-7">
                <!-- <div class="ax-transparent-gridline">
                  <div x-init="$nextTick(() => { $el._x_chart = new ApexCharts($el,pages.charts.historyTransactionsLine); $el._x_chart.render() });"></div>
                </div> -->
              </div>
              <div class="col-span-12 rounded-lg bg-slate-50 p-4 dark:bg-navy-600 sm:col-span-6 xl:col-span-5">
                <div class="space-y-4">
                  <?php foreach ($transactions as $tx): ?>
                  <div class="flex cursor-pointer items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <!-- <div class="avatar">
                        <img class="rounded-full" src="../images/avatar/avatar-20.jpg" alt="avatar">
                      </div> -->
                      <div>
                        <p class="text-slate-700 line-clamp-1 dark:text-navy-100">
                          <?= ucfirst($tx['type']) ?>
                      <?php if ($tx['reference_type'] === 'order'): ?>
                        - Order #<?= htmlspecialchars($tx['reference_id'] ?? 'N/A') ?>
                      <?php endif; ?>
                        </p>
                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-200">
                          <?= date('d M Y • H:i', strtotime($tx['created_at'])) ?>
                        </p>
                      </div>
                    </div>
                    <p class="font-medium <?= $tx['amount'] > 0 ? 'text-success' : 'text-error' ?>">                      
                    <?= $tx['amount'] > 0 ? '+' : '-' ?><?= number_format(abs($tx['amount']), 0) ?>
                    </p>
                  </div>
                  <?php endforeach; ?>                  
                  <!-- <div class="flex cursor-pointer items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div class="avatar">
                        <img class="rounded-full" src="../images/avatar/avatar-11.jpg" alt="avatar">
                      </div>
                      <div>
                        <p class="text-slate-700 line-clamp-1 dark:text-navy-100">
                          Kartina West
                        </p>
                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-200">
                          Dec 13, 2021 - 11:30
                        </p>
                      </div>
                    </div>
                    <p class="font-medium text-error">$547.63</p>
                  </div>
                  <div class="flex cursor-pointer items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div class="avatar">
                        <img class="rounded-full" src="../images/avatar/avatar-7.jpg" alt="avatar">
                      </div>
                      <div>
                        <p class="text-slate-700 line-clamp-1 dark:text-navy-100">
                          Samantha Shelton
                        </p>
                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-200">
                          Dec 10, 2021 - 09:41
                        </p>
                      </div>
                    </div>
                    <p class="font-medium text-success">$736.24</p>
                  </div>
                  <div class="flex cursor-pointer items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div class="avatar">
                        <img class="rounded-full" src="../images/avatar/avatar-5.jpg" alt="avatar">
                      </div>
                      <div>
                        <p class="text-slate-700 line-clamp-1 dark:text-navy-100">
                          Joe Perkins
                        </p>
                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-200">
                          Dec 06, 2021 - 11:41
                        </p>
                      </div>
                    </div>
                    <p class="font-medium text-success">$558.88</p>
                  </div>
                  <div class="flex cursor-pointer items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div class="avatar">
                        <img class="rounded-full" src="../images/avatar/avatar-19.jpg" alt="avatar">
                      </div>
                      <div>
                        <p class="text-slate-700 line-clamp-1 dark:text-navy-100">
                          Henry Curtis
                        </p>
                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-200">
                          Dec 19, 2021 - 11:55
                        </p>
                      </div>
                    </div>
                    <p class="font-medium text-success">$33.63</p>
                  </div>
                  <div class="flex cursor-pointer items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div class="avatar">
                        <img class="rounded-full" src="../images/avatar/avatar-18.jpg" alt="avatar">
                      </div>
                      <div>
                        <p class="text-slate-700 line-clamp-1 dark:text-navy-100">
                          Derrick Simmons
                        </p>
                        <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-200">
                          Dec 16, 2021 - 14:45
                        </p>
                      </div>
                    </div>
                    <p class="font-medium text-success">$674.63</p>
                  </div> -->
                </div>
              </div>
            </div>
          </div>

          <!-- <div class="card col-span-12 px-4 pb-5 sm:px-5 lg:col-span-4">
            <div class="flex items-center justify-between py-3">
              <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                Send Money
              </h2>
              <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn -mr-1 size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
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
            <div class="flex space-x-2">
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

              <div class="avatar size-8 hover:z-10">
                <img class="rounded-full ring ring-white dark:ring-navy-700" src="../images/avatar/avatar-19.jpg" alt="avatar">
              </div>
            </div>
            <div class="mt-2 flex items-center justify-between">
              <p class="text-xs+">View All Contacts</p>

              <button class="btn -mr-1 size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
              </button>
            </div>
            <div class="mt-2 space-y-4">
              <label class="block">
                <span class="text-xs+">Pay to</span>
                <input x-input-mask="{
                          creditCard: true
                      }" class="form-input mt-1.5 h-9 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="**** **** **** ****" type="text">
              </label>
              <div>
                <span class="text-xs+">Amount</span>

                <div class="mt-1.5 flex h-9 -space-x-px">
                  <select class="form-select rounded-l-lg border border-slate-300 bg-white px-3 py-2 pr-9 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-700 dark:hover:border-navy-400 dark:focus:border-accent">
                    <option>$</option>
                    <option>£</option>
                    <option>€</option>
                  </select>
                  <input class="form-input w-full rounded-r-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Price" type="text">
                </div>
              </div>
            </div>
            <div class="mt-5 flex justify-between text-slate-400 dark:text-navy-300">
              <p class="text-xs+">Commission:</p>
              <p>3$</p>
            </div>
            <div class="mt-2 flex justify-between">
              <p>Total:</p>
              <p class="font-medium text-slate-700 dark:text-navy-100">3$</p>
            </div>
            <button class="btn mt-5 h-10 w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
              Send Money
            </button>
          </div> -->
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
  </body>
</html>
