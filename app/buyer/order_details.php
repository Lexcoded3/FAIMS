<?php
session_start();
$required_role = 'buyer'; // Only buyers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

$buyer_id  = $_SESSION['id'];
$order_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header("Location: orders.php?error=invalid");
    exit;
}

// Fetch order header
$sql = "
    SELECT * FROM orders 
    WHERE id = ? AND buyer_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $buyer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: orders.php?error=notfound");
    exit;
}

// Fetch order items
$sql_items = "
    SELECT oi.*, p.name AS product_name, p.unit
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Order Details</title>
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
            <p class="mt-1 hidden sm:block">Order #<?= htmlspecialchars($order['order_code']) ?></p>
          </div>  
        </div>
        <div class="col-span-12 lg:col-span-8 space-y-6">
          <div class="bg-navy-700 rounded-2xl border-gray-200 overflow-hidden">

      <!-- Status Banner -->
      <div class="bg-success text-white px-6 py-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="text-2xl font-bold">Status: <?= ucfirst($order['status']) ?></h2>
            <p class="mt-1 opacity-90">
              Placed on <?= date('d M Y • H:i', strtotime($order['created_at'])) ?>
            </p>
          </div>
          <div class="text-right">
            <p class="text-3xl font-bold">
              UGX <?= number_format($order['amount'], 0) ?>
            </p>
            <p class="text-sm opacity-90">Total</p>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="p-2">
        <h3 class="text-xl font-semibold mb-3">Order Items</h3>
        <div class="space-y-4">
          <?php foreach ($items as $item): ?>
            <div class="flex gap-5 pb-5 last:border-b-0 last:pb-0">
              <div class="w-24 h-24 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden">
                <!-- Placeholder image; replace with real product image if linked -->
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                  <i class="fas fa-seedling text-4xl"></i>
                </div>
              </div>
              <div class="flex-1">
                <h4 class="font-semibold text-lg"><?= htmlspecialchars($item['product_name']) ?></h4>
                <p class="text-sm text-gray-600 mt-1">
                  <?= number_format($item['quantity']) ?> × UGX <?= number_format($item['unit_price'], 0) ?>
                </p>
                <p class="font-bold text-green-700 mt-2">
                  Subtotal: UGX <?= number_format($item['subtotal'], 0) ?>
                </p>
              </div>
            </div>
            <div class="flex -space-x-px">
              <button
                class="btn rounded-l-full rounded-r-none border border-primary font-medium text-primary hover:bg-primary hover:text-white focus:bg-primary focus:text-white active:bg-primary/90"
              >
                Pay Now
              </button>
              <button
                class="btn rounded-l-none rounded-r-full border border-error font-medium text-error hover:bg-error hover:text-white focus:bg-error focus:text-white active:bg-error/90"
              >
                Cancel Order
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Delivery & Payment Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-gray-50 border-t">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="text-left">
          <h4 class="font-semibold mb-3">Delivery Information</h4>
          <p><strong>Location:</strong> <?= htmlspecialchars($order['delivery_location'] ?: 'Not specified') ?></p>
          <?php if ($order['delivery_window_start']): ?>
            <p><strong>Window:</strong> 
              <?= date('d M Y', strtotime($order['delivery_window_start'])) ?> – 
              <?= date('d M Y', strtotime($order['delivery_window_end'])) ?>
            </p>
          <?php endif; ?>
        </div>
        <div class="text-right">
          <h4 class="font-semibold mb-3">Payment</h4>
          <p><strong>Status:</strong> <?= ucfirst($order['payment_status']) ?></p>
          <p><strong>Method:</strong> <?= ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'mobile_money')) ?></p>
        </div>
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
  </body>
</html>
