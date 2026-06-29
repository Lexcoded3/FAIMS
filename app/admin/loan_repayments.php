<?php
session_start();
$required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

// Handle new payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_payment') {
    $loan_id         = (int)$_POST['loan_id'];
    $amount_paid     = (float)$_POST['amount_paid'];
    $payment_date    = $_POST['payment_date'] ?: date('Y-m-d');
    $payment_method  = $_POST['payment_method'] ?? 'mobile_money';
    $receipt_number  = trim($_POST['receipt_number'] ?? '');
    $notes           = trim($_POST['notes'] ?? '');
    $received_by     = $_SESSION['user_id'];

    if ($loan_id <= 0 || $amount_paid <= 0) {
        $error = "Invalid loan or amount";
    } else {
        $conn->begin_transaction();

        try {
            // Insert repayment
            $stmt = $conn->prepare("
                INSERT INTO loan_repayments (
                    loan_id, payment_number, amount_paid, payment_date, 
                    payment_method, receipt_number, received_by, notes
                ) VALUES (?, 
                    (SELECT COALESCE(MAX(payment_number), 0) + 1 FROM loan_repayments WHERE loan_id = ?),
                    ?, ?, ?, ?, ?, ?
                )
            ");
            $stmt->bind_param("iidsssis", $loan_id, $loan_id, $amount_paid, $payment_date, $payment_method, $receipt_number, $received_by, $notes);
            $stmt->execute();

            // Update loan totals & status
            $update = $conn->prepare("
                UPDATE loans SET
                    total_paid = total_paid + ?,
                    last_payment_date = ?,
                    status = CASE
                        WHEN total_paid + ? >= total_repayable THEN 'repaid'
                        WHEN status = 'pending' THEN 'disbursed'
                        WHEN status = 'approved' THEN 'disbursed'
                        ELSE status
                    END,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $update->bind_param("dddi", $amount_paid, $payment_date, $amount_paid, $loan_id);
            $update->execute();

            $conn->commit();
            $success = "Payment recorded successfully";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch repayments with filters
$filters = [];
$sql = "
    SELECT 
        r.id, r.loan_id, r.amount_paid, r.payment_date, r.payment_method,
        r.receipt_number, r.notes, r.created_at,
        l.requested_amount, l.status AS loan_status,
        lp.name AS product_name,
        u.name AS farmer_name, u.phone
    FROM loan_repayments r
    JOIN loans l ON r.loan_id = l.id
    JOIN loan_products lp ON l.product_id = lp.id
    JOIN users u ON l.farmer_id = u.id
    WHERE 1=1
";

if (!empty($_GET['date_from'])) {
    $sql .= " AND r.payment_date >= ?";
    $filters[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $sql .= " AND r.payment_date <= ?";
    $filters[] = $_GET['date_to'];
}
if (!empty($_GET['loan_id'])) {
    $sql .= " AND r.loan_id = ?";
    $filters[] = (int)$_GET['loan_id'];
}
if (!empty($_GET['search'])) {
    $like = "%" . $_GET['search'] . "%";
    $sql .= " AND (u.name LIKE ? OR u.phone LIKE ? OR r.receipt_number LIKE ?)";
    $filters = array_merge($filters, [$like, $like, $like]);
}

$sql .= " ORDER BY r.payment_date DESC, r.created_at DESC";

$stmt = $conn->prepare($sql);
if ($filters) $stmt->bind_param(str_repeat('s', count($filters)), ...$filters);
$stmt->execute();
$repayments = $stmt->get_result();

// For new payment form - get active/disbursed loans
$active_loans = $conn->query("
    SELECT l.id, u.name, lp.name AS product, l.requested_amount, l.total_paid, l.total_repayable
    FROM loans l
    JOIN users u ON l.farmer_id = u.id
    JOIN loan_products lp ON l.product_id = lp.id
    WHERE l.status IN ('disbursed', 'active', 'overdue')
    ORDER BY u.name
");
      
?>
<?php
  // Quick stats queries
  $stats = [];

  // 1. Total repayments (all time)
  $stats['total_repayments'] = $conn->query("SELECT COALESCE(SUM(amount_paid), 0) FROM loan_repayments")->fetch_row()[0] ?? 0;

  // 2. This month
  $this_month = date('Y-m-01');
  $stats['this_month'] = $conn->query("SELECT COALESCE(SUM(amount_paid), 0) FROM loan_repayments WHERE payment_date >= '$this_month'")->fetch_row()[0] ?? 0;

  // 3. Today
  $today = date('Y-m-d');
  $stats['today'] = $conn->query("SELECT COALESCE(SUM(amount_paid), 0) FROM loan_repayments WHERE DATE(payment_date) = '$today'")->fetch_row()[0] ?? 0;

  // 4. Overdue amount & count
  $overdue = $conn->query("
    SELECT 
      COALESCE(SUM(total_repayable - total_paid), 0) AS overdue_amount,
      COUNT(*) AS overdue_count
    FROM loans 
    WHERE status = 'overdue'
  ")->fetch_assoc();
  $stats['overdue_amount'] = $overdue['overdue_amount'] ?? 0;
  $stats['overdue_count']  = $overdue['overdue_count'] ?? 0;

  // 5. Total portfolio (for overdue %)
  $total_portfolio = $conn->query("
    SELECT COALESCE(SUM(total_repayable), 0) 
    FROM loans 
    WHERE status IN ('disbursed', 'active', 'overdue')
  ")->fetch_row()[0] ?? 0;

  $stats['overdue_percent'] = $total_portfolio > 0 ? round(($stats['overdue_amount'] / $total_portfolio) * 100, 1) : 0;
  ?>

<!DOCTYPE html>
<html lang="en">
  <head> 
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Loan Repayments</title>
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
                <p class="text-xs+ uppercase">Total Repayments</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    UGX <?= number_format($stats['total_repayments']) ?>
                  </p>
                  <p class="text-xs text-success">+21%</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-warning/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-warning" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-users translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-12 translate-x-1/4 translate-y-1/4 opacity-15" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">This Month</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    UGX <?= number_format($stats['this_month']) ?>
                  </p>
                  <p class="text-xs text-success">+21%</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-info/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                          </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-list translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-12 translate-x-1/4 translate-y-1/4 opacity-15" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                          </svg>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">Today</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    UGX<br><?= number_format($stats['today']) ?>
                  </p>
                  <p class="text-xs text-success">+8%</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-success/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-thumbs-up translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-12 translate-x-1/4 translate-y-1/4 opacity-15" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
              </div>
            </div>
            <div class="card flex-row justify-between p-4">
              <div>
                <p class="text-xs+ uppercase">OverDue</p>
                <div class="mt-8 flex items-baseline space-x-1">
                  <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                    UGX<br><?= number_format($stats['overdue_amount']) ?>
                  </p>
                  <p class="text-xs <?= $stats['overdue_percent'] > 0 ? 'text-error' : 'text-slate-800 dark:text-navy-50' ?>"><?= number_format($stats['overdue_percent'], 1) ?>%</p>
                </div>
              </div>
              <div class="mask is-squircle flex size-10 items-center justify-center bg-error/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-error" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                          </svg>

              </div>
              <div class="absolute bottom-0 right-0 overflow-hidden rounded-lg">
                <!-- <i class="fa-solid fa-thumbs-up translate-x-1/4 translate-y-1/4 text-5xl opacity-15"></i> -->
                <svg xmlns="http://www.w3.org/2000/svg" class="size-12 translate-x-1/4 translate-y-1/4 opacity-15" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                          </svg>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-6 lg:gap-6">
            <div class="card mt-3">
        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  #
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Borrower
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Loan ID / Product
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Amount Paid/Date
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Method/Receipt
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Notes
                </th>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                  Action
                </th>
              </tr>
            </thead>
            <tbody>
              <?php $index = 1; while ($row = $repayments->fetch_assoc()): ?>
                  <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500 hover:bg-slate-50 dark:hover:bg-navy-600/50">
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <p class="font-medium text-primary dark:text-accent-light">
                        #<?= $index++ ?>
                      </p>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <div class="flex items-center space-x-3">
                        <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($row['farmer_name']) ?><br>
                        <?= htmlspecialchars($product['code'] ?? '—') ?><br>
                      </span>
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5"><?= htmlspecialchars($row['product_name']) ?></td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">UGX <?= number_format($row['amount_paid']) ?><br>
                      <p class="font-medium text-primary dark:text-accent-light">
                          <?= date('d M Y', strtotime($row['payment_date'])) ?>
                        </p>
                      </td>
                    <td class="text-success px-4 py-3 sm:px-5"><?= htmlspecialchars($row['receipt_number'] ?? '—') ?><br>
                      <p class="font-medium text-primary dark:text-accent-light">
                          <?= str_replace('_', ' ', $row['payment_method']) ?>
                        </p>
                      </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <?= htmlspecialchars($row['notes'] ?? '—') ?>
                    </div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-right">
                      <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                        <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"></path>
                          </svg>
                        </button>

                        
                        <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
               <div
        class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700"
      >
                <ul>
          <li>
            <a
              href="loan-view.php?id=<?= $loan['id'] ?>"
              class="flex h-8 items-center space-x-3 px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="mt-px size-4.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"
                />
              </svg>
              <span> View</span></a
            >
          </li>
          <li>
            <a
              href="#"
              class="flex h-8 items-center space-x-3 px-3 pr-8 font-medium tracking-wide text-error outline-none transition-all hover:bg-error/20 focus:bg-error/20"
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
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
              <span> Delete item</span></a
            >
          </li>
        </ul>
              </div>
            </div>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php if ($repayments->num_rows === 0): ?>
                <tr>
                  <td colspan="10" class="py-10 px-8 text-center text-slate-500 dark:text-navy-300">
                    
                    <div
                      class="alert flex overflow-hidden rounded-lg border border-info text-info"
                    >
                      <div class="bg-info p-3 text-white">
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
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                          />
                        </svg>
                      </div>
                      <div class="px-4 py-3 sm:px-5">No loan Products found.</div>
                    </div>

                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination from your template -->
        <div class="flex flex-col justify-between space-y-4 px-4 py-4 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
          <!-- Keep your original pagination / entries selector here -->
          <!-- ... paste your pagination code ... -->
        </div>
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
    fetch('approve_loan.php', {
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
