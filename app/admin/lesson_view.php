<?php
session_start();
$required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

// Basic guards
if (!isset($_SESSION['id'])) {
    die('Unauthorized access');
}

if (!isset($_GET['id'])) {
    die('Lesson not specified');
}

$user_id   = (int) $_SESSION['id'];
$lesson_id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| 1. Fetch lesson
|--------------------------------------------------------------------------
*/
$lesson_q = mysqli_query($conn, "
    SELECT 
    tl.*,
    tc.title AS course_title,
    u.name AS posted_by_name,
    DATE_FORMAT(tl.created_at, '%M %d') AS posted_date
FROM training_lessons tl
JOIN training_courses tc ON tc.id = tl.course_id
LEFT JOIN users u ON u.id = tl.posted_by
WHERE tl.id = $lesson_id

");

if (mysqli_num_rows($lesson_q) === 0) {
    die('Lesson not found');
}

$lesson = mysqli_fetch_assoc($lesson_q);

/*
|--------------------------------------------------------------------------
| 2. Ensure progress row exists (STARTED)
|--------------------------------------------------------------------------
*/
$progress_q = mysqli_query($conn, "
    SELECT * FROM training_progress
    WHERE user_id = $user_id AND lesson_id = $lesson_id
");

if (mysqli_num_rows($progress_q) === 0) {
    mysqli_query($conn, "
        INSERT INTO training_progress (user_id, lesson_id, status, started_at)
        VALUES ($user_id, $lesson_id, 'started', NOW())
    ");

    $status = 'started';
} else {
    $progress = mysqli_fetch_assoc($progress_q);
    $status   = $progress['status'];
}

/*
|--------------------------------------------------------------------------
| 3. Handle completion
|--------------------------------------------------------------------------
*/
if (isset($_POST['complete_lesson']) && $status !== 'completed') {
    mysqli_query($conn, "
        UPDATE training_progress
        SET status = 'completed',
            completed_at = NOW()
        WHERE user_id = $user_id AND lesson_id = $lesson_id
    ");

    $status = 'completed';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - <?= htmlspecialchars($lesson['title']) ?></title>
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

  <body x-data="" class="is-header-blur" x-bind="$store.global.documentBody">
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
        <?php include 'dashboardsider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)]">
        <div class="grid grid-cols-12 lg:gap-6">
          <div class="col-span-12 pt-6 lg:col-span-8 lg:pb-6">
            <div class="card p-4 lg:p-6">
              <!-- Author -->
              <div>
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div x-data="usePopper({
                       offset: 12,
                       placement: 'bottom',
                       modifiers: [
                          {name: 'preventOverflow', options: {padding: 10}}
                       ]                     
                    })" class="flex" @mouseleave="isShowPopper = false" @mouseenter="isShowPopper = true">
                      <div x-ref="popperRef" class="avatar size-12">
                        <img class="mask is-squircle" src="../images/app-logo.png" alt="avatar">
                      </div>
                      <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                        <div class="popper-box">
                          <div class="flex w-48 flex-col items-center rounded-md border border-slate-150 bg-white p-3 text-center dark:border-navy-600 dark:bg-navy-700">
                            <div class="avatar size-16">
                              <img class="rounded-full" src="../images/app-logo.png" alt="avatar">
                            </div>
                            <p class="mt-2 font-medium tracking-wide text-slate-700 dark:text-navy-100">
                              <?= htmlspecialchars($lesson['posted_by_name']) ?>
                            </p>
                            <a href="#" class="font-inter text-xs tracking-wide hover:text-primary focus:text-primary dark:hover:text-accent-light dark:focus:text-accent-light">@travisfuller
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

                      <?php if (!empty($lesson['posted_by_name'])): ?>
                      <a href="#" class="font-medium text-slate-700 line-clamp-1 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">
                        FAIMS - <?= htmlspecialchars($lesson['posted_by_name']) ?>
                        <?php else: ?>
                          Admin
                      <?php endif; ?>
                      </a>
                      <div class="mt-1.5 flex items-center text-xs">
                        <span class="line-clamp-1"><?= $lesson['posted_date'] ?></span>
                        <div class="mx-2 my-0.5 w-px self-stretch bg-white/20"></div>
                        <p class="shrink-0">8 min red</p>
                      </div>
                    </div>
                  </div>

                  <div class="flex space-x-3">
                    <div class="hidden sm:flex">
                      <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                        </svg>
                      </button>
                      <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <i class="fab fa-twitter text-base"></i>
                      </button>
                      <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <i class="fab fa-linkedin text-base"></i>
                      </button>
                      <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <i class="fab fa-instagram text-base"></i>
                      </button>
                      <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                        <i class="fab fa-facebook text-base"></i>
                      </button>
                    </div>
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
                </div>
                <div class="mt-6 flex items-center space-x-3 sm:hidden">
                  <button class="btn space-x-2 rounded-full border border-slate-300 px-4 text-xs+ font-medium text-slate-700 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-100 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                    </svg>

                    <span> Save</span>
                  </button>
                  <div class="flex">
                    <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <i class="fab fa-twitter text-base"></i>
                    </button>
                    <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <i class="fab fa-linkedin text-base"></i>
                    </button>
                    <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <i class="fab fa-instagram text-base"></i>
                    </button>
                    <button class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <i class="fab fa-facebook text-base"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Blog Post -->
              <div class="mt-6 font-inter text-base text-slate-600 dark:text-navy-200">
                <h1 class="text-xl font-medium text-slate-900 dark:text-navy-50 lg:text-2xl">
                  <?= htmlspecialchars($lesson['course_title']) ?> : <?= htmlspecialchars($lesson['title']) ?>
                </h1>
                <h3 class="mt-1">
                  <?= htmlspecialchars($lesson['content']) ?>
                </h3>
                <img class="mt-5 h-80 w-full rounded-lg object-cover object-center" src="../images/object/object-2.jpg" alt="image">
                <p class="mt-1 text-center text-xs+ text-slate-400 dark:text-navy-300">
                  <span> Photo by </span>
                  <a href="#" class="underline">Unsplash</a>
                </p>
                <div class="border-l-4 border-slate-300 pl-4 dark:border-navy-400">
                  <p class="font-medium italic text-slate-800 dark:text-navy-100">
                    Why is Tailwind removing the default styles on my h1
                    elements? How do I disable this? What do you mean I lose all
                    the other base styles too?
                  </p>
                </div>
                <br>
                <ul class="list-inside list-disc font-medium text-slate-800 dark:text-navy-100">
                  <li>
                    Now this is a story all about how, my life got
                    flipped-turned upside down
                  </li>
                  <li>And I'd like to take a minute just sit right there</li>
                  <li>
                    I'll tell you how I became the prince of a town called
                    Bel-Air
                  </li>
                </ul>
                <br>
                <p>
                  <?= nl2br(htmlspecialchars($lesson['content'])) ?>
                </p>
              </div>

              <!-- Footer Blog Post -->
              <div class="mt-5 flex space-x-3">
                <!-- Status badge -->
                <div class="mb-6">
                    <?php if ($status === 'completed'): ?>
                        <span class="badge bg-success/15 text-success">Completed</span>
                    <?php else: ?>
                        <span class="badge bg-warning/15 text-warning">In Progress</span>
                    <?php endif; ?>
                </div>
              </div>

                <!-- PDF -->              
              <div class="mt-5 flex space-x-3">
                <?php if (!empty($lesson['pdf'])): ?>
                  <a href="../uploads/pdfs/<?= htmlspecialchars($lesson['pdf']) ?>" target="_blank"
                    class="btn size-9 rounded-full bg-info/10 p-0 font-medium hover:bg-info/20 focus:bg-info/20 active:bg-info/25" x-tooltip.info="'Download PDF'"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="size-5 text-info"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path 
                      stroke-linecap="round"
                      stroke-width="2"
                      stroke-linejoin="round" 
                      d="m9 13.5 3 3m0 0 3-3m-3 3v-6m1.06-4.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                    </svg>
                  </a>
                  <?php endif; ?>

                  <!-- Video -->
                <?php if (!empty($lesson['video'])): ?>
                  <div x-data="{showModal:false}">
                  <button @click="showModal = true"
                    class="btn mask is-hexagon size-9 bg-warning p-0 font-medium text-white hover:bg-warning-focus focus:bg-warning-focus active:bg-warning-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90" x-tooltip.primary="'Watch Video'"
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
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                      />
                    </svg>
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
          class="relative w-full max-w-2xl origin-bottom rounded-lg bg-white pb-4 transition-all duration-300 dark:bg-navy-700"
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
              Lesson video
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
          <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <video controls class="w-full rounded-lg">
                            <source src="../uploads/videos/<?= htmlspecialchars($lesson['video']) ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
            <!-- <table class="w-full text-left">
              <thead>
                <tr
                  class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500"
                >
                  <th
                    class="whitespace-nowrap px-3 py-3 font-semibold uppercase text-slate-800 dark:text-navy-100 lg:px-5"
                  >
                    #
                  </th>
                  <th
                    class="whitespace-nowrap px-3 py-3 font-semibold uppercase text-slate-800 dark:text-navy-100 lg:px-5"
                  >
                    Name
                  </th>
                  <th
                    class="whitespace-nowrap px-3 py-3 font-semibold uppercase text-slate-800 dark:text-navy-100 lg:px-5"
                  >
                    Role
                  </th>
                  <th
                    class="whitespace-nowrap px-3 py-3 font-semibold uppercase text-slate-800 dark:text-navy-100 lg:px-5"
                  >
                    Status
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500"
                >
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">1</td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    Cy Ganderton
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">Admin</td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    <div
                      class="badge space-x-2.5 rounded-full bg-primary/10 text-primary dark:bg-accent-light/15 dark:text-accent-light"
                    >
                      <div class="size-2 rounded-full bg-current"></div>
                      <span>Online</span>
                    </div>
                  </td>
                </tr>
                <tr
                  class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500"
                >
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">2</td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    StarCodeKh
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">Teacher</td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    <div
                      class="badge space-x-2.5 rounded-full bg-primary/10 text-primary dark:bg-accent-light/15 dark:text-accent-light"
                    >
                      <div class="size-2 rounded-full bg-current"></div>
                      <span>Online</span>
                    </div>
                  </td>
                </tr>
                <tr
                  class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500"
                >
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">3</td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    Konnor Guzman
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">Moderator</td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    <div
                      class="badge space-x-2.5 rounded-full bg-primary/10 text-primary dark:bg-accent-light/15 dark:text-accent-light"
                    >
                      <div class="size-2 rounded-full bg-current"></div>
                      <span>Online</span>
                    </div>
                  </td>
                </tr>
                <tr
                  class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500"
                >
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">4</td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    Alfredo Elliott
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">Admin</td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    <div
                      class="badge space-x-2.5 rounded-full bg-warning/10 text-warning dark:bg-warning/15"
                    >
                      <div class="size-2 rounded-full bg-current"></div>
                      <span>Offline</span>
                    </div>
                  </td>
                </tr>
                <tr
                  class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500"
                >
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">5</td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    Derrick Simmons
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">Teacher</td>
                  <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                    <div
                      class="badge space-x-2.5 rounded-full bg-primary/10 text-primary dark:bg-accent-light/15 dark:text-accent-light"
                    >
                      <div class="size-2 rounded-full bg-current"></div>
                      <span>Offline</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table> -->
          </div>
          <div class="text-center">
            <button
              class="btn mt-4 border border-primary/30 bg-primary/10 font-medium text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:border-accent-light/30 dark:bg-accent-light/10 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
  <?php endif; ?>
                  <!-- Completion -->
                <?php if ($status !== 'completed'): ?>
                    <form method="POST">
                        <button name="complete_lesson"
                            class="btn bg-success text-white rounded-full px-6 hover:bg-success-focus hover:shadow-lg hover:shadow-success/50 focus:bg-success-focus active:bg-success-focus/90">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                            </svg>
                            <span> Mark as Completed</span>
                        </button>
                    </form>
                <?php else: ?>
                    <button class="btn space-x-2 rounded-full border border-slate-300 px-4 text-xs+ font-medium text-slate-700 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-100 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 text-white-300" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                  </svg>
                  <span> You Have Completed this lesson</span>
                </button>
                <?php endif; ?>
              </div>
            </div>

            <div class="mt-5">
              <div class="flex items-center justify-between">
                <p class="text-lg font-medium text-slate-800 dark:text-navy-100">
                  Recent Articles
                </p>
                <a href="#" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">View All</a>
              </div>
              <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-1 lg:gap-6">
                <div class="card lg:flex-row">
                  <img class="h-48 w-full shrink-0 rounded-t-lg bg-cover bg-center object-cover object-center lg:h-auto lg:w-48 lg:rounded-t-none lg:rounded-l-lg" src="../images/object/object-2.jpg" alt="image">
                  <div class="flex w-full grow flex-col px-4 py-3 sm:px-5">
                    <div class="flex items-center justify-between">
                      <a class="text-xs+ text-info" href="#">Frameworks</a>
                      <div class="-mr-1.5 flex space-x-1">
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                          </svg>
                        </button>

                        <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                          <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                    </div>
                    <div>
                      <a href="#" class="text-lg font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">What is Tailwind CSS?</a>
                    </div>
                    <p class="mt-1 line-clamp-3">
                      Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                      Eveniet, provident quasi recusandae repudiandae rerum
                      temporibus!
                    </p>
                    <div class="grow">
                      <div class="mt-2 flex items-center text-xs">
                        <a href="#" class="flex items-center space-x-2 hover:text-slate-800 dark:hover:text-navy-100">
                          <div class="avatar size-6">
                            <img class="rounded-full" src="../images/avatar/avatar-10.jpg" alt="avatar">
                          </div>
                          <span class="line-clamp-1">John Doe</span>
                        </a>
                        <div class="mx-3 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                        <span class="shrink-0 text-slate-400 dark:text-navy-300">June 23, 2021
                        </span>
                      </div>
                    </div>
                    <div class="mt-1 flex justify-end">
                      <a href="#" class="btn px-2.5 py-1.5 font-medium text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25">
                        READ ARTICLE
                      </a>
                    </div>
                  </div>
                </div>
                <div class="card lg:flex-row">
                  <img class="h-48 w-full shrink-0 rounded-t-lg bg-cover bg-center object-cover object-center lg:h-auto lg:w-48 lg:rounded-t-none lg:rounded-l-lg" src="../images/object/object-3.jpg" alt="image">
                  <div class="flex w-full grow flex-col px-4 py-3 sm:px-5">
                    <div class="flex items-center justify-between">
                      <a class="text-xs+ text-info" href="#">Frameworks</a>
                      <div class="-mr-1.5 flex space-x-1">
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                          </svg>
                        </button>

                        <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                          <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                    </div>
                    <div>
                      <a href="#" class="text-lg font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Tailwind CSS Card Example
                      </a>
                    </div>
                    <p class="mt-1 line-clamp-3">
                      Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                      Est repellat nisi corrupti. Lorem, ipsum.
                    </p>
                    <div class="grow">
                      <div class="mt-2 flex items-center text-xs">
                        <a href="#" class="flex items-center space-x-2 hover:text-slate-800 dark:hover:text-navy-100">
                          <div class="avatar size-6">
                            <img class="rounded-full" src="../images/avatar/avatar-2.jpg" alt="avatar">
                          </div>
                          <span class="line-clamp-1">Konnor Guzman </span>
                        </a>
                        <div class="mx-3 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                        <span class="shrink-0 text-slate-400 dark:text-navy-300">May 25, 2021
                        </span>
                      </div>
                    </div>
                    <div class="mt-1 flex justify-end">
                      <a href="#" class="btn px-2.5 py-1.5 font-medium text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25">
                        READ ARTICLE
                      </a>
                    </div>
                  </div>
                </div>
                <div class="card lg:flex-row">
                  <img class="h-48 w-full shrink-0 rounded-t-lg bg-cover bg-center object-cover object-center lg:h-auto lg:w-48 lg:rounded-t-none lg:rounded-l-lg" src="../images/object/object-4.jpg" alt="image">
                  <div class="flex w-full grow flex-col px-4 py-3 sm:px-5">
                    <div class="flex items-center justify-between">
                      <a class="text-xs+ text-info" href="#">Programming Language</a>
                      <div class="-mr-1.5 flex space-x-1">
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                          </svg>
                        </button>

                        <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                          <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                    </div>
                    <div>
                      <a href="#" class="text-lg font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">What is PHP?
                      </a>
                    </div>
                    <p class="mt-1 line-clamp-3">
                      Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                      Eveniet, provident quasi recusandae repudiandae rerum
                      temporibus!
                    </p>
                    <div class="grow">
                      <div class="mt-2 flex items-center text-xs">
                        <a href="#" class="flex items-center space-x-2 hover:text-slate-800 dark:hover:text-navy-100">
                          <div class="avatar size-6">
                            <img class="rounded-full" src="../images/avatar/avatar-1.jpg" alt="avatar">
                          </div>
                          <span class="line-clamp-1">StarCodeKh </span>
                        </a>
                        <div class="mx-3 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                        <span class="shrink-0 text-slate-400 dark:text-navy-300">March 14, 2022
                        </span>
                      </div>
                    </div>
                    <div class="mt-1 flex justify-end">
                      <a href="#" class="btn px-2.5 py-1.5 font-medium text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25">
                        READ ARTICLE
                      </a>
                    </div>
                  </div>
                </div>
                <div class="card lg:flex-row">
                  <img class="h-48 w-full shrink-0 rounded-t-lg bg-cover bg-center object-cover object-center lg:h-auto lg:w-48 lg:rounded-t-none lg:rounded-l-lg" src="../images/object/object-14.jpg" alt="image">
                  <div class="flex w-full grow flex-col px-4 py-3 sm:px-5">
                    <div class="flex items-center justify-between">
                      <a class="text-xs+ text-info" href="#">UI/UX</a>
                      <div class="-mr-1.5 flex space-x-1">
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                          </svg>
                        </button>

                        <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                          <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                    </div>
                    <div>
                      <a href="#" class="text-lg font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Top Design Systems
                      </a>
                    </div>
                    <p class="mt-1 line-clamp-3">
                      Lorem ipsum dolor sit amet consectetur adipisicing elit.
                      Quidem quibusdam, ipsam in eveniet quod voluptatum!
                    </p>
                    <div class="grow">
                      <div class="mt-2 flex items-center text-xs">
                        <a href="#" class="flex items-center space-x-2 hover:text-slate-800 dark:hover:text-navy-100">
                          <div class="avatar size-6">
                            <img class="rounded-full" src="../images/avatar/avatar-7.jpg" alt="avatar">
                          </div>
                          <span class="line-clamp-1">Alfredo Elliott </span>
                        </a>
                        <div class="mx-3 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                        <span class="shrink-0 text-slate-400 dark:text-navy-300">March 14, 2022
                        </span>
                      </div>
                    </div>
                    <div class="mt-1 flex justify-end">
                      <a href="#" class="btn px-2.5 py-1.5 font-medium text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25">
                        READ ARTICLE
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-span-12 py-6 lg:sticky lg:bottom-0 lg:col-span-4 lg:self-end">
            <div class="card">
              <div class="h-24 rounded-t-lg bg-primary dark:bg-accent">
                <img class="h-full w-full rounded-t-lg object-cover object-center" src="../images/object/object-7.jpg" alt="image">
              </div>
              <div class="px-4 pt-2 pb-5 sm:px-5">
                <div class="avatar -mt-12 size-20">
                  <img class="rounded-full border-2 border-white dark:border-navy-700" src="../images/app-logo.png" alt="avatar">
                </div>
                <h3 class="pt-2 text-lg font-medium text-slate-700 dark:text-navy-100">
                  StarCodeKh
                </h3>
                <p class="text-xs+ text-slate-400 dark:text-navy-300">
                  1,596 followers
                </p>
                <p class="mt-3">
                  Professional Full-Stack Developer and amateur cyclist living in
                  New York City, USA.
                </p>
                <div class="mt-5 flex space-x-1">
                  <button class="btn h-7 rounded-full bg-slate-150 px-3 text-xs+ font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                    Follow
                  </button>
                  <button class="btn h-7 w-7 rounded-full bg-slate-150 px-0 text-xs+ font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                    <i class="far fa-envelope"></i>
                  </button>
                  <button class="btn h-7 w-7 rounded-full bg-slate-150 px-0 text-xs+ font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90">
                    <i class="fa fa-ellipsis-h"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="mt-5">
              <p class="border-b border-slate-200 pb-2 text-base text-slate-800 dark:border-navy-600 dark:text-navy-100">
                More from StarCodeKh
              </p>
              <div class="mt-3 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-1">
                <div class="flex justify-between space-x-2">
                  <div class="flex flex-1 flex-col justify-between">
                    <div>
                      <p class="text-xs font-medium line-clamp-1">06 Nov</p>
                      <div class="mt-1 text-slate-800 line-clamp-3 dark:text-navy-100">
                        <a href="#" class="font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">What is Tailwind CSS?</a>
                      </div>
                    </div>
                    <div class="flex items-center justify-between">
                      <p class="text-xs font-medium line-clamp-1">2 min read</p>

                      <div class="flex">
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                          </svg>
                        </button>
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                  <img src="../images/object/object-1.jpg" class="size-24 rounded-lg object-cover object-center" alt="image">
                </div>
                <div class="flex justify-between space-x-2">
                  <div class="flex flex-1 flex-col justify-between">
                    <div>
                      <p class="text-xs font-medium line-clamp-1">13 Oct</p>
                      <div class="mt-1 text-slate-800 line-clamp-3 dark:text-navy-100">
                        <a href="#" class="font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Top Design Systems</a>
                      </div>
                    </div>
                    <div class="flex items-center justify-between">
                      <p class="text-xs font-medium line-clamp-1">6 min read</p>

                      <div class="flex">
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                          </svg>
                        </button>
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                  <img src="../images/object/object-18.jpg" class="size-24 rounded-lg object-cover object-center" alt="image">
                </div>
                <div class="flex justify-between space-x-2">
                  <div class="flex flex-1 flex-col justify-between">
                    <div>
                      <p class="text-xs font-medium line-clamp-1">22 Oct</p>
                      <div class="mt-1 text-slate-800 line-clamp-3 dark:text-navy-100">
                        <a href="#" class="font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">10 Tips for Making a Good Camera Even Better</a>
                      </div>
                    </div>
                    <div class="flex items-center justify-between">
                      <p class="text-xs font-medium line-clamp-1">8 min read</p>

                      <div class="flex">
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                          </svg>
                        </button>
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                  <img src="../images/object/object-16.jpg" class="size-24 rounded-lg object-cover object-center" alt="image">
                </div>
                <div class="flex justify-between space-x-2">
                  <div class="flex flex-1 flex-col justify-between">
                    <div>
                      <p class="text-xs font-medium line-clamp-1">01 Nov</p>
                      <div class="mt-1 text-slate-800 line-clamp-3 dark:text-navy-100">
                        <a href="#" class="font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">25 Surprising Facts About Chair</a>
                      </div>
                    </div>
                    <div class="flex items-center justify-between">
                      <p class="text-xs font-medium line-clamp-1">
                        14 min read
                      </p>

                      <div class="flex">
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                          </svg>
                        </button>
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                  <img src="../images/object/object-11.jpg" class="size-24 rounded-lg object-cover object-center" alt="image">
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
