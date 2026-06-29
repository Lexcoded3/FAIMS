<?php
session_start();
$required_role = 'admin'; // Only admins can view activity logs
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'activity_log_helper.php';

$admin_id = $_SESSION['id'];

// Get filter parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$filters = [
    'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
    'action' => isset($_GET['action']) ? $_GET['action'] : null,
    'table' => isset($_GET['table']) ? $_GET['table'] : null,
    'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : null,
    'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : null,
    'search' => isset($_GET['search']) ? $_GET['search'] : null,
];

// Fetch activity logs
$logs = getActivityLogs($conn, $limit, $offset, $filters);
$total_logs = getActivityLogsCount($conn, $filters);
$total_pages = ceil($total_logs / $limit);

// Fetch statistics
$stats = getActivityStats($conn, 7);

// Get all unique actions for filter dropdown
$actions_result = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$all_actions = [];
while ($row = $actions_result->fetch_assoc()) {
    $all_actions[] = $row['action'];
}

// Get all unique tables for filter dropdown
$tables_result = $conn->query("SELECT DISTINCT table_name FROM activity_logs ORDER BY table_name");
$all_tables = [];
while ($row = $tables_result->fetch_assoc()) {
    $all_tables[] = $row['table_name'];
}

// Get all users for filter dropdown
$users_result = $conn->query("SELECT id, name, role FROM users ORDER BY name LIMIT 100");
$all_users = [];
while ($row = $users_result->fetch_assoc()) {
    $all_users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAIMS - Activity Logs</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">
    <link rel="stylesheet" href="../css/app.css">
    <script src="../js/app.js" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>
</head>
<body x-data="" x-bind="$store.global.documentBody" class="is-header-blur is-sidebar-open">
    <!-- App preloader -->
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900">
        <div class="app-preloader-inner relative inline-block size-48"></div>
    </div>

    <!-- Page Wrapper -->
    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak>
        <!-- Sidebar -->
        <div class="sidebar print:hidden">
            <div class="main-sidebar">
                <div class="flex h-full w-full flex-col items-center border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
                    <div class="flex pt-4">
                        <a href="index.php">
                            <img class="size-11 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
                        </a>
                    </div>
                    <?php include 'sidenav.php';?>
                </div>
            </div>
            <?php include 'userssider.php';?>
        </div>

        <!-- App Header -->
        <?php include 'toprightsidenav.php';?>

        <!-- Main Content -->
        <main class="main-content w-full px-[var(--margin-x)] pb-8">
            <div class="mt-6">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Activity Logs</h1>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track all user actions across the platform</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- <button onclick="exportLogs()" class="btn flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300 dark:hover:bg-navy-600">
                                <i class="fa-solid fa-download text-sm"></i>
                                <span>Export</span>
                            </button> -->
                             <button onclick="exportLogs()"
                                class="btn space-x-2 bg-info font-medium text-white hover:bg-info-focus hover:shadow-lg hover:shadow-info/50 focus:bg-info-focus focus:shadow-lg focus:shadow-info/50 active:bg-info-focus/90"
                              >
                                <span>Export</span>
                                <i class="fa-solid fa-download text-sm"></i>
                          </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                    <div class="card p-4 sm:p-5 border-l-4 border-primary">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Total Activities (7d)</p>
                                <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo number_format($stats['total_logs']); ?></p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <i class="fa-solid fa-chart-line text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card p-4 sm:p-5 border-l-4 border-success">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Action Types</p>
                                <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo count($stats['by_action']); ?></p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success/10 text-success">
                                <i class="fa-solid fa-layer-group text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card p-4 sm:p-5 border-l-4 border-warning">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Active Users</p>
                                <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo count($stats['by_user']); ?></p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning/10 text-warning">
                                <i class="fa-solid fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-6 mt-6">
                    <div class="border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-filter text-primary"></i>
                            Filter Activities
                        </h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <form method="GET" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <!-- User Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1.5">User</label>

                                    <select name="user_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                                        <option value="">All Users</option>
                                        <?php foreach ($all_users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo ($filters['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['name']); ?> (<?php echo $user['role']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Action Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1.5">Action Type</label>
                                    <select name="action" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                                        <option value="">All Actions</option>
                                        <?php foreach ($all_actions as $action): ?>
                                            <option value="<?php echo htmlspecialchars($action); ?>" <?php echo ($filters['action'] === $action) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($action); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Table Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1.5">Table Affected</label>
                                    <select name="table" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                                        <option value="">All Tables</option>
                                        <?php foreach ($all_tables as $table): ?>
                                            <option value="<?php echo htmlspecialchars($table); ?>" <?php echo ($filters['table'] === $table) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($table); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Start Date -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1.5">From Date</label>
                                    <input type="date" name="start_date" value="<?php echo $filters['start_date']; ?>" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                                </div>

                                <!-- End Date -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1.5">To Date</label>
                                    <input type="date" name="end_date" value="<?php echo $filters['end_date']; ?>" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                                </div>

                                <!-- Search -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1.5">Search</label>
                                    <input type="text" name="search" placeholder="Search actions..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" class="btn flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-focus">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <span>Apply Filters</span>
                                </button>
                                <!-- <a href="activity_logs.php" class="btn flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                                    <i class="fa-solid fa-rotate-right"></i>
                                    <span>Reset</span>
                                </a> -->
                                <a href="activity_logs.php">
                                <button
                                        class="btn space-x-2 bg-secondary font-medium text-white hover:bg-secondary-focus focus:bg-secondary-focus active:bg-secondary-focus/90"
                                      >
                                        <i class="fa-solid fa-rotate-right"></i>
                                        <span>Reset</span>
                                      </button>
                                  </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Activity Table -->
                <div class="card">
                    <div class="border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-list text-primary"></i>
                            Recent Activities (<?php echo $total_logs; ?> total)
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-navy-500">
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-200 dark:text-slate-100 sm:px-5">User</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-200 dark:text-slate-100 sm:px-5">Action</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-200 dark:text-slate-100 sm:px-5">Table</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-200 dark:text-slate-100 sm:px-5">Record ID</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-200 dark:text-slate-100 sm:px-5">Timestamp</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-200 dark:text-slate-100 sm:px-5">IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400 sm:px-5">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="fa-solid fa-inbox text-3xl text-slate-300 dark:text-slate-600"></i>
                                                <p>No activities found</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="border-b border-slate-100 hover:bg-slate-50 dark:border-navy-600 dark:hover:bg-navy-700/50 transition">
                                            <td class="px-4 py-3 sm:px-5">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary dark:bg-accent/10 dark:text-accent">
                                                        <?php echo strtoupper(substr($log['user_name'] ?? 'U', 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($log['user_name'] ?? 'Unknown'); ?></p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($log['user_role'] ?? ''); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                    <i class="fa-solid fa-circle-dot text-xs"></i>
                                                    <?php echo htmlspecialchars($log['action']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 sm:px-5">
                                                <code class="text-xs font-mono bg-slate-100 dark:bg-navy-700 px-2 py-1 rounded"><?php echo htmlspecialchars($log['table_name']); ?></code>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 sm:px-5">
                                                <?php echo $log['record_id'] ?? '—'; ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 sm:px-5">
                                                <span title="<?php echo $log['created_at']; ?>"><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400 sm:px-5 font-mono text-xs">
                                                <?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="border-t border-slate-200 p-4 dark:border-navy-500 sm:px-5 flex items-center justify-between">
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $total_logs); ?> of <?php echo $total_logs; ?> results
                            </p>
                            <div class="flex gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo buildPaginationUrl($page - 1, $filters); ?>" class="btn rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="<?php echo buildPaginationUrl($i, $filters); ?>" class="btn rounded-lg px-3 py-2 text-sm font-medium <?php echo ($i === $page) ? 'bg-primary text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo buildPaginationUrl($page + 1, $filters); ?>" class="btn rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-slate-300">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Teleport target -->
    <div id="x-teleport-target"></div>

    <script>
        window.addEventListener("DOMContentLoaded", () => Alpine.start());

        function buildPaginationUrl(page, filters) {
            let url = 'activity_logs.php?page=' + page;
            if (filters.user_id) url += '&user_id=' + filters.user_id;
            if (filters.action) url += '&action=' + encodeURIComponent(filters.action);
            if (filters.table) url += '&table=' + encodeURIComponent(filters.table);
            if (filters.start_date) url += '&start_date=' + filters.start_date;
            if (filters.end_date) url += '&end_date=' + filters.end_date;
            if (filters.search) url += '&search=' + encodeURIComponent(filters.search);
            return url;
        }

        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'export_activity_logs.php?' + params.toString();
        }
    </script>
</body>
</html>

<?php
function buildPaginationUrl($page, $filters) {
    $url = 'activity_logs.php?page=' . $page;
    if ($filters['user_id']) $url .= '&user_id=' . $filters['user_id'];
    if ($filters['action']) $url .= '&action=' . urlencode($filters['action']);
    if ($filters['table']) $url .= '&table=' . urlencode($filters['table']);
    if ($filters['start_date']) $url .= '&start_date=' . $filters['start_date'];
    if ($filters['end_date']) $url .= '&end_date=' . $filters['end_date'];
    if ($filters['search']) $url .= '&search=' . urlencode($filters['search']);
    return $url;
}
?>
