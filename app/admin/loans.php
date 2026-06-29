<?php
session_start();
$required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

  // 1. Total unique borrowers (farmers who have at least one loan record)
$borrowers_count = $conn->query("SELECT COUNT(DISTINCT farmer_id) FROM loans")->fetch_row()[0] ?? 0;

// 2. Total value of currently active loans
// Note: we use approved_amount (not amount_approved) + correct status values
$active_loans = $conn->query("
    SELECT COALESCE(SUM(approved_amount), 0) 
    FROM loans 
    WHERE status IN ('disbursed', 'active')
")->fetch_row()[0] ?? 0;

// 3. Total payments received today
$today_payments = $conn->query("
    SELECT COALESCE(SUM(amount_paid), 0) 
    FROM loan_repayments 
    WHERE DATE(payment_date) = CURDATE()
")->fetch_row()[0] ?? 0;

// 4. Total still receivable (outstanding balance)
$receivable = $conn->query("
    SELECT COALESCE(SUM(total_repayable - total_paid), 0) 
    FROM loans 
    WHERE status IN ('disbursed', 'active', 'overdue')
")->fetch_row()[0] ?? 0;
      
?>
<!DOCTYPE html>
<html lang="en">
  <head> 
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Loans</title>
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
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-4 grid grid-cols-1 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4 lg:gap-6">
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">Borrowers</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    <?= number_format($borrowers_count) ?>
                  </p>
                  <p class="text-xs text-success">+21%</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-warning/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-warning" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                          </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <i class="fa-solid fa-users translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">Active loans</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    UGX<br><?= number_format($active_loans) ?><br>
                  </p>
                  <p class="text-xs text-success">+4%</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-info/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"></path>
                          </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <i class="fa-solid fa-list translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">payment today</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    UGX<br><?= number_format($today_payments) ?>
                  </p>
                  <p class="text-xs text-success">+8%</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-success/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3"></path>
                          </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-thumbs-up translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-12 translate-x-1/4 translate-y-1/4 opacity-15" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3"></path>
                          </svg>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">Pay receivable</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    UGX<br><?= number_format($receivable) ?>
                  </p>
                  <p class="text-xs text-error">-2.3%</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-error/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-warning" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75h4.875a2.625 2.625 0 0 1 0 5.25H12M8.25 9.75 10.5 7.5M8.25 9.75 10.5 12m9-7.243V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185Z" />
                          </svg>

              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-thumbs-up translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-12 translate-x-1/4 translate-y-1/4 opacity-15" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75h4.875a2.625 2.625 0 0 1 0 5.25H12M8.25 9.75 10.5 7.5M8.25 9.75 10.5 12m9-7.243V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185Z" />
                          </svg>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-2 lg:gap-6">
            <!-- <div>
              <div class="flex h-8 items-center justify-between">
                <h2 class="font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                  Activity
                </h2>

                <select class="form-select h-8 rounded-full border border-slate-300 bg-slate-50 px-2.5 pr-9 text-xs+ hover:border-slate-400 focus:border-primary dark:border-navy-600 dark:bg-navy-900 dark:hover:border-navy-400 dark:focus:border-accent">
                  <option>05 - 12 May</option>
                  <option>12 - 19 May</option>
                  <option>19 - 26 May</option>
                  <option>26 - 02 June</option>
                  <option>02 - 09 June</option>
                </select>
              </div>

              <div>
                <div x-init="$nextTick(() => { $el._x_chart = new ApexCharts($el,pages.charts.influencerActivity); $el._x_chart.render() });"></div>
              </div>
            </div> -->
            <div>
              <div class="flex h-8 items-center justify-between">
                <h2 class="font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                  Top loan Performers
                </h2>

                <a href="#" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">
                  View All
                </a>
              </div>

              <table class="w-full">
                <tbody>
                  <?php
    // Query: top 5 farmers by repayment percentage
    // Progress = (total_paid / total_repayable) * 100
    // Only include loans that are disbursed/active/repaid/overdue (exclude pending/rejected)
    $sql = "
      SELECT 
        u.id AS farmer_id,
        u.name,
        u.phone,
        u.location,
        COUNT(l.id) AS loan_count,
        COALESCE(SUM(l.total_repayable), 0) AS total_due,
        COALESCE(SUM(l.total_paid), 0) AS total_paid,
        CASE 
          WHEN COALESCE(SUM(l.total_repayable), 0) > 0 
          THEN ROUND((COALESCE(SUM(l.total_paid), 0) / SUM(l.total_repayable)) * 100, 0)
          ELSE 0 
        END AS repayment_rate
      FROM users u
      LEFT JOIN loans l ON u.id = l.farmer_id 
        AND l.status IN ('disbursed', 'active', 'overdue', 'repaid')
      WHERE u.role = 'farmer'
      GROUP BY u.id
      HAVING loan_count > 0
      ORDER BY repayment_rate DESC
      LIMIT 5
    ";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $rate = (int)$row['repayment_rate'];
        // Even cleaner (PHP 8.0+)
          $rate_class = match (true) {
              $rate >= 90 => 'text-success',
              $rate >= 70 => 'text-warning',
              default     => 'text-error',
          };
    ?>
                  <tr>
                    <td class="whitespace-nowrap pt-4">
                      <div class="flex items-center space-x-3">
                        <div class="avatar size-9">
                          <img class="rounded-full" src="<?= htmlspecialchars($row['image_paths'] ?? '../images/avatars/default.jpg') ?>" 
                                alt="avatar">
                        </div>
                        <h3 class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          <?= htmlspecialchars($row['name']) ?>
                        </h3>
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-2 pt-4">
                      <a href="#" class="font-inter tracking-wide text-slate-400 hover:text-primary focus:text-primary dark:hover:text-accent-light dark:focus:text-accent-light"><?= htmlspecialchars($row['location'] ?? '—') ?> • 
              <?= $row['loan_count'] ?> loan<?= $row['loan_count'] > 1 ? 's' : '' ?>
                      </a>
                    </td>
                    <td class="whitespace-nowrap pt-4">
                      <p class="text-right font-medium text-slate-700 dark:text-navy-100">
                        <span class="font-medium <?= $rate_class ?>">
                        <?= $rate ?>%
                      </span>
                      </p>
                    </td>
                  </tr>
                  <?php
                    }
                  } else {
                    echo '<tr><td> <div
    class="alert flex overflow-hidden rounded-lg bg-warning/10 text-warning dark:bg-warning/15"
  >
    <div class="flex flex-1 items-center space-x-3 p-4">
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
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
      </svg>
      <div class="flex-1">No repayment data yet</div>
    </div>

    <div class="w-1.5 bg-warning"></div>
  </div></td></tr>';
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="flex flex-col rounded-xl bg-info/10 py-5 dark:bg-navy-800 lg:flex-row">
            <div class="flex flex-col px-4 sm:px-5 lg:w-49 lg:shrink-0 lg:py-3">
              <h3 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100 lg:text-lg">
                Pending Applications
              </h3>
              <p class="mt-3 grow">
                 New loan requests awaiting review<br> (based on applied products)
              </p>
              <div class="mt-3 flex items-center space-x-2">
                <div class="flex size-7 items-center justify-center rounded-full bg-success/15 text-success">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                  </svg>
                </div>
                <p class="text-base font-medium text-slate-700 dark:text-navy-100">
                  <?= $pending_count ?? '12' ?> pending
                </p>
              </div>
            </div>
            <?php
              $pending_sql = "
                SELECT 
                  l.id,
                  l.requested_amount,
                  l.application_date,
                  l.purpose,
                  lp.name AS product_name,
                  u.name AS farmer_name,
                  u.phone,
                  u.location
                FROM loans l
                JOIN loan_products lp ON l.product_id = lp.id
                JOIN users u ON l.farmer_id = u.id
                WHERE l.status = 'pending'
                ORDER BY l.application_date DESC
                LIMIT 8
              ";

              $pending_result = $conn->query($pending_sql);

              if ($pending_result && $pending_result->num_rows > 0) {
                while ($app = $pending_result->fetch_assoc()) {
                  $days_ago = (int)((time() - strtotime($app['application_date'])) / 86400);
              ?>
            <div class="scrollbar-sm mt-5 flex space-x-4 overflow-x-auto px-4 sm:px-5 lg:mt-0 lg:pl-0">
              <div class="flex w-38 shrink-0 flex-col items-center">
                <img class="z-10 size-10" src="../images/logos/instagram-round.svg" alt="flag">

                <div class="card -mt-5 w-full rounded-2xl px-3 py-5 text-center">
                  <p class="mt-3 text-base font-medium text-slate-700 dark:text-navy-100">
                    <?= htmlspecialchars($app['farmer_name']) ?>
                  </p>
                  <a href="#" class="mt-1 font-inter text-xs+ tracking-wide text-slate-400 hover:text-primary focus:text-primary dark:hover:text-accent-light dark:focus:text-accent-light">Applied <?= $days_ago === 0 ? 'today' : $days_ago . ' day' . ($days_ago === 1 ? '' : 's') . ' ago' ?>
                  </a>
                  <div class="mt-4">
                  <p class="text-sm font-medium text-slate-700 dark:text-navy-100">
                    <?= htmlspecialchars($app['product_name']) ?>
                  </p>
                  <p class="text-xl font-semibold text-slate-800 dark:text-navy-50 mt-1">
                    UGX <?= number_format($app['requested_amount']) ?>
                  </p>
                  <p class="text-xs text-slate-500 dark:text-navy-300 mt-1">
                    <?= htmlspecialchars($app['purpose'] ?? '—') ?>
                  </p>
                </div>
                  <!-- <div class="mt-6 flex justify-center space-x-1 font-inter">
                    <p class="text-4xl font-medium text-slate-700 dark:text-navy-100">
                      +2
                    </p>
                    <p class="mt-1 font-medium text-slate-700 dark:text-navy-100">
                      %
                    </p>
                  </div> -->
                  <div class="mt-5 flex space-x-3">
                  <button 
                    onclick="approveLoan(<?= $app['id'] ?>)"
                    class="flex-1 rounded-md bg-success px-4 py-2 text-sm font-medium text-white hover:bg-success/90 focus:outline-none focus:ring-2 focus:ring-success/50">
                    Approve
                  </button>
                  <button 
                    onclick="rejectLoan(<?= $app['id'] ?>)"
                    class="flex-1 rounded-md bg-error px-4 py-2 text-sm font-medium text-white hover:bg-error/90 focus:outline-none focus:ring-2 focus:ring-error/50">
                    Reject
                  </button>
                </div>
                </div>
              </div>
              <?php
                  }
                } else {
                ?>
                <div class="flex w-full items-center justify-center py-10 text-slate-500 dark:text-navy-300">
                  No pending loan applications at the moment.
                </div>
                <?php
                }
                ?>
            </div>
          </div>
        </div>
        <!-- Rejection Modal -->
<div x-data="{ open: false, loanId: null, reason: '', loading: false }"
     x-show="open"
     x-transition
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
     @keydown.escape="open = false">

  <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-navy-700">
    <!-- Close button -->
    <button @click="open = false" class="absolute right-4 top-4 text-slate-500 hover:text-slate-700 dark:text-navy-300 dark:hover:text-navy-100">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>

    <h3 class="text-lg font-semibold text-slate-800 dark:text-navy-50">
      Reject Loan Application
    </h3>

    <p class="mt-2 text-sm text-slate-600 dark:text-navy-200">
      The farmer will not be able to see this loan anymore. Optional: add a reason.
    </p>

    <div class="mt-5">
      <label for="reason" class="block text-sm font-medium text-slate-700 dark:text-navy-100">
        Rejection Reason (optional)
      </label>
      <textarea
        x-model="reason"
        id="reason"
        rows="4"
        class="mt-1 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-primary focus:ring-primary dark:border-navy-600 dark:bg-navy-800 dark:text-navy-100 dark:focus:border-accent dark:focus:ring-accent"
        placeholder="e.g. Incomplete documents, ineligible crop, over limit..."
      ></textarea>
    </div>

    <div class="mt-6 flex justify-end space-x-3">
      <button
        @click="open = false"
        class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-navy-600 dark:text-navy-200 dark:hover:bg-navy-600">
        Cancel
      </button>

      <button
        @click="submitReject()"
        :disabled="loading"
        class="flex items-center rounded-md bg-error px-5 py-2 text-sm font-medium text-white hover:bg-error/90 focus:outline-none focus:ring-2 focus:ring-error/50 disabled:opacity-50">
        <span x-show="!loading">Reject Loan</span>
        <span x-show="loading" class="flex items-center">
          <svg class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h8a8 8 0 01-16 0z"></path>
          </svg>
          Processing...
        </span>
      </button>
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
      function approveLoan(id) {
  if (confirm('Approve this loan application?')) {
    fetch('../approve_loan.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'loan_id=' + id
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Loan approved!');
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    });
  }
}

function rejectLoan(id) {
  const reason = prompt('Enter rejection reason (optional):');
  if (reason !== null) {
    fetch('reject_loan.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'loan_id=' + id + '&reason=' + encodeURIComponent(reason || '')
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Loan rejected.');
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    });
  }
}
    </script>

  </body>
</html>
