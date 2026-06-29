<?php
session_start();
require 'includes/auth.php';
require __DIR__ .'../../config/db.php';

// Get topic ID from URL
$topic_id = intval($_GET['id']);

// Fetch topic + author
$stmt = $conn->prepare("
    SELECT forum_topics.*, users.name AS author_name
    FROM forum_topics
    JOIN users ON forum_topics.user_id = users.id
    WHERE forum_topics.id = ? AND forum_topics.status = 'active'
");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$result = $stmt->get_result();

$topic = $result->fetch_assoc();

if (!$topic) {
    echo "<p>Topic not found or hidden.</p>";
    exit;
}

// Increase view count
$updateViews = $conn->prepare("UPDATE forum_topics SET views = views + 1 WHERE id = ?");
$updateViews->bind_param("i", $topic_id);
$updateViews->execute();

// Refresh topic data to get updated views
$topic['views']++;

// Fetch replies
$stmt = $conn->prepare("
    SELECT forum_replies.*, users.name AS replier_name
    FROM forum_replies
    JOIN users ON forum_replies.user_id = users.id
    WHERE topic_id=?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $topic_id);
$stmt->execute();
$replies_result = $stmt->get_result();

$replies = [];
while($row = $replies_result->fetch_assoc()){
    $replies[] = $row;
}

// Get total likes
$stmt = $conn->prepare("SELECT COUNT(*) AS total_likes FROM forum_topic_likes WHERE topic_id=?");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$likes_result = $stmt->get_result();
$likes_data = $likes_result->fetch_assoc();
$total_likes = $likes_data['total_likes'];

// Check if current user liked
$userLiked = false;

$stmt = $conn->prepare("SELECT id FROM forum_topic_likes WHERE topic_id=? AND user_id=?");
$stmt->bind_param("ii", $topic_id, $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0){
    $userLiked = true;
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

    <title>FAIMS - Topic- <?= htmlspecialchars($topic['title']) ?></title>
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
                        <img class="mask is-squircle" src="../images/avatar/avatar-19.jpg" alt="avatar">
                      </div>
                      <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                        <div class="popper-box">
                          <div class="flex w-48 flex-col items-center rounded-md border border-slate-150 bg-white p-3 text-center dark:border-navy-600 dark:bg-navy-700">
                            <div class="avatar size-16">
                              <img class="rounded-full" src="../images/avatar/avatar-19.jpg" alt="avatar">
                            </div>
                            <p class="mt-2 font-medium tracking-wide text-slate-700 dark:text-navy-100">
                              <?= htmlspecialchars($topic['author_name']) ?> 
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
                      <a href="#" class="font-medium text-slate-700 line-clamp-1 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">
                        <?= htmlspecialchars($topic['author_name']) ?> 
                      </a>
                      <div class="mt-1.5 flex items-center text-xs">
                        <span class="line-clamp-1"><?= $topic['created_at'] ?></span>
                        <div class="mx-2 my-0.5 w-px self-stretch bg-white/20"></div>
                        <p class="shrink-0">8 min read</p>
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
                  <?= htmlspecialchars($topic['title']) ?>
                </h1>
                <h3 class="mt-1">
                  <?= nl2br(htmlspecialchars($topic['content'])) ?>
                </h3>
                                <?php
                    $topicId = intval($_GET['id']);
                    $images = $conn->query("SELECT image_path FROM forum_topic_images WHERE topic_id=$topicId");

                    while($img = $images->fetch_assoc()){
                        echo '<img src="'.$img['image_path'].'" alt="Topic Image" class="mt-5 h-80 w-full rounded-lg object-cover object-center">';
                    }
                    ?>
                <!-- <img class="mt-5 h-80 w-full rounded-lg object-cover object-center" src="../images/object/object-2.jpg" alt="image"> -->
                <p class="mt-1 text-center text-xs+ text-slate-400 dark:text-navy-300">
                  <span> Photo by </span>
                  <a href="#" class="underline">Unsplash</a>
                </p>
                <br>
                <p>
                  <?= nl2br(htmlspecialchars($topic['content'])) ?>
                </p>            
              </div>
              <div class="mt-2">
                <a href="#" class="text-xs+ text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent">#Sample  </a>
                <a href="#" class="text-xs+ text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent">#Soil  </a>
                <a href="#" class="text-xs+ text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent">#Next  </a>
              </div>
              <div class="mt-3">
              <div class="flex items-center justify-between">
                <div class="-ml-1.5 flex">
                  <div x-data="likeSystem(<?= $topic_id ?>, <?= $userLiked ? 'true' : 'false' ?>, <?= $total_likes ?>)"
              class="flex items-center">
                            <button x-tooltip.secondary.x-data="'<?= $total_likes ?> Likes'" @click="toggleLike"
                  :title="totalLikes + ' Likes'" class="btn size-9 rounded-full p-0 hover:bg-slate-300/20 hover:text-secondary focus:bg-slate-300/20 focus:text-secondary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:hover:text-secondary-light dark:focus:bg-navy-300/20 dark:focus:text-secondary-light dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" :class="liked ? 'text-secondary fill-secondary scale-110' : 'text-gray-400'"
             :fill="liked ? 'currentColor' : 'none'">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                  </button>
                  </div>
                  <button x-tooltip.success="'<?= count($replies) ?> Comments'" class="btn size-9 rounded-full p-0 hover:bg-slate-300/20 hover:text-success focus:bg-slate-300/20 focus:text-success active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                  </button>
                  <button x-tooltip.info="'Share'" class="btn size-9 rounded-full p-0 hover:bg-slate-300/20 hover:text-info focus:bg-slate-300/20 focus:text-info active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                  </button>
                </div>
                <div>
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                  <span><?= $topic['views'] ?></span>
                </div>
              </div>
            </div>
            <?php if($role == 'farmer' || $role == 'buyer' || $role == 'admin' || $role == 'extension'): ?>
                <form action="actions/reply.php" method="POST">
            <div class="mt-3 flex items-center justify-between space-x-3">
              <div class="avatar size-8">
                <img class="rounded-full" src="../images/avatar/avatar-20.jpg" alt="avatar">
              </div>
              <div class="relative flex w-full">
                <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                <input type="text" name="content" class="form-input peer h-8 w-full rounded-full border border-slate-300 bg-transparent px-8 py-2 text-xs+ placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Write your comment..." required>
                <div class="pointer-events-none absolute flex h-full w-8 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                  <i class="fa fa-ellipsis-v"></i>
                </div>
                <div class="absolute right-0 z-10 flex size-8 items-center justify-center">
                  <button type="submit" class="btn size-6 rounded-full p-0 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:hover:text-accent-light dark:focus:bg-navy-300/20 dark:focus:text-accent-light dark:active:bg-navy-300/25">
                    <i class="fa-solid fa-paper-plane"></i>
                  </button>
                </div>
              </div>
            </div>
        </form>
            <?php endif; ?>

              <!-- Footer Blog Post -->
            </div>

          </div>
          <div class="col-span-12 py-6 lg:sticky lg:bottom-0 lg:col-span-4">
            <div class="flex items-center space-x-2">
                  <div class="flex size-8 items-center justify-center rounded-lg bg-warning/10 text-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                  </div>
                  <h3 class="text-base text-slate-700 dark:text-navy-100">
                    Comments on this post
                  </h3>
                </div>
             <div class="mt-5">
              <!-- <p class="border-b border-slate-200 pb-2 text-base text-slate-800 dark:border-navy-600 dark:text-navy-100">
                Comments 
              </p> -->
              <div class="mt-3 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-1 is-scrollbar-hidden relative space-y-2.5 overflow-y-auto p-0.5" x-init="Sortable.create($el,{
                        animation: 200,
                        group:'board-cards',
                        easing: 'cubic-bezier(0, 0, 0.2, 1)',
                        direction: 'vertical',
                        delay: 150,
                        delayOnTouchOnly: true,
                      })">
                <?php if(count($replies) > 0): ?>

                <?php
                $currentLabel = '';

                foreach($replies as $reply):

                    $replyDate = new DateTime($reply['created_at']);
                    $today = new DateTime();
                    $yesterday = new DateTime('yesterday');

                    if($replyDate->format('Y-m-d') == $today->format('Y-m-d')){
                        $label = "Today";
                    }
                    elseif($replyDate->format('Y-m-d') == $yesterday->format('Y-m-d')){
                        $label = "Yesterday";
                    }
                    else{
                        $label = $replyDate->format('l jS Y');
                    }

                    // Print separator only when date changes
                    if($label !== $currentLabel){
                      echo  "<div class='mx-4 flex items-center space-x-3'>
                              <div class='h-px flex-1 bg-slate-200 dark:bg-navy-500'></div>
                              <p>$label</p>
                              <div class='h-px flex-1 bg-slate-200 dark:bg-navy-500'></div>
                            </div>";

                        $currentLabel = $label;
                    }
                ?>

                <div class="flex items-end space-x-2.5 sm:space-x-5 ">
              <!-- <div class="flex flex-col items-start space-y-3.5"> -->
                <div class="mr-4 max-w-lg sm:mr-10">
                  <div class="rounded-2xl rounded-tl-none bg-white p-3 text-slate-700 shadow-sm dark:bg-navy-700 dark:text-navy-100">
                    <?= nl2br(htmlspecialchars($reply['content'])) ?>
                    <?php if($role == 'admin'): ?>
                    <a href="actions/delete_reply.php?id=<?= $reply['id'] ?>&topic_id=<?= $topic['id'] ?>" class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </a>
                        <?php endif; ?>
                    <p class="mt-1 text-center text-xs+ text-slate-400 dark:text-navy-300">
                  <span> <?= htmlspecialchars($reply['replier_name']) ?> |
                <?= $replyDate->format('H:i') ?></span>
                </p>
                    
                  </div>
                </div>
              <!-- </div> -->
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
                        <div class="px-4 py-3 sm:px-5">No replies yet. Be the first to reply!</div>
                      </div>
                    <!-- <p>No replies yet. Be the first to reply!</p> -->
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-12 lg:gap-6">
          <div class="col-span-12 py-6 lg:sticky lg:bottom-0 lg:col-span-8 lg:self-end">
            <div class="card">
              <div class="h-24 rounded-t-lg bg-primary dark:bg-accent">
                <img class="h-full w-full rounded-t-lg object-cover object-center" src="../images/object/object-7.jpg" alt="image">
              </div>
              <div class="px-4 pt-2 pb-5 sm:px-5">
                <div class="avatar -mt-12 size-20">
                  <img class="rounded-full border-2 border-white dark:border-navy-700" src="../images/avatar/avatar-19.jpg" alt="avatar">
                </div>
                <h3 class="pt-2 text-lg font-medium text-slate-700 dark:text-navy-100">
                  <?= htmlspecialchars($topic['author_name']) ?> 
                </h3>
                <p class="text-xs+ text-slate-400 dark:text-navy-300">
                  189 followers
                </p>
                <p class="mt-3">
                  Professional Full-Time Agronomist
                  kampala City, UG.
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
          
          </div>
        </div>
      </main>
    </div>
    <script>
function likeSystem(topicId, initialLiked, initialTotal) {
    return {
        liked: initialLiked,
        totalLikes: initialTotal,

        toggleLike() {
            fetch('actions/toggle_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'topic_id=' + topicId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.liked = data.liked;
                    this.totalLikes = data.total_likes;
                }
            });
        }
    }
}
</script>


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
