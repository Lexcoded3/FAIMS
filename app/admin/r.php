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

<main class="main-content w-full px-[var(--margin-x)] pb-10">
  <div class="mt-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
        Loan Reports & Analytics
      </h1>

      <div class="flex space-x-4">
        <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>" class="form-input">
        <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>" class="form-input">
        <button class="btn bg-primary text-white px-5 py-2 rounded-md hover:bg-primary-focus">
          Apply Filter
        </button>
      </div>
    </div>

    <!-- Key Stats Cards -->
    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
      <div class="card p-6 relative overflow-hidden">
        <p class="text-xs uppercase text-slate-600 dark:text-navy-200">Total Disbursed</p>
        <p class="mt-3 text-3xl font-semibold text-primary">
          UGX <?= number_format($stats['total_disbursed']) ?>
        </p>
        <div class="absolute bottom-0 right-0 opacity-10">
          <i class="fa-solid fa-hand-holding-dollar text-7xl text-primary"></i>
        </div>
      </div>

      <div class="card p-6 relative overflow-hidden">
        <p class="text-xs uppercase text-slate-600 dark:text-navy-200">Total Repaid</p>
        <p class="mt-3 text-3xl font-semibold text-success">
          UGX <?= number_format($stats['total_repaid']) ?>
        </p>
        <div class="absolute bottom-0 right-0 opacity-10">
          <i class="fa-solid fa-check-double text-7xl text-success"></i>
        </div>
      </div>

      <div class="card p-6 relative overflow-hidden">
        <p class="text-xs uppercase text-slate-600 dark:text-navy-200">Recovery Rate</p>
        <p class="mt-3 text-3xl font-semibold <?= $recovery_rate >= 90 ? 'text-success' : ($recovery_rate >= 70 ? 'text-warning' : 'text-error') ?>">
          <?= $recovery_rate ?>%
        </p>
        <div class="absolute bottom-0 right-0 opacity-10">
          <i class="fa-solid fa-percentage text-7xl text-warning"></i>
        </div>
      </div>

      <div class="card p-6 relative overflow-hidden">
        <p class="text-xs uppercase text-slate-600 dark:text-navy-200">Overdue Amount</p>
        <p class="mt-3 text-3xl font-semibold <?= $stats['overdue_amount'] > 0 ? 'text-error' : 'text-slate-800 dark:text-navy-50' ?>">
          UGX <?= number_format($stats['overdue_amount']) ?>
        </p>
        <div class="absolute bottom-0 right-0 opacity-10">
          <i class="fa-solid fa-triangle-exclamation text-7xl text-error"></i>
        </div>
      </div>

      <div class="card p-6 relative overflow-hidden">
        <p class="text-xs uppercase text-slate-600 dark:text-navy-200">Overdue Loans</p>
        <p class="mt-3 text-3xl font-semibold <?= $stats['overdue_count'] > 0 ? 'text-error' : 'text-slate-800 dark:text-navy-50' ?>">
          <?= number_format($stats['overdue_count']) ?>
        </p>
        <div class="absolute bottom-0 right-0 opacity-10">
          <i class="fa-solid fa-file-invoice text-7xl text-error"></i>
        </div>
      </div>
    </div>

    <!-- Trends Chart -->
    <div class="mt-10 card p-6">
      <h2 class="text-lg font-medium text-slate-800 dark:text-navy-50">
        Monthly Disbursements vs Repayments
      </h2>
      <div id="trends-chart" class="mt-6 h-80"></div>
      <script>
        document.addEventListener('DOMContentLoaded', () => {
          const options = {
            chart: { type: 'area', height: 350, stacked: false },
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
            legend: { position: 'top' }
          };
          const chart = new ApexCharts(document.querySelector("#trends-chart"), options);
          chart.render();
        });
      </script>
    </div>

    <!-- Breakdown by Product -->
    <div class="mt-10">
      <h2 class="text-lg font-medium text-slate-800 dark:text-navy-50">
        Performance by Loan Product
      </h2>

      <div class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase ...">Product</th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase ..."># Loans</th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase ...">Disbursed</th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase ...">Repaid</th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase ...">Overdue</th>
                <th class="bg-slate-100 px-6 py-4 text-left text-xs font-semibold uppercase ...">Recovery %</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($prod = $by_product->fetch_assoc()): 
                $recovery = $prod['disbursed'] > 0 ? round(($prod['repaid'] / $prod['disbursed']) * 100, 1) : 0;
                $recovery_class = $recovery >= 90 ? 'text-success' : ($recovery >= 70 ? 'text-warning' : 'text-error');
              ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-navy-600/50">
                  <td class="whitespace-nowrap px-6 py-4 font-medium"><?= htmlspecialchars($prod['name']) ?></td>
                  <td class="whitespace-nowrap px-6 py-4 text-center"><?= $prod['loan_count'] ?></td>
                  <td class="whitespace-nowrap px-6 py-4">UGX <?= number_format($prod['disbursed']) ?></td>
                  <td class="whitespace-nowrap px-6 py-4 text-success">UGX <?= number_format($prod['repaid']) ?></td>
                  <td class="whitespace-nowrap px-6 py-4 <?= $prod['overdue'] > 0 ? 'text-error' : '' ?>">
                    UGX <?= number_format($prod['overdue']) ?>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-right">
                    <span class="<?= $recovery_class ?> font-semibold"><?= $recovery ?>%</span>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>