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

<main class="main-content w-full px-[var(--margin-x)] pb-10">
  <div class="mt-6">
    <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
      Loan Repayments & Recording
    </h1>

    <?php if (isset($success)): ?>
      <div class="mt-4 rounded-lg bg-success/10 p-4 text-success dark:bg-success/20">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
      <div class="mt-4 rounded-lg bg-error/10 p-4 text-error dark:bg-error/20">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Record New Payment Form -->
    <div class="card mt-6 p-6">
      <h2 class="text-lg font-medium text-slate-800 dark:text-navy-50">
        Record New Payment
      </h2>
      <form method="POST" class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        <input type="hidden" name="action" value="add_payment">

        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-navy-100">Select Loan</label>
          <select name="loan_id" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-navy-600 dark:bg-navy-700">
            <option value="">-- Choose loan --</option>
            <?php while ($loan = $active_loans->fetch_assoc()): ?>
              <option value="<?= $loan['id'] ?>">
                <?= htmlspecialchars($loan['name']) ?> - <?= htmlspecialchars($loan['product']) ?> 
                (UGX <?= number_format($loan['requested_amount']) ?> - Paid: <?= number_format($loan['total_paid']) ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-navy-100">Amount Paid (UGX)</label>
          <input type="number" name="amount_paid" step="0.01" min="1" required class="mt-1 block w-full rounded-lg border ...">
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-navy-100">Payment Date</label>
          <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" class="mt-1 block w-full rounded-lg border ...">
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-navy-100">Payment Method</label>
          <select name="payment_method" class="mt-1 block w-full rounded-lg border ...">
            <option value="mobile_money">Mobile Money</option>
            <option value="bank">Bank Transfer</option>
            <option value="cash">Cash</option>
            <option value="group_collection">Group Collection</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-navy-100">Receipt / Reference #</label>
          <input type="text" name="receipt_number" class="mt-1 block w-full rounded-lg border ...">
        </div>

        <div class="md:col-span-2 lg:col-span-3">
          <label class="block text-sm font-medium text-slate-700 dark:text-navy-100">Notes</label>
          <textarea name="notes" rows="3" class="mt-1 block w-full rounded-lg border ..."></textarea>
        </div>

        <div class="md:col-span-2 lg:col-span-3 flex justify-end">
          <button type="submit" class="btn bg-success text-white px-6 py-2 rounded-md hover:bg-success/90">
            Record Payment
          </button>
        </div>
      </form>
    </div>

    <!-- Repayments Table (your template style) -->
    <div class="mt-10">
      <div class="flex items-center justify-between">
        <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
          Payment History
        </h2>
        <div class="flex">
          <!-- Search toggle -->
          <div class="flex items-center" x-data="{isInputActive:false}">
            <label class="block">
              <input 
                x-effect="isInputActive === true && $nextTick(() => { $el.focus()});" 
                :class="isInputActive ? 'w-32 lg:w-48' : 'w-0'" 
                class="form-input bg-transparent px-1 text-right transition-all duration-100 ..." 
                placeholder="Search farmer / receipt..." 
                type="text">
            </label>
            <button @click="isInputActive = !isInputActive" class="btn size-8 rounded-full p-0 ...">
              <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase ...">#</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase ...">Farmer</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase ...">Loan ID / Product</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase ...">Amount Paid</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase ...">Date</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase ...">Method</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase ...">Receipt</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase ...">Notes</th>
                <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase ...">Recorded</th>
              </tr>
            </thead>
            <tbody>
              <?php $index = 1; while ($row = $repayments->fetch_assoc()): ?>
                <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500 hover:bg-slate-50 dark:hover:bg-navy-600/50">
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5"><?= $index++ ?></td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 font-medium"><?= htmlspecialchars($row['farmer_name']) ?></td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    #<?= $row['loan_id'] ?><br>
                    <span class="text-xs text-slate-500"><?= htmlspecialchars($row['product_name']) ?></span>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 font-medium text-success">
                    UGX <?= number_format($row['amount_paid']) ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    <?= date('d M Y', strtotime($row['payment_date'])) ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 capitalize"><?= str_replace('_', ' ', $row['payment_method']) ?></td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5"><?= htmlspecialchars($row['receipt_number'] ?? '—') ?></td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-slate-600 dark:text-navy-200">
                    <?= htmlspecialchars($row['notes'] ?? '—') ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-xs text-slate-500 dark:text-navy-300">
                    <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                  </td>
                </tr>
              <?php endwhile; ?>
              <?php if ($repayments->num_rows === 0): ?>
                <tr>
                  <td colspan="9" class="py-10 text-center text-slate-500 dark:text-navy-300">
                    No repayments recorded yet.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination placeholder -->
        <div class="flex flex-col justify-between space-y-4 px-4 py-4 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
          <!-- Paste your existing pagination / entries selector code here -->
        </div>
      </div>
    </div>
  </div>
</main>