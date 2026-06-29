<?php
session_start();
$required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';


if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin'])) {
    header('Location: ../../login.php');
    exit;
}

// Date range filter (default: last 12 months)
$from_date = $_GET['from_date'] ?? date('Y-m-01', strtotime('-12 months'));
$to_date   = $_GET['to_date'] ?? date('Y-m-d');

// Main stats
$stats_sql = "
    SELECT 
        (SELECT COALESCE(SUM(approved_amount), 0) FROM loans WHERE status IN ('disbursed','active','overdue','repaid') AND application_date BETWEEN ? AND ?) AS total_disbursed,
        (SELECT COALESCE(SUM(total_paid), 0) FROM loan_repayments r JOIN loans l ON r.loan_id = l.id WHERE r.payment_date BETWEEN ? AND ?) AS total_repaid,
        (SELECT COALESCE(SUM(total_repayable - total_paid), 0) FROM loans WHERE status = 'overdue') AS overdue_amount,
        (SELECT COUNT(*) FROM loans WHERE status = 'overdue') AS overdue_count,
        (SELECT COUNT(*) FROM loans WHERE status = 'defaulted') AS defaulted_count
";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("ssss", $from_date, $to_date, $from_date, $to_date);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$recovery_rate = $stats['total_disbursed'] > 0 ? round(($stats['total_repaid'] / $stats['total_disbursed']) * 100, 1) : 0;
$overdue_percent = $stats['total_disbursed'] > 0 ? round(($stats['overdue_amount'] / $stats['total_disbursed']) * 100, 1) : 0;

// Monthly trends (for chart)
$trends_sql = "
    SELECT 
        DATE_FORMAT(r.payment_date, '%Y-%m') AS month,
        COALESCE(SUM(r.amount_paid), 0) AS repayments,
        COALESCE(SUM(l.approved_amount), 0) AS disbursed
    FROM loan_repayments r
    RIGHT JOIN loans l ON r.loan_id = l.id
    WHERE l.application_date BETWEEN ? AND ?
    GROUP BY month
    ORDER BY month ASC
";
$trends_stmt = $conn->prepare($trends_sql);
$trends_stmt->bind_param("ss", $from_date, $to_date);
$trends_stmt->execute();
$trends_result = $trends_stmt->get_result();

$months = [];
$disbursed_data = [];
$repayments_data = [];

while ($row = $trends_result->fetch_assoc()) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $disbursed_data[] = (float)$row['disbursed'];
    $repayments_data[] = (float)$row['repayments'];
}

// By product breakdown
$by_product = $conn->query("
    SELECT lp.name,
           COUNT(l.id) AS loan_count,
           COALESCE(SUM(l.approved_amount), 0) AS disbursed,
           COALESCE(SUM(l.total_paid), 0) AS repaid,
           COALESCE(SUM(l.total_repayable - l.total_paid), 0) AS overdue
    FROM loan_products lp
    LEFT JOIN loans l ON lp.id = l.product_id
    GROUP BY lp.id
    ORDER BY disbursed DESC
");
?>

<!DOCTYPE html>
<html lang="en">
  <head> 
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Loan Reports</title>
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
        <?php include 'loanssider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

       <!-- Main Content Wrapper -->
      <main class="main-content w-full pb-8">
        <div class="mt-6 flex flex-col items-center justify-between space-y-2 px-[var(--margin-x)] text-center transition-all duration-[.25s] sm:flex-row sm:space-y-0 sm:text-left">
          <div>
            <h3 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
              Loan Reports & Analytics
            </h3>
            <p class="mt-1 hidden sm:block">Manage reports on loans</p>
          </div>
          <!-- <div>
            <p class="font-medium text-slate-700 dark:text-navy-100">
              Featured Authors
            </p>
            <div class="mt-2 flex space-x-2">
              <div class="avatar size-8">
                <img class="mask is-squircle" src="../images/app-logo.png" alt="avatar">
              </div>
              <div class="avatar size-8">
                <img class="mask is-squircle" src="../images/avatar/avatar-11.jpg" alt="avatar">
              </div>
              <div class="avatar size-8">
                <img class="mask is-squircle" src="../images/avatar/avatar-18.jpg" alt="avatar">
              </div>
              <div class="avatar size-8">
                <img class="mask is-squircle" src="../images/avatar/avatar-19.jpg" alt="avatar">
              </div>
              <div class="avatar size-8">
                <img class="mask is-squircle" src="../images/avatar/avatar-20.jpg" alt="avatar">
              </div>
            </div>
          </div> -->
        </div>
        <div class="mt-4 grid grid-cols-1 gap-4 px-[var(--margin-x)] transition-all duration-[.25s] sm:mt-5 sm:grid-cols-2 sm:gap-5 lg:mt-6 lg:grid-cols-4 lg:gap-6">
          <div class="card p-4 sm:col-span-2 sm:p-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:gap-6">
              <div class="flex items-center space-x-4 rounded-2xl border border-slate-150 p-4 dark:border-navy-600">
                <div class="mask is-star-2 flex size-12 items-center justify-center bg-warning/10 dark:bg-warning">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-warning dark:text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="font-inter">
                  <p class="text-base font-semibold text-slate-700 dark:text-navy-100">
                    UGX <?= number_format($stats['total_disbursed']) ?>
                  </p>
                  <p>Total Disbursed</p>
                </div>
              </div>
              <div class="flex items-center space-x-4 rounded-2xl border border-slate-150 p-4 dark:border-navy-600">
                <div class="mask is-star-2 flex size-12 items-center justify-center bg-primary/10 dark:bg-accent">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-primary dark:text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"d="M5 13l4 4L19 7"></path>
                  </svg>
                </div>
                <div class="font-inter">
                  <p class="text-base font-semibold text-slate-700 dark:text-navy-100">
                    UGX <?= number_format($stats['total_repaid']) ?>
                  </p>
                  <p>Total Repaid</p>
                </div>
              </div>
              <div class="flex items-center space-x-4 rounded-2xl border border-slate-150 p-4 dark:border-navy-600">
                <div class="mask is-star-2 flex size-12 items-center justify-center bg-secondary/10 dark:bg-secondary">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-secondary dark:text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                  </svg>
                </div>
                <div class="font-inter">
                  <p class="text-base font-semibold <?= $stats['overdue_amount'] > 0 ? 'text-error' : 'text-slate-700 dark:text-navy-100' ?>">
                    UGX <?= number_format($stats['overdue_amount']) ?>
                  </p>
                  <p>Overdue Amount</p>
                </div>
              </div>
              <div class="flex items-center space-x-4 rounded-2xl border border-slate-150 p-4 dark:border-navy-600">
                <div class="mask is-star-2 flex size-12 items-center justify-center bg-success/10 dark:bg-success">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-success dark:text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                  </svg>
                </div>
                <div class="font-inter">
                  <p class="text-base font-semibold <?= $stats['overdue_amount'] > 0 ? 'text-error' : 'text-slate-700 dark:text-navy-100' ?>">
                    <?= $recovery_rate ?>%
                  </p>
                  <p>Recovery Rate</p>
                </div>
              </div>
            </div>
          </div>
         <div class="card p-4 sm:col-span-2 sm:p-5">
            <!-- <div class="mt-3.5 flex grow items-baseline justify-between px-4 sm:px-5">
              <div>
                <p class="font-medium">Monthly Disbursements vs Repayments</p>
                <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                  45k
                </p>
              </div>
              <div class="badge space-x-1 rounded-full bg-success/10 py-1 px-1.5 text-success dark:bg-success/15">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewbox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                </svg>
                <span>46%</span>
              </div>
            </div> -->
            <div class="ax-transparent-gridline">
             <div id="trends-chart" class="mt-3 h-100"></div>
            </div>
          </div>
        </div>

        <div class="mt-4 px-[var(--margin-x)] transition-all duration-[.25s] sm:mt-5 lg:mt-6">
          <div class="flex h-8 items-center justify-between">
            <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
               Performance by Loan Product
            </h2>
            <a href="#" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">View All</a>
          </div>
          <div class="col-span-12 lg:col-span-12">
            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
              <table class="is-zebra w-full text-left">
                <thead>
                  <tr>
                    <th
                      class="whitespace-nowrap rounded-l-lg bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                    >
                      Product
                    </th>
                    <th
                      class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                    >
                      #Loans
                    </th>
                    <th
                      class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                    >
                      Disbursed
                    </th>
                    <th
                      class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                    >
                      Repaid
                    </th>
                    <th
                      class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                    >
                      Overdue
                    </th>
                    <th
                      class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                    >
                      Recovery %
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($prod = $by_product->fetch_assoc()): 
                $recovery = $prod['disbursed'] > 0 ? round(($prod['repaid'] / $prod['disbursed']) * 100, 1) : 0;
                $recovery_class = $recovery >= 90 ? 'text-success' : ($recovery >= 70 ? 'text-warning' : 'text-error');
              ?>
                  <tr>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <?= htmlspecialchars($prod['name']) ?></td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5"><?= $prod['loan_count'] ?></td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      UGX <?= number_format($prod['disbursed']) ?>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">UGX <?= number_format($prod['repaid']) ?></td>
                    <td class="whitespace-nowrap px-6 py-4 <?= $prod['overdue'] > 0 ? 'text-error' : '' ?>">
                    UGX <?= number_format($prod['overdue']) ?>
                  </td>
                  <td class="whitespace-nowrap rounded-r-lg px-4 py-3 sm:px-5 text-right">
                    <span class="<?= $recovery_class ?> font-semibold"><?= $recovery ?>%</span>
                  </td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
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
            <script>
        document.addEventListener('DOMContentLoaded', () => {
          const options = {
            chart: { type: 'area', height: 230, stacked: false },
            series: [
              { name: 'Disbursed', data: <?= json_encode($disbursed_data) ?> },
              { name: 'Repaid', data: <?= json_encode($repayments_data) ?> }
            ],
            xaxis: { categories: <?= json_encode($months) ?> },
            colors: ['#3b82f6', '#10b981'],
            fill: { opacity: [0.4, 0.8], type: 'solid' },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            tooltip: { shared: true, intersect: false },
            legend: { position: 'bottom' }
          };
          const chart = new ApexCharts(document.querySelector("#trends-chart"), options);
          chart.render();
        });
      </script>
  </body>
</html>
