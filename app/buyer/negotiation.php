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

// Optional: filter by status (all, pending, accepted, etc.)
$filter = $_GET['filter'] ?? 'all';
$where = "n.buyer_id = ?";
$params = [$buyer_id];
$types = "i";

if ($filter === 'pending') {
    $where .= " AND n.status = 'pending'";
} elseif ($filter === 'accepted') {
    $where .= " AND n.status = 'accepted'";
} elseif ($filter === 'rejected') {
    $where .= " AND n.status = 'rejected'";
}

// Fetch all negotiations for this buyer
$sql = "
    SELECT n.id, n.product_id, n.proposed_price, n.proposed_quantity, n.message, n.status, n.created_at,
           p.name AS product_name, p.price AS original_price, p.unit,
           f.name AS farmer_name, f.phone AS farmer_phone
    FROM negotiations n
    JOIN products p ON n.product_id = p.id
    JOIN users f ON n.farmer_id = f.id
    WHERE $where
    ORDER BY n.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$negotiations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Negotiations</title>
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
        <?php include 'negotiationsider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-6 flex flex-col items-center justify-between space-y-2 text-center sm:flex-row sm:space-y-0 sm:text-left">
          <div>
            <h3 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
              My Negotiations
            </h3>
            <p class="mt-1 hidden sm:block">List of Your ongoing negotiations</p>
          </div>
          <div class="inline-space mt-5 flex flex-wrap">
            <a href="?filter=all">
                <button class="btn rounded-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                  All
                </button>
              </a>
              <a href="?filter=pending">
                <button class="btn rounded-full bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                  Pending
                </button> 
                </a>
                <a href="?filter=accepted">               
                <button class="btn rounded-full bg-success font-medium text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90">
                  Accepted
                </button>
              </a>
              <a href="?filter=rejected">
                <button class="btn rounded-full bg-error font-medium text-white hover:bg-error-focus focus:bg-error-focus active:bg-error-focus/90">
                  Rejected
                </button>
              </a>
              </div>
        </div>
               <?php if (empty($negotiations)): ?>
      <div class="text-center py-18 bg-navy rounded-2xl shadow  border-gray-700 space-y-6 mb-3">
        <i class="fas fa-handshake text-7xl text-gray-300 mb-6"></i>
        <h2 class="text-2xl font-semibold text-gray-700 mb-3">No negotiations yet</h2>
        <p class="text-gray-500 mb-8">When you make an offer or receive a counter-offer, it will appear here.</p>
        <a href="products.php" class="inline-block bg-green-600 text-white px-8 py-4 rounded-xl hover:bg-green-700">
          Browse Marketplace
        </a>
      </div>
    <?php else: ?>
        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 lg:gap-6 xl:grid-cols-4">
   
          <?php foreach ($negotiations as $neg): ?>
          <div class="card rounded-2xl border border-slate-200 dark:border-navy-600 shadow-sm hover:shadow-md transition">

  <!-- HEADER -->
  <div class="p-4 border-b border-slate-100 dark:border-navy-600">
    <div class="flex justify-between items-start">

      <div>
        <h3 class="font-semibold text-slate-800 dark:text-navy-100">
          <?= htmlspecialchars($neg['product_name']) ?>
        </h3>
        <p class="text-xs text-slate-500 mt-1">
          <?= htmlspecialchars($neg['farmer_name']) ?>
        </p>
      </div>

      <span class="px-2 py-1 text-xs rounded-full
        <?= match($neg['status']) {
            'pending' => 'bg-warning/15 text-warning',
            'accepted' => 'bg-success/15 text-success',
            'rejected' => 'bg-error/15 text-error',
            default => 'bg-slate-100 text-slate-500'
        } ?>">
        <?= ucfirst($neg['status']) ?>
      </span>

    </div>
  </div>

  <!-- BODY -->
  <div class="p-4 space-y-3">

    <div class="flex justify-between text-sm">
  <span class="text-slate-400">Offered Price</span>
  <span class="text-slate-600 dark:text-navy-200 font-normal">
    <?= number_format($neg['proposed_price'], 0) ?> / <?= $neg['unit'] ?>
  </span>
</div>

<div class="flex justify-between text-sm">
  <span class="text-slate-400">Quantity</span>
  <span class="text-slate-600 dark:text-navy-200 font-normal">
    <?= number_format($neg['proposed_quantity']) ?> <?= $neg['unit'] ?>
  </span>
</div>

    <?php if (!empty($neg['message'])): ?>
      <p class="text-xs text-slate-500 bg-slate-50 dark:bg-navy-700 p-2 rounded-lg">
        <?= htmlspecialchars($neg['message']) ?>
      </p>
    <?php endif; ?>

  </div>

  <!-- FOOTER -->
  <div class="p-4 border-t border-slate-100 dark:border-navy-600 flex justify-between items-center">

    <span class="text-xs text-slate-400">
      <?= date('d M Y', strtotime($neg['created_at'])) ?>
    </span>

    <a href="negotiation-details.php?id=<?= $neg['id'] ?>"
       class="px-3 py-1.5 text-xs rounded-lg bg-primary text-white hover:bg-primary-focus transition">
      View details
    </a>

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
