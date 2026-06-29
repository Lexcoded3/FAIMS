<?php
session_start();
 $required_role = 'farmer';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/db.php';

 $farmer_id = $_SESSION['id'];

// Fetch available loan products for the application modal
// Note: Adjust 'name' if your loan_products table uses a different column (e.g., 'product_name')
 $products = $conn->query("SELECT id, name FROM loan_products")->fetch_all(MYSQLI_ASSOC);

// Fetch farmer's loans (Joining products to get the actual name)
 $stmt = $conn->prepare("
    SELECT l.*, lp.name AS product_name 
    FROM loans l 
    LEFT JOIN loan_products lp ON l.product_id = lp.id 
    WHERE l.farmer_id = ? 
    ORDER BY l.created_at DESC
");
 $stmt->bind_param("i", $farmer_id);
 $stmt->execute();
 $loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate Summary Stats
 $activeStatuses = ['active', 'disbursed', 'overdue'];
 $activeLoans = array_filter($loans, fn($l) => in_array($l['status'], $activeStatuses));

 $totalOwed = array_sum(array_map(fn($l) => floatval($l['total_repayable'] ?? 0), $activeLoans));
 $totalPaid = array_sum(array_map(fn($l) => floatval($l['total_paid'] ?? 0), $activeLoans));
 $pendingCount = count(array_filter($loans, fn($l) => in_array($l['status'], ['pending', 'under_review'])));

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $amount = floatval($_POST['requested_amount'] ?? 0);
    $duration = intval($_POST['duration_months'] ?? 0);
    $purpose = trim($_POST['purpose'] ?? '');

    if ($product_id > 0 && $amount > 0 && $duration > 0) {
        $insert = $conn->prepare("INSERT INTO loans (farmer_id, product_id, requested_amount, duration_months, purpose) VALUES (?, ?, ?, ?, ?)");
         $insert->bind_param("iidis", $farmer_id, $product_id, $amount, $duration, $purpose);
        
        if ($insert->execute()) {
            header("Location: l.php?msg=applied");
            exit();
        } else {
            $error_msg = "Failed to submit application. Please try again.";
        }
    } else {
        $error_msg = "Please fill in all required fields correctly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - My Loans</title>
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
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900"><div class="app-preloader-inner relative inline-block size-48"></div></div>
    
    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak>
        <!-- Sidebar -->
        <div class="sidebar print:hidden">
            <div class="main-sidebar"><div class="flex h-full w-full flex-col items-center border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
                <div class="flex pt-4"><a href="index.php"><img class="size-11" src="../images/app-logo.png" alt="logo"></a></div>
                <?php include 'sidenav.php';?>
            </div></div>
            <?php include 'loansider.php';?>
        </div>
        <?php include '../farmer/toprightsidenav.php';?>

        <main class="main-content w-full px-[var(--margin-x)] pb-8">
            <!-- Header -->
            <div class="flex items-center justify-between py-5 lg:py-6">
                <div class="flex items-center space-x-4">
                    <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">My Loans</h2>
                    <div class="hidden h-full py-1 sm:flex"><div class="h-full w-px bg-slate-300 dark:bg-navy-600"></div></div>
                    <ul class="hidden flex-wrap items-center space-x-2 sm:flex">
                        <li class="text-slate-500 dark:text-navy-300">Financial Services</li>
                    </ul>
                </div>
                
                <?php if(!empty($products)): ?>
                <button @click="$dispatch('open-loan-modal')" class="btn bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus rounded-lg px-4 py-2.5 text-sm font-medium shadow-sm">
                    <i class="fas fa-plus mr-2"></i> New Application
                </button>
                <?php endif; ?>
            </div>

            <!-- Alerts -->
            <?php if(isset($_GET['msg']) && $_GET['msg'] === 'applied'): ?>
                <div class="mb-4 flex items-center p-4 rounded-lg bg-success/10 border border-success text-success text-sm">
                    <i class="fas fa-check-circle mr-3 text-lg"></i>
                    Application submitted successfully. It is currently under review.
                </div>
            <?php endif; ?>

            

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="card p-5 flex items-center space-x-4">
                    <div class="mask is-squircle flex size-12 items-center justify-center bg-error/10">
                        <i class="fas fa-file-invoice-dollar text-xl text-error"></i>
                    </div>
                    <div>
                        <p class="text-xs+ text-slate-400 dark:text-navy-300">Outstanding Balance</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-navy-100">UGX <?= number_format($totalOwed - $totalPaid) ?></p>
                    </div>
                </div>
                
                <div class="card p-5 flex items-center space-x-4">
                    <div class="mask is-squircle flex size-12 items-center justify-center bg-warning/10">
                        <i class="fas fa-hourglass-half text-xl text-warning"></i>
                    </div>
                    <div>
                        <p class="text-xs+ text-slate-400 dark:text-navy-300">Pending/Review</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-navy-100"><?= $pendingCount ?></p>
                    </div>
                </div>

                <div class="card p-5 flex items-center space-x-4">
                    <div class="mask is-squircle flex size-12 items-center justify-center bg-success/10">
                        <i class="fas fa-check-double text-xl text-success"></i>
                    </div>
                    <div>
                        <p class="text-xs+ text-slate-400 dark:text-navy-300">Total Repaid</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-navy-100">UGX <?= number_format($totalPaid) ?></p>
                    </div>
                </div>
            </div>

            <!-- Loans List -->
            <div class="card overflow-hidden mb-16">
                <div class="px-5 py-4 border-b border-slate-150 dark:border-navy-600">
                    <h3 class="font-medium text-slate-700 dark:text-navy-100">Loan History</h3>
                </div>

                <?php if(empty($loans)): ?>
                    <div class="p-10 text-center">
                        <i class="fas fa-hand-holding-usd text-4xl text-slate-300 dark:text-navy-600 mb-4 block"></i>
                        <p class="text-slate-500 dark:text-navy-300">You don't have any loan records yet.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-navy-800/50 text-xs+ uppercase tracking-wide text-slate-500 dark:text-navy-300">
                                <tr>
                                    <th class="px-5 py-3">Product</th>
                                    <th class="px-5 py-3">Requested</th>
                                    <th class="px-5 py-3">Approved / Repayable</th>
                                    <th class="px-5 py-3">Balance</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Applied</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-150 dark:divide-navy-600">
                                <?php foreach($loans as $loan): 
                                    // Map your complex enum statuses to UI colors
                                    $statusConfig = [
                                        'pending'       => ['bg' => 'bg-slate-100 dark:bg-navy-600', 'text' => 'text-slate-600 dark:text-navy-200'],
                                        'under_review'  => ['bg' => 'bg-warning/10', 'text' => 'text-warning'],
                                        'approved'      => ['bg' => 'bg-info/10', 'text' => 'text-info'],
                                        'disbursed'     => ['bg' => 'bg-info/10', 'text' => 'text-info'],
                                        'active'        => ['bg' => 'bg-success/10', 'text' => 'text-success'],
                                        'overdue'       => ['bg' => 'bg-error/10', 'text' => 'text-error font-bold'],
                                        'defaulted'     => ['bg' => 'bg-error/10', 'text' => 'text-error font-bold'],
                                        'rejected'      => ['bg' => 'bg-error/10', 'text' => 'text-error'],
                                        'repaid'        => ['bg' => 'bg-success/10', 'text' => 'text-success'],
                                        'written_off'   => ['bg' => 'bg-slate-100 dark:bg-navy-600', 'text' => 'text-slate-500 line-through'],
                                    ];
                                    $sCfg = $statusConfig[$loan['status']] ?? $statusConfig['pending'];
                                    
                                    $balance = floatval($loan['total_repayable'] ?? 0) - floatval($loan['total_paid'] ?? 0);
                                ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-navy-800/30">
                                    <td class="px-5 py-4">
                                        <p class="font-medium text-slate-800 dark:text-navy-100"><?= htmlspecialchars($loan['product_name'] ?? 'Unknown Product') ?></p>
                                        <p class="text-xs text-slate-400 dark:text-navy-300"><?= $loan['duration_months'] ?> Months</p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 dark:text-navy-200">
                                        UGX <?= number_format($loan['requested_amount']) ?>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 dark:text-navy-200">
                                        <?php if($loan['approved_amount'] !== NULL): ?>
                                            <p>UGX <?= number_format($loan['approved_amount']) ?></p>
                                            <p class="text-xs text-slate-400">(Repay: UGX <?= number_format($loan['total_repayable']) ?>)</p>
                                        <?php else: ?>
                                            <span class="text-slate-400">---</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4 font-medium <?= $balance > 0 && in_array($loan['status'], ['active', 'overdue']) ? 'text-error' : 'text-slate-700 dark:text-navy-200' ?>">
                                        UGX <?= number_format($balance) ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize <?= $sCfg['bg'] ?> <?= $sCfg['text'] ?>">
                                            <?= str_replace('_', ' ', $loan['status']) ?>
                                        </span>
                                        <?php if($loan['status'] === 'rejected' && $loan['rejection_reason']): ?>
                                            <p class="text-xs text-error mt-1" title="<?= htmlspecialchars($loan['rejection_reason']) ?>">
                                                <i class="fas fa-info-circle"></i> Reason available
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4 text-slate-500 dark:text-navy-300 whitespace-nowrap">
                                        <?= date('M d, Y', strtotime($loan['application_date'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Application Modal -->
    <div 
        x-data="{ isOpen: false }" 
        @open-loan-modal.window="isOpen = true"
        x-show="isOpen" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="isOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-xl w-full max-w-lg p-6 relative max-h-[90vh] overflow-y-auto" @click.stop>
            
            <button @click="isOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-navy-200">
                <i class="fas fa-times text-lg"></i>
            </button>

            <h3 class="text-lg font-bold text-slate-800 dark:text-navy-50 mb-1">New Loan Application</h3>
            <p class="text-sm text-slate-500 dark:text-navy-300 mb-6">Select a product and specify the amount you need.</p>

            <form action="l.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Loan Product *</label>
                    <select name="product_id" required class="form-input w-full rounded-lg border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100">
                        <option value="">-- Select Product --</option>
                        <?php foreach($products as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Amount (UGX) *</label>
                        <input type="number" name="requested_amount" required min="10000" step="1000" placeholder="e.g. 500000" class="form-input w-full rounded-lg border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Duration (Months) *</label>
                        <input type="number" name="duration_months" required min="1" max="24" placeholder="e.g. 6" class="form-input w-full rounded-lg border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Purpose / Justification</label>
                    <textarea name="purpose" rows="3" placeholder="Briefly state what you need this loan for..." class="form-input w-full rounded-lg border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100"></textarea>
                </div>

                <div class="flex justify-end space-x-3 pt-2 border-t border-slate-150 dark:border-navy-600">
                    <button type="button" @click="isOpen = false" class="btn px-4 py-2 rounded-lg border border-slate-300 dark:border-navy-600 text-slate-700 dark:text-navy-100 hover:bg-slate-100 dark:hover:bg-navy-700 text-sm font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="btn bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus px-5 py-2 rounded-lg text-sm font-medium">
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="x-teleport-target"></div>
    <script>window.addEventListener("DOMContentLoaded", () => Alpine.start());</script>
    <!-- Login Success Notification Trigger -->
<div x-data 
     x-init="
        const params = new URLSearchParams(window.location.search);
        if(params.get('msg') === 'applied') {
            // Fire the notification
            $notification({text:'Application submitted successfully. It is currently under review.', variant:'success', position:'right-top'});
            
            // Clean the URL so it doesn't trigger again on refresh
            const url = new URL(window.location);
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname);
        }
     ">
</div>
</body>
</html>