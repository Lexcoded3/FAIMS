<?php
session_start();
$required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
// =========================
// Filters
// =========================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// =========================
// Search condition
// =========================
$search_sql = "";
$search_params = [];
$search_types = "";
if (!empty($search)) {
    $search_sql = " AND (o.order_code LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $search_params = [$search_param, $search_param, $search_param];
    $search_types = "sss";
}

// =========================
// Status filter
// =========================
$status_sql = "";
$status_params = [];
$status_types = "";
if (!empty($status_filter)) {
    $status_sql = " AND o.status = ?";
    $status_params = [$status_filter];
    $status_types = "s";
}

// =========================
// Combined params & types (for both queries)
// =========================
$base_params = array_merge($search_params, $status_params);
$base_types  = $search_types . $status_types;

// =========================
// MAIN QUERY
// =========================
$query = "
    SELECT
        o.id,
        o.order_code,
        o.status,
        o.amount,
        o.created_at,
        u.id AS user_id,
        u.name AS buyer_name,
        u.email,
        u.image_paths
    FROM orders o
    LEFT JOIN users u ON o.buyer_id = u.id
    WHERE 1=1
    $search_sql
    $status_sql
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Main query prepare failed: " . $conn->error);
}

$params = $base_params;           // copy base
$types  = $base_types;            // copy base

// Add pagination params
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt->bind_param($types, ...$params);
$stmt->execute();
$orderQuery = $stmt->get_result();

// =========================
// COUNT QUERY
// =========================
$count_query = "
    SELECT COUNT(*) as total
    FROM orders o
    LEFT JOIN users u ON o.buyer_id = u.id
    WHERE 1=1
    $search_sql
    $status_sql
";

$count_stmt = $conn->prepare($count_query);
if (!$count_stmt) {
    die("Count query prepare failed: " . $conn->error);
}

// Bind only base params for count query (no LIMIT/OFFSET)
if (!empty($base_params)) {
    $count_stmt->bind_param($base_types, ...$base_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_orders = $count_result->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_orders / $limit);

// =========================
// STATS QUERY (Global - no filters)
// =========================
$stats_query = "
    SELECT
        COUNT(*) as all_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_revenue
    FROM orders
";

$stats_result = $conn->query($stats_query);
if (!$stats_result) {
    die("Stats query failed: " . $conn->error);
}
$stats = $stats_result->fetch_assoc();

$completedOrders = $stats['completed_orders'] ?? 0;
$pendingOrders   = $stats['pending_orders'] ?? 0;
$cancelledOrders = $stats['cancelled_orders'] ?? 0;
$allEarnings     = $stats['completed_revenue'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Orders</title>
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
        <?php include 'orderssider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 px-[var(--margin-x)] transition-all duration-[.25s] sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <!-- Stats Cards -->
          <div class="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-4 sm:gap-5">
            <div class="card px-4 py-5 sm:px-5">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs+ text-slate-500 dark:text-slate-400">Total Revenue</p>
                  <p class="text-2xl font-semibold text-slate-900 dark:text-white mt-1">
                    UGX <?php echo number_format($allEarnings); ?>
                  </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent">
                  <i class="fa-solid fa-money-bill-wave text-xl"></i>
                </div>
              </div>
            </div>

            <div class="card px-4 py-5 sm:px-5">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs+ text-slate-500 dark:text-slate-400">Completed Orders</p>
                  <p class="text-2xl font-semibold text-slate-900 dark:text-white mt-1">
                    <?php echo $completedOrders; ?>
                  </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success/10 text-success dark:bg-success/20 dark:text-emerald-400">
                  <i class="fa-solid fa-circle-check text-xl"></i>
                </div>
              </div>
            </div>

            <div class="card px-4 py-5 sm:px-5">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs+ text-slate-500 dark:text-slate-400">Pending Orders</p>
                  <p class="text-2xl font-semibold text-slate-900 dark:text-white mt-1">
                    <?php echo $pendingOrders; ?>
                  </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning/10 text-warning dark:bg-warning/20">
                  <i class="fa-solid fa-hourglass-end text-xl"></i>
                </div>
              </div>
            </div>

            <div class="card px-4 py-5 sm:px-5">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs+ text-slate-500 dark:text-slate-400">Cancelled Orders</p>
                  <p class="text-2xl font-semibold text-slate-900 dark:text-white mt-1">
                    <?php echo $cancelledOrders; ?>
                  </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-error/10 text-error dark:bg-error/20">
                  <i class="fa-solid fa-xmark text-xl"></i>
                </div>
              </div>
            </div>
          </div>
          <!-- Orders Table -->
          <div class="col-span-12">
            <div class="card">
              <!-- Header -->
              <div class="border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                <div class="flex items-center justify-between gap-4">
                  <h2 class="text-lg font-semibold text-slate-900 dark:text-white">All Orders</h2>
                  
                  <!-- Search & Filter -->
                  <div class="flex items-center gap-2">
                    <form method="GET" class="flex gap-2">
                      <div class="flex items-center gap-2">
                        <input type="text" name="search" placeholder="Search order..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="form-input rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-navy-500 dark:bg-navy-700">
                        
                        <select name="status" class="form-input rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-navy-500 dark:bg-navy-700">
                          <option value="">All Status</option>
                          <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                          <option value="completed" <?php echo ($status_filter === 'completed') ? 'selected' : ''; ?>>Completed</option>
                          <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>

                        <button type="submit" class="btn flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                          <i class="fa-solid fa-magnifying-glass"></i>
                          <span>Search</span>
                        </button>
                      </div>
                    </form>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                    <a href="orders.php">
                    <button
                      class="btn size-9 rounded-full bg-slate-150 p-0 font-medium text-slate-800 hover:bg-slate-200 hover:shadow-lg hover:shadow-slate-200/50 focus:bg-slate-200 focus:shadow-lg focus:shadow-slate-200/50 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:hover:shadow-navy-450/50 dark:focus:bg-navy-450 dark:focus:shadow-navy-450/50 dark:active:bg-navy-450/90"
                    >
                      <i class="fa-solid fa-rotate-right"></i>
                    </button>
                    </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Table -->
              <div class="overflow-x-auto">
                <table class="w-full text-left">
                  <thead>
                    <tr>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold text-slate-800 dark:bg-navy-800 dark:text-navy-100 sm:px-5">Order ID</th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold text-slate-800 dark:bg-navy-800 dark:text-navy-100 sm:px-5">Date</th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold text-slate-800 dark:bg-navy-800 dark:text-navy-100 sm:px-5">Customer</th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold text-slate-800 dark:bg-navy-800 dark:text-navy-100 sm:px-5">Status</th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold text-slate-800 dark:bg-navy-800 dark:text-navy-100 sm:px-5">Amount</th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold text-slate-800 dark:bg-navy-800 dark:text-navy-100 sm:px-5">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($orderQuery->num_rows === 0): ?>
                      <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400 sm:px-5">
                          <div class="flex flex-col items-center gap-2">
                            <i class="fa-solid fa-inbox text-3xl text-slate-300 dark:text-slate-600"></i>
                            <p>No orders found</p>
                          </div>
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php while ($row = $orderQuery->fetch_assoc()): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50 dark:border-navy-600 dark:hover:bg-navy-700/50">
                          <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                            <p class="font-semibold text-primary dark:text-accent-light">#<?php echo $row['order_code']; ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">ID: <?php echo $row['id']; ?></p>
                          </td>
                          <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                            <p class="font-medium text-slate-900 dark:text-white"><?php echo date("d M Y", strtotime($row['created_at'])); ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo date("H:i A", strtotime($row['created_at'])); ?></p>
                          </td>
                          <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                            <div class="flex items-center gap-2">
                              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary dark:bg-accent/10 dark:text-accent">
                                <?php echo strtoupper(substr($row['buyer_name'] ?? 'U', 0, 1)); ?>
                              </div>
                              <div>
                                <p class="font-medium text-slate-900 dark:text-white text-sm"><?php echo htmlspecialchars($row['buyer_name']); ?></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($row['email']); ?></p>
                              </div>
                            </div>
                          </td>
                          <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                              <?php 
                                  $status = $row['status'] ?? 'pending';

                                  // Map status to your framework's color names
                                  $color = match($status) {
                                      'completed' => 'success',
                                      'cancelled'     => 'error',
                                      'processing'     => 'info',
                                      'confirmed'     => 'secondary',
                                      default     => 'warning', // or 'info'/'secondary'
                                  };
                                  
                                  $status_text = ucfirst($status);
                              ?>
                              <div class="badge space-x-2.5 rounded-full bg-<?= $color ?>/10 text-<?= $color ?> dark:bg-<?= $color ?>/15">
                                  <div class="size-2 rounded-full bg-current"></div>
                                  <span><?= $status_text ?></span>
                              </div>
                          </td>

                          <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                            <p class="font-semibold text-slate-900 dark:text-white">UGX <?php echo number_format($row['amount']); ?></p>
                          </td>
                          <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                            <div x-data="{ showModal: false, orderId: <?php echo $row['id']; ?> }" class="flex gap-2">
                              <button @click="showModal = true" class="btn rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus" x-tooltip="'Details'">
                                <i class="fa-solid fa-eye"></i>
                              </button>
                              <?php if ($row['status'] === 'pending'): ?>
                                <a href="complete_order.php?id=<?php echo $row['id']; ?>" >
                              <button
                                  class="btn size-9 bg-success p-0 font-medium text-white hover:bg-success-focus hover:shadow-lg hover:shadow-success/50 focus:bg-success-focus focus:shadow-lg focus:shadow-success/50 active:bg-success-focus/90" x-tooltip="'Complete'"
                                >
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
                                      d="m4.5 12.75 6 6 9-13.5"
                                    />
                                  </svg>
                                </button>
                              </a>
                              <?php endif; ?>

                              <!-- Modal -->
                              <template x-teleport="#x-teleport-target">
                                <div class="fixed inset-0 z-[100] flex items-center justify-center overflow-hidden px-4 py-6 sm:px-5" x-show="showModal" @keydown.window.escape="showModal = false" role="dialog">
                                  <div class="absolute inset-0 bg-slate-900/60 transition-opacity" @click="showModal = false" x-show="showModal" x-transition></div>
                                  
                                  <div class="relative max-w-sm rounded-lg bg-white px-4 pb-4 transition-all dark:bg-navy-700 sm:px-5" x-show="showModal" x-transition>
                                    <div class="my-3 flex items-center justify-between">
                                      <h2 class="font-semibold text-slate-700 dark:text-navy-100">Order Details #<?php echo $row['order_code']; ?></h2>
                                      <button @click="showModal = false" class="btn -mr-1.5 size-7 rounded-full p-0 hover:bg-slate-300/20 dark:hover:bg-navy-300/20">
                                        <i class="fa-solid fa-xmark"></i>
                                      </button>
                                    </div>
                                    
                                    <div class="space-y-3 py-3">
                                      <div class="flex justify-between">
                                        <span class="text-slate-600 dark:text-slate-400">Order ID:</span>
                                        <span class="font-semibold text-slate-900 dark:text-white"><?php echo $row['id']; ?></span>
                                      </div>
                                      <div class="flex justify-between">
                                        <span class="text-slate-600 dark:text-slate-400">Code:</span>
                                        <span class="font-semibold text-slate-900 dark:text-white"><?php echo $row['order_code']; ?></span>
                                      </div>
                                      <div class="flex justify-between">
                                        <span class="text-slate-600 dark:text-slate-400">Customer:</span>
                                        <span class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($row['buyer_name']); ?></span>
                                      </div>
                                      <div class="flex justify-between">
                                        <span class="text-slate-600 dark:text-slate-400">Amount:</span>
                                        <span class="font-semibold text-slate-900 dark:text-white">UGX <?php echo number_format($row['amount']); ?></span>
                                      </div>
                                      <div class="flex justify-between">
                                        <span class="text-slate-600 dark:text-slate-400">Status:</span>
                                        <span class="font-semibold text-<?php echo $status_color; ?>-600 dark:text-<?php echo $status_color; ?>-400"><?php echo ucfirst($row['status']); ?></span>
                                      </div>
                                      <div class="flex justify-between">
                                        <span class="text-slate-600 dark:text-slate-400">Date:</span>
                                        <span class="font-semibold text-slate-900 dark:text-white"><?php echo date("d M Y H:i", strtotime($row['created_at'])); ?></span>
                                      </div>
                                    </div>

                                    <div class="border-t border-slate-200 pt-3 dark:border-navy-500">
                                      <button @click="showModal = false" class="btn w-full rounded-lg bg-primary text-xs+ font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                                        Close
                                      </button>
                                    </div>
                                  </div>
                                </div>
                              </template>
                            </div>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div class="border-t border-slate-200 p-4 dark:border-navy-500 sm:px-5 flex items-center justify-between">
                <p class="text-sm text-slate-600 dark:text-slate-400">
                  Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $total_orders); ?> of <?php echo $total_orders; ?> orders
                </p>
                
                <?php if ($total_pages > 1): ?>
                  <div class="flex gap-1">
                    <?php if ($page > 1): ?>
                      <a href="orders.php?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="btn rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                        <i class="fa-solid fa-chevron-left"></i>
                      </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                      <a href="orders.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" 
                         class="btn rounded-lg px-3 py-2 text-sm font-medium <?php echo ($i === $page) ? 'bg-primary text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300'; ?>">
                        <?php echo $i; ?>
                      </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                      <a href="orders.php?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="btn rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                        <i class="fa-solid fa-chevron-right"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
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
  </body>
</html>
