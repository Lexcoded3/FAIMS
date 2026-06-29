<?php
session_start();
require 'includes/auth.php';
require __DIR__ .'../../config/db.php';

// Make sure category ID exists
if(!isset($_GET['id'])){
    echo "<p>Category not specified.</p>";
    exit;
}

$category_id = intval($_GET['id']);

// Fetch category info
$stmt = $conn->prepare("SELECT * FROM forum_categories WHERE id=?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$cat_result = $stmt->get_result();
$category = $cat_result->fetch_assoc();

if(!$category){
    echo "<p>Category not found.</p>";
    exit;
}

// Fetch topics in this category
$stmt = $conn->prepare("
    SELECT forum_topics.*, users.name AS author_name
    FROM forum_topics
    JOIN users ON forum_topics.user_id = users.id
    WHERE category_id=? AND forum_topics.status='active'
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$topics_result = $stmt->get_result();

$topics = [];
while($row = $topics_result->fetch_assoc()){
    $row['images'] = []; // prepare image container
    $topics[$row['id']] = $row;
}
if(!empty($topics)){
    $topic_ids = implode(',', array_keys($topics));

    $img_query = $conn->query("
        SELECT topic_id, image_path
        FROM forum_topic_images
        WHERE topic_id IN ($topic_ids)
    ");

    while($img = $img_query->fetch_assoc()){
        $topics[$img['topic_id']]['images'][] = $img['image_path'];
    }
}
function time_ago($datetime_str, $full = false) {
    $now = new DateTime();
    $ago = new DateTime($datetime_str);
    
    $diff = $now->diff($ago);
    
    $units = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    foreach ($units as $key => $unit) {
        if ($diff->$key >= 1) {
            $count = $diff->$key;
            return $count . ' ' . $unit . ($count > 1 ? 's' : '') . ' ago';
        }
    }
    
    return 'just now';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./style.css">
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
              <a href="index.htm.html">
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
      <main class="main-content w-full px-[var(--margin-x)]">
        <div class="grid grid-cols-12 lg:gap-6">
          <div class="col-span-12 pt-6 lg:col-span-8 lg:pb-6">
            

            <div class="mt-5">
              <div class="flex items-center justify-between">
                <p class="text-lg font-medium text-slate-800 dark:text-navy-100">
                  <?= htmlspecialchars($category['name']) ?> Posts
                </p>
                <?php if($role == 'farmer' || $role == 'buyer' || $role == 'admin'): ?>
                <a href="addpost.php" class="flex items-center justify-center space-x-2 font-medium text-slate-600 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  <span>New Post</span>
                </a>
                <?php endif; ?>
              </div>
              <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-1 lg:gap-6">
                <?php if(count($topics) > 0): ?>
                        <?php foreach($topics as $topic): ?>
                                    <div class="card lg:flex-row">
                                      <?php
                    $imageSrc = '../images/object/object-2.jpg'; // default fallback

                    if(!empty($topic['images'])){
                        $imageSrc = '../forum/'.$topic['images'][0]; 
                    }
                    ?>

                    <img class="h-48 w-full shrink-0 rounded-t-lg bg-cover bg-center object-cover object-center lg:h-auto lg:w-48 lg:rounded-t-none lg:rounded-l-lg" 
                         src="<?= htmlspecialchars($imageSrc) ?>" 
                         alt="image">

                  <div class="flex w-full grow flex-col px-4 py-3 sm:px-5">
                    <div class="flex items-center justify-between">
                      <a class="text-xs+ text-info" href="#"><?= htmlspecialchars($topic['title']) ?> </a>
                      <div class="-mr-1.5 flex space-x-1">
                        <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                          </svg>
                        </button>
                        <?php if($role == 'admin'): ?>
                        <button class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 hover:text-error focus:bg-slate-300/20 focus:text-error active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                          </svg>
                        </button>
                        <?php endif; ?>

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
                      <a href="#" class="text-lg font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light"><?= htmlspecialchars($topic['title']) ?></a>
                    </div>
                    <p class="mt-1 line-clamp-3">
                      <?= htmlspecialchars(substr($topic['content'], 0, 65)) ?>...
                    </p>
                    <div class="grow">
                      <div class="mt-2 flex items-center text-xs">
                        <a href="#" class="flex items-center space-x-2 hover:text-slate-800 dark:hover:text-navy-100">
                          <!-- <div class="avatar size-6">
                            <img class="rounded-full" src="../images/avatar/avatar-10.jpg" alt="avatar">
                          </div> -->
                          <span class="line-clamp-1"><?= htmlspecialchars($topic['author_name']) ?></span>
                        </a>
                        <div class="mx-3 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                        <span class="shrink-0 text-slate-400 dark:text-navy-300"><?= date('M d - H:i', strtotime($topic['created_at'])) ?>
                        </span>
                        <div class="mx-3 my-1 w-px self-stretch bg-slate-200 dark:bg-navy-500"></div>
                        <span class="shrink-0 text-slate-400 dark:text-navy-300"> 
                          <?= time_ago($topic['created_at']) ?>
                        </span>
                      </div>
                    </div>
                    <div class="mt-1 flex justify-end">
                      <a href="topic.php?id=<?= $topic['id'] ?>" class="btn px-2.5 py-1.5 font-medium text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25">
                        READ POST
                      </a>
                    </div>
                  </div>
                </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                      <div class="alert flex overflow-hidden rounded-lg border border-info text-info">
                        <div class="bg-info p-3 text-white">
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
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                          </svg>
                        </div>
                        <div class="px-4 py-3 sm:px-5">No topics yet. Be the first to create one!</div>
                      </div>

                    <?php endif; ?>
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
