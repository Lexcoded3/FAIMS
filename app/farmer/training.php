<?php
session_start();
$required_role = 'farmer'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$courses = mysqli_query($conn,"SELECT * FROM training_courses ORDER BY created_at DESC");



?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Training</title>
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
        <?php include 'orderssider.php';?>
      </div>

      <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <div class="col-span-12 lg:col-span-12 xl:col-span-12">
            <div :class="$store.breakpoints.smAndUp && 'via-purple-300'" class="card mt-12 bg-gradient-to-l from-pink-300 to-indigo-400 p-5 sm:mt-0 sm:flex-row">
              <div class="flex justify-center sm:order-last">
                <img class="-mt-16 h-40 sm:mt-0" src="../images/illustrations/teacher.svg" alt="">
              </div>
              <div class="mt-2 flex-1 pt-2 text-center text-white sm:mt-0 sm:text-left">
                <h3 class="text-xl">
                  Welcome Back, <span class="font-semibold"><?= htmlspecialchars($_SESSION['name']); ?></span>
                </h3>
                <p class="mt-2 leading-relaxed">
                  Your completed
                  <span class="font-semibold text-navy-700">8%</span> of tasks
                </p>
                <p>Progress is <span class="font-semibold">excellent!</span></p>
                <a a href="courses.php"> 
                <button class="btn mt-6 bg-slate-50 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80">
                 View Courses
                </button>
              </a>
              </div>
            </div>
            <div class="col-span-12 lg:col-span-8 xl:col-span-9">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-1 lg:gap-6">

              <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex h-8 items-center justify-between">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    My Courses
                  </h2>
                  <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                   <path d="M14 11H8M10 15H8M16 7H8M20 6.8V17.2C20 18.8802 20 19.7202 19.673 20.362C19.3854 20.9265 18.9265 21.3854 18.362 21.673C17.7202 22 16.8802 22 15.2 22H8.8C7.11984 22 6.27976 22 5.63803 21.673C5.07354 21.3854 4.6146 20.9265 4.32698 20.362C4 19.7202 4 18.8802 4 17.2V6.8C4 5.11984 4 4.27976 4.32698 3.63803C4.6146 3.07354 5.07354 2.6146 5.63803 2.32698C6.27976 2 7.11984 2 8.8 2H15.2C16.8802 2 17.7202 2 18.362 2.32698C18.9265 2.6146 19.3854 3.07354 19.673 3.63803C20 4.27976 20 5.11984 20 6.8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                 </svg>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-x-5 lg:grid-cols-1">
                  <div class="flex">
          <div class="swiper mx-0 mt-4 px-[var(--margin-x)] transition-all duration-[.25s]" x-init="$nextTick(()=>new Swiper($el,{  slidesPerView: 'auto', spaceBetween: 18}))">
            <div class="swiper-wrapper">


              <?php 
$colors = ['#4f46e5','#22c55e','#facc15','#0ea5e9','#ef4444'];
$i = 0;

while($course = mysqli_fetch_assoc($courses)): 

$borderColor = $colors[$i % count($colors)];
$i++;
?>

<div style="border-left:4px solid <?php echo $borderColor; ?>;" class="card swiper-slide flex w-72 shrink-0 justify-between rounded-xl border-l-4 p-4">

  <div>
    <p class="font-medium tracking-wide text-slate-700 line-clamp-2 dark:text-navy-100">
      <?php echo htmlspecialchars($course['title']); ?>
    </p>

    <a href="#" class="mt-0.5 text-xs+ text-slate-400 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100">
      <?php echo htmlspecialchars($course['category']); ?>
    </a>
  </div>

  <div class="mt-6">

    <?php
      // TEMP progress (later we calculate real)
      $progress = rand(10,80);
    ?>

    <div x-tooltip.primary="'<?php echo $progress; ?>% Completed'" class="progress h-1 bg-slate-150 dark:bg-navy-500">
      <div style="background:<?php echo $borderColor; ?>; width:<?php echo $progress; ?>%"></div>
    </div>

    <div class="mt-2 flex items-center justify-between dark:text-accent-light">

      <p class="font-medium">Beginner</p>

      <a href="lesson_view.php?course=<?php echo $course['id']; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" x-tooltip.light="'View'">
                         <path d="M3.33789 7C5.06694 4.01099 8.29866 2 12.0001 2C17.5229 2 22.0001 6.47715 22.0001 12C22.0001 17.5228 17.5229 22 12.0001 22C8.29866 22 5.06694 19.989 3.33789 17M12 16L16 12M16 12L12 8M16 12H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></path>
                      </svg>
      </a>

    </div>
  </div>
</div>

<?php endwhile; ?>

            </div>
          </div>
        </div>

                </div>
              </div>            
            </div>
          </div>
          </div>
          <!-- <div class="col-span-12 lg:col-span-4 xl:col-span-3">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-1 lg:gap-6">

              <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex h-8 items-center justify-between">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Tips & Guides
                  </h2>
                  <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9.5 22H14.5M10 10H14M12 10L12 16M15 15.3264C17.3649 14.2029 19 11.7924 19 9C19 5.13401 15.866 2 12 2C8.13401 2 5 5.13401 5 9C5 11.7924 6.63505 14.2029 9 15.3264V16C9 16.9319 9 17.3978 9.15224 17.7654C9.35523 18.2554 9.74458 18.6448 10.2346 18.8478C10.6022 19 11.0681 19 12 19C12.9319 19 13.3978 19 13.7654 18.8478C14.2554 18.6448 14.6448 18.2554 14.8478 17.7654C15 17.3978 15 16.9319 15 16V15.3264Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                 </svg>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-x-5 lg:grid-cols-1">
                  <div class="flex justify-between space-x-2 rounded-lg bg-slate-100 p-2.5 dark:bg-navy-700">
                    <div class="flex flex-1 flex-col justify-between">
                      <div class="line-clamp-2">
                        <a href="#" class="font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">What is Tailwind CSS?</a>
                      </div>
                      <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                          <div class="avatar h-7 w-7">
                            <img class="rounded-full" src="../images/avatar/avatar-20.jpg" alt="avatar">
                          </div>
                          <div>
                            <p class="text-xs font-medium line-clamp-1">
                              John D.
                            </p>
                            <p class="text-tiny+ text-slate-400 line-clamp-1 dark:text-navy-300">
                              2 min read
                            </p>
                          </div>
                        </div>
                        <div class="flex">
                          <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
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
                    <img src="../images/object/object-18.jpg" class="size-20 rounded-lg object-cover object-center" alt="image">
                  </div>

                  <div class="flex justify-between space-x-2 rounded-lg bg-slate-100 p-2.5 dark:bg-navy-700">
                    <div class="flex flex-1 flex-col justify-between">
                      <div class="line-clamp-2">
                        <a href="#" class="font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">Tailwind CSS Card Example</a>
                      </div>
                      <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                          <div class="avatar h-7 w-7">
                            <img class="rounded-full" src="../images/avatar/avatar-19.jpg" alt="avatar">
                          </div>
                          <div>
                            <p class="text-xs font-medium line-clamp-1">
                              Travis F.
                            </p>
                            <p class="text-tiny+ text-slate-400 line-clamp-1 dark:text-navy-300">
                              5 min read
                            </p>
                          </div>
                        </div>
                        <div class="flex">
                          <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
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
                    <img src="../images/object/object-2.jpg" class="size-20 rounded-lg object-cover object-center" alt="image">
                  </div>

                  <div class="flex justify-between space-x-2 rounded-lg bg-slate-100 p-2.5 dark:bg-navy-700">
                    <div class="flex flex-1 flex-col justify-between">
                      <div class="line-clamp-2">
                        <a href="#" class="font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">10 Tips for Making a Good Camera Even Better</a>
                      </div>
                      <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                          <div class="avatar h-7 w-7">
                            <img class="rounded-full" src="../images/avatar/avatar-18.jpg" alt="avatar">
                          </div>
                          <div>
                            <p class="text-xs font-medium line-clamp-1">
                              Alfredo E .
                            </p>
                            <p class="text-tiny+ text-slate-400 line-clamp-1 dark:text-navy-300">
                              4 min read
                            </p>
                          </div>
                        </div>
                        <div class="flex">
                          <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
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
                    <img src="../images/object/object-1.jpg" class="size-20 rounded-lg object-cover object-center" alt="image">
                  </div>
                </div>
              </div>            
            </div>
          </div> -->
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
