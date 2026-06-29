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
            header("Location: loans.php?msg=applied");
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
                    <div x-data="{showModal:false}">
                        <button
                          @click="showModal = true"
                          class="btn bg-slate-150 font-medium text-primary hover:bg-primary focus:bg-primary-200 active:bg-primary-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90"
                        >
                        <i class="fas fa-plus mr-2"></i>
                          New Application
                        </button>
                        <template x-teleport="#x-teleport-target">
                          <div
                            class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5"
                            x-show="showModal"
                            role="dialog"
                            @keydown.window.escape="showModal = false"
                          >
                            <div
                              class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300"
                              @click="showModal = false"
                              x-show="showModal"
                              x-transition:enter="ease-out"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="ease-in"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                            ></div>
                            <div
                              class="relative w-full max-w-lg origin-top rounded-lg bg-white transition-all duration-300 dark:bg-navy-700"
                              x-show="showModal"
                              x-transition:enter="easy-out"
                              x-transition:enter-start="opacity-0 scale-95"
                              x-transition:enter-end="opacity-100 scale-100"
                              x-transition:leave="easy-in"
                              x-transition:leave-start="opacity-100 scale-100"
                              x-transition:leave-end="opacity-0 scale-95"
                            >
                              <div
                                class="flex justify-between rounded-t-lg bg-slate-200 px-4 py-3 dark:bg-navy-800 sm:px-5"
                              >
                                <h3 class="text-base font-medium text-slate-700 dark:text-navy-100">
                                  New Loan Application
                                </h3>
                                <button
                                  @click="showModal = !showModal"
                                  class="btn -mr-1.5 size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25"
                                >
                                  <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="size-4.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                  >
                                    <path
                                      stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M6 18L18 6M6 6l12 12"
                                    ></path>
                                  </svg>
                                </button>
                              </div>
                              <div class="px-4 py-4 sm:px-5">
                                <p>
                                  Select a product and specify the amount you need
                                </p>
                                <form action="loans.php" method="POST">
                                <div class="mt-4 space-y-4">
                                  <label class="block">
                                    <span>Loan Product *</span>
                                    <select  name="product_id" required
                                      class="form-select mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:bg-navy-700 dark:hover:border-navy-400 dark:focus:border-accent"
                                    >
                                      <option value="">-- Select Product --</option>
                                            <?php foreach($products as $p): ?>
                                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                            <?php endforeach; ?>
                                    </select>
                                  </label>
                                  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                      <label class="block">
                                        <span>Amount (UGX)</span>
                                        <span class="relative mt-1.5 flex">
                                          <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" ype="number" name="requested_amount" required min="10000" step="1000" placeholder="e.g. 500000">
                                          <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                                            <i class="fa fa-money-bill"></i>
                                          </span>
                                        </span>
                                      </label>
                                      <label class="block">
                                        <span>Duration (Months)</span>
                                        <span class="relative mt-1.5 flex">
                                          <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="number" name="duration_months" required min="1" max="24" placeholder="e.g. 6">
                                          <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                                            <i class="fa fa-calendar"></i>
                                          </span>
                                        </span>
                                      </label>
                                    </div>
                                  <label class="block">
                                    <span>Purpose / Justification</span>
                                    <textarea
                                    name="purpose"
                                      rows="4"
                                        placeholder="Briefly state what you need this loan for..."
                                      class="form-textarea mt-1.5 w-full resize-none rounded-lg border border-slate-300 bg-transparent p-2.5 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                                    ></textarea>
                                  </label>
                                  
                                  <div class="space-x-2 text-right">
                                    <button
                                      @click="showModal = false"
                                      class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90"
                                    >
                                      Cancel
                                    </button>
                                    <button
                                        type="submit"
                                      class="btn min-w-[7rem] rounded-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90"
                                    >
                                      Submit Application
                                    </button>
                                  </div>
                                    </form>
                                </div>
                              </div>
                            </div>
                          </div>
                        </template>
                      </div>
                <?php endif; ?>
            </div>

        

            

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6" style="gap: 1.5rem; margin-bottom: 2rem;">
              <div class="card p-4 sm:p-5">
                <div class="flex size-12 items-center justify-center rounded-xl bg-primary shadow-xl shadow-primary/50 dark:bg-accent dark:shadow-accent/50">
                  <i class="fa fa-dollar-sign text-xl text-white"></i>
                </div>
                <p class="mt-4">Outstanding Balance</p>
                <p class="mt-2 font-medium text-slate-700 dark:text-navy-100">
                  <span class="text-2xl">UGX <?= number_format($totalOwed - $totalPaid) ?></span>
                  <!-- <span class="text-base">.3k</span> -->
                </p>
                <!-- <p class="mt-1 flex items-center text-xs text-success">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                  </svg>
                  <span>4.3%</span>
                </p> -->
              </div>
              <div class="card p-4 sm:p-5">
                <div class="flex size-12 items-center justify-center rounded-xl bg-warning shadow-xl shadow-warning/50">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"></path>
                  </svg>
                </div>
                <p class="mt-4">Pending/Review</p>
                <p class="mt-2 font-medium text-slate-700 dark:text-navy-100">
                  <span class="text-2xl"><?= $pendingCount ?></span>
                  <!-- <span class="text-base">.14k</span> -->
                </p>
                <!-- <p class="mt-1 flex items-center text-xs text-error">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-error" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                  </svg>
                  <span>1.9%</span>
                </p> -->
              </div>
              <div class="card p-4 sm:p-5">
                <div class="flex size-12 items-center justify-center rounded-xl bg-success shadow-xl shadow-success/50">
                  <i class="fas fa-check-double text-xl text-white"></i>
                </div>
                <p class="mt-4">Total Repaid</p>
                <p class="mt-2 font-medium text-slate-700 dark:text-navy-100">
                  <span class="text-2xl">UGX <?= number_format($totalPaid) ?></span>
                </p>
                <!-- <p class="mt-1 flex items-center text-xs text-success">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                  </svg>
                  <span>7.11%</span>
                </p> -->
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
                            <thead class="bg-navy-500 dark:bg-navy-800/50 text-xs+ uppercase tracking-wide text-slate-500 dark:text-navy-300">
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
                                    <td class="px-6 py-5">
                                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold capitalize <?= $sCfg['bg'] ?> <?= $sCfg['text'] ?>">
                                            <?= str_replace('_', ' ', $loan['status']) ?>
                                        </span>
                                        <?php if($loan['status'] === 'rejected' && $loan['rejection_reason']): ?>
                                            <p class="text-xs text-error mt-2" title="<?= htmlspecialchars($loan['rejection_reason']) ?>">
                                                <i class="fas fa-info-circle"></i> Hover for reason
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
    <div id="x-teleport-target"></div>
    <script>window.addEventListener("DOMContentLoaded", () => Alpine.start());</script>
    <!-- Login Success Notification Trigger -->
<div x-data 
     x-init="
        const params = new URLSearchParams(window.location.search);
        if(params.get('msg') === 'applied') {
            // Fire the notification
            $notification({text:'Application submitted successfully. Under review!', variant:'success', position:'right-top'});
            
            // Clean the URL so it doesn't trigger again on refresh
            const url = new URL(window.location);
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname);
        }
     ">
</div>
</body>
</html>