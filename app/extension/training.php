<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'training.php';

// Handle delete course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_course') {
    $cid = (int)$_POST['course_id'];
    // Only allow deleting own courses
    $conn->query("DELETE FROM training_courses WHERE id=$cid AND created_by=$extension_id");
    // Lessons + progress cascade if FK set; otherwise:
    $conn->query("DELETE FROM training_progress WHERE lesson_id IN (SELECT id FROM training_lessons WHERE course_id=$cid)");
    $conn->query("DELETE FROM training_lessons WHERE course_id=$cid");
    header('Location: training.php?deleted=1'); exit;
}

$search   = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$filter   = $conn->real_escape_string(trim($_GET['category'] ?? ''));

$where = "WHERE 1=1";
if ($search !== '') $where .= " AND (tc.title LIKE '%$search%' OR tc.description LIKE '%$search%')";
if ($filter !== '') $where .= " AND tc.category='$filter'";

// Courses with lesson count + progress count
$courses = [];
$res = $conn->query("
    SELECT tc.*,
           u.name AS author,
           COUNT(DISTINCT tl.id)           AS lesson_count,
           COUNT(DISTINCT tp.id)           AS progress_count,
           SUM(tp.status='completed')      AS completed_count
    FROM training_courses tc
    LEFT JOIN users u           ON u.id = tc.created_by
    LEFT JOIN training_lessons tl ON tl.course_id = tc.id
    LEFT JOIN training_progress tp ON tp.lesson_id = tl.id
    $where
    GROUP BY tc.id
    ORDER BY tc.created_at DESC
");
while ($r = $res->fetch_assoc()) $courses[] = $r;

// Distinct categories
$categories = [];
$res = $conn->query("SELECT DISTINCT category FROM training_courses WHERE category IS NOT NULL ORDER BY category");
while ($r = $res->fetch_assoc()) $categories[] = $r['category'];

// Stats
$total_courses  = count($courses);
$total_lessons  = $conn->query("SELECT COUNT(*) AS c FROM training_lessons tl JOIN training_courses tc ON tc.id=tl.course_id")->fetch_assoc()['c'];
$total_enrolled = $conn->query("SELECT COUNT(DISTINCT user_id) AS c FROM training_progress")->fetch_assoc()['c'];
$my_courses     = $conn->query("SELECT COUNT(*) AS c FROM training_courses WHERE created_by=$extension_id")->fetch_assoc()['c'];

$category_colors = [
    'agronomy'    => ['bg'=>'#EAF3DE','text'=>'#3B6D11'],
    'pest'        => ['bg'=>'#FCEBEB','text'=>'#A32D2D'],
    'irrigation'  => ['bg'=>'#E6F1FB','text'=>'#185FA5'],
    'business'    => ['bg'=>'#FAEEDA','text'=>'#854F0B'],
    'soil'        => ['bg'=>'#FAEEDA','text'=>'#633806'],
    'default'     => ['bg'=>'#F1EFE8','text'=>'#5F5E5A'],
];
function cat_style(string $cat, array $map): string {
    $key = strtolower($cat);
    $c   = $map[$key] ?? $map['default'];
    return "background:{$c['bg']};color:{$c['text']}";
}
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
.course-card{transition:border-color .15s,transform .15s}
.course-card:hover{transform:translateY(-1px)}
.thumb{width:100%;height:120px;object-fit:cover;background:#f3f4f6;display:flex;align-items:center;justify-content:center}
.thumb-placeholder{width:100%;height:120px;display:flex;align-items:center;justify-content:center}
.progress-ring{transform:rotate(-90deg)}
.tag {
    display: inline-flex;
    align-items: center;
    font-size: 10px;
    font-weight: 600;
    padding: 1px 10px;
    border-radius: 9999px;
    border-width: 1px;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

/* Updated Category Styles */
.tag-agronomy { background: rgba(59, 109, 17, 0.1); border-color: rgba(59, 109, 17, 0.3); color: #3B6D11; }
.tag-pest { background: rgba(163, 45, 45, 0.1); border-color: rgba(163, 45, 45, 0.3); color: #A32D2D; }
.tag-irrigation { background: rgba(24, 95, 165, 0.1); border-color: rgba(24, 95, 165, 0.3); color: #185FA5; }
.tag-business { background: rgba(133, 79, 11, 0.1); border-color: rgba(133, 79, 11, 0.3); color: #854F0B; }
.tag-soil { background: rgba(99, 56, 6, 0.1); border-color: rgba(99, 56, 6, 0.3); color: #633806; }
.tag-default { background: rgba(95, 94, 90, 0.1); border-color: rgba(95, 94, 90, 0.3); color: #5F5E5A; }

</style>
  </head>

  <body x-data="trainingManager()" x-data="{ thumbPreview: null, dragging: false,
        previewThumb(e){ const f=e.target.files[0]; if(!f) return; const r=new FileReader(); r.onload=ev=>this.thumbPreview=ev.target.result; r.readAsDataURL(f); } }" class="is-header-blur" x-bind="$store.global.documentBody">
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
          <div class="col-span-12 lg:col-span-8 xl:col-span-7">
            <div :class="$store.breakpoints.smAndUp && 'via-purple-300'" class="card mt-12 bg-gradient-to-l from-pink-300 to-indigo-400 p-5 sm:mt-0 sm:flex-row">
              <div class="flex justify-center sm:order-last">
                <img class="-mt-16 h-40 sm:mt-0" src="../images/illustrations/teacher.svg" alt="">
              </div>
              <div class="mt-2 flex-1 pt-2 text-center text-white sm:mt-0 sm:text-left">
                <h3 class="text-xl">
                  Welcome Back, <span class="font-semibold"><?= htmlspecialchars($_SESSION['name']) ?? 'Extension Worker';?></span>
                </h3>
                <p class="mt-2 leading-relaxed">
                  Your class completed
                  <span class="font-semibold text-navy-700">45%</span> of tasks
                </p>
                <p>Progress is <span class="font-semibold">excellent!</span></p>
                <a href="">
                <button class="btn mt-6 bg-slate-50 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80">
                  New Course
                </button>
                </a>
              </div>
            </div>

            <div class="mt-4 sm:mt-5 lg:mt-6">
              <div class="flex h-8 items-center justify-between">
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  All Courses
                </h2>
                <a href="#" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">View All</a>
              </div>
            </div>
          </div>
          <div class="col-span-12 lg:col-span-4 xl:col-span-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-1 lg:gap-6">
              <div class="card p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:gap-6">
              <div class="flex items-center space-x-4 rounded-2xl border border-slate-150 p-4 dark:border-navy-600">
                <div class="mask is-star-2 flex size-12 items-center justify-center bg-warning/10 dark:bg-warning">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-warning dark:text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"></path>
                  </svg>
                </div>
                <div class="font-inter">
                  <p class="text-base font-semibold text-slate-700 dark:text-navy-100">
                    <?= $total_courses ?>
                  </p>
                  <p>Courses</p>
                </div>
              </div>
              <div class="flex items-center space-x-4 rounded-2xl border border-slate-150 p-4 dark:border-navy-600">
                <div class="mask is-star-2 flex size-12 items-center justify-center bg-primary/10 dark:bg-accent">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-primary dark:text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"></path>
                  </svg>
                </div>
                <div class="font-inter">
                  <p class="text-base font-semibold text-slate-700 dark:text-navy-100">
                   <?= $total_lessons ?>
                  </p>
                  <p>Lessons</p>
                </div>
              </div>
              <div class="flex items-center space-x-4 rounded-2xl border border-slate-150 p-4 dark:border-navy-600">
                <div class="mask is-star-2 flex size-12 items-center justify-center bg-secondary/10 dark:bg-secondary">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-secondary dark:text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"></path>
                  </svg>
                </div>
                <div class="font-inter">
                  <p class="text-base font-semibold text-slate-700 dark:text-navy-100">
                    <?= $total_enrolled ?>
                  </p>
                  <p>Enrolled</p>
                </div>
              </div>
              <div class="flex items-center space-x-4 rounded-2xl border border-slate-150 p-4 dark:border-navy-600">
                <div class="mask is-star-2 flex size-12 items-center justify-center bg-success/10 dark:bg-success">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-success dark:text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"></path>
                  </svg>
                </div>
                <div class="font-inter">
                  <p class="text-base font-semibold text-slate-700 dark:text-navy-100">
                    <?= $my_courses ?>
                  </p>
                  <p>My courses</p>
                </div>
              </div>
            </div>
              </div>

            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 lg:gap-6">
          <?php if(empty($courses)): ?>
        <div class="text-center py-16">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#E1F5EE">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1D9E75" stroke-width="1.5"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 18v2M16 18v2M6 20h12"/></svg>
            </div>
            <p class="text-sm text-gray-500 mb-1" style="font-weight:500">No courses yet</p>
            <p class="text-xs text-gray-400 mb-4">Create your first course to start training farmers</p>
            <a href="training_course_edit.php" class="btn-primary">Create first course</a>
        </div>
        <?php else: ?>
          <?php foreach($courses as $c):
                $is_mine     = (int)$c['created_by'] === $extension_id;
                $lessons     = (int)$c['lesson_count'];
                $enrolled    = (int)$c['progress_count'];
                $completed   = (int)$c['completed_count'];
                $pct         = ($enrolled > 0) ? min(100, round(($completed / $enrolled) * 100)) : 0;
                $thumb_url   = $c['thumbnail'] ? '../uploads/thumbnails/'.htmlspecialchars($c['thumbnail']) : null;
                $cat         = $c['category'] ?? 'general';
                $cat_s       = cat_style($cat, $category_colors);
            ?>
          <div class="card course-card">
            <div class="flex items-center justify-between p-4">
              <div class="flex items-center space-x-3">
                <div x-data="usePopper({
                     offset: 12,
                     placement: 'bottom',
                     modifiers: [
                        {name: 'preventOverflow', options: {padding: 10}}
                     ]                     
                  })" class="flex" @mouseleave="isShowPopper = false" @mouseenter="isShowPopper = true">
                  <!-- <div x-ref="popperRef" class="avatar size-9">
                    <img class="rounded-full" src="../images/avatar/avatar-20.jpg" alt="avatar">
                  </div> -->
                  <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                    <div class="popper-box">
                      <div class="flex w-48 flex-col items-center rounded-md border border-slate-150 bg-white p-3 text-center dark:border-navy-600 dark:bg-navy-700">
                        <div class="avatar size-16">
                          <img class="rounded-full" src="../images/avatar/avatar-20.jpg" alt="avatar">
                        </div>
                        <p class="mt-2 font-medium tracking-wide text-slate-700 dark:text-navy-100">
                          StarCodeKh
                        </p>
                        <a href="#" class="font-inter text-xs tracking-wide hover:text-primary focus:text-primary dark:hover:text-accent-light dark:focus:text-accent-light">@travisaccount
                        </a>
                        <button class="btn mt-4 h-6 rounded-full bg-primary px-4 text-xs font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                          Follow
                        </button>
                      </div>
                      <div class="size-4" data-popper-arrow="">
                        <svg viewbox="0 0 16 9" xmlns="http://www.w3.org/2000/svg" class="absolute size-4" fill="currentColor">
                          <path class="text-slate-150 dark:text-navy-600" d="M1.5 8.357s-.48.624 2.754-4.779C5.583 1.35 6.796.01 8 0c1.204-.009 2.417 1.33 3.76 3.578 3.253 5.43 2.74 4.78 2.74 4.78h-13z"></path>
                          <path class="text-white dark:text-navy-700" d="M0 9s1.796-.017 4.67-4.648C5.853 2.442 6.93 1.293 8 1.286c1.07-.008 2.147 1.14 3.343 3.066C14.233 9.006 15.999 9 15.999 9H0z"></path>
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>
                <div>
                  <p class="text-slate-700 line-clamp-1 dark:text-navy-100">
                    <span class="tag tag-<?= strtolower($cat) ?>">
                          <?= ucfirst($cat) ?>
                      </span>
                        <?php if($is_mine): ?>
                        <span class="text-gray-400" style="font-size:10px;font-weight:500">Yours</span>
                        <?php endif; ?>
                  </p>
                  <p class="text-xs text-slate-400 dark:text-navy-300">
                    2 hours ago
                  </p>
                </div>
              </div>
              <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn -mr-1.5 size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
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
            <?php if($thumb_url && file_exists($thumb_url)): ?>
            <img class="h-20 w-full object-cover object-center" src="<?= $thumb_url ?>" alt="image">
            <?php else: ?>
            <img class="h-20 w-full object-cover object-center" src="../images/object/object-8.jpg" alt="image">
            <?php endif; ?>
            <div class="grow px-4 pt-4">
              <a href="#" class="text-base font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light"><?= htmlspecialchars($c['title']) ?></a>
              <p class="mt-2 line-clamp-3">
                 <?= htmlspecialchars($c['description'] ?? '') ?>
              </p>
              <div class="inline-space mt-3 flex flex-wrap">
                <div class="badge space-x-2 rounded-full bg-info/10 text-info hover:bg-info/20 focus:bg-primary/20 active:bg-info/25 dark:bg-info-light/10 dark:text-info-light dark:hover:bg-info-light/20 dark:focus:bg-info-light/20 dark:active:bg-info-light/25">
                  <svg 
                  xmlns="http://www.w3.org/2000/svg" 
                  fill="none" viewBox="0 0 24 24" 
                  stroke-width="1.5" 
                  stroke="currentColor" 
                  class="size-4">
                    <path
                      stroke-linecap="round" stroke-linejoin="round"
                      d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605"
                      
                    ></path>
                  </svg>
                  <span><?= $lessons ?> lesson<?= $lessons!=1?'s':'' ?></span>
                </div>
                <div class="badge space-x-2 rounded-full bg-success/10 text-success hover:bg-success/20 focus:bg-primary/20 active:bg-success/25 dark:bg-success-light/10 dark:text-success-light dark:hover:bg-success-light/20 dark:focus:bg-success-light/20 dark:active:bg-success-light/25">
                  <svg 
                  xmlns="http://www.w3.org/2000/svg" 
                  fill="none" viewBox="0 0 24 24" 
                  stroke-width="1.5" 
                  stroke="currentColor" 
                  class="size-4">
                    <path
                      stroke-linecap="round" stroke-linejoin="round"
                      d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"
                      
                    ></path>
                  </svg>
                  <span><?= $enrolled ?> enrolled</span>
                </div>
                 <div class="badge space-x-2 rounded-full bg-secondary/10 text-secondary hover:bg-secondary/20 focus:bg-primary/20 active:bg-secondary/25 dark:bg-secondary-light/10 dark:text-secondary-light dark:hover:bg-secondary-light/20 dark:focus:bg-secondary-light/20 dark:active:bg-secondary-light/25">
                  <svg 
                  xmlns="http://www.w3.org/2000/svg" 
                  fill="none" viewBox="0 0 24 24" 
                  stroke-width="1.5" 
                  stroke="currentColor" 
                  class="size-4">
                    <path
                      stroke-linecap="round" stroke-linejoin="round"
                      d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"
                      
                    ></path>
                  </svg>
                  <span><?= date('d M Y', strtotime($c['created_at'])) ?></span>
                </div>
              </div>
              <!-- Progress bar -->
                    <?php if($enrolled > 0): ?>
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400">Completion</span>
                            <span class="mono text-xs text-gray-600"><?= $pct ?>%</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full" style="background:#f3f4f6">
                            <div class="h-1.5 rounded-full" style="width:<?= $pct ?>%;background:#1D9E75;transition:width .4s"></div>
                        </div>
                    </div>
                    <?php endif; ?>
            </div>
            <div class="flex justify-between px-4 py-4">
              <div class="flex flex-wrap space-x-2">
                <a href="training_course_edit.php?id=<?= $c['id'] ?>">
                <button
                  class="btn size-9 rounded-full bg-info/10 p-0 font-medium text-info hover:bg-info/20 focus:bg-info/20 active:bg-info/25" x-tooltip.info="'Edit'"
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
                      d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                    />
                  </svg>
                </button>
              </a>
              <a href="training_lesson_edit.php?course_id=<?= $c['id'] ?>">
                <button
                  class="btn size-9 bg-success p-0 font-medium text-white hover:bg-success-focus hover:shadow-lg hover:shadow-success/50 focus:bg-success-focus focus:shadow-lg focus:shadow-success/50 active:bg-success-focus/90"x-tooltip.success="'Add lesson'"
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
                      d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                    />
                  </svg>
                </button>
              </a>
              <a href="training_progress.php?course_id=<?= $c['id'] ?>">
                <button
                  class="btn size-9 border border-warning/30 bg-warning/10 p-0 font-medium text-warning hover:bg-warning/20 hover:shadow-lg hover:shadow-warning/50 focus:bg-warning/20 focus:shadow-lg focus:shadow-warning/50 active:bg-warning/25" x-tooltip.warning="'Progress'"
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
                      d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"
                    />
                  </svg>
                </button>
              </a>
              <?php if($is_mine): ?>
                <button type="button" 
                @click="confirmDelete(<?= $c['id'] ?>, '<?= addslashes($c['title']) ?>')"
                  class="btn size-9 p-0 font-medium text-error hover:bg-error/20 focus:bg-error/20 active:bg-error/25" x-tooltip.error="'Delete'"
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
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                </button>
                <template x-teleport="#x-teleport-target">
                  <div class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5" x-show="showDeleteModal" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                    <div class="relative w-full max-w-md origin-top rounded-lg bg-white p-4 transition-all duration-300 dark:bg-navy-700" x-show="showDeleteModal" x-transition:enter="transition-all duration-300" x-transition:enter-start="-translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition-all duration-300" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="-translate-y-4 opacity-0">
                      <div class="text-center">
                        <div class="inline-flex size-14 items-center justify-center rounded-full bg-error/10 text-error">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </div>
                        <h2 class="mt-4 text-xl font-semibold text-slate-800 dark:text-navy-50">Delete Course?</h2>
                        <p class="mt-2 text-slate-500 dark:text-navy-200">
                          Are you sure you want to delete <span class="font-bold text-slate-700 dark:text-navy-100" x-text="courseToDelete.title"></span>? This action cannot be undone.
                        </p>
                      </div>
                      <div class="mt-6 flex justify-end space-x-3">
                        <button @click="showDeleteModal = false" class="btn border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">Cancel</button>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete_course">
                            <input type="hidden" name="course_id" :value="courseToDelete.id">
                            <button type="submit" class="btn bg-error font-medium text-white hover:bg-error-focus focus:bg-error-focus active:bg-error-focus/90">Delete Course</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </template>
                
                        <?php endif; ?>
              </div>
            </div>
            <div class="border-t border-slate-200 px-2 py-1.5 dark:border-navy-500">
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </main>
    </div>
    <!-- 
        This is a place for Alpine.js Teleport feature 
        @see https://alpinejs.dev/directives/teleport
      -->
    <div id="x-teleport-target"></div>
    <script>
function trainingManager() {
    return {
        showDeleteModal: false,
        courseToDelete: { id: null, title: '' },

        init() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('deleted')) {
                this.$notification({ text: 'Course deleted successfully!', variant: 'error', position: 'right-top' });
            }
        },

        confirmDelete(id, title) {
            this.courseToDelete = { id, title };
            this.showDeleteModal = true;
        }
    }
}
</script>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
  </body>
</html>
