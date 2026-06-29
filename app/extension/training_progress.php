<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'training_progress.php';

$filter_course = (int)($_GET['course_id'] ?? 0);
$filter_search = $conn->real_escape_string(trim($_GET['search'] ?? ''));

// All courses (for filter dropdown)
$courses = [];
$res = $conn->query("SELECT id,title FROM training_courses ORDER BY title");
while ($r = $res->fetch_assoc()) $courses[] = $r;

// Course filter label
$course_title = '';
if ($filter_course > 0) {
    $cr = $conn->query("SELECT title FROM training_courses WHERE id=$filter_course");
    if ($cr && $cr->num_rows > 0) $course_title = $cr->fetch_assoc()['title'];
}

// ── Summary stats ─────────────────────────────────────────────────────────
$total_enrolled  = $conn->query("SELECT COUNT(DISTINCT user_id) AS c FROM training_progress")->fetch_assoc()['c'];
$total_completed = $conn->query("SELECT COUNT(*) AS c FROM training_progress WHERE status='completed'")->fetch_assoc()['c'];
$total_started   = $conn->query("SELECT COUNT(*) AS c FROM training_progress WHERE status='started'")->fetch_assoc()['c'];
$total_lessons   = $conn->query("SELECT COUNT(*) AS c FROM training_lessons")->fetch_assoc()['c'];

// ── Per-farmer progress ────────────────────────────────────────────────────
$course_cond = $filter_course > 0 ? "AND tl.course_id=$filter_course" : '';
$search_cond = $filter_search !== '' ? "AND u.name LIKE '%$filter_search%'" : '';

$farmers = [];
$res = $conn->query("
    SELECT
        u.id, u.name, u.location, u.phone,
        COUNT(DISTINCT tp.lesson_id)                              AS lessons_touched,
        SUM(tp.status='completed')                                AS lessons_completed,
        SUM(tp.status='started')                                  AS lessons_in_progress,
        MAX(tp.started_at)                                        AS last_activity,
        COUNT(DISTINCT tl.course_id)                              AS courses_active
    FROM training_progress tp
    JOIN training_lessons tl ON tl.id = tp.lesson_id $course_cond
    JOIN users u             ON u.id  = tp.user_id   $search_cond
    GROUP BY u.id
    ORDER BY last_activity DESC
");
while ($r = $res->fetch_assoc()) $farmers[] = $r;

// ── Course completion breakdown ────────────────────────────────────────────
$course_stats = [];
$res = $conn->query("
    SELECT
        tc.id, tc.title, tc.category,
        COUNT(DISTINCT tl.id)                           AS total_lessons,
        COUNT(DISTINCT tp.user_id)                      AS enrolled,
        SUM(tp.status='completed')                      AS completions,
        COUNT(DISTINCT CASE WHEN tp.status='completed'
              AND (SELECT COUNT(*) FROM training_lessons x WHERE x.course_id=tc.id)
              = (SELECT COUNT(*) FROM training_progress y
                 JOIN training_lessons z ON z.id=y.lesson_id
                 WHERE z.course_id=tc.id AND y.user_id=tp.user_id AND y.status='completed')
              THEN tp.user_id END)                      AS fully_completed
    FROM training_courses tc
    LEFT JOIN training_lessons tl ON tl.course_id = tc.id
    LEFT JOIN training_progress tp ON tp.lesson_id = tl.id
    GROUP BY tc.id
    ORDER BY enrolled DESC LIMIT 6
");
while ($r = $res->fetch_assoc()) $course_stats[] = $r;

// ── Per-lesson heatmap data ────────────────────────────────────────────────
$lesson_stats = [];
$cond = $filter_course > 0 ? "WHERE tl.course_id=$filter_course" : '';
$res = $conn->query("
    SELECT
        tl.id, tl.title, tc.title AS course_title,
        COUNT(tp.id)              AS starts,
        SUM(tp.status='completed') AS completions
    FROM training_lessons tl
    JOIN training_courses tc ON tc.id = tl.course_id
    LEFT JOIN training_progress tp ON tp.lesson_id = tl.id
    $cond
    GROUP BY tl.id
    ORDER BY starts DESC
    LIMIT 10
");
while ($r = $res->fetch_assoc()) $lesson_stats[] = $r;

$avatar_colors = ['#1D9E75','#378ADD','#D85A30','#BA7517','#993556','#0F6E56','#185FA5'];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>Agriconnect - Training Courses — Extension</title>
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
.heat-bar{height:6px;border-radius:3px;transition:width .5s ease}
.progress-pill{display:inline-flex;align-items:center;gap:4px;font-size:11px;padding:2px 8px;border-radius:20px;font-weight:500}
.farmer-row:hover{background:#f9fafb}
.ring-wrap{position:relative;display:inline-flex;align-items:center;justify-content:center}
</style>
  </head>

  <body class="is-header-blur" x-bind="$store.global.documentBody"
  x-data="{ detailFarmer: null, detailModal: false }">
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
              <a href="index.htm.html">
                <img class="size-11 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
              </a>
            </div>

            <!-- Main Sections Links -->
            <?php include 'sidenav.php';?>            
          </div>
        </div>

        <!-- Sidebar Panel -->
        <?php include 'trainingsider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

       <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="col-span-12 lg:col-span-8 xl:col-span-8">
            <div :class="$store.breakpoints.smAndUp && 'via-purple-300'" class="card mt-12 bg-gradient-to-l from-pink-300 to-indigo-400 p-5 sm:mt-0 sm:flex-row">
              <!-- <div class="flex justify-center sm:order-last">
                <img class="-mt-16 h-40 sm:mt-0" src="images/illustrations/teacher.svg" alt="">
              </div> -->
              <div class="mt-2 flex-1 pt-2 text-center text-white sm:mt-0 sm:text-left">
                <!-- Summary stats -->
                    <div class="grid grid-cols-4 gap-4 mb-5">
                        <div class="bg-navy-600 rounded-xl border-navy-100 p-4">
                            <p class="text-xs text-gray-400 mb-1">Farmers enrolled</p>
                            <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_enrolled ?></p>
                        </div>
                        <div class="bg-navy-600 rounded-xl border border-navy-100 p-4">
                            <p class="text-xs text-gray-400 mb-1">Lessons completed</p>
                            <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_completed ?></p>
                        </div>
                        <div class="bg-navy-600 rounded-xl border border-navy-100 p-4">
                            <p class="text-xs text-gray-400 mb-1">In progress</p>
                            <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_started ?></p>
                        </div>
                        <div class="bg-navy-600 rounded-xl border border-navy-100 p-4">
                            <p class="text-xs text-gray-400 mb-1">Total lessons</p>
                            <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_lessons ?></p>
                        </div>
                    </div>

              </div>
            </div>

            <!-- <div class="mt-4 sm:mt-5 lg:mt-6">
              <div class="flex h-8 items-center justify-between">
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Week 2 Classes
                </h2>
                <a href="#" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">View All</a>
              </div>
              <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5">
                <div class="card flex-row overflow-hidden">
                  <div class="h-full w-1 bg-gradient-to-b from-blue-500 to-purple-600"></div>
                  <div class="flex flex-1 flex-col justify-between p-4 sm:px-5">
                    <div>
                      <img class="size-12 rounded-lg object-cover object-center" src="images/others/testing-sm.jpg" alt="image">
                      <h3 class="mt-3 font-medium text-slate-700 line-clamp-2 dark:text-navy-100">
                        Basic English
                      </h3>
                      <p class="text-xs+">Mon. 08:00 - 09:00</p>
                      <div class="mt-2 flex space-x-1.5">
                        <a href="#" class="tag bg-primary py-1 px-1.5 text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                          Language
                        </a>
                      </div>
                    </div>
                    <div class="mt-10 flex justify-between">
                      <div class="flex -space-x-2">
                        <div class="avatar size-7 hover:z-10">
                          <img class="rounded-full ring ring-white dark:ring-navy-700" src="images/avatar/avatar-16.jpg" alt="avatar">
                        </div>

                        <div class="avatar size-7 hover:z-10">
                          <div class="is-initial rounded-full bg-info text-xs+ uppercase text-white ring ring-white dark:ring-navy-700">
                            jd
                          </div>
                        </div>

                        <div class="avatar size-7 hover:z-10">
                          <img class="rounded-full ring ring-white dark:ring-navy-700" src="images/avatar/avatar-19.jpg" alt="avatar">
                        </div>
                      </div>
                      <button class="btn size-7 rounded-full bg-slate-150 p-0 font-medium text-slate-800 hover:bg-slate-200 hover:shadow-lg hover:shadow-slate-200/50 focus:bg-slate-200 focus:shadow-lg focus:shadow-slate-200/50 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:hover:shadow-navy-450/50 dark:focus:bg-navy-450 dark:focus:shadow-navy-450/50 dark:active:bg-navy-450/90">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 rotate-45" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="card flex-row overflow-hidden">
                  <div class="h-full w-1 bg-gradient-to-b from-info to-info-focus"></div>
                  <div class="flex flex-1 flex-col justify-between p-4 sm:px-5">
                    <div>
                      <img class="size-12 rounded-lg object-cover object-center" src="images/others/design-sm.jpg" alt="image">
                      <h3 class="mt-3 font-medium text-slate-700 line-clamp-2 dark:text-navy-100">
                        Learn UI/UX Design
                      </h3>
                      <p class="text-xs+">Tue. 10:00 - 11:30</p>
                      <div class="mt-2 flex space-x-1.5">
                        <a href="#" class="tag bg-info py-1 px-1.5 text-white hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90">
                          UI/UX Design
                        </a>
                      </div>
                    </div>
                    <div class="mt-10 flex justify-between">
                      <div class="flex -space-x-2">
                        <div class="avatar size-7 hover:z-10">
                          <img class="rounded-full ring ring-white dark:ring-navy-700" src="images/avatar/avatar-20.jpg" alt="avatar">
                        </div>

                        <div class="avatar size-7 hover:z-10">
                          <div class="is-initial rounded-full bg-warning text-xs+ uppercase text-white ring ring-white dark:ring-navy-700">
                            iu
                          </div>
                        </div>

                        <div class="avatar size-7 hover:z-10">
                          <img class="rounded-full ring ring-white dark:ring-navy-700" src="images/avatar/avatar-17.jpg" alt="avatar">
                        </div>
                      </div>
                      <button class="btn size-7 rounded-full bg-slate-150 p-0 font-medium text-slate-800 hover:bg-slate-200 hover:shadow-lg hover:shadow-slate-200/50 focus:bg-slate-200 focus:shadow-lg focus:shadow-slate-200/50 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:hover:shadow-navy-450/50 dark:focus:bg-navy-450 dark:focus:shadow-navy-450/50 dark:active:bg-navy-450/90">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 rotate-45" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="card flex-row overflow-hidden">
                  <div class="h-full w-1 bg-gradient-to-b from-secondary-light to-secondary"></div>
                  <div class="flex flex-1 flex-col justify-between p-4 sm:px-5">
                    <div>
                      <img class="size-12 rounded-lg object-cover object-center" src="images/others/sales-presentation-sm.jpg" alt="image">
                      <h3 class="mt-3 font-medium text-slate-700 line-clamp-2 dark:text-navy-100">
                        Basic of digital marketing
                      </h3>
                      <p class="text-xs+">Wed. 09:00 - 11:00</p>
                      <div class="mt-2 flex space-x-1.5">
                        <a href="#" class="tag bg-secondary px-1.5 py-1 text-white hover:bg-secondary-focus focus:bg-secondary-focus active:bg-secondary-focus/90">
                          Marketing
                        </a>
                      </div>
                    </div>
                    <div class="mt-10 flex justify-between">
                      <div class="flex -space-x-2">
                        <div class="avatar size-7 hover:z-10">
                          <img class="rounded-full ring ring-white dark:ring-navy-700" src="images/avatar/avatar-16.jpg" alt="avatar">
                        </div>

                        <div class="avatar size-7 hover:z-10">
                          <div class="is-initial rounded-full bg-info text-xs+ uppercase text-white ring ring-white dark:ring-navy-700">
                            jd
                          </div>
                        </div>

                        <div class="avatar size-7 hover:z-10">
                          <img class="rounded-full ring ring-white dark:ring-navy-700" src="images/avatar/avatar-19.jpg" alt="avatar">
                        </div>
                      </div>
                      <button class="btn size-7 rounded-full bg-slate-150 p-0 font-medium text-slate-800 hover:bg-slate-200 hover:shadow-lg hover:shadow-slate-200/50 focus:bg-slate-200 focus:shadow-lg focus:shadow-slate-200/50 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:hover:shadow-navy-450/50 dark:focus:bg-navy-450 dark:focus:shadow-navy-450/50 dark:active:bg-navy-450/90">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 rotate-45" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div> -->

            <div class="mt-4 sm:mt-5 lg:mt-6">
              <div class="flex items-center justify-between">
                <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                  Engagemet table
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
                <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                  <table class="is-hoverable w-full text-left">
                    <thead>
                      <tr>
                        <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          NAME
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          COURSES
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          COMPLETED
                        </th>
                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                          PROGRESS
                        </th>
                        <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                        LAST ACTIVE
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($farmers)): ?>
                            <tr><td colspan="5" class="px-4 py-10 text-center text-xs text-gray-400">
                                No farmer progress data yet<?= $filter_course?' for this course':'' ?>
                            </td></tr>
                        <?php else: foreach($farmers as $i=>$f):
                            $touched   = max(1, (int)$f['lessons_touched']);
                            $completed = (int)$f['lessons_completed'];
                            $pct       = min(100, round(($completed / $touched) * 100));
                            $color     = $avatar_colors[$i % count($avatar_colors)];
                        ?>
                      <tr class=" farmer-row cursor-pointer border-y border-transparent border-b-slate-200 dark:border-b-navy-500"
                      @click="detailFarmer=<?= json_encode([
                                    'name'       => $f['name'],
                                    'location'   => $f['location']??'',
                                    'phone'      => $f['phone']??'',
                                    'touched'    => $f['lessons_touched'],
                                    'completed'  => $f['lessons_completed'],
                                    'inprogress' => $f['lessons_in_progress'],
                                    'courses'    => $f['courses_active'],
                                    'last'       => $f['last_activity'] ? date('d M Y', strtotime($f['last_activity'])) : '—',
                                    'initials'   => strtoupper(substr($f['name'],0,2)),
                                    'color'      => $color,
                                ]) ?>; detailModal=true">
                                <!-- Farmer detail modal -->
<div x-show="detailModal"
     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     :style="detailModal?'display:flex':'display:none'" @keydown.escape.window="detailModal=false"
     style="position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.35);align-items:center;justify-content:center">
    <div class="bg-white rounded-2xl w-full max-w-sm mx-4 overflow-hidden" @click.stop x-show="detailFarmer">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm text-white" :style="'background:'+detailFarmer?.color" style="font-weight:500" x-text="detailFarmer?.initials"></div>
                <div>
                    <p class="text-sm text-gray-800" style="font-weight:500" x-text="detailFarmer?.name"></p>
                    <p class="text-xs text-gray-400" x-text="detailFarmer?.location"></p>
                </div>
            </div>
            <button @click="detailModal=false" class="text-gray-300 hover:text-gray-500"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3l10 10M13 3L3 13"/></svg></button>
        </div>
        <div class="px-5 py-4">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="rounded-lg p-3 text-center" style="background:#EAF3DE">
                    <p class="mono text-xl" style="font-weight:500;color:#3B6D11" x-text="detailFarmer?.completed"></p>
                    <p class="text-xs mt-0.5" style="color:#3B6D11">Completed</p>
                </div>
                <div class="rounded-lg p-3 text-center" style="background:#E6F1FB">
                    <p class="mono text-xl" style="font-weight:500;color:#185FA5" x-text="detailFarmer?.inprogress"></p>
                    <p class="text-xs mt-0.5" style="color:#185FA5">In progress</p>
                </div>
            </div>
            <div class="space-y-1">
                <div class="flex justify-between py-1.5" style="border-bottom:1px solid #f9fafb">
                    <span class="text-xs text-gray-400">Lessons touched</span>
                    <span class="mono text-xs text-gray-700" style="font-weight:500" x-text="detailFarmer?.touched"></span>
                </div>
                <div class="flex justify-between py-1.5" style="border-bottom:1px solid #f9fafb">
                    <span class="text-xs text-gray-400">Active courses</span>
                    <span class="mono text-xs text-gray-700" style="font-weight:500" x-text="detailFarmer?.courses"></span>
                </div>
                <div class="flex justify-between py-1.5" style="border-bottom:1px solid #f9fafb">
                    <span class="text-xs text-gray-400">Phone</span>
                    <span class="mono text-xs text-gray-700" style="font-weight:500" x-text="detailFarmer?.phone||'—'"></span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-xs text-gray-400">Last active</span>
                    <span class="mono text-xs text-gray-700" style="font-weight:500" x-text="detailFarmer?.last"></span>
                </div>
            </div>
        </div>
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end">
            <button @click="detailModal=false" class="btn-ghost">Close</button>
        </div>
    </div>
</div>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <div class="flex items-center space-x-4">
                            <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs text-white flex-shrink-0" style="background:<?= $color ?>;font-weight:500"><?= strtoupper(substr($f['name'],0,1)) ?></div>
                                        <div>
                                            <p class="text-xs text-gray-700" style="font-weight:500"><?= htmlspecialchars($f['name']) ?></p>
                                            <p class="text-xs text-gray-400"><?= htmlspecialchars($f['location']??'') ?></p>
                                        </div>
                                    </div>
                          </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <a href="#" class="hover:underline focus:underline"><?= $f['courses_active'] ?>
                          </a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <div class="badge space-x-2.5 text-success">
                            <div class="size-2 rounded-full bg-current"></div>
                            <span><?= $completed ?> / <?= $touched ?></span>
                          </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                          <div class="flex items-center gap-2">
                                        <div class="flex-1 h-1.5 rounded-full" style="background:#f3f4f6;min-width:60px">
                                            <div class="h-1.5 rounded-full" style="width:<?= $pct ?>%;background:<?= $pct>=80?'#1D9E75':($pct>=40?'#EF9F27':'#E24B4A') ?>;transition:width .4s"></div>
                                        </div>
                                        <span class="mono text-xs text-gray-500"><?= $pct ?>%</span>
                                    </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                        <?= $f['last_activity'] ? date('d M', strtotime($f['last_activity'])) : '—' ?>
                        </td>
                      </tr>
                      <?php endforeach; endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="col-span-12 lg:col-span-4 xl:col-span-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-1 lg:gap-6">
                <div class="card pb-5">
                <div class="mt-3 flex items-center justify-between px-4">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Top lessons
                  </h2>
                  <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                    <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn -mr-1.5 size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
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
                    <div class="p-3 space-y-2.5">
                        <?php if(empty($lesson_stats)): ?>
                        <p class="text-xs text-gray-400 text-center py-4">No lesson activity yet</p>
                        <?php else:
                            $max_starts = max(1, max(array_column($lesson_stats, 'starts')));
                            foreach($lesson_stats as $ls):
                                $bar_pct = round(((int)$ls['starts'] / $max_starts) * 100);
                                $done_pct = $ls['starts'] > 0 ? round(((int)$ls['completions'] / (int)$ls['starts']) * 100) : 0;
                        ?>
                        <div>
                            <div class="flex items-start justify-between mb-1">
                                <p class="text-xs text-gray-700 truncate flex-1 pr-2"><?= htmlspecialchars(mb_strimwidth($ls['title'],0,28,'…')) ?></p>
                                <span class="mono text-xs text-gray-400 flex-shrink-0"><?= $ls['starts'] ?></span>
                            </div>
                            <div class="flex gap-1 items-center">
                                <div class="flex-1 rounded-full" style="height:4px;background:#f3f4f6">
                                    <div class="heat-bar" style="width:<?= $bar_pct ?>%;background:#1D9E75;height:4px"></div>
                                </div>
                                <span class="text-gray-400" style="font-size:10px;min-width:30px;text-align:right"><?= $done_pct ?>% done</span>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
              </div>
              <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex h-8 items-center justify-between">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Courses
                  </h2>
                  <a href="#" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">View All</a>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-x-5 lg:grid-cols-1">
                    <?php if(empty($course_stats)): ?>
                        <p class="text-xs text-gray-400 text-center py-4">No data yet</p>
                        <?php else: foreach($course_stats as $cs):
                            $enr = max(1, (int)$cs['enrolled']);
                            $pct = min(100, round(((int)$cs['completions'] / ($enr * max(1,(int)$cs['total_lessons']))) * 100));
                        ?>
                  <div class="card p-3">
                    <div class="flex items-center justify-between space-x-2">
                      <div class="flex items-center space-x-3">
                        <!-- <div class="avatar size-10">
                          <img class="rounded-full" src="images/avatar/avatar-20.jpg" alt="avatar">
                          <div class="absolute right-0 size-3 rounded-full border-2 border-white bg-primary dark:border-navy-700 dark:bg-accent"></div>
                        </div> -->
                        <div>
                          <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                            <?= htmlspecialchars(mb_strimwidth($cs['title'],0,30,'…')) ?>
                          </p>
                          <p class="mt-0.5 text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                            <?= $pct ?>% completed
                          </p>
                        </div>
                      </div>
                      <div class="relative cursor-pointer">
                        <button class="btn size-8 rounded-full bg-slate-150 p-0 font-medium text-slate-700 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-100 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90" x-tooltip.secondary="'<?= $cs['enrolled'] ?> Enrolled'">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path>
                          </svg>
                        </button>
                        <div class="absolute top-0 right-0 -m-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-primary px-1 text-tiny font-medium leading-none text-white dark:bg-accent">
                         <?= $cs['enrolled'] ?>
                        </div>
                      </div>
                    </div>
                    <div class="w-full rounded-full" style="height:5px;background:#f3f4f6">
                                <div class="rounded-full" style="height:5px;width:<?= $pct ?>%;background:#1D9E75;transition:width .4s"></div>
                            </div>
                  </div>
                  <?php endforeach; endif; ?>
                  <!-- <div class="card p-3">
                    <div class="flex items-center justify-between space-x-2">
                      <div class="flex items-center space-x-3">
                        <div class="avatar size-10">
                          <img class="rounded-full" src="images/avatar/avatar-19.jpg" alt="avatar">
                        </div>
                        <div>
                          <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                            Konnor Guzman
                          </p>
                          <p class="mt-0.5 text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                            78% completed
                          </p>
                        </div>
                      </div>
                      <div class="relative cursor-pointer">
                        <button class="btn size-8 rounded-full bg-slate-150 p-0 font-medium text-slate-700 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-100 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="card p-3">
                    <div class="flex items-center justify-between space-x-2">
                      <div class="flex items-center space-x-3">
                        <div class="avatar size-10">
                          <img class="rounded-full" src="images/avatar/avatar-18.jpg" alt="avatar">
                        </div>
                        <div>
                          <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                            Alfredo Elliott
                          </p>
                          <p class="mt-0.5 text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                            58% completed
                          </p>
                        </div>
                      </div>
                      <div class="relative cursor-pointer">
                        <button class="btn size-8 rounded-full bg-slate-150 p-0 font-medium text-slate-700 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-100 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                          </svg>
                        </button>
                        <div class="absolute top-0 right-0 -m-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-primary px-1 text-tiny font-medium leading-none text-white dark:bg-accent">
                          3
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card p-3">
                    <div class="flex items-center justify-between space-x-2">
                      <div class="flex items-center space-x-3">
                        <div class="avatar size-10">
                          <img class="rounded-full" src="images/avatar/avatar-5.jpg" alt="avatar">
                          <div class="absolute right-0 size-3 rounded-full border-2 border-white bg-primary dark:border-navy-700 dark:bg-accent"></div>
                        </div>
                        <div>
                          <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                            Derrick Simmons
                          </p>
                          <p class="mt-0.5 text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                            65% completed
                          </p>
                        </div>
                      </div>
                      <div class="relative cursor-pointer">
                        <button class="btn size-8 rounded-full bg-slate-150 p-0 font-medium text-slate-700 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-100 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div> -->
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
