<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'reports.php';

// Get report ID from URL
$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($report_id === 0) {
    header("Location: reports.php");
    exit;
}
$where = "WHERE extension_id=$extension_id";
$total       = $conn->query("SELECT COUNT(*) AS c FROM extension_reports $where")->fetch_assoc()['c'];

// Fetch the specific report
$stmt = $conn->prepare("SELECT id, title, district, report, created_at FROM extension_reports WHERE id = ? AND extension_id = ?");
$stmt->bind_param("ii", $report_id, $extension_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: reports.php");
    exit;
}

$report = $result->fetch_assoc();
$stmt->close();

// Detect tag for the report
function detect_tag(string $t): string {
    $t = strtolower($t);
    if (preg_match('/disease|pest|blight|worm|virus|fungus|rust|armyworm/',$t)) return 'disease';
    if (preg_match('/yield|harvest|crop|produce/',$t))  return 'yield';
    if (preg_match('/soil|erosion|degrad|fertility/',$t)) return 'soil';
    if (preg_match('/water|irrigation|flood|drought|rain/',$t)) return 'water';
    return 'general';
}

$tag = detect_tag($report['title'] . ' ' . $report['report']);
$tag_labels = [
    'disease' => 'Disease/Pest',
    'yield' => 'Yield',
    'soil' => 'Soil Health',
    'water' => 'Water Management',
    'general' => 'General'
];

// Format date
$report_date = date('M d, Y', strtotime($report['created_at']));
$report_number = str_pad($report['id'], 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Field Report #<?= $report_number ?></title>
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
      localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>
    <style>
      body {
        .mono{font-family:'DM Mono',monospace}
        .tag{display:inline-flex;align-items:center;font-size:11px;font-weight:500;padding:4px 10px;border-radius:20px}
        .tag-disease{background:#FCEBEB;color:#A32D2D}
        .tag-yield{background:#EAF3DE;color:#3B6D11}
        .tag-soil{background:#FAEEDA;color:#854F0B}
        .tag-water{background:#E6F1FB;color:#185FA5}
        .tag-general{background:#F1EFE8;color:#5F5E5A}
      }
      
      @media print {
        .print\\:hidden { display: none !important; }
        .sidebar, nav, button { display: none !important; }
        body { background: white !important; }
        .card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
      }
    </style>
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
        <?php include 'reportssider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="flex items-center justify-between py-5 lg:py-6 print:hidden">
          <div class="flex items-center space-x-3">
            <a href="reports.php" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </a>
            <h2 class="text-xl font-medium text-slate-700 line-clamp-1 dark:text-navy-50 lg:text-2xl">
              Field Report #<?= $report_number ?>
            </h2>
          </div>

          <div class="flex space-x-2">
            <button @click="window.print()" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25 sm:h-9 sm:w-9">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
              </svg>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1">
          <div class="card px-5 py-12 sm:px-18">
            <!-- Header Section -->
            <div class="flex flex-col justify-between sm:flex-row">
              <div class="text-center sm:text-left">
                <h2 class="text-2xl font-semibold uppercase" style="color:#1D9E75">
                  FAIMS
                </h2>
                <div class="space-y-1 pt-2 text-sm">
                  <p class="font-medium">Farm Advisory & Information Management System</p>
                  <p class="text-slate-500">Extension Services Division</p>
                  <p class="text-slate-500">Ministry of Agriculture, Uganda</p>
                </div>
              </div>
              <div class="mt-4 text-center sm:m-0 sm:text-right">
                <h2 class="text-2xl font-semibold uppercase" style="color:#1D9E75">
                  Field Report
                </h2>
                <div class="space-y-1 pt-2 text-sm">
                  <p>Report #: <span class="font-semibold mono"><?= $report_number ?></span></p>
                  <p>Date: <span class="font-semibold"><?= $report_date ?></span></p>
                  <p>District: <span class="font-semibold"><?= htmlspecialchars($report['district']) ?></span></p>
                </div>
              </div>
            </div>

            <div class="my-7 h-px bg-slate-200 dark:bg-navy-500"></div>

            <!-- Reporter Information -->
            <div class="flex flex-col justify-between sm:flex-row">
              <div class="text-center sm:text-left">
                <p class="text-lg font-medium text-slate-600 dark:text-navy-100">
                  Submitted By:
                </p>
                <div class="space-y-1 pt-2">
                  <p class="font-semibold"><?= htmlspecialchars($extension_name) ?></p>
                  <p class="text-sm text-slate-500">Extension Officer</p>
                </div>
              </div>
              <div class="mt-4 text-center sm:m-0 sm:text-right">
                <p class="text-lg font-medium text-slate-600 dark:text-navy-100">
                  Report Category:
                </p>
                <div class="pt-2">
                  <span class="tag tag-<?= $tag ?>"><?= $tag_labels[$tag] ?></span>
                </div>
              </div>
            </div>

            <div class="my-7 h-px bg-slate-200 dark:bg-navy-500"></div>

            <!-- Report Content -->
            <div class="space-y-6">
              <div>
                <h3 class="text-lg font-semibold text-slate-700 dark:text-navy-100 mb-3">
                  <?= htmlspecialchars($report['title']) ?>
                </h3>
                <div class="prose max-w-none text-slate-600 dark:text-navy-100">
                  <?= nl2br(htmlspecialchars($report['report'])) ?>
                </div>
              </div>
            </div>

            <div class="my-7 h-px bg-slate-200 dark:bg-navy-500"></div>

            <!-- Footer -->
            <div class="text-center text-sm text-slate-500">
              <p>This is an official field report from the FARMER ACCESS INFORMATION SYSTEM.</p>
              <p class="mt-1">For inquiries, contact your district agricultural office.</p>
            </div>
          </div>
        </div>
      </main>
    </div>

    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
  </body>
</html>