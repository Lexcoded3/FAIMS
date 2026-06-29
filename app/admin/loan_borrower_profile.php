<?php
session_start();
$required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'extension'])) {
    header('Location: ../../login.php');
    exit;
}

// Get farmer ID from URL
$farmer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($farmer_id <= 0) {
    die("Invalid farmer ID");
}

// Fetch farmer basic info
$farmer_stmt = $conn->prepare("
    SELECT id, name, phone, location, image_paths AS avatar, created_at
    FROM users
    WHERE id = ? AND role = 'farmer'
");
$farmer_stmt->bind_param("i", $farmer_id);
$farmer_stmt->execute();
$farmer = $farmer_stmt->get_result()->fetch_assoc();

if (!$farmer) {
    die("Farmer not found");
}

// Summary stats
$stats_sql = "
    SELECT 
        COUNT(l.id) AS total_loans,
        COALESCE(SUM(l.requested_amount), 0) AS total_borrowed,
        COALESCE(SUM(l.total_paid), 0) AS total_repaid,
        COALESCE(SUM(l.total_repayable), 0) AS total_due,
        CASE 
            WHEN COALESCE(SUM(l.total_repayable), 0) > 0 
            THEN ROUND((COALESCE(SUM(l.total_paid), 0) / SUM(l.total_repayable)) * 100, 1)
            ELSE 0 
        END AS repayment_percent,
        SUM(CASE WHEN l.status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count,
        SUM(CASE WHEN l.status IN ('disbursed', 'active') THEN 1 ELSE 0 END) AS active_loans
    FROM loans l
    WHERE l.farmer_id = ?
";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $farmer_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// All loans for this farmer
$loans_sql = "
    SELECT 
        l.id,
        l.requested_amount,
        l.approved_amount,
        l.application_date,
        l.status,
        l.purpose,
        lp.name AS product_name,
        l.total_repayable,
        l.total_paid,
        ROUND((l.total_paid / l.total_repayable) * 100, 1) AS progress_percent
    FROM loans l
    JOIN loan_products lp ON l.product_id = lp.id
    WHERE l.farmer_id = ?
    ORDER BY l.application_date DESC
";
$loans_stmt = $conn->prepare($loans_sql);
$loans_stmt->bind_param("i", $farmer_id);
$loans_stmt->execute();
$loans_result = $loans_stmt->get_result();

// Repayment history (all payments for this farmer's loans)
$payments_sql = "
    SELECT 
        r.id,
        r.amount_paid,
        r.payment_date,
        r.payment_method,
        r.receipt_number,
        r.notes,
        r.created_at,
        l.id AS loan_id,
        lp.name AS product_name
    FROM loan_repayments r
    JOIN loans l ON r.loan_id = l.id
    JOIN loan_products lp ON l.product_id = lp.id
    WHERE l.farmer_id = ?
    ORDER BY r.payment_date DESC, r.created_at DESC
";
$payments_stmt = $conn->prepare($payments_sql);
$payments_stmt->bind_param("i", $farmer_id);
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
  <head> 
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Loan Borrowers</title>
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

  <body x-data="" x-bind="$store.global.documentBody">
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
        <?php include 'loanssider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

       <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="flex items-center space-x-4 py-5 lg:py-6">
          <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">
            Borrower
          </h2>
          <div class="hidden h-full py-1 sm:flex">
            <div class="h-full w-px bg-slate-300 dark:bg-navy-600"></div>
          </div>
          <ul class="hidden flex-wrap items-center space-x-2 sm:flex">
            <li class="flex items-center space-x-2">
              <a class="text-primary transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent" href="#">Details</a>
              <svg x-ignore="" xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </li>
            <li><?= htmlspecialchars($farmer['name']) ?></li>
          </ul>
        </div>

        <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
          <div class="col-span-12 lg:col-span-4">
            <div class="card">
            <div class="h-24 rounded-t-lg bg-primary dark:bg-accent">
              <img class="h-full w-full rounded-t-lg object-cover object-center" src="../images/object/object-2.jpg" alt="image">
            </div>
            <div class="px-4 py-2 sm:px-5">
              <div class="flex justify-between space-x-4">
                <div class="avatar -mt-12 size-20">
                  <img class="rounded-full border-2 border-white dark:border-navy-700" src="../images/avatar/avatar-4.jpg" alt="avatar">
                </div>
                <div class="flex space-x-2">
                  <button class="btn h-7 w-7 rounded-full bg-primary/10 p-0 text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:bg-accent-light/10 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25">
                    <i class="fab fa-twitter"></i>
                  </button>
                  <button class="btn h-7 w-7 rounded-full bg-primary/10 p-0 text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:bg-accent-light/10 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25">
                    <i class="fab fa-instagram text-base"></i>
                  </button>
                  <button class="btn h-7 w-7 rounded-full bg-primary/10 p-0 text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:bg-accent-light/10 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25">
                    <i class="fab fa-facebook-f"></i>
                  </button>
                </div>
              </div>
              <h3 class="pt-2 text-lg font-medium text-slate-700 dark:text-navy-100">
                <?= htmlspecialchars($farmer['name']) ?>
              </h3>
              <p class="text-xs"><?= htmlspecialchars($farmer['location'] ?? 'Not specified') ?>, UG   (Member Since - <?= date('M Y', strtotime($farmer['created_at'])) ?>)</p>
               <div class="space-y-3 text-xs+">
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Phone 
                      </p>
                      <p class="text-right"><?= htmlspecialchars($farmer['phone'] ?? '—') ?></p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Total Borrowed
                      </p>
                      <p class="text-right">UGX <?= number_format($stats['total_borrowed']) ?></p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Total Repaid
                      </p>
                      <p class="text-right">UGX <?= number_format($stats['total_repaid']) ?></p>
                    </div>
                    <div class="flex justify-between">
                      <p class="font-medium text-slate-700 dark:text-navy-100">
                        Repayment %
                      </p>
                      <p class="text-right <?= $stats['repayment_percent'] >= 90 ? 'text-success' : ($stats['repayment_percent'] >= 70 ? 'text-warning' : 'text-error') ?>">
                        <?= number_format($stats['repayment_percent'], 1) ?>%</p>
                    </div>
                  </div>
              <div class="flex items-center space-x-4 pt-2">
                <div class="w-9/12">
                  <div class="ax-transparent-gridline" x-init="$nextTick(() => { $el._x_chart = new ApexCharts($el,pages.charts.cardUser1); $el._x_chart.render() });"></div>
                </div>
                <div class="w-3/12 text-center">
                  
                  <p class="text-xs+ text-slate-800 dark:text-navy-50 text-left">Loans: <?= $stats['total_loans'] ?></p>
                  <p class="text-xs+ text-left <?= $stats['overdue_count'] > 0 ? 'text-error' : 'text-slate-800 dark:text-navy-50' ?>">Due: <?= $stats['overdue_count'] ?></p>
                </div>
              </div>
              <div class="flex justify-center space-x-3 py-3">
                <button class="btn size-9 rounded-full bg-slate-150 p-0 font-medium text-slate-800 hover:bg-slate-200 hover:shadow-lg hover:shadow-slate-200/50 focus:bg-slate-200 focus:shadow-lg focus:shadow-slate-200/50 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:hover:shadow-navy-450/50 dark:focus:bg-navy-450 dark:focus:shadow-navy-450/50 dark:active:bg-navy-450/90" x-tooltip="'Call'">
                  <i class="fa fa-phone text-xs+ text-info"></i>
                </button>
                <button class="btn size-9 rounded-full bg-slate-150 p-0 font-medium text-slate-800 hover:bg-slate-200 hover:shadow-lg hover:shadow-slate-200/50 focus:bg-slate-200 focus:shadow-lg focus:shadow-slate-200/50 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:hover:shadow-navy-450/50 dark:focus:bg-navy-450 dark:focus:shadow-navy-450/50 dark:active:bg-navy-450/90" x-tooltip="'Message'">
                  <i class="fa-solid fa-comment-dots"></i>
                </button>
                <button class="btn size-9 rounded-full bg-slate-150 p-0 font-medium text-slate-800 hover:bg-slate-200 hover:shadow-lg hover:shadow-slate-200/50 focus:bg-slate-200 focus:shadow-lg focus:shadow-slate-200/50 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:hover:shadow-navy-450/50 dark:focus:bg-navy-450 dark:focus:shadow-navy-450/50 dark:active:bg-navy-450/90" x-tooltip="'Reminder'">
                  <i class="fa fa-bell text-xs+ text-success"></i>
                </button>
              </div>
            </div>
          </div>
          </div>
          <div class="col-span-12 lg:col-span-8">
            <div class="flex flex-col gap-4 lg:gap-6">
            <div class="card">
              <div class="flex flex-col items-center space-y-4 border-b border-slate-200 p-4 dark:border-navy-500 sm:flex-row sm:justify-between sm:space-y-0 sm:px-5">
                <h2 class="text-lg font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  All Loans
                </h2>
              </div>
              <div class="p-4 sm:p-5">
                <div class="flex flex-col">                  
                   <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                  <table class="is-hoverable w-full text-left">
                    <thead>
                      <tr>
                        <th
                          class="whitespace-nowrap rounded-l-lg bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                        >
                          #
                        </th>
                        <th
                          class="whitespace-nowrap rounded-l-lg bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                        >
                          Product
                        </th>
                        <th
                          class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                        >
                          Amount
                        </th>
                        <th
                          class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                        >
                          Status
                        </th>
                        <th
                          class="whitespace-nowrap rounded-r-lg bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                        >
                          Applied
                        </th>
                        <th
                          class="whitespace-nowrap rounded-r-lg bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                        >
                          Action
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                        <?php $index = 1; while ($loan = $loans_result->fetch_assoc()): ?>
                        <tr class="border border-transparent border-b-slate-200 dark:border-b-navy-500">
                           <td class="whitespace-nowrap rounded-l-lg px-4 py-3 sm:px-5"><?= $index++ ?>
                           </td>
                          <td class="whitespace-nowrap px-4 py-3 sm:px-5"><?= htmlspecialchars($loan['product_name']) ?>
                          </td>
                          <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                            UGX <?= number_format($loan['requested_amount']) ?>
                    <?php if ($loan['approved_amount'] && $loan['approved_amount'] != $loan['requested_amount']): ?><br>
                      <span class="text-xs text-slate-500">(Appr: <?= number_format($loan['approved_amount']) ?>)</span>
                    <?php endif; ?>
                          </td>
                          <td class="whitespace-nowrap px-4 py-3 sm:px-5">
    <?php
    // Assign Tailwind classes based on the ENUM status
    $status_style = match ($loan['status']) {
        'pending'       => 'bg-slate-100 text-slate-500 dark:bg-navy-500 dark:text-navy-100',
        'under_review'  => 'bg-info/10 text-info',
        'approved'      => 'bg-primary/10 text-primary dark:text-accent-light',
        'rejected'      => 'bg-error/10 text-error',
        'disbursed'     => 'bg-secondary/10 text-secondary',
        'active'        => 'bg-success/10 text-success',
        'overdue'       => 'bg-warning/10 text-warning',
        'repaid'        => 'bg-success text-white', // Solid green for completion
        'defaulted', 
        'written_off'   => 'bg-error text-white',   // Solid red for loss
        default         => 'bg-slate-100 text-slate-700',
    };
    ?>
    
    <span class="badge shrink-0 rounded-full px-2.5 py-1 text-xs+ font-medium <?= $status_style ?>">
        <?= str_replace('_', ' ', ucfirst($loan['status'])) ?>
    </span>
</td>

                          <td class="whitespace-nowrap rounded-r-lg px-4 py-3 sm:px-5">
                          <?= date('d M Y', strtotime($loan['application_date'])) ?>
                          </td>
                          <td class="whitespace-nowrap px-6 py-4 text-right">
                    <a href="loan-view.php?id=<?= $loan['id'] ?>" class="text-primary hover:underline">View</a>
                  </td>
                        </tr>
                         <?php endwhile; ?>
                              <?php if ($payments_result->num_rows === 0): ?>
                                No repayments recorded for this farmer.
                                <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
               
                </div>
              </div>
            </div>
            <div class="card">
              <!-- Header remains the same -->
              <div class="flex flex-col items-center space-y-4 border-b border-slate-200 p-4 dark:border-navy-500 sm:flex-row sm:justify-between sm:space-y-0 sm:px-5">
                <h2 class="text-lg font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Repayment History
                </h2>
              </div>

              <div class="p-4 sm:p-5">
                <!-- Removed 'overflow-x-auto' to prevent scrolling; table will now try to fit the container -->
                <div class="min-w-full">
                  <!-- Added 'table-fixed' to force columns to respect the container width -->
                  <table class="w-full text-left table-fixed">
                    <thead>
                      <tr>
                        <!-- Assigned specific widths to columns to ensure the most important data has room -->
                        <th class="w-4/12 whitespace-nowrap rounded-l-lg bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Date
                        </th>
                        <th class="w-3/12 whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Loan
                        </th>
                        <th class="w-3/12 whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Amount
                        </th>
                        <th class="w-2/12 whitespace-nowrap rounded-r-lg bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          Receipt
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ($payments_result->num_rows > 0): ?>
                        <?php while ($payment = $payments_result->fetch_assoc()): ?>
                          <tr class="border-b border-slate-200 transition-colors hover:bg-slate-50 dark:border-navy-500 dark:hover:bg-navy-600">
                            <!-- 'truncate' ensures that if text is too long, it shows '...' instead of breaking the layout -->
                            <td class="px-4 py-3 sm:px-5 truncate text-xs+">
                              <?= date('d M y H:i', strtotime($payment['payment_date'])) ?>
                            </td>
                            <td class="px-4 py-3 sm:px-5 truncate font-medium text-slate-700 dark:text-navy-100">
                              <?= htmlspecialchars($payment['product_name']) ?>
                            </td>
                            <td class="px-4 py-3 sm:px-5 truncate text-success font-bold">
                              UGX <?= number_format($payment['amount_paid']) ?>
                            </td>
                            <td class="px-4 py-3 sm:px-5 truncate text-slate-500">
                              <?= htmlspecialchars($payment['receipt_number'] ?? '—') ?>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="4" class="py-12 text-center text-slate-400">No repayments recorded.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          </div>
        </div>
      </main>

    
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

