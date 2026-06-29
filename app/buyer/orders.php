<?php
session_start();
$required_role = 'buyer'; // Only buyers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$buyer_id = $_SESSION['id'];

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = "o.buyer_id = ?";
$params = [$buyer_id];
$types = "i";

if ($filter === 'active') {
    $where .= " AND o.status IN ('pending', 'confirmed', 'processing')";
} elseif ($filter === 'completed') {
    $where .= " AND o.status IN ('delivered', 'completed', 'cancelled')";
}

// Fetch orders
$sql = "
    SELECT o.id, o.order_code, o.amount, o.status, o.created_at, 
           o.delivery_location, o.payment_status,
           COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Orders</title>
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
        <?php include 'orderssider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

       <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8 space-y-6">
        <div class="mt-6 flex flex-col items-center justify-between space-y-2 text-center sm:flex-row sm:space-y-0 sm:text-left ">
          <div>
            <h3 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
              My Orders
            </h3>
            <p class="mt-1 hidden sm:block">List of your ongoing projects</p>
          </div>
          <div class="flex -space-x-px mb-6">
            <div class="flex flex-wrap gap-3 mb-6">
      <a href="?filter=all" 
         class="px-5 py-2 rounded-full font-medium <?= $filter === 'all' ? 'bg-success text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
        All
      </a>
      <a href="?filter=active" 
         class="px-5 py-2 rounded-full font-medium <?= $filter === 'active' ? 'bg-success text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
        Active
      </a>
      <a href="?filter=completed" 
         class="px-5 py-2 rounded-full font-medium <?= $filter === 'completed' ? 'bg-success text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
        Completed
      </a>
    </div>
          </div>    
        </div>
        <div class="col-span-12 lg:col-span-8 space-y-6">
          <?php if (empty($orders)): ?>
      <div class="text-center py-8 bg-navy-700 rounded-2xl  border-navy-200">
        <i class="fas fa-box-open text-4xl text-gray-300 mb-6"></i>
        <h2 class="text-2xl font-semibold text-gray-700 mb-3">No orders yet</h2>
        <p class="text-gray-500 mb-6">When you place an order, it will appear here.</p>
        <a href="products.php" class="inline-block bg-success text-white px-6 py-1 rounded-xl hover:bg-success">
          Browse Marketplace
        </a>
      </div>
    <?php else: ?>
      <?php foreach ($orders as $order): ?>
            <div class="card bg-gradient-to-br from-purple-500 to-indigo-600 px-4 pb-4 sm:px-5">
              <!-- Header -->
            <div class="text-white bg-navy-50 px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b">
              <div class="flex w-9/12 items-center space-x-1">
                <h2 class="text-sm+ font-medium tracking-wide  line-clamp-1">
                  Order #<?= htmlspecialchars($order['order_code']) ?>
                </h2>
                <button class="btn size-5 shrink-0 rounded-full p-0 text-white hover:bg-white/20 focus:bg-white/20 active:bg-white/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewbox="0 0 20 20" fill="currentColor">
                        <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z"></path>
                        <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z"></path>
                      </svg>
                    </button>
                <p class="text-sm text-gray-500">
                  Placed on <?= date('d M Y • H:i', strtotime($order['created_at'])) ?>
                </p>
              </div>
              <div class="flex items-center gap-3">
                <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                             <?= match($order['status']) {
                                 'pending'     => 'bg-warning text-warning-800',
                                 'confirmed'   => 'bg-primary text-blue-800',
                                 'processing'  => 'bg-purple-100 text-purple-800',
                                 'delivered'   => 'bg-success text-green-800',
                                 'completed'   => 'bg-success text-white',
                                 'cancelled'   => 'bg-error text-red-800',
                                 default       => 'bg-navy text-gray-800'
                             } ?>">
                  <?= ucfirst($order['status']) ?>
                </span>
                <?php if ($order['payment_status'] === 'paid'): ?>
                  <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full bg-success text-white">
                    Paid
                  </span>
                <?php endif; ?>
              </div>
            </div>
              <div class="flex items-center justify-between py-3 text-white">
                <h2 class="text-sm+ font-medium tracking-wide">Items: <?= $order['item_count'] ?></h2>
                <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn -mr-1.5 size-8 rounded-full p-0 hover:bg-white/20 focus:bg-white/20 active:bg-white/25">
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
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:gap-6">
                <div>
                  <div class="mt-3 text-3xl font-semibold text-white">
                    UGX <?= number_format($order['amount'], 0) ?>
                  </div>
                  <p class="mt-3 text-xs+ text-indigo-100"><?= htmlspecialchars($order['delivery_location'] ?: 'Not specified') ?></p>
                </div>

                <div class="grid grid-cols-3 gap-4 sm:gap-5 lg:gap-6">
                  <div>
                    <!-- <p class="text-indigo-100">Income</p>
                    <div class="mt-1 flex items-center space-x-2">
                      <div class="flex size-7 items-center justify-center rounded-full bg-navy/20 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                        </svg>
                      </div>
                      <p class="text-base font-medium text-white">$2,225.22</p>
                    </div> -->
                    <a href="order_details.php?id=<?= $order['id'] ?>" >
                    <button class="btn mt-3 space-x-2 w-full bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
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
                          d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"
                        />
                      </svg>
                      <span>Details</span>
                    </button>
                  </a>
                  </div>
                  <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                  <div>
                    <!-- <p class="text-indigo-100">Income</p>
                    <div class="mt-1 flex items-center space-x-2">
                      <div class="flex size-7 items-center justify-center rounded-full bg-black/20 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                        </svg>
                      </div>
                      <p class="text-base font-medium text-white">$2,225.22</p>
                    </div> -->

                    <button class="btn mt-3 w-full space-x-2 w-full bg-success font-medium text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90">
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
                         d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"
                        />
                      </svg>
                      <span>Pay</span>
                    </button>
                  </div>
                  <?php endif; ?>
                  <?php if (in_array($order['status'], ['pending'])): ?>
                  <div>
                    <!-- <p class="text-indigo-100">Expense</p>
                    <div class="mt-1 flex items-center space-x-2">
                      <div class="flex size-7 items-center justify-center rounded-full bg-black/20 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                        </svg>
                      </div>
                      <p class="text-base font-medium text-white">$225.22</p>
                    </div> -->
                    <button class="btn mt-3 w-full space-x-2 w-full bg-error font-medium text-white hover:bg-error-focus focus:bg-error-focus active:bg-error-focus/90">
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
                         d="M6 18 18 6M6 6l12 12"
                        />
                      </svg>
                      <span>Cancel</span>
                    </button>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
           <?php endif; ?>
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
