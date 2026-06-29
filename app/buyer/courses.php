<?php
session_start();
$required_role = 'farmer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';
$user_id = $_SESSION['id'];

$courses = mysqli_query($conn,"SELECT * FROM training_courses ORDER BY created_at DESC");


?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Courses</title>
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
        <?php include 'indexsider.php';?>
      </div>

        <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
       <main class="main-content w-full pb-8">
        <div class="mt-5 px-[var(--margin-x)] transition-all duration-[.25s]">
          <p class="text-base font-medium text-slate-700 dark:text-navy-100">
            My Courses
          </p>
        </div>
        <div class="flex">
          <div class="swiper mx-0 mt-4 px-[var(--margin-x)] transition-all duration-[.25s]" x-init="$nextTick(()=>new Swiper($el,{  slidesPerView: 'auto', spaceBetween: 12}))">
            <div class="swiper-wrapper">
              <?php 
              $colors = ['#4f46e5','#22c55e','#facc15','#0ea5e9','#ef4444'];
              $i = 0;

              while($course = mysqli_fetch_assoc($courses)): 

              $borderColor = $colors[$i % count($colors)];
              $i++;

              ?>
              <?php
                $course_id = $course['id'];

                // total lessons
                $total_q = mysqli_query($conn, "
                    SELECT COUNT(*) AS total
                    FROM training_lessons
                    WHERE course_id = $course_id
                ");
                $total = mysqli_fetch_assoc($total_q)['total'];

                // completed lessons (this farmer)
                $completed_q = mysqli_query($conn, "
                    SELECT COUNT(DISTINCT tp.lesson_id) AS completed
                    FROM training_progress tp
                    JOIN training_lessons tl ON tl.id = tp.lesson_id
                    WHERE tp.user_id = $user_id
                    AND tl.course_id = $course_id
                ");
                $completed = mysqli_fetch_assoc($completed_q)['completed'];

                $progress = ($total > 0) ? round(($completed / $total) * 100) : 0;
                ?>

              <div style="border-left:4px solid <?php echo $borderColor; ?>;" class="card swiper-slide flex w-72 shrink-0 justify-between rounded-xl border-l-4 p-4">
                <div>
                  <p class="font-medium tracking-wide text-slate-700 line-clamp-2 dark:text-navy-100">
                    <?php echo htmlspecialchars($course['title']); ?>
                  </p>
                  <a href="#" class="mt-0.5 text-xs+ text-slate-400 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100"><?php echo htmlspecialchars($course['category']); ?></a>
                </div>

                <div class="mt-6">
                  <div x-tooltip.primary="'<?php echo $progress; ?>% Completed'" class="progress h-1 bg-slate-150 dark:bg-navy-500">
                    <div style="background:<?php echo $borderColor; ?>; width:<?php echo $progress; ?>%"></div>
                  </div>

                  <div class="mt-2 flex items-center justify-between dark:text-accent-light">
                    <p class="font-medium" style="color:<?php echo $borderColor; ?>;">Explore lessons</p>
                    <div x-data="lessonModal()">
                      <button @click="showModal = true" @click.prevent="open(<?php echo $course['id']; ?>)" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                           <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path d="M3.33789 7C5.06694 4.01099 8.29866 2 12.0001 2C17.5229 2 22.0001 6.47715 22.0001 12C22.0001 17.5228 17.5229 22 12.0001 22C8.29866 22 5.06694 19.989 3.33789 17M12 16L16 12M16 12L12 8M16 12H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
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
                                  <?php echo htmlspecialchars($course['title']); ?> Lessons
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
                                <table class="w-full text-left">
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
                                        Lesson
                                      </th>
                                      <th
                                        class="whitespace-nowrap px-3 py-3 font-semibold uppercase text-slate-800 dark:text-navy-100 lg:px-5"
                                      >
                                        Content
                                      </th>
                                      <th
                                        class="whitespace-nowrap px-3 py-3 font-semibold uppercase text-slate-800 dark:text-navy-100 lg:px-5"
                                      >
                                        Action
                                      </th>
                                    </tr>
                                  </thead>
                                  <tbody>
                      <!-- Loading -->
                      <tr x-show="loading">
                        <td colspan="4" class="px-4 py-6 text-center">
                          <div class="spinner is-grow relative size-7 text-center">
                        <span
                          class="absolute inline-block h-full w-full rounded-full bg-warning opacity-75"
                        ></span>
                        <span
                          class="absolute inline-block h-full w-full rounded-full bg-warning opacity-75"
                        ></span>
                      </div>
                          Loading lessons...

                        </td>
                      </tr>

                      <!-- No lessons -->
                      <tr x-show="!loading && lessons.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                            <div
                        class="alert flex overflow-hidden rounded-lg bg-warning/10 text-warning dark:bg-warning/15"
                      >
                        <div class="flex flex-1 items-center space-x-3 p-4">
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
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                          </svg>
                          <div class="flex-1">No lessons available for this course yet.</div>
                        </div>

                        <div class="w-1.5 bg-warning"></div>
                      </div>
                        </td>
                      </tr>

                      <!-- Lessons -->
                      <template x-for="(lesson, index) in lessons" :key="lesson.id">
                        <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                          <td class="whitespace-nowrap px-4 py-3 sm:px-5" x-text="index + 1"></td>

                          <td class="whitespace-nowrap px-4 py-3 sm:px-5"
                              x-text="lesson.title.substring(0, 12) + '…'"></td>

                          <td class="whitespace-nowrap px-4 py-3 sm:px-5"
                              x-text="lesson.content.substring(0, 10) + '…'"></td>

                          <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                            <a :href="'lesson_view.php?id=' + lesson.id"
                               class="badge space-x-2.5 rounded-full bg-warning/10 text-warning
                                      dark:bg-accent-light/15 dark:text-accent-light">
                              <div class="size-2 rounded-full bg-current"></div>
                              <span>View</span>
                            </a>
                          </td>
                        </tr>
                      </template>
                    </tbody>

                                </table>
                              </div>
                              <div class="text-center">
                                <button
                                  class="btn mt-4 border border-primary/30 bg-primary/10 font-medium text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:border-accent-light/30 dark:bg-accent-light/10 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25"
                                >
                                  Show All
                                </button>
                              </div>
                            </div>
                          </div>
                        </template>
                      </div>
                  </div>
                </div>
              </div>
                  <script>
                  function lessonModal() {
                    return {
                      showModal: false,
                      lessons: [],
                      loading: false,
                      courseId: null,

                      open(courseId) {
                        this.showModal = true;
                        this.loading = true;
                        this.lessons = [];
                        this.courseId = courseId;

                        fetch('fetch_lessons.php?course_id=' + courseId)
                          .then(res => res.json())
                          .then(data => {
                            this.lessons = data;
                            this.loading = false;
                          });
                      }
                    }
                  }
                  </script>

              <?php endwhile; ?>
            </div>
          </div>
        </div>
                        <?php
                        
                        ?>
        <div class="mt-4 grid grid-cols-12 gap-4 px-[var(--margin-x)] transition-all duration-[.25s] sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="order-first col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:order-none lg:col-span-6 lg:gap-6">
            <div class="card justify-between p-5">
              <p class="font-medium">Courses In Progress</p>
              <div class="flex items-center justify-between pt-4">
                <p class="text-3xl font-semibold text-slate-700 dark:text-navy-100">
                  <?php echo $completed_courses_q; ?>
                </p>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-10 text-primary dark:text-accent" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
            </div>
            <div class="card justify-between p-5">
              <p class="font-medium">Completed Courses</p>
              <div class="flex items-center justify-between pt-4">
                <p class="text-3xl font-semibold text-slate-700 dark:text-navy-100">
                  14
                </p>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-10 text-secondary dark:text-secondary-light" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
              </div>
            </div>
            <div class="card justify-between p-5">
              <p class="font-medium">Watching Time</p>
              <div class="flex items-center justify-between pt-4">
                <p class="text-slate-700 dark:text-navy-100">
                  <span class="text-3xl font-semibold">214h</span>
                  <span class="text-xl font-medium">21m</span>
                </p>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-10 text-warning" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
              </div>
            </div>
            <div class="card justify-between p-5">
              <p class="font-medium">Earning Points</p>
              <div class="flex items-center justify-between pt-4">
                <p class="text-3xl font-semibold text-slate-700 dark:text-navy-100">
                  8
                </p>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-10 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3-3m0 0l3 3m-3-3v8m0-13a9 9 0 110 18 9 9 0 010-18z"></path>
                </svg>
              </div>
            </div>
          </div>
          <div class="card col-span-12 pb-3 lg:col-span-6">
            <div class="mt-3 flex h-8 items-center justify-between px-4 sm:px-5">
              <h2 class="font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                Courses Timeline
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

            <div class="course-schedule-chart pr-2">
              <div x-init="$nextTick(() => { $el._x_chart = new ApexCharts($el,pages.charts.courseTimeline); $el._x_chart.render() });"></div>
            </div>
          </div>
          <div class="col-span-12 lg:col-span-4">
            <div class="flex items-center justify-between">
              <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                Completed Course
              </h2>
              <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
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
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-x-5 lg:grid-cols-1">
              <div class="card p-2.5">
                <div class="flex justify-between space-x-2">
                  <div class="flex flex-1 flex-col justify-between">
                    <div>
                      <a href="#" class="font-medium text-slate-700 outline-none transition-colors line-clamp-2 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Learn JavaScript Unit Testing</a>
                      <a href="#" class="text-xs text-slate-400 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100">Konnor Guzman</a>
                    </div>
                    <div class="flex items-center space-x-2 text-xs">
                      <div class="flex shrink-0 items-center space-x-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>10h 32m</p>
                      </div>
                      <div class="mx-2 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                      <span class="line-clamp-1">124 Students</span>
                    </div>
                  </div>
                  <img class="size-24 rounded-lg object-cover" src="../images/others/testing-sm.jpg" alt="image">
                </div>
              </div>
              <div class="card p-2.5">
                <div class="flex justify-between space-x-2">
                  <div class="flex flex-1 flex-col justify-between">
                    <div>
                      <a href="#" class="font-medium text-slate-700 outline-none transition-colors line-clamp-2 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Basic of digital marketing</a>
                      <a href="#" class="text-xs text-slate-400 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100">Alfredo Elliott</a>
                    </div>
                    <div class="flex items-center space-x-2 text-xs">
                      <div class="flex shrink-0 items-center space-x-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>16h 14m</p>
                      </div>
                      <div class="mx-2 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                      <span class="line-clamp-1">475 Students </span>
                    </div>
                  </div>
                  <img class="size-24 rounded-lg object-cover" src="../images/illustrations/store-ui.svg" alt="image">
                </div>
              </div>
              <div class="card p-2.5">
                <div class="flex justify-between space-x-2">
                  <div class="flex flex-1 flex-col justify-between">
                    <div>
                      <a href="#" class="font-medium text-slate-700 outline-none transition-colors line-clamp-2 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Sales Analytics Advanced Complete Course</a>
                      <a href="#" class="text-xs text-slate-400 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100">StarCodeKh</a>
                    </div>
                    <div class="flex items-center space-x-2 text-xs">
                      <div class="flex shrink-0 items-center space-x-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>4h 47m</p>
                      </div>
                      <div class="mx-2 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                      <span class="line-clamp-1">985 Students </span>
                    </div>
                  </div>
                  <img class="size-24 rounded-lg object-cover" src="../images/others/sales-presentation-sm.jpg" alt="image">
                </div>
              </div>
              <div class="card p-2.5">
                <div class="flex justify-between space-x-2">
                  <div class="flex flex-1 flex-col justify-between">
                    <div>
                      <a href="#" class="font-medium text-slate-700 outline-none transition-colors line-clamp-2 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Learn UI/UX Design</a>
                      <a href="#" class="text-xs text-slate-400 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100">Henry Curtis</a>
                    </div>
                    <div class="flex items-center space-x-2 text-xs">
                      <div class="flex shrink-0 items-center space-x-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 text-slate-400 dark:text-navy-300" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>7h 56m</p>
                      </div>
                      <div class="mx-2 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                      <span class="line-clamp-1">369 Students </span>
                    </div>
                  </div>
                  <img class="size-24 rounded-lg object-cover" src="../images/others/design-sm.jpg" alt="image">
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
