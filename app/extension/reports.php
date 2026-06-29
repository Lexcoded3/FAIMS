<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header('Location: ../auth/'); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'reports.php';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $report_id = (int)$_POST['report_id'];
    $stmt = $conn->prepare("DELETE FROM extension_reports WHERE id = ? AND extension_id = ?");
    $stmt->bind_param("ii", $report_id, $extension_id);
    $stmt->execute();
    $stmt->close();
    header("Location: reports.php?deleted=1");
    exit;
}

// Handle edit action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $report_id = (int)$_POST['report_id'];
    $title = trim($_POST['title']);
    $district = trim($_POST['district']);
    $report = trim($_POST['report']);
    
    $stmt = $conn->prepare("UPDATE extension_reports SET title = ?, district = ?, report = ? WHERE id = ? AND extension_id = ?");
    $stmt->bind_param("sssii", $title, $district, $report, $report_id, $extension_id);
    $stmt->execute();
    $stmt->close();
    header("Location: reports.php?updated=1");
    exit;
}

$search   = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$district = $conn->real_escape_string(trim($_GET['district'] ?? ''));
$page     = max(1,(int)($_GET['page'] ?? 1));
$per_page = 5;
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

// external reports
if (isset($_GET['scope']) && $_GET['scope'] === 'external') {
    $where = "WHERE extension_id != $extension_id";
}

// disease filter
if (isset($_GET['type']) && $_GET['type'] === 'disease') {
    $where .= " AND report REGEXP 'armyworm|pest|disease|blight|fungus|virus'";
}

// new reports
if (isset($_GET['filter']) && $_GET['filter'] === 'new') {
    $where .= " AND created_at >= NOW() - INTERVAL 1 DAY";
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
        /* Base Tag Style */
        .tag {
            display: inline-flex;
            align-items: center;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 10px;
            border-radius: 9999px;
            border-width: 1px;
            transition: all 0.2s;
            cursor: default;
        }

        /* Variant Styles using your theme colors */
        .tag-disease {
            background-color: rgba(255, 74, 74, 0.1); /* bg-error/10 */
            border-color: rgba(255, 74, 74, 0.3);     /* border-error/30 */
            color: #ff4a4a;                           /* text-error */
        }

        .tag-yield {
            background-color: rgba(0, 169, 110, 0.1); /* bg-success/10 */
            border-color: rgba(0, 169, 110, 0.3);     /* border-success/30 */
            color: #00a96e;                           /* text-success */
        }

        .tag-soil {
            background-color: rgba(255, 159, 67, 0.1); /* bg-warning/10 */
            border-color: rgba(255, 159, 67, 0.3);     /* border-warning/30 */
            color: #ff9f43;                            /* text-warning */
        }

        .tag-water {
            background-color: rgba(0, 184, 217, 0.1); /* bg-info/10 */
            border-color: rgba(0, 184, 217, 0.3);     /* border-info/30 */
            color: #00b8d9;                           /* text-info */
        }

        .tag-general {
            background-color: rgba(100, 116, 139, 0.1); /* bg-slate/10 */
            border-color: rgba(100, 116, 139, 0.3);     /* border-slate/30 */
            color: #64748b;                             /* text-slate */
        }

        /* Optional: Add a hover effect for all tags */
        .tag:hover {
            filter: brightness(0.95);
        }
        .tag-pending{background:#FAEEDA;color:#854F0B}
        .tag-approved,.tag-active{background:#E1F5EE;color:#0F6E56}
        .tag-rejected{background:#FCEBEB;color:#A32D2D}
      }
    </style>
  </head>

  <body x-data="reportManager()" class="is-header-blur is-sidebar-open" x-bind="$store.global.documentBody">
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
      <main class="main-content flex-1 w-full min-w-0 px-[var(--margin-x)] pb-8">
          <div class="flex items-center space-x-1 lg:py-1 mt-4">
                        <form 
                          action=""
                          method="GET" class="flex items-end gap-4 flex-wrap w-full">
                          <!-- User -->
                          <label class="relative flex">
                              <input
                                class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                                placeholder="Search reports…"
                                type="text" name="search" value="<?= htmlspecialchars($_GET['search']??'') ?>"
                              />
                              <div
                                class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent"
                              >
                                <svg
                                  xmlns="http://www.w3.org/2000/svg"
                                  class="size-4.5 transition-colors duration-200"
                                  fill="currentColor"
                                  viewBox="0 0 24 24"
                                >
                                  <path
                                    d="M3.316 13.781l.73-.171-.73.171zm0-5.457l.73.171-.73-.171zm15.473 0l.73-.171-.73.171zm0 5.457l.73.171-.73-.171zm-5.008 5.008l-.171-.73.171.73zm-5.457 0l-.171.73.171-.73zm0-15.473l-.171-.73.171.73zm5.457 0l.171-.73-.171.73zM20.47 21.53a.75.75 0 101.06-1.06l-1.06 1.06zM4.046 13.61a11.198 11.198 0 010-5.115l-1.46-.342a12.698 12.698 0 000 5.8l1.46-.343zm14.013-5.115a11.196 11.196 0 010 5.115l1.46.342a12.698 12.698 0 000-5.8l-1.46.343zm-4.45 9.564a11.196 11.196 0 01-5.114 0l-.342 1.46c1.907.448 3.892.448 5.8 0l-.343-1.46zM8.496 4.046a11.198 11.198 0 015.115 0l.342-1.46a12.698 12.698 0 00-5.8 0l.343 1.46zm0 14.013a5.97 5.97 0 01-4.45-4.45l-1.46.343a7.47 7.47 0 005.568 5.568l.342-1.46zm5.457 1.46a7.47 7.47 0 005.568-5.567l-1.46-.342a5.97 5.97 0 01-4.45 4.45l.342 1.46zM13.61 4.046a5.97 5.97 0 014.45 4.45l1.46-.343a7.47 7.47 0 00-5.568-5.567l-.342 1.46zm-5.457-1.46a7.47 7.47 0 00-5.567 5.567l1.46.342a5.97 5.97 0 014.45-4.45l-.343-1.46zm8.652 15.28l3.665 3.664 1.06-1.06-3.665-3.665-1.06 1.06z"
                                  ></path>
                                </svg>
                              </div>
                            </label>

                          <!-- Role -->
                          <label class="block w-40">
                            <!-- <span class="text-slate-600 dark:text-navy-100">Role</span> -->
                            <select name="district"
                              class="form-select mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:bg-navy-700 dark:hover:border-navy-400 dark:focus:border-accent">

                              <option value="">All districts</option>
                                <?php foreach($districts as $d): ?>
                                  <option value="<?= htmlspecialchars($d) ?>" <?= ($district===$d)?'selected':'' ?>><?= htmlspecialchars($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                          </label>

                          <!-- Button -->
                          <div class="flex items-end">
                          <div class="flex -space-x-px">
                            <button type="submit"
                              class="btn rounded-r-none rounded-l-full bg-success/10 font-medium text-success hover:bg-success/20 focus:bg-success/20 active:bg-success/25"
                            >
                              Filter
                            </button>
                            </form>
                            <?php if($district||$search): ?>
                              <a href="reports.php">
                            <button
                              class="btn rounded-none bg-error font-medium text-white hover:bg-error-focus focus:bg-error-focus active:bg-error-focus/90"
                            >
                              Clear
                            </button>
                            </a>
                            <?php endif; ?>
                            <button
                              class="btn rounded-l-none rounded-r-full bg-info/10 font-medium text-info hover:bg-info/20 focus:bg-info/20 active:bg-info/25"
                            >
                              Refresh
                            </button>
                          </div>    
                                    
                          </div>
                        </div>
        <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="col-span-12">
            <div class="flex items-center justify-between">
              <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                My reports
              </h2>
              <div class="flex">
                <div class="flex items-center" x-data="{isInputActive:false}">
                  <label class="block">
                    <input x-effect="isInputActive === true && $nextTick(() => { $el.focus()});" :class="isInputActive ? 'w-32 lg:w-48' : 'w-0'" class="form-input bg-transparent px-1 text-right transition-all duration-100 placeholder:text-slate-500 dark:placeholder:text-navy-200" placeholder="Search here..." type="text">
                  </label>
                  <button @click="isInputActive = !isInputActive" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                  </button>
                </div>
                <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                  </button>
                  <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                    <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                      <ul>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Action</a>
                        </li>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Another Action</a>
                        </li>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Something else</a>
                        </li>
                      </ul>
                      <div class="my-1 h-px bg-slate-150 dark:bg-navy-500"></div>
                      <ul>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Separated Link</a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="card mt-3">
              <div class="overflow-x-auto w-full">
                <table class="is-hoverable w-full text-left">
                  <thead>
                    <tr>
                      <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                        #
                      </th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                        Title
                      </th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                        District
                      </th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                        Type
                      </th>
                      <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                        Date
                      </th>
                      <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if(empty($reports)): ?>
                    <tr><td colspan="6" class="px-4 py-10 text-center text-xs text-gray-400">No reports found</td></tr>
                    <?php else: foreach($reports as $i=>$r): $tag=detect_tag($r['title']); ?>
                    <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                      <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                        <p class="font-medium text-primary dark:text-accent-light">
                          #<?= str_pad($r['id'], 4, '0', STR_PAD_LEFT) ?>
                        </p>
                      </td>
                      <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                        <p class="font-medium"><?= htmlspecialchars($r['title']) ?></p>
                        <p class="mt-0.5 text-xs text-slate-500" x-tooltip.interactive.content="'#content-<?= $r['id'] ?>'"><?= htmlspecialchars(mb_strimwidth($r['report'],0,30,'…')) ?></p>
                      </td>
                      <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                        <p class="w-30 overflow-hidden text-ellipsis text-xs+">
                          <?= htmlspecialchars($r['district']) ?>
                        </p>
                        <template id="content-<?= $r['id'] ?>">
                          <div class="flex space-x-1 rounded-lg bg-slate-150 p-3 dark:bg-navy-500">
                            <!-- <div class="avatar">
                              <img
                                class="rounded-full"
                                src="images/avatar/avatar-13.jpg"
                                alt="image"
                              />
                            </div> -->
                            <div>
                              <p class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($r['district']) ?></p>
                              <p class="text-xs text-slate-500 dark:text-navy-200">
                                <?= htmlspecialchars($r['report']) ?>
                              </p>
                            </div>
                          </div>
                        </template>
                      </td>
                      <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <span class="tag tag-<?= $tag ?>">
                              <?= ucfirst($tag) ?>
                          </span>
                      </td>
                      <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                        <p class="text-sm+ mono text-slate-700 dark:text-navy-100">
                          <?= date('d M Y',strtotime($r['created_at'])) ?>
                        </p>
                      </td>
                      <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                        <div x-data="usePopper({placement:'bottom-start',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                          <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25"
                            x-ref="popperRef" @click="isShowPopper = !isShowPopper">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                            </svg>
                          </button>
                          <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                            <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                              <ul>
                                <li>
                                  <button @click="viewReport(<?= htmlspecialchars(json_encode($r)) ?>)"
                                    class="flex h-8 w-full items-center space-x-3 px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-px size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>View</span>
                                  </button>
                                </li>
                                <li>
                                  <button @click="editReport(<?= htmlspecialchars(json_encode($r)) ?>)"
                                    class="flex h-8 w-full items-center space-x-3 px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <span>Edit</span>
                                  </button>
                                </li>
                                <li>
                                  <a href="print_report.php?id=<?= $r['id'] ?>" target="_blank"
                                    class="flex h-8 items-center space-x-3 px-3 pr-8 font-medium tracking-wide text-info outline-none transition-all hover:bg-info/20 focus:bg-info/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    <span>Print</span>
                                  </a>
                                </li>
                                <li>
                                  <button @click="deleteReport(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['title'])) ?>')"
                                    class="flex h-8 w-full items-center space-x-3 px-3 pr-8 font-medium tracking-wide text-error outline-none transition-all hover:bg-error/20 focus:bg-error/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Delete</span>
                                  </button>
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>
              <!-- Pagination -->
              <?php if($total_pages>1): ?>
              <div class="flex flex-col justify-between space-y-4 px-4 py-4 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
                <div class="text-xs+">Showing <?= $offset+1 ?> - <?= min($offset+$per_page,$total) ?> of <?= $total ?> reports</div>

                <ol class="pagination">
                  <?php if($page > 1): ?>
                  <li class="rounded-l-lg bg-slate-150 dark:bg-navy-500">
                    <a href="?page=<?= $page-1 ?><?= $search?"&search=$search":'' ?><?= $district?"&district=$district":'' ?>" 
                      class="flex size-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-300 focus:bg-slate-300 active:bg-slate-300/80 dark:text-navy-200 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                      </svg>
                    </a>
                  </li>
                  <?php endif; ?>

                  <?php 
                  $start = max(1, $page - 2);
                  $end = min($total_pages, $page + 2);
                  for($p = $start; $p <= $end; $p++): 
                  ?>
                  <li class="bg-slate-150 dark:bg-navy-500">
                    <a href="?page=<?= $p ?><?= $search?"&search=$search":'' ?><?= $district?"&district=$district":'' ?>"
                      class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-3 leading-tight transition-colors <?= $p==$page ? 'bg-primary text-white dark:bg-accent' : 'hover:bg-slate-300 focus:bg-slate-300 active:bg-slate-300/80 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90' ?>">
                      <?= $p ?>
                    </a>
                  </li>
                  <?php endfor; ?>

                  <?php if($page < $total_pages): ?>
                  <li class="rounded-r-lg bg-slate-150 dark:bg-navy-500">
                    <a href="?page=<?= $page+1 ?><?= $search?"&search=$search":'' ?><?= $district?"&district=$district":'' ?>"
                      class="flex size-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-300 focus:bg-slate-300 active:bg-slate-300/80 dark:text-navy-200 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                      </svg>
                    </a>
                  </li>
                  <?php endif; ?>
                </ol>
              </div>
              <?php endif; ?>
            </div>
                    
      </main>
    </div>
    <!-- View Report Modal -->
    <template x-teleport="#x-teleport-target">
      <div class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5"
        x-show="showViewModal" role="dialog" @keydown.window.escape="showViewModal = false">
        <div class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300" @click="showViewModal = false" x-show="showViewModal" x-transition:enter="ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full max-w-2xl origin-bottom rounded-lg bg-white transition-all duration-300 dark:bg-navy-700"
          x-show="showViewModal" x-transition:enter="easy-out" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="easy-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
          
          <div class="flex justify-between rounded-t-lg bg-slate-200 px-4 py-3 dark:bg-navy-800 sm:px-5">
            <h3 class="text-base font-medium text-slate-700 dark:text-navy-100">
              View Report
            </h3>
            <button @click="showViewModal = false" class="btn -mr-1.5 size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div class="px-4 py-4 sm:px-5 max-h-[calc(100vh-200px)] overflow-y-auto">
            <div class="space-y-4">
              <div>
                <label class="block">
                  <span class="font-medium text-slate-600 dark:text-navy-100">Report ID</span>
                  <p class="mt-1.5 mono text-sm" x-text="'#' + String(currentReport.id).padStart(4, '0')"></p>
                </label>
              </div>
              
              <div>
                <label class="block">
                  <span class="font-medium text-slate-600 dark:text-navy-100">Title</span>
                  <p class="mt-1.5" x-text="currentReport.title"></p>
                </label>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block">
                    <span class="font-medium text-slate-600 dark:text-navy-100">District</span>
                    <p class="mt-1.5" x-text="currentReport.district"></p>
                  </label>
                </div>
                
                <div>
                  <label class="block">
                    <span class="font-medium text-slate-600 dark:text-navy-100">Date</span>
                    <p class="mt-1.5 mono text-sm" x-text="formatDate(currentReport.created_at)"></p>
                  </label>
                </div>
              </div>

              <div>
                <label class="block">
                  <span class="font-medium text-slate-600 dark:text-navy-100">Report Content</span>
                  <div class="mt-1.5 rounded-lg bg-slate-100 dark:bg-navy-900 p-4">
                    <p class="whitespace-pre-wrap text-sm" x-text="currentReport.report"></p>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-2 px-4 py-3 sm:px-5">
            <button @click="showViewModal = false"
              class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
              Close
            </button>
            <a :href="'print_report.php?id=' + currentReport.id" target="_blank"
              class="btn min-w-[7rem] rounded-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
              Print Report
            </a>
          </div>
        </div>
      </div>
    </template>

    <!-- Edit Report Modal -->
    <template x-teleport="#x-teleport-target">
      <div class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5"
        x-show="showEditModal" role="dialog" @keydown.window.escape="showEditModal = false">
        <div class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300" @click="showEditModal = false" x-show="showEditModal" x-transition:enter="ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full max-w-2xl origin-bottom rounded-lg bg-white transition-all duration-300 dark:bg-navy-700"
          x-show="showEditModal" x-transition:enter="easy-out" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="easy-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
          
          <div class="flex justify-between rounded-t-lg bg-slate-200 px-4 py-3 dark:bg-navy-800 sm:px-5">
            <h3 class="text-base font-medium text-slate-700 dark:text-navy-100">
              Edit Report
            </h3>
            <button @click="showEditModal = false" class="btn -mr-1.5 size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="report_id" x-model="currentReport.id">
            
            <div class="px-4 py-4 sm:px-5 max-h-[calc(100vh-200px)] overflow-y-auto">
              <div class="space-y-4">
                <label class="block">
                  <span class="font-medium text-slate-600 dark:text-navy-100">Title</span>
                  <input name="title" x-model="currentReport.title"
                    class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                    placeholder="Report title" type="text" required />
                </label>

                <label class="block">
                  <span class="font-medium text-slate-600 dark:text-navy-100">District</span>
                  <input name="district" x-model="currentReport.district"
                    class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                    placeholder="District name" type="text" required />
                </label>

                <label class="block">
                  <span class="font-medium text-slate-600 dark:text-navy-100">Report Content</span>
                  <textarea name="report" x-model="currentReport.report" rows="8"
                    class="form-textarea mt-1.5 w-full resize-none rounded-lg border border-slate-300 bg-transparent p-2.5 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                    placeholder="Enter detailed report..." required></textarea>
                </label>
              </div>
            </div>

            <div class="flex justify-end space-x-2 px-4 py-3 sm:px-5">
              <button type="button" @click="showEditModal = false"
                class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                Cancel
              </button>
              <button type="submit"
                class="btn min-w-[7rem] rounded-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <template x-teleport="#x-teleport-target">
      <div class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5"
        x-show="showDeleteModal" role="dialog" @keydown.window.escape="showDeleteModal = false">
        <div class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300" @click="showDeleteModal = false" x-show="showDeleteModal" x-transition:enter="ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full max-w-lg origin-bottom rounded-lg bg-white transition-all duration-300 dark:bg-navy-700"
          x-show="showDeleteModal" x-transition:enter="easy-out" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="easy-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
          
          <div class="flex justify-between rounded-t-lg bg-slate-200 px-4 py-3 dark:bg-navy-800 sm:px-5">
            <h3 class="text-base font-medium text-slate-700 dark:text-navy-100">
              Confirm Delete
            </h3>
            <button @click="showDeleteModal = false" class="btn -mr-1.5 size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div class="px-4 py-4 sm:px-5">
            <div class="flex items-start space-x-3">
              <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-error/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-slate-700 dark:text-navy-100">
                  Are you sure you want to delete this report?
                </p>
                <p class="mt-2 text-sm text-slate-500 dark:text-navy-300">
                  "<span x-text="deleteTitle"></span>" will be permanently deleted. This action cannot be undone.
                </p>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-2 px-4 py-3 sm:px-5">
            <button @click="showDeleteModal = false"
              class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
              Cancel
            </button>
            <form method="POST" action="" class="inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="report_id" x-model="deleteId">
              <button type="submit"
                class="btn min-w-[7rem] rounded-full bg-error font-medium text-white hover:bg-error-focus focus:bg-error-focus active:bg-error-focus/90">
                Delete Report
              </button>
            </form>
          </div>
        </div>
      </div>
    </template>
    <!-- 
        This is a place for Alpine.js Teleport feature 
        @see https://alpinejs.dev/directives/teleport
      -->
    <div id="x-teleport-target"></div>
    <script>
      function reportManager() {
        return {
          showViewModal: false,
          showEditModal: false,
          showDeleteModal: false,
          currentReport: {},
          deleteId: null,
          deleteTitle: '',

          viewReport(report) {
            this.currentReport = {...report};
            this.showViewModal = true;
          },

          editReport(report) {
            this.currentReport = {...report};
            this.showEditModal = true;
          },

          deleteReport(id, title) {
            this.deleteId = id;
            this.deleteTitle = title;
            this.showDeleteModal = true;
          },
          init() {
            // Check URL for parameters on load
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('updated')) {
                this.$notification({
                    text: 'Report updated successfully!',
                    variant: 'info',
                    position: 'center-top'
                });
            }
            
            if (urlParams.has('deleted')) {
                this.$notification({
                    text: 'Report deleted successfully!',
                    variant: 'error',
                    position: 'center-top'
                });
            }
           },

          formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-GB', { 
              day: '2-digit', 
              month: 'short', 
              year: 'numeric' 
            });
          }
        }
      }

      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
  </body>
</html>
