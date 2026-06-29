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

<main class="main-content w-full px-[var(--margin-x)] pb-10">
  <div class="mt-6">
    <!-- Back button + Farmer header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <a href="borrowers.php" class="btn size-10 rounded-full bg-slate-100 p-0 hover:bg-slate-200 dark:bg-navy-600 dark:hover:bg-navy-500">
          <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </a>
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
          <?= htmlspecialchars($farmer['name']) ?>
        </h1>
      </div>
      <div class="flex space-x-3">
        <button class="btn bg-primary text-white px-5 py-2 rounded-md hover:bg-primary-focus">
          Contact Farmer
        </button>
        <button class="btn bg-error/10 text-error px-5 py-2 rounded-md hover:bg-error/20">
          Send Reminder
        </button>
      </div>
    </div>

    <!-- Farmer Info Card -->
    <div class="card mt-6 p-6">
      <div class="flex flex-col md:flex-row md:items-start md:space-x-6">
        <div class="flex-shrink-0">
          <img 
            src="<?= htmlspecialchars($farmer['avatar'] ?? '../images/avatars/default.jpg') ?>" 
            alt="Farmer" 
            class="size-24 rounded-full object-cover border-4 border-white shadow-lg dark:border-navy-600"
          >
        </div>
        <div class="mt-4 md:mt-0 flex-1">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <p class="text-xs uppercase text-slate-500 dark:text-navy-300">Phone</p>
              <p class="mt-1 text-lg font-medium"><?= htmlspecialchars($farmer['phone'] ?? '—') ?></p>
            </div>
            <div>
              <p class="text-xs uppercase text-slate-500 dark:text-navy-300">Location</p>
              <p class="mt-1 text-lg font-medium"><?= htmlspecialchars($farmer['location'] ?? 'Not specified') ?></p>
            </div>
            <div>
              <p class="text-xs uppercase text-slate-500 dark:text-navy-300">Member Since</p>
              <p class="mt-1 text-lg font-medium"><?= date('M Y', strtotime($farmer['created_at'])) ?></p>
            </div>
            <div>
              <p class="text-xs uppercase text-slate-500 dark:text-navy-300">Total Loans</p>
              <p class="mt-1 text-2xl font-semibold"><?= $stats['total_loans'] ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div class="card p-5 relative overflow-hidden">
        <p class="text-xs uppercase text-slate-600 dark:text-navy-200">Total Borrowed</p>
        <p class="mt-3 text-2xl font-semibold text-slate-800 dark:text-navy-50">
          UGX <?= number_format($stats['total_borrowed']) ?>
        </p>
        <div class="absolute bottom-0 right-0 opacity-10">
          <i class="fa-solid fa-hand-holding-dollar text-6xl text-primary"></i>
        </div>
      </div>

      <div class="card p-5 relative overflow-hidden">
        <p class="text-xs uppercase text-slate-600 dark:text-navy-200">Total Repaid</p>
        <p class="mt-3 text-2xl font-semibold text-success">
          UGX <?= number_format($stats['total_repaid']) ?>
        </p>
        <div class="absolute bottom-0 right-0 opacity-10">
          <i class="fa-solid fa-check-to-slot text-6xl text-success"></i>
        </div>
      </div>

      <div class="card p-5 relative overflow-hidden">
        <p class="text-xs uppercase text-slate-600 dark:text-navy-200">Repayment %</p>
        <p class="mt-3 text-2xl font-semibold <?= $stats['repayment_percent'] >= 90 ? 'text-success' : ($stats['repayment_percent'] >= 70 ? 'text-warning' : 'text-error') ?>">
          <?= number_format($stats['repayment_percent'], 1) ?>%
        </p>
        <div class="absolute bottom-0 right-0 opacity-10">
          <i class="fa-solid fa-percent text-6xl text-warning"></i>
        </div>
      </div>

      <div class="card p-5 relative overflow-hidden">
        <p class="text-xs uppercase text-slate-600 dark:text-navy-200">Overdue Loans</p>
        <p class="mt-3 text-2xl font-semibold <?= $stats['overdue_count'] > 0 ? 'text-error' : 'text-slate-800 dark:text-navy-50' ?>">
          <?= $stats['overdue_count'] ?>
        </p>
        <div class="absolute bottom-0 right-0 opacity-10">
          <i class="fa-solid fa-triangle-exclamation text-6xl text-error"></i>
        </div>
      </div>
    </div>

    <!-- Loans List -->
    <div class="mt-10">
      <h2 class="text-lg font-medium text-slate-800 dark:text-navy-50">
        All Loans
      </h2>

      <div class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  #
                </th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Product
                </th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Amount
                </th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Status
                </th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Applied
                </th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Progress
                </th>
                <th class="bg-slate-100 px-6 py-4 text-right text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
              <?php $index = 1; while ($loan = $loans_result->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-navy-600/50">
                  <td class="whitespace-nowrap px-6 py-4"><?= $index++ ?></td>
                  <td class="whitespace-nowrap px-6 py-4 font-medium"><?= htmlspecialchars($loan['product_name']) ?></td>
                  <td class="whitespace-nowrap px-6 py-4">
                    UGX <?= number_format($loan['requested_amount']) ?>
                    <?php if ($loan['approved_amount'] && $loan['approved_amount'] != $loan['requested_amount']): ?>
                      <span class="text-xs text-slate-500">(Appr: <?= number_format($loan['approved_amount']) ?>)</span>
                    <?php endif; ?>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4">
                    <span class="badge <?= $loan['status_class'] ?? 'bg-slate-100 text-slate-700' ?>">
                      <?= ucfirst($loan['status']) ?>
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-slate-600 dark:text-navy-200">
                    <?= date('d M Y', strtotime($loan['application_date'])) ?>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4">
                    <div class="w-24 h-2 bg-slate-200 rounded-full dark:bg-navy-600">
                      <div 
                        class="h-full rounded-full <?= $loan['progress_percent'] >= 90 ? 'bg-success' : ($loan['progress_percent'] >= 70 ? 'bg-warning' : 'bg-error') ?>" 
                        style="width: <?= $loan['progress_percent'] ?>%"
                      ></div>
                    </div>
                    <span class="text-xs <?= $loan['progress_percent'] >= 90 ? 'text-success' : ($loan['progress_percent'] >= 70 ? 'text-warning' : 'text-error') ?>">
                      <?= number_format($loan['progress_percent'], 1) ?>%
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-right">
                    <a href="loan-view.php?id=<?= $loan['id'] ?>" class="text-primary hover:underline">View</a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    <!-- Repayment History -->
    <div class="mt-10">
      <h2 class="text-lg font-medium text-slate-800 dark:text-navy-50">
        Repayment History
      </h2>

      <div class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Date
                </th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Loan Product
                </th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Amount Paid
                </th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Method
                </th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Receipt #
                </th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                  Notes
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
              <?php while ($payment = $payments_result->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-navy-600/50">
                  <td class="whitespace-nowrap px-6 py-4">
                    <?= date('d M Y H:i', strtotime($payment['payment_date'] . ' ' . substr($payment['created_at'], 11, 8))) ?>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4"><?= htmlspecialchars($payment['product_name']) ?></td>
                  <td class="whitespace-nowrap px-6 py-4 font-medium text-success">
                    UGX <?= number_format($payment['amount_paid']) ?>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 capitalize">
                    <?= str_replace('_', ' ', $payment['payment_method']) ?>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4">
                    <?= htmlspecialchars($payment['receipt_number'] ?? '—') ?>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-slate-600 dark:text-navy-200">
                    <?= htmlspecialchars($payment['notes'] ?? '—') ?>
                  </td>
                </tr>
              <?php endwhile; ?>
              <?php if ($payments_result->num_rows === 0): ?>
                <tr>
                  <td colspan="6" class="py-10 text-center text-slate-500 dark:text-navy-300">
                    No repayments recorded for this farmer.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>