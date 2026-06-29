<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'reports.php';

$search   = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$district = $conn->real_escape_string(trim($_GET['district'] ?? ''));
$page     = max(1,(int)($_GET['page'] ?? 1));
$per_page = 15;
$offset   = ($page-1)*$per_page;

$where = "WHERE extension_id=$extension_id";
if ($district !== '') $where .= " AND district='$district'";
if ($search !== '')   $where .= " AND (title LIKE '%$search%' OR report LIKE '%$search%')";

$total       = $conn->query("SELECT COUNT(*) AS c FROM extension_reports $where")->fetch_assoc()['c'];
$total_pages = max(1,(int)ceil($total/$per_page));

$reports = [];
$res = $conn->query("SELECT id,title,district,report,created_at FROM extension_reports $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
while ($r = $res->fetch_assoc()) $reports[] = $r;

$districts = [];
$res = $conn->query("SELECT DISTINCT district FROM extension_reports WHERE extension_id=$extension_id ORDER BY district");
while ($r = $res->fetch_assoc()) $districts[] = $r['district'];

function detect_tag(string $t): string {
    $t = strtolower($t);
    if (preg_match('/disease|pest|blight|worm|virus|fungus|rust|armyworm/',$t)) return 'disease';
    if (preg_match('/yield|harvest|crop|produce/',$t))  return 'yield';
    if (preg_match('/soil|erosion|degrad|fertility/',$t)) return 'soil';
    if (preg_match('/water|irrigation|flood|drought|rain/',$t)) return 'water';
    return 'general';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Reports</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="../css/app.css">

    <!-- Javascript Assets -->
    <script src="../js/app.js" defer=""></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
      /**
       * THIS SCRIPT REQUIRED FOR PREVENT FLICKERING IN SOME BROWSERS
       */
      localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>
    <style>
      body
      {
        .mono{font-family:'DM Mono',monospace}
        .stat-card{transition:transform .15s}
.stat-card:hover{transform:translateY(-1px)}
.fade-in{animation:fadeIn .3s ease forwards}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.tag{display:inline-flex;align-items:center;font-size:11px;font-weight:500;padding:2px 8px;border-radius:20px}
.tag-disease{background:#FCEBEB;color:#A32D2D}
.tag-yield{background:#EAF3DE;color:#3B6D11}
.tag-soil{background:#FAEEDA;color:#854F0B}
.tag-water{background:#E6F1FB;color:#185FA5}
.tag-general{background:#F1EFE8;color:#5F5E5A}
.tag-pending{background:#FAEEDA;color:#854F0B}
.tag-approved,.tag-active{background:#E1F5EE;color:#0F6E56}
.tag-rejected{background:#FCEBEB;color:#A32D2D}
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
        <?php include 'reportssider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="flex items-center justify-between py-5 lg:py-6">
          <h2 class="text-xl font-medium text-slate-700 line-clamp-1 dark:text-navy-50 lg:text-2xl">
            Invoice
          </h2>

          <div class="flex">
            <button @click="window.print()" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25 sm:h-9 sm:w-9">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
              </svg>
            </button>
            <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25 sm:h-9 sm:w-9">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </button>
          </div>
        </div>
        <div class="grid grid-cols-1">
          <div class="card px-5 py-12 sm:px-18">
            <div class="flex flex-col justify-between sm:flex-row">
              <div class="text-center sm:text-left">
                <h2 class="text-2xl font-semibold uppercase text-primary dark:text-accent-light">
                  FAIMS
                </h2>
                <div class="space-y-1 pt-2">
                  <p>Sparksuite, Inc.</p>
                  <p>12345 Sunny Road</p>
                  <p>Sunnyville, CA 12345</p>
                </div>
              </div>
              <div class="mt-4 text-center sm:m-0 sm:text-right">
                <h2 class="text-2xl font-semibold uppercase text-primary dark:text-accent-light">
                  report
                </h2>
                <div class="space-y-1 pt-2">
                  <p>Report #: <span class="font-semibold">123</span></p>
                  <p>
                    Created: <span class="font-semibold"><?= date('M d, Y') ?></span>
                  </p>
                  <p>Due: <span class="font-semibold"> July 23, 2021</span></p>
                </div>
              </div>
            </div>
            <div class="my-7 h-px bg-slate-200 dark:bg-navy-500"></div>
            <div class="flex flex-col justify-between sm:flex-row">
              <div class="text-center sm:text-left">
                <p class="text-lg font-medium text-slate-600 dark:text-navy-100">
                  Reported By:
                </p>
                <div class="space-y-1 pt-2">
                  <p class="font-semibold"><? echo $_SESSION['name'] ?? 'Extension Worker';?></p>
                  <p>johndoe@example.com</p>
                  <p>260 W. Storm Street New York, NY 10025.</p>
                </div>
              </div>
              <!-- <div class="mt-4 text-center sm:m-0 sm:text-right">
                <p class="text-lg font-medium text-slate-600 dark:text-navy-100">
                  Payment Method:
                </p>
                <div class="space-y-1 pt-2">
                  <p class="font-medium">Visa **** **** 1234</p>
                </div>
              </div> -->
            </div>
            <div class="my-7 h-px bg-slate-200 dark:bg-navy-500"></div>
            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
              <table class="is-zebra w-full text-left">
                <thead>
                  <tr>
                    <th class="whitespace-nowrap rounded-l-lg bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                      #
                    </th>
                    <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                      DESCRIPTION
                    </th>
                    <th class="whitespace-nowrap bg-slate-200 px-3 py-3 text-right font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                      HRS
                    </th>
                    <th class="whitespace-nowrap bg-slate-200 px-3 py-3 text-right font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                      RATE
                    </th>
                    <th class="whitespace-nowrap rounded-r-lg bg-slate-200 px-3 py-3 text-right font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                      SUBTOTAL
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="whitespace-nowrap rounded-l-lg px-4 py-3 sm:px-5">
                      1
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <div>
                        <p class="font-medium text-slate-600 dark:text-navy-100">
                          Template Design
                        </p>
                        <p class="text-xs+">
                          Lorem ipsum dolor sit amet, consectetur adipisicing
                          elit. Perferendis
                        </p>
                      </div>
                    </td>
                    <td class="w-3/12 whitespace-nowrap px-4 py-3 text-right sm:px-5">
                      10
                    </td>
                    <td class="w-3/12 whitespace-nowrap px-4 py-3 text-right sm:px-5">
                      55
                    </td>
                    <td class="w-3/12 whitespace-nowrap rounded-r-lg px-4 py-3 text-right font-semibold sm:px-5">
                      550
                    </td>
                  </tr>
                  <tr>
                    <td class="whitespace-nowrap rounded-l-lg px-4 py-3 sm:px-5">
                      2
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <div>
                        <p class="font-medium text-slate-600 dark:text-navy-100">
                          Mobile App
                        </p>
                        <p class="text-xs+">
                          Lorem ipsum dolor sit amet, consectetur adipisicing
                          elit.
                        </p>
                      </div>
                    </td>
                    <td class="w-3/12 whitespace-nowrap px-4 py-3 text-right sm:px-5">
                      8
                    </td>
                    <td class="w-3/12 whitespace-nowrap px-4 py-3 text-right sm:px-5">
                      40
                    </td>
                    <td class="w-3/12 whitespace-nowrap rounded-r-lg px-4 py-3 text-right font-semibold sm:px-5">
                      320
                    </td>
                  </tr>
                  <tr>
                    <td class="whitespace-nowrap rounded-l-lg px-4 py-3 sm:px-5">
                      3
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <div>
                        <p class="font-medium text-slate-600 dark:text-navy-100">
                          CRM App
                        </p>
                        <p class="text-xs+">
                          Lorem ipsum dolor sit amet, consectetur adipisicing
                          elit. Distinctio et ipsa modi.
                        </p>
                      </div>
                    </td>
                    <td class="w-3/12 whitespace-nowrap px-4 py-3 text-right sm:px-5">
                      80
                    </td>
                    <td class="w-3/12 whitespace-nowrap px-4 py-3 text-right sm:px-5">
                      65
                    </td>
                    <td class="w-3/12 whitespace-nowrap rounded-r-lg px-4 py-3 text-right font-semibold sm:px-5">
                      5200
                    </td>
                  </tr>
                  <tr>
                    <td class="whitespace-nowrap rounded-l-lg px-4 py-3 sm:px-5">
                      4
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <div>
                        <p class="font-medium text-slate-600 dark:text-navy-100">
                          CMS App
                        </p>
                        <p class="text-xs+">
                          Lorem ipsum dolor sit amet, consectetur.
                        </p>
                      </div>
                    </td>
                    <td class="w-3/12 whitespace-nowrap px-4 py-3 text-right sm:px-5">
                      25
                    </td>
                    <td class="w-3/12 whitespace-nowrap px-4 py-3 text-right sm:px-5">
                      35
                    </td>
                    <td class="w-3/12 whitespace-nowrap rounded-r-lg px-4 py-3 text-right font-semibold sm:px-5">
                      875
                    </td>
                  </tr>
                  <tr>
                    <td class="whitespace-nowrap rounded-l-lg px-4 py-3 sm:px-5">
                      5
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                      <div>
                        <p class="font-medium text-slate-600 dark:text-navy-100">
                          UI/UX Design
                        </p>
                        <p class="text-xs+">
                          Lorem ipsum dolor sit amet, consectetur adipisicing
                          elit. Animi
                        </p>
                      </div>
                    </td>
                    <td class="w-3/12 whitespace-nowrap px-4 py-3 text-right sm:px-5">
                      25
                    </td>
                    <td class="w-3/12 whitespace-nowrap px-4 py-3 text-right sm:px-5">
                      15
                    </td>
                    <td class="w-3/12 whitespace-nowrap rounded-r-lg px-4 py-3 text-right font-semibold sm:px-5">
                      375
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="my-7 h-px bg-slate-200 dark:bg-navy-500"></div>

            <div class="flex flex-col justify-end sm:flex-row">
              <div class="mt-4 text-center sm:m-0 sm:text-right">
                <p class="text-lg font-medium text-slate-600 dark:text-navy-100">
                  Total:
                </p>
                <div class="space-y-1 pt-2">
                  <p>Summary : <span class="font-medium">$7320</span></p>
                  <p>Discount : <span class="font-medium">$20</span></p>
                  <p>Tax : <span class="font-medium">20%</span></p>
                  <p class="text-lg text-primary dark:text-accent-light">
                    Total: <span class="font-medium">8780</span>
                  </p>
                </div>
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
