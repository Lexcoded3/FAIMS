<?php
session_start();
require 'includes/auth.php';
require __DIR__ .'../../config/db.php';


$sql = "SELECT * FROM forum_categories ORDER BY created_at DESC";
$result = $conn->query($sql);

$categories = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Category colors for fallback avatars
$categoryColors = [
    1 => ['bg' => '#1D9E75', 'icon' => 'fa-leaf'],           // Crop - Green
    2 => ['bg' => '#DC2626', 'icon' => 'fa-cow'],            // Livestock - Red
    3 => ['bg' => '#F59E0B', 'icon' => 'fa-chart-line'],     // Market - Orange
    4 => ['bg' => '#3B82F6', 'icon' => 'fa-briefcase'],      // Agri-Biz - Blue
    5 => ['bg' => '#8B5CF6', 'icon' => 'fa-tools'],          // Tech - Purple
];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Forum</title>
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
              <a href="index.htm.html">
                <img class="size-11 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
              </a>
            </div>

            <!-- Main Sections Links -->
            <?php include 'sidenav.php';?>
            

            
          </div>
        </div>

        <!-- Sidebar Panel -->
        <?php include 'forumsider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
       <?php if($_SESSION['role']=='admin'){ ?>
        <?php include '../admin/toprightsidenav.php';?>
         <?php }elseif ($_SESSION['role']=='farmer') { ?>
         <?php include '../farmer/toprightsidenav.php';?> 
           <?php }elseif ($_SESSION['role']=='buyer') { ?>
           <?php include '../buyer/toprightsidenav.php';?> 
             <?php }elseif ($_SESSION['role']=='extension') { ?>
             <?php include '../extension/toprightsidenav.php';?> 
                        <?php }?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full place-items-center px-[var(--margin-x)] pb-6">
        <div class="py-5 text-center lg:py-6">
          <p class="text-sm uppercase">Are you new here?</p>
          <h3 class="mt-1 text-xl font-semibold text-slate-600 dark:text-navy-100">
            Welcome. Where do you like to Start?
          </h3>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:gap-6">
          <?php foreach($categories as $cat): ?>
            <?php 
              // Get image path and check if file exists
              $imagePath = $cat['image_path'] ?? '';
              $hasImage = !empty($imagePath) && file_exists(__DIR__ . '/..' . $imagePath);
              
              // Get color and icon for this category
              $color = $categoryColors[$cat['id']] ?? ['bg' => '#64748B', 'icon' => 'fa-folder'];
            ?>
          <div class="card lg:flex-row">
            <!-- Image or Fallback -->
            <div class="relative h-48 w-full shrink-0 overflow-hidden rounded-t-lg bg-cover bg-center object-cover object-center lg:h-auto lg:w-48 lg:rounded-t-none lg:rounded-l-lg">
              <?php if($hasImage): ?>
                <!-- Show category image from database -->
                <img class="h-full w-full object-cover" 
                     src="<?= htmlspecialchars('../' . ltrim($imagePath, '/')) ?>" 
                     alt="<?= htmlspecialchars($cat['name']) ?>"
                     onerror="this.style.display='none'; this.nextElementSibling && (this.nextElementSibling.style.display='flex');">
              <?php endif; ?>
              
              <!-- Fallback: Colored avatar with icon -->
              <div class="h-full w-full flex items-center justify-center transition-all" 
                   style="background: <?= $color['bg'] ?>; <?= $hasImage ? 'display: none;' : '' ?>">
                <div class="text-center">
                  <i class="fas <?= $color['icon'] ?> text-5xl text-white mb-3"></i>
                  <p class="text-white font-semibold text-sm"><?= htmlspecialchars(mb_strimwidth($cat['name'], 0, 20, "...")) ?></p>
                </div>
              </div>
            </div>

            <div class="flex w-full grow flex-col px-4 py-3 sm:px-5">
              <div class="flex items-center justify-between">
                <a class="text-xs+ text-info" href="#">Category</a>
                <div class="-mr-1.5 flex space-x-1">
                  <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"></path>
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
                <a href="#" class="text-lg font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light"><?= htmlspecialchars(mb_strimwidth($cat['name'], 0, 30, "...")) ?></a>
              </div>
              <p class="mt-1 line-clamp-3">
                <?= htmlspecialchars($cat['description']) ?>
              </p>
              <div class="grow">
                <div class="mt-2 flex items-center text-xs">
                  <a href="#" class="flex items-center space-x-2 hover:text-slate-800 dark:hover:text-navy-100">
                    <!-- <div class="avatar size-6">
                      <img class="rounded-full" src="../images/avatar/avatar-10.jpg" alt="avatar">
                    </div> -->
                    <span class="line-clamp-1">Admin</span>
                  </a>
                  <div class="mx-3 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                  <span class="shrink-0 text-slate-400 dark:text-navy-300">June 23, 2021
                  </span>
                </div>
              </div>
              <div class="mt-1 flex justify-end">
                <a href="category.php?id=<?= $cat['id'] ?>"  class="btn px-2.5 py-1.5 font-medium text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25">
                  Explore Forum
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
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