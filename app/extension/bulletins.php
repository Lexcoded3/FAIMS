<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header('Location: /login.php'); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'bulletins.php';

$search    = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$mine_only = isset($_GET['mine']) && $_GET['mine'] === '1';
$page      = max(1,(int)($_GET['page'] ?? 1));
$per_page  = 20;
$offset    = ($page-1)*$per_page;

$where = "WHERE u.role='extension'";
if ($mine_only) $where .= " AND p.user_id=$extension_id";
if ($search !== '') $where .= " AND (p.title LIKE '%$search%' OR p.content LIKE '%$search%')";

$total       = $conn->query("SELECT COUNT(*) AS c FROM posts p JOIN users u ON u.id=p.user_id $where")->fetch_assoc()['c'];
$total_pages = max(1,(int)ceil($total/$per_page));

$bulletins = [];
$res = $conn->query("SELECT p.id, p.user_id,p.title,p.content,p.created_at,u.name AS author,p.user_id FROM posts p JOIN users u ON u.id=p.user_id $where ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");
while ($r = $res->fetch_assoc()) $bulletins[] = $r;

function btype(string $title, string $content=''): array {
    $t = strtolower($title.' '.$content);
    if (preg_match('/disease|pest|blight|worm|virus|fungus|rust|outbreak/',$t)) return ['Disease alert','background:#FCEBEB;color:#A32D2D'];
    if (preg_match('/best practice|technique|method|how to|guide|tip/',$t))     return ['Best practice','background:#EAF3DE;color:#3B6D11'];
    if (preg_match('/season|planting|harvest|weather|rain|dry/',$t))            return ['Seasonal','background:#E6F1FB;color:#185FA5'];
    if (preg_match('/price|market|sell|buy|rate/',$t))                          return ['Market info','background:#FAEEDA;color:#854F0B'];
    return ['General info','background:#F1EFE8;color:#5F5E5A'];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Bulletins</title>
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
        <?php include 'bulletinsider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-6 flex flex-col items-center justify-between space-y-2 text-center sm:flex-row sm:space-y-0 sm:text-left">
          <div>
            <h3 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
              Bulletins
            </h3>
            <p class="mt-1 hidden sm:block">Recent bulletins.</p>
          </div>
          <div>
            <form method="GET">
            <div class="mt-2 flex space-x-2">
              <div class="flex items-center justify-between space-x-3 sm:space-x-5">
              <div class="flex w-full max-w-lg">
                <label class="relative flex w-full">
                  <input class="form-input peer h-9 w-full rounded-l-lg bg-white px-3 py-2 shadow-soft ring-primary/50 placeholder:text-slate-400 focus:ring dark:bg-navy-700 dark:shadow-none dark:ring-accent/50 dark:placeholder:text-navy-300 lg:pl-9" placeholder="Search Bulletins..." 
                  type="text" name="search" value="<?= htmlspecialchars($_GET['search']??'') ?>">
                  <span class="pointer-events-none absolute hidden h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent lg:flex">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 transition-colors duration-200" fill="currentColor" viewbox="0 0 24 24">
                      <path d="M3.316 13.781l.73-.171-.73.171zm0-5.457l.73.171-.73-.171zm15.473 0l.73-.171-.73.171zm0 5.457l.73.171-.73-.171zm-5.008 5.008l-.171-.73.171.73zm-5.457 0l-.171.73.171-.73zm0-15.473l-.171-.73.171.73zm5.457 0l.171-.73-.171.73zM20.47 21.53a.75.75 0 101.06-1.06l-1.06 1.06zM4.046 13.61a11.198 11.198 0 010-5.115l-1.46-.342a12.698 12.698 0 000 5.8l1.46-.343zm14.013-5.115a11.196 11.196 0 010 5.115l1.46.342a12.698 12.698 0 000-5.8l-1.46.343zm-4.45 9.564a11.196 11.196 0 01-5.114 0l-.342 1.46c1.907.448 3.892.448 5.8 0l-.343-1.46zM8.496 4.046a11.198 11.198 0 015.115 0l.342-1.46a12.698 12.698 0 00-5.8 0l.343 1.46zm0 14.013a5.97 5.97 0 01-4.45-4.45l-1.46.343a7.47 7.47 0 005.568 5.568l.342-1.46zm5.457 1.46a7.47 7.47 0 005.568-5.567l-1.46-.342a5.97 5.97 0 01-4.45 4.45l.342 1.46zM13.61 4.046a5.97 5.97 0 014.45 4.45l1.46-.343a7.47 7.47 0 00-5.568-5.567l-.342 1.46zm-5.457-1.46a7.47 7.47 0 00-5.567 5.567l1.46.342a5.97 5.97 0 014.45-4.45l-.343-1.46zm8.652 15.28l3.665 3.664 1.06-1.06-3.665-3.665-1.06 1.06z"></path>
                    </svg>
                  </span>
                </label>
                <button type="submit" class="btn h-9 rounded-l-none bg-primary px-3 font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90 lg:px-5">
                  <span class="hidden lg:inline-flex">Search</span>
                  <svg class="size-4.5 lg:hidden" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                </button>                
              </div>
              <div class="flex">
                <button class="btn size-8 shrink-0 rounded-full p-0 text-slate-700 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:text-navy-100 dark:hover:bg-navy-300/20 dark:hover:text-accent dark:focus:bg-navy-300/20 dark:focus:text-accent dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M22 6.5h-9.5M6 6.5H2M9 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM22 17.5h-6M9.5 17.5H2M13 20a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"></path>
                  </svg>
                </button>
                <label class="inline-flex items-center space-x-2">
                <input name="mine" value="1" <?= $mine_only?'checked':'' ?>
                  class="form-switch is-outline h-5 w-10 rounded-full border border-slate-400/70 bg-transparent before:rounded-full before:bg-slate-300 checked:border-secondary checked:before:bg-secondary dark:border-navy-400 dark:before:bg-navy-300 dark:checked:border-secondary-light dark:checked:before:bg-secondary-light"
                  type="checkbox" x-tooltip.secondary="'Only Mine'"
                />
              </label>
              </div>
            </div>
            </div>
          </form>
          </div>
        </div>
        <div class="mt-4">
          <h3 class="text-base font-medium text-slate-600 dark:text-navy-100">
            Today
          </h3>
          <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 lg:gap-6">
            <?php if(empty($bulletins)): ?>
        <div class="text-center py-16">
            <p class="text-xs text-gray-400 mb-3">No bulletins posted yet</p>
            <a href="post_bulletin.php" class="btn-primary">Post the first bulletin</a>
        </div>
        <?php else: ?>
            <?php foreach($bulletins as $b):
                [$type,$style] = btype($b['title'],$b['content']);
                $is_mine = (int)$b['user_id']===$extension_id;
                $preview = mb_strimwidth(strip_tags($b['content']),0,180,'…');
            ?>
            <div class="card">
            <div class="flex items-center justify-between p-4">
              <div class="flex items-center space-x-3">
                <div x-data="usePopper({
                     offset: 12,
                     placement: 'bottom',
                     modifiers: [
                        {name: 'preventOverflow', options: {padding: 10}}
                     ]                     
                  })" class="flex" @mouseleave="isShowPopper = false" @mouseenter="isShowPopper = true">
                  <div class="avatar size-8 hover:z-10">
                      <div class="is-initial rounded-full bg-info text-xs+ uppercase text-white ring-1 ring-primary dark:ring-accent">
                        <?= strtoupper(substr($b['author'],0,1)) ?>
                      </div>
                    </div>                  
                  <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                    <div class="popper-box">
                      <div class="flex w-48 flex-col items-center rounded-md border border-slate-150 bg-white p-3 text-center dark:border-navy-600 dark:bg-navy-700">
                        <div class="avatar size-16">
                          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs text-white flex-shrink-0" style="background:<?= $color ?>;font-weight:500"><?= strtoupper(substr($b['author'],0,1)) ?></div>
                        </div>
                        <p class="mt-2 font-medium tracking-wide text-slate-700 dark:text-navy-100">
                          <?= htmlspecialchars($b['author']) ?>
                        </p>
                        <a href="mailto:samalexalexis3@gmail.com" class="font-inter text-xs tracking-wide hover:text-primary focus:text-primary dark:hover:text-accent-light dark:focus:text-accent-light">@samalex
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
                    <?= htmlspecialchars($b['author']) ?>
                  </p>
                  <p class="text-xs text-slate-400 dark:text-navy-300">
                    <?= date('d M Y',strtotime($b['created_at'])) ?>
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
                            <button @click="$dispatch('open-view-modal', { title: '<?= addslashes($b['title']) ?>', content: '<?= addslashes($b['content']) ?>', author: '<?= addslashes($b['author']) ?>', date: '<?= date('d M Y',strtotime($b['created_at'])) ?>' })" 
                                    class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100">
                                View Details
                            </button>
                        </li>

                        <?php if ($is_mine): ?>
                        <li>
                            <button @click="$dispatch('open-edit-modal', { id: <?= $b['id'] ?>, title: '<?= addslashes($b['title']) ?>', content: '<?= addslashes($b['content']) ?>' })"
                                    class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100">
                                Edit Bulletin
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <!-- <img class="h-48 w-full object-cover object-center" src="images/object/object-2.jpg" alt="image"> -->
            <div class="grow px-4 pt-4">
              <a href="#" class="text-base font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light"><?= htmlspecialchars($b['title']) ?></a>
              <p class="mt-2 line-clamp-3">
                <?= htmlspecialchars($preview) ?>
              </p>
              <div class="inline-space mt-3 flex flex-wrap">
                <a href="#" class="tag rounded-full bg-success/10 text-success hover:bg-success/20 focus:bg-success/20 active:bg-success/25">
                  <?= $type ?>
                </a>
              </div>
            </div>
            <div class="flex-right justify-between px-4 py-4">
              <div class="-mr-1.5 flex">
                <button x-tooltip.secondary="'Like'" class="btn h-7 w-7 rounded-full p-0 text-secondary hover:bg-secondary/20 focus:bg-secondary/20 active:bg-secondary/25 dark:text-secondary-light dark:hover:bg-secondary-light/20 dark:focus:bg-secondary-light/20 dark:active:bg-secondary-light/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                  </svg>
                </button>
                <button x-tooltip="'Save'" class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                  </svg>
                </button>
              </div>
            </div>
            <div class="border-t border-slate-200 px-2 py-1.5 dark:border-navy-500">
              <div class="relative flex w-full">
                <input class="form-input peer h-8 w-full bg-transparent px-8 py-2 text-xs+ placeholder:text-slate-400/70" placeholder="Write the commnet..." type="text">
                <div class="pointer-events-none absolute flex h-full w-8 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                  <i class="fa fa-paper-plane"></i>
                </div>
                <div class="absolute right-0 z-10 flex size-8 items-center justify-center">
                  <button class="btn h-7 w-7 rounded-full p-0 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:hover:text-accent-light dark:focus:bg-navy-300/20 dark:focus:text-accent-light dark:active:bg-navy-300/25">
                    <i class="fa-solid fa-microphone"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          </div>
          <?php if($total_pages>1): ?>
        <div class="flex items-center justify-between mt-5">
            <p class="text-xs text-gray-400">Showing <?= $offset+1 ?>–<?= min($offset+$per_page,$total) ?> of <?= $total ?></p>
            <div class="flex items-center gap-1">
                <?php for($p=1;$p<=$total_pages;$p++): ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?><?= $mine_only?'&mine=1':'' ?>"
                   class="w-7 h-7 flex items-center justify-center rounded-lg text-xs transition-colors <?= $p===$page?'text-white':'text-gray-500 hover:bg-gray-100' ?>"
                   style="<?= $p===$page?'background:#1D9E75':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; endif; ?>
        </div>
      </main>
    </div>

    <div x-data="{show: false, post: {}}" 
     @open-view-modal.window="show = true; post = $event.detail" 
     class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5" 
     x-show="show" x-cloak>
    <div class="absolute inset-0 bg-slate-900/50 transition-opacity duration-300" @click="show = false" x-show="show" x-transition:enter="ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>
    <div class="relative w-full max-w-lg origin-top rounded-lg bg-white p-5 transition-all duration-300 dark:bg-navy-700" x-show="show">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-medium text-slate-700 dark:text-navy-100" x-text="post.title"></h3>
            <button @click="show = false" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20"><i class="fa fa-times"></i></button>
        </div>
        <p class="text-xs text-slate-400 mb-4">By <span x-text="post.author"></span> on <span x-text="post.date"></span></p>
        <div class="text-slate-600 dark:text-navy-200" x-text="post.content"></div>
    </div>
</div>

<div x-data="{show: false, post: {}}" 
     @open-edit-modal.window="show = true; post = $event.detail" 
     class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5" 
     x-show="show" x-cloak>
    <div class="absolute inset-0 bg-slate-900/50 transition-opacity duration-300" @click="show = false" x-show="show"></div>
    <div class="relative w-full max-w-lg origin-top rounded-lg bg-white p-5 transition-all duration-300 dark:bg-navy-700" x-show="show">
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="post_id" :value="post.id">
            <h3 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-4">Edit Bulletin</h3>
            
            <label class="block">
                <span>Title</span>
                <input name="title" :value="post.title" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2" type="text">
            </label>
            
            <label class="block mt-4">
                <span>Content</span>
                <textarea name="content" rows="4" :value="post.content" class="form-textarea mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2"></textarea>
            </label>

            <div class="mt-4 flex justify-end space-x-2">
                <button type="button" @click="show = false" class="btn bg-slate-150 text-slate-800 hover:bg-slate-200">Cancel</button>
                <button type="submit" class="btn bg-primary text-white hover:bg-primary-focus">Update Changes</button>
            </div>
        </form>
    </div>
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
