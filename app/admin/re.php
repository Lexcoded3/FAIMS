<?php
session_start();
 $required_role = 'admin';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// --- Handle Filters ---
 $whereClauses = ["1=1"];
 $params = [];
 $types = "";

if (!empty($_GET['type']) && in_array($_GET['type'], ['disease', 'yield', 'soil', 'water', 'other'])) {
    $whereClauses[] = "er.type = ?";
    $params[] = $_GET['type'];
    $types .= "s";
}

if (!empty($_GET['district'])) {
    $whereClauses[] = "er.district LIKE ?";
    $params[] = "%" . $_GET['district'] . "%";
    $types .= "s";
}

 $whereSQL = implode(" AND ", $whereClauses);

// --- Fetch Reports ---
// Assuming your users table has 'firstname' and 'lastname'. If it's just 'name', change CONCAT to u.name
 $sql = "SELECT er.*, CONCAT(u.name) AS author_name 
        FROM extension_reports er 
        JOIN users u ON er.extension_id = u.id 
        WHERE $whereSQL 
        ORDER BY er.created_at DESC";

 $stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
 $stmt->execute();
 $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Calculate Stats ---
// We do this in PHP to avoid 5 extra SQL queries
 $stats = ['total' => 0, 'disease' => 0, 'yield' => 0, 'soil' => 0, 'water' => 0, 'other' => 0];
 $districts = [];

foreach ($reports as $r) {
    $stats['total']++;
    if (isset($stats[$r['type']])) $stats[$r['type']]++;
    if (!empty($r['district']) && !in_array($r['district'], $districts)) {
        $districts[] = $r['district'];
    }
}
sort($districts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Extension Reports - FAIMSAdmin</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">
    <link rel="stylesheet" href="../css/app.css">
    <script src="../js/app.js" defer></script>
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <script>localStorage.getItem("_x_darkMode_on") === "true" && document.documentElement.classList.add("dark");</script>
</head>
<body x-data="" class="is-header-blur is-sidebar-open" x-bind="$store.global.documentBody">
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900"><div class="app-preloader-inner relative inline-block size-48"></div></div>
    
    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak>
        <!-- Sidebar (Assuming you have an admin sidebar) -->
        <?php include 'sidenav.php'; ?> 

        <main class="main-content w-full px-[var(--margin-x)] pb-8">
            <!-- Header -->
            <div class="flex items-center justify-between py-5 lg:py-6">
                <div class="flex items-center space-x-4">
                    <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">Extension Reports</h2>
                    <div class="hidden h-full py-1 sm:flex"><div class="h-full w-px bg-slate-300 dark:bg-navy-600"></div></div>
                    <ul class="hidden flex-wrap items-center space-x-2 sm:flex">
                        <li class="text-slate-500 dark:text-navy-300">Field Intelligence</li>
                    </ul>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-slate-800 dark:text-navy-100"><?= $stats['total'] ?></p>
                    <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Total Reports</p>
                </div>
                <div class="card p-4 text-center border-l-4 border-l-error">
                    <p class="text-2xl font-bold text-error"><?= $stats['disease'] ?></p>
                    <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Disease</p>
                </div>
                <div class="card p-4 text-center border-l-4 border-l-success">
                    <p class="text-2xl font-bold text-success"><?= $stats['yield'] ?></p>
                    <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Yield</p>
                </div>
                <div class="card p-4 text-center border-l-4 border-l-warning">
                    <p class="text-2xl font-bold text-warning"><?= $stats['soil'] ?></p>
                    <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Soil</p>
                </div>
                <div class="card p-4 text-center border-l-4 border-l-info">
                    <p class="text-2xl font-bold text-info"><?= $stats['water'] ?></p>
                    <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Water</p>
                </div>
                <div class="card p-4 text-center border-l-4 border-l-slate-400">
                    <p class="text-2xl font-bold text-slate-500"><?= $stats['other'] ?></p>
                    <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Other</p>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" class="card p-4 mb-6 flex flex-col sm:flex-row gap-4 items-end">
                <div class="w-full sm:w-1/3">
                    <label class="block text-xs+ text-slate-500 dark:text-navy-300 mb-1">Report Type</label>
                    <select name="type" class="form-input w-full rounded-lg border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100">
                        <option value="">All Types</option>
                        <option value="disease" <?= (($_GET['type'] ?? '') == 'disease') ? 'selected' : '' ?>>Disease</option>
                        <option value="yield" <?= (($_GET['type'] ?? '') == 'yield') ? 'selected' : '' ?>>Yield</option>
                        <option value="soil" <?= (($_GET['type'] ?? '') == 'soil') ? 'selected' : '' ?>>Soil</option>
                        <option value="water" <?= (($_GET['type'] ?? '') == 'water') ? 'selected' : '' ?>>Water</option>
                        <option value="other" <?= (($_GET['type'] ?? '') == 'other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="w-full sm:w-1/3">
                    <label class="block text-xs+ text-slate-500 dark:text-navy-300 mb-1">District</label>
                    <select name="district" class="form-input w-full rounded-lg border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100">
                        <option value="">All Districts</option>
                        <?php foreach($districts as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= (($_GET['district'] ?? '') == $d) ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" class="btn bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus px-4 py-2.5 rounded-lg text-sm font-medium w-full sm:w-auto">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <a href="admin_reports.php" class="btn border border-slate-300 dark:border-navy-600 text-slate-700 dark:text-navy-100 hover:bg-slate-100 dark:hover:bg-navy-700 px-4 py-2.5 rounded-lg text-sm font-medium w-full sm:w-auto text-center">
                        Clear
                    </a>
                </div>
            </form>

            <!-- Reports Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-navy-800/50 text-xs+ uppercase tracking-wide text-slate-500 dark:text-navy-300">
                            <tr>
                                <th class="px-5 py-3">Title & District</th>
                                <th class="px-5 py-3">Author</th>
                                <th class="px-5 py-3">Type</th>
                                <th class="px-5 py-3">Date Filed</th>
                                <th class="px-5 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150 dark:divide-navy-600">
                            <?php if(empty($reports)): ?>
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-slate-400 dark:text-navy-400">
                                        No reports found matching your filters.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                // Type Color Mapping
                                $typeColors = [
                                    'disease' => 'bg-error/10 text-error',
                                    'yield' => 'bg-success/10 text-success',
                                    'soil' => 'bg-warning/10 text-warning',
                                    'water' => 'bg-info/10 text-info',
                                    'other' => 'bg-slate-100 text-slate-600 dark:bg-navy-600 dark:text-navy-200'
                                ];
                                
                                foreach ($reports as $r): 
                                    $tColor = $typeColors[$r['type']] ?? $typeColors['other'];
                                ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-navy-800/30">
                                    <td class="px-5 py-4">
                                        <p class="font-medium text-slate-800 dark:text-navy-100"><?= htmlspecialchars($r['title']) ?></p>
                                        <p class="text-xs text-slate-400 dark:text-navy-300 mt-0.5"><i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($r['district']) ?></p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 dark:text-navy-200">
                                        <?= htmlspecialchars($r['author_name'] ?? 'Unknown Worker') ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize <?= $tColor ?>">
                                            <?= $r['type'] ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-500 dark:text-navy-300 whitespace-nowrap">
                                        <?= date('M d, Y h:i A', strtotime($r['created_at'])) ?>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button @click="$dispatch('open-report-modal', { 
                                            title: '<?= htmlspecialchars(addslashes($r['title'])) ?>', 
                                            author: '<?= htmlspecialchars(addslashes($r['author_name'])) ?>',
                                            district: '<?= htmlspecialchars(addslashes($r['district'])) ?>',
                                            type: '<?= $r['type'] ?>',
                                            date: '<?= date('M d, Y h:i A', strtotime($r['created_at'])) ?>',
                                            body: `<?= htmlspecialchars(addslashes($r['report'])) ?>`
                                        })" class="btn text-info hover:bg-info/10 dark:hover:bg-info/10 px-3 py-1.5 rounded-lg text-xs font-medium">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- View Report Modal -->
    <div 
        x-data="{ 
            isOpen: false, 
            report: { title: '', author: '', district: '', type: '', date: '', body: '' },
            init() {
                this.$el.addEventListener('open-report-modal', (event) => {
                    this.report = event.detail;
                    this.isOpen = true;
                });
            }
        }" 
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
        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-xl w-full max-w-2xl relative max-h-[85vh] flex flex-col" @click.stop>
            
            <div class="p-6 border-b border-slate-150 dark:border-navy-600 flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-navy-100"><?= htmlspecialchars($r['title'] ?? 'Report Details') ?></h3>
                    <p class="text-sm text-slate-500 dark:text-navy-300 mt-1">
                        By <span x-text="report.author" class="font-medium text-slate-700 dark:text-navy-200"></span> 
                        • <i class="fas fa-map-marker-alt text-xs mx-1"></i><span x-text="report.district"></span>
                    </p>
                </div>
                <button @click="isOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-navy-200">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto grow">
                <div class="flex items-center space-x-3 mb-6">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize bg-slate-100 dark:bg-navy-600 text-slate-600 dark:text-navy-200" x-text="report.type"></span>
                    <span class="text-xs text-slate-400 dark:text-navy-300" x-text="report.date"></span>
                </div>
                
                <!-- Render report body with line breaks preserved -->
                <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-navy-200 whitespace-pre-wrap bg-slate-50 dark:bg-navy-700/50 p-5 rounded-lg border border-slate-200 dark:border-navy-600" x-text="report.body">
                </div>
            </div>
        </div>
    </div>

    <div id="x-teleport-target"></div>
    <script>window.addEventListener("DOMContentLoaded", () => Alpine.start());</script>
</body>
</html>