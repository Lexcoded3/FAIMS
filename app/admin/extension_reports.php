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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Extension Reports</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="../css/app.css">

    <!-- Javascript Assets -->
    <script src="../js/app.js" defer=""></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- PDF Export Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
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
        <?php include 'extension_reportsider.php';?>
      </div>

       <?php include 'toprightsidenav.php';?>

       <main class="main-content w-full px-[var(--margin-x)] pb-8">
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
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4" style="gap: 1.25rem; margin-bottom: 1.5rem;">
                <div class="card p-4 text-center border-l-4 border-l-secondary">
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
            <form method="GET" class="card p-5" style="margin-bottom: 1.5rem;">
                <div class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="w-full sm:w-1/3">
                        <label class="block text-xs+ text-slate-500 dark:text-navy-300 mb-1">Report Type</label>
                        <select name="type" class="form-select mt-1.5 w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 hover:bg-slate-200 focus:ring dark:bg-navy-900/90 dark:ring-accent/50 dark:hover:bg-navy-900 dark:focus:bg-navy-900">
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
                        <select name="district" class="form-select mt-1.5 w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 hover:bg-slate-200 focus:ring dark:bg-navy-900/90 dark:ring-accent/50 dark:hover:bg-navy-900 dark:focus:bg-navy-900">
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
                        <button type="button" class="generate-pdf-btn btn bg-success text-white hover:opacity-90 dark:bg-success px-4 py-2.5 rounded-lg text-sm font-medium w-full sm:w-auto">
                            <i class="fas fa-print mr-1"></i> PDF
                        </button>
                        <a href="admin_reports.php" class="btn border border-slate-300 dark:border-navy-600 text-slate-700 dark:text-navy-100 hover:bg-slate-100 dark:hover:bg-navy-700 px-4 py-2.5 rounded-lg text-sm font-medium w-full sm:w-auto text-center">
                            Clear
                        </a>
                    </div>
                </div>
            </form>

            <!-- Reports Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-navy-600 dark:bg-navy-800/50 text-xs+ uppercase tracking-wide text-slate-500 dark:text-navy-300">
                            <tr>
                                <th class="px-5 py-3">Title & District</th>
                                <th class="px-5 py-3">Author</th>
                                <th class="px-5 py-3">Type</th>
                                <th class="px-5 py-3">Date Filed</th>
                                <th class="px-5 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-150 dark:divide-navy-600">
                            <?php if(empty($reports)): ?>
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-slate-400 dark:text-navy-400">
                                        No reports found matching your filters.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $typeColors = [
                                    'disease' => 'bg-error/10 text-error',
                                    'yield' => 'bg-success/10 text-success',
                                    'soil' => 'bg-warning/10 text-warning',
                                    'water' => 'bg-info/10 text-info',
                                    'climate' => 'bg-secondary/10 text-secondary',
                                    'other' => 'bg-slate-100 text-slate-600 dark:bg-navy-600 dark:text-navy-200'
                                ];
                                
                                foreach ($reports as $r): 
                                    $tColor = $typeColors[$r['type']] ?? $typeColors['other'];
                                ?>
                                <tr class="hover:bg-navy-500 dark:hover:bg-navy-800/30">
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
                                        <button type="button" class="view-report-btn btn text-info hover:bg-info/10 dark:hover:bg-info/10 px-3 py-1.5 rounded-lg text-xs font-medium" 
                                            data-title="<?= htmlspecialchars($r['title']) ?>"
                                            data-author="<?= htmlspecialchars($r['author_name'] ?? 'Unknown Worker') ?>"
                                            data-district="<?= htmlspecialchars($r['district']) ?>"
                                            data-type="<?= $r['type'] ?>"
                                            data-date="<?= date('M d, Y h:i A', strtotime($r['created_at'])) ?>"
                                            data-body="<?= htmlspecialchars($r['report']) ?>">
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

        <!-- View Report Modal -->
        <div id="reportModal"
            x-data="{ 
                isOpen: false, 
                report: { title: '', author: '', district: '', type: '', date: '', body: '' }
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
                        <h3 class="text-lg font-bold text-slate-800 dark:text-navy-100" x-text="report.title"></h3>
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
                    
                    <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-navy-200 whitespace-pre-wrap bg-slate-50 dark:bg-navy-700/50 p-5 rounded-lg border border-slate-200 dark:border-navy-600" x-text="report.body">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PDF Template - Must be visible to html2pdf (position: absolute off-screen is better than fixed) -->
    <div id="pdf-report-content" style="position: absolute; top: -10000px; left: 0; width: 1200px; background: white; color: #000; font-family: Arial, sans-serif; padding: 20px; display: block;">
        
        <!-- PDF Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #cbd5e1; padding-bottom: 15px; margin-bottom: 20px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: #0f172a; margin: 0;">FAIMS</h1>
                <p style="font-size: 14px; color: #64748b; margin: 5px 0 0 0;">Extension Worker Field Reports</p>
            </div>
            <div style="text-align: right; font-size: 12px; color: #64748b;">
                <p style="margin: 0;">Generated: <?= date('d M Y, h:i A') ?></p>
                <p style="margin: 0;">Filters: <?= ucfirst($_GET['type'] ?? 'All Types') ?> | <?= htmlspecialchars($_GET['district'] ?? 'All Districts') ?></p>
            </div>
        </div>

        <!-- PDF Summary Stats -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 25px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div style="text-align: center; flex: 1;"><p style="font-size: 20px; font-weight: 700; margin:0; color:#000;"><?= $stats['total'] ?></p><p style="font-size: 11px; color: #64748b; margin: 0;">TOTAL</p></div>
            <div style="text-align: center; flex: 1;"><p style="font-size: 20px; font-weight: 700; color: #ef4444; margin:0;"><?= $stats['disease'] ?></p><p style="font-size: 11px; color: #64748b; margin: 0;">DISEASE</p></div>
            <div style="text-align: center; flex: 1;"><p style="font-size: 20px; font-weight: 700; color: #22c55e; margin:0;"><?= $stats['yield'] ?></p><p style="font-size: 11px; color: #64748b; margin: 0;">YIELD</p></div>
            <div style="text-align: center; flex: 1;"><p style="font-size: 20px; font-weight: 700; color: #f59e0b; margin:0;"><?= $stats['soil'] ?></p><p style="font-size: 11px; color: #64748b; margin: 0;">SOIL</p></div>
            <div style="text-align: center; flex: 1;"><p style="font-size: 20px; font-weight: 700; color: #3b82f6; margin:0;"><?= $stats['water'] ?></p><p style="font-size: 11px; color: #64748b; margin: 0;">WATER</p></div>
        </div>

        <!-- PDF Table -->
        <table style="width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 20px;">
            <thead>
                <tr style="background: #334155; color: white; text-align: left;">
                    <th style="padding: 10px; border: 1px solid #334155;">Date</th>
                    <th style="padding: 10px; border: 1px solid #334155;">Title & District</th>
                    <th style="padding: 10px; border: 1px solid #334155;">Author</th>
                    <th style="padding: 10px; border: 1px solid #334155;">Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reports as $r): ?>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 10px; border: 1px solid #e2e8f0; color:#000;"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; color:#000;">
                        <strong><?= htmlspecialchars($r['title']) ?></strong><br>
                        <span style="color: #64748b; font-size: 10px;"><?= htmlspecialchars($r['district']) ?></span>
                    </td>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; color:#000;"><?= htmlspecialchars($r['author_name'] ?? 'Unknown') ?></td>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; text-transform: uppercase; font-weight: 600; color:#000;"><?= $r['type'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- PDF Footer -->
        <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 10px; color: #94a3b8;">
            FAIMSAutomated Report • Strictly Confidential
        </div>
    </div>

    <div id="x-teleport-target"></div>

    <script>
      window.addEventListener("DOMContentLoaded", () => {
        Alpine.start();

        // Check if html2pdf loaded
        if (typeof html2pdf === 'undefined') {
            console.error('html2pdf library failed to load');
        }

        // Modal functionality - simple JavaScript instead of Alpine dispatch
        const modal = document.getElementById('reportModal');
        const reportModalData = {
            isOpen: false,
            report: { title: '', author: '', district: '', type: '', date: '', body: '' }
        };

        // Attach click handlers to view buttons
        document.querySelectorAll('.view-report-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get data from button attributes
                reportModalData.report = {
                    title: this.dataset.title,
                    author: this.dataset.author,
                    district: this.dataset.district,
                    type: this.dataset.type,
                    date: this.dataset.date,
                    body: this.dataset.body
                };

                // Update Alpine component
                const alpine = Alpine.entangle(modal, '__x');
                if (modal.__x_report) {
                    modal.__x_report.report = reportModalData.report;
                    modal.__x_report.isOpen = true;
                }

                // Fallback: Direct manipulation if Alpine fails
                modal.style.display = 'flex';
                const heading = modal.querySelector('h3');
                if (heading) heading.textContent = reportModalData.report.title;
                const author = modal.querySelector('[x-text="report.author"]');
                if (author) author.textContent = reportModalData.report.author;
                const district = modal.querySelector('[x-text="report.district"]');
                if (district) district.textContent = reportModalData.report.district;
                const type = modal.querySelector('[x-text="report.type"]');
                if (type) type.textContent = reportModalData.report.type;
                const date = modal.querySelector('[x-text="report.date"]');
                if (date) date.textContent = reportModalData.report.date;
                const body = modal.querySelector('[x-text="report.body"]');
                if (body) body.textContent = reportModalData.report.body;
            });
        });

        // Close modal on button click
        const closeBtn = modal.querySelector('button[x-click]') || modal.querySelector('button:last-of-type');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }

        // Close modal on backdrop click
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

        // PDF Generation
        document.querySelectorAll('.generate-pdf-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (typeof html2pdf === 'undefined') {
                    alert("PDF library not loaded. Please refresh and try again.");
                    console.error('html2pdf is undefined');
                    return;
                }

                const originalHTML = this.innerHTML;
                const originalDisabled = this.disabled;
                
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating...';
                this.disabled = true;

                const element = document.getElementById('pdf-report-content');
                
                if (!element) {
                    alert("PDF template not found.");
                    this.innerHTML = originalHTML;
                    this.disabled = originalDisabled;
                    return;
                }

                // Clone element for PDF (to avoid modifying original)
                const clonedElement = element.cloneNode(true);
                
                const opt = {
                    margin:       [10, 10, 10, 10],
                    filename:     'FAIMS_Extension_Report_' + new Date().toISOString().slice(0,10) + '.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true, logging: false, allowTaint: true },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
                };

                html2pdf()
                    .set(opt)
                    .from(clonedElement)
                    .save()
                    .then(() => {
                        console.log('✓ PDF generated successfully');
                        this.innerHTML = originalHTML;
                        this.disabled = originalDisabled;
                    })
                    .catch((err) => {
                        console.error('PDF error:', err);
                        alert("PDF failed: " + (err.message || 'Unknown error'));
                        this.innerHTML = originalHTML;
                        this.disabled = originalDisabled;
                    });
            });
        });
      });
    </script>
</body>
</html>