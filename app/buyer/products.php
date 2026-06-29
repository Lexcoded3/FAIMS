<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'buyer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$buyer_id = $_SESSION['id'] ?? 0;  // ← make sure this is set during login
// ─── Filters & Search ────────────────────────────────────────
$search     = $_GET['search'] ?? '';
$category   = (int)($_GET['category'] ?? 0);
$min_price  = (float)($_GET['min_price'] ?? 0);
$max_price  = (float)($_GET['max_price'] ?? 0);
$district   = $_GET['district'] ?? '';
$sort       = $_GET['sort'] ?? 'newest';   // newest, price_asc, price_desc, quantity_desc

// Build WHERE clause
$where = ["p.status = 'active'"];   // ← fixed: specify p.status
$params = [];
$types  = "";

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}
if ($category > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $category;
    $types .= "i";
}
if ($min_price > 0) {
    $where[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}
if ($max_price > 0) {
    $where[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}
if ($district !== '') {
    $where[] = "u.location LIKE ?";
    $params[] = "%$district%";
    $types .= "s";
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
// Sorting
$order_by = match($sort) {
    'price_asc'    => "p.price ASC",
    'price_desc'   => "p.price DESC",
    'quantity_desc'=> "p.quantity DESC",
    default        => "p.created_at DESC"   // newest
};

// Pagination (simple)
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 12;
$offset     = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(*) as total 
              FROM products p 
              LEFT JOIN users u ON p.farmer_id = u.id 
              $where_sql";

$stmt_count = $conn->prepare($count_sql);
if ($types) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT p.*, c.name as category_name, u.name as farmer_name, u.location as farmer_location
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.farmer_id = u.id
        $where_sql
        ORDER BY $order_by
        LIMIT ? OFFSET ?";

$params_full = array_merge($params, [$per_page, $offset]);
$types_full  = $types . "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types_full, ...$params_full);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Load categories for filter dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Products</title>
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

  <body x-data="" x-bind="$store.global.documentBody" class="is-header-blur is-sidebar-open">
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
        <?php include 'productsider.php';?>
      </div>

     <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content pos-app w-full px-[var(--margin-x)] pb-6 transition-all duration-[.25s]">
        <div class="mt-5 grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
  <div class="col-span-12 lg:col-span-12">
    <form method="GET">
      <div class="flex items-center gap-3 sm:gap-4 flex-nowrap overflow-x-auto pb-1">
        <!-- LEFT SIDE: Filters group – tight & compact -->
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
          <!-- Filter button -->
          <button type="button" class="btn size-9 shrink-0 rounded-full p-0 text-slate-700 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:text-navy-100 dark:hover:bg-navy-300/20 dark:hover:text-accent dark:focus:bg-navy-300/20 dark:focus:text-accent dark:active:bg-navy-300/25">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M22 6.5h-9.5M6 6.5H2M9 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM22 17.5h-6M9.5 17.5H2M13 20a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"></path>
            </svg>
          </button>

          <!-- Category -->
          <select name="category" class="h-9 min-w-[130px] rounded-lg border-gray-300 bg-white px-3 text-sm shadow-soft focus:ring-primary dark:bg-navy-700 dark:border-navy-600">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <!-- Min Price -->
          <input name="min_price" value="<?= $min_price ?: '' ?>" type="number" placeholder="Min UGX" class="h-9 w-28 rounded-lg border-gray-300 bg-white px-3 text-sm shadow-soft focus:ring-primary dark:bg-navy-700" min="0">

          <!-- Max Price -->
          <input name="max_price" value="<?= $max_price ?: '' ?>" type="number" placeholder="Max UGX" class="h-9 w-28 rounded-lg border-gray-300 bg-white px-3 text-sm shadow-soft focus:ring-primary dark:bg-navy-700" min="0">

          <!-- Sort -->
          <select name="sort" class="h-9 min-w-[140px] rounded-lg border-gray-300 bg-white px-3 text-sm shadow-soft focus:ring-primary dark:bg-navy-700 dark:border-navy-600">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price ↑</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price ↓</option>
            <option value="quantity_desc" <?= $sort === 'quantity_desc' ? 'selected' : '' ?>>Qty ↓</option>
          </select>
        </div>

        <!-- RIGHT SIDE: Search input – shorter & pushed extreme right -->
        <div class="flex items-center gap-0 ml-auto shrink-0">
          <label class="relative flex">
            <input 
              name="search" 
              value="<?= htmlspecialchars($search ?? '') ?>" 
              class="form-input peer h-9 w-42 sm:w-22 lg:w-50 rounded-l-lg bg-white px-3 py-2 shadow-soft ring-primary/50 placeholder:text-slate-400 focus:ring dark:bg-navy-700 dark:shadow-none dark:ring-accent/50 dark:placeholder:text-navy-300 lg:pl-9" 
              placeholder="Search crops..." 
              type="text"
            >
            <span class="pointer-events-none absolute hidden h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent lg:flex">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5 transition-colors duration-200" fill="currentColor" viewBox="0 0 24 24">
                <path d="M3.316 13.781l.73-.171-.73.171zm0-5.457l.73.171-.73-.171zm15.473 0l.73-.171-.73.171zm0 5.457l.73.171-.73-.171zm-5.008 5.008l-.171-.73.171.73zm-5.457 0l-.171.73.171-.73zm0-15.473l-.171-.73.171.73zm5.457 0l.171-.73-.171.73zM20.47 21.53a.75.75 0 101.06-1.06l-1.06 1.06zM4.046 13.61a11.198 11.198 0 010-5.115l-1.46-.342a12.698 12.698 0 000 5.8l1.46-.343zm14.013-5.115a11.196 11.196 0 010 5.115l1.46.342a12.698 12.698 0 000-5.8l-1.46.343zm-4.45 9.564a11.196 11.196 0 01-5.114 0l-.342 1.46c1.907.448 3.892.448 5.8 0l-.343-1.46zM8.496 4.046a11.198 11.198 0 015.115 0l.342-1.46a12.698 12.698 0 00-5.8 0l.343 1.46zm0 14.013a5.97 5.97 0 01-4.45-4.45l-1.46.343a7.47 7.47 0 005.568 5.568l.342-1.46zm5.457 1.46a7.47 7.47 0 005.568-5.567l-1.46-.342a5.97 5.97 0 01-4.45 4.45l.342 1.46zM13.61 4.046a5.97 5.97 0 014.45 4.45l1.46-.343a7.47 7.47 0 00-5.568-5.567l-.342 1.46zm-5.457-1.46a7.47 7.47 0 00-5.567 5.567l1.46.342a5.97 5.97 0 014.45-4.45l-.343-1.46zm8.652 15.28l3.665 3.664 1.06-1.06-3.665-3.665-1.06 1.06z"></path>
              </svg>
            </span>
          </label>
          <button type="submit" 
                  class="btn h-9 rounded-l-none bg-primary px-3 font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90 lg:px-5">
            <span class="hidden lg:inline-flex">Search</span>
            <svg class="size-4.5 lg:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
        <div class="mt-5 grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
          <div class="col-span-12 sm:col-span-6 lg:col-span-8">
            <div class="swiper" x-init="$nextTick(()=>$el._x_swiper= new Swiper($el,{  slidesPerView: 'auto', spaceBetween: 14,navigation:{nextEl:'.next-btn',prevEl:'.prev-btn'}}))">
              <div class="flex items-center justify-between">
                <p class="text-base font-medium text-slate-700 dark:text-navy-100">
                  Categories
                </p>
                <div class="flex">
                  <button class="btn prev-btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 disabled:pointer-events-none disabled:select-none disabled:opacity-60 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"></path>
                    </svg>
                  </button>
                  <button class="btn next-btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 disabled:pointer-events-none disabled:select-none disabled:opacity-60 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                  </button>
                </div>
              </div>
              <!-- <div class="swiper-wrapper mt-5" x-data="{selected:'slide-1'}">
                <div class="card swiper-slide w-24 shrink-0 cursor-pointer" @click="selected = 'slide-1'">
                  <div class="flex flex-col items-center rounded-lg px-2 py-4" :class="selected === 'slide-1' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                    <img class="w-12" src="../images/foods/food-icon-1.svg" alt="image">
                    <h3 class="pt-2 font-medium tracking-wide line-clamp-1">
                      Burger
                    </h3>
                  </div>
                </div>
                <div class="card swiper-slide w-24 shrink-0 cursor-pointer" @click="selected = 'slide-2'">
                  <div class="flex flex-col items-center rounded-lg px-2 py-4" :class="selected === 'slide-2' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                    <img class="w-12" src="../images/foods/food-icon-4.svg" alt="image">
                    <h3 class="pt-2 font-medium tracking-wide line-clamp-1">
                      Hot Dog
                    </h3>
                  </div>
                </div>
                <div class="card swiper-slide w-24 shrink-0 cursor-pointer" @click="selected = 'slide-3'">
                  <div class="flex flex-col items-center rounded-lg px-2 py-4" :class="selected === 'slide-3' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                    <img class="w-12" src="../images/foods/food-icon-6.svg" alt="image">
                    <h3 class="pt-2 font-medium tracking-wide line-clamp-1">
                      Pizza
                    </h3>
                  </div>
                </div>
                <div class="card swiper-slide w-24 shrink-0 cursor-pointer" @click="selected = 'slide-4'">
                  <div class="flex flex-col items-center rounded-lg px-2 py-4" :class="selected === 'slide-4' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                    <img class="w-12" src="../images/foods/food-icon-5.svg" alt="image">
                    <h3 class="pt-2 font-medium tracking-wide line-clamp-1">
                      Sandwich
                    </h3>
                  </div>
                </div>
                <div class="card swiper-slide w-24 shrink-0 cursor-pointer" @click="selected = 'slide-5'">
                  <div class="flex flex-col items-center rounded-lg px-2 py-4" :class="selected === 'slide-5' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                    <img class="w-12" src="../images/foods/food-icon-10.svg" alt="image">
                    <h3 class="pt-2 font-medium tracking-wide line-clamp-1">
                      Popcorn
                    </h3>
                  </div>
                </div>
                <div class="card swiper-slide w-24 shrink-0 cursor-pointer" @click="selected = 'slide-6'">
                  <div class="flex flex-col items-center rounded-lg px-2 py-4" :class="selected === 'slide-6' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                    <img class="w-12" src="../images/foods/food-icon-13.svg" alt="image">
                    <h3 class="pt-2 font-medium tracking-wide line-clamp-1">
                      Taco
                    </h3>
                  </div>
                </div>
                <div class="card swiper-slide w-24 shrink-0 cursor-pointer" @click="selected = 'slide-7'">
                  <div class="flex flex-col items-center rounded-lg px-2 py-4" :class="selected === 'slide-7' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                    <img class="w-12" src="../images/foods/food-icon-8.svg" alt="image">
                    <h3 class="pt-2 font-medium tracking-wide line-clamp-1">
                      Burrito
                    </h3>
                  </div>
                </div>
                <div class="card swiper-slide w-24 shrink-0 cursor-pointer" @click="selected = 'slide-8'">
                  <div class="flex flex-col items-center rounded-lg px-2 py-4" :class="selected === 'slide-8' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                    <img class="w-12" src="../images/foods/food-icon-12.svg" alt="image">
                    <h3 class="pt-2 font-medium tracking-wide line-clamp-1">
                      Pizza
                    </h3>
                  </div>
                </div>
                <div class="card swiper-slide w-24 shrink-0 cursor-pointer" @click="selected = 'slide-9'">
                  <div class="flex flex-col items-center rounded-lg px-2 py-4" :class="selected === 'slide-9' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                    <img class="w-12" src="../images/foods/food-icon-7.svg" alt="image">
                    <h3 class="pt-2 font-medium tracking-wide line-clamp-1">
                      Burrito
                    </h3>
                  </div>
                </div>
              </div> -->
            </div>


          
            <div class="mt-4 grid grid-cols-2 gap-4 sm:mt-5 sm:grid-cols-2 sm:gap-5 lg:mt-6 lg:grid-cols-3 xl:grid-cols-4">
                <!-- Products Grid -->
              <?php if (empty($products)): ?>
                <div class="col-span-12 sm:col-span-6 lg:col-span-8">
                <div class="text-center py-12 bg-green-700 rounded-xl shadow">
                  <i class="fas fa-box-open text-6xl text-navy-300 mb-4"></i>
                  <p class="text-xl text-gray-600">No products found matching your filters.</p>
                  <p class="text-gray-500 mt-2">Try adjusting your search or filters.</p>
                </div>
              </div>
              <?php else: ?>
              <?php foreach ($products as $product): ?>
              <div class="card p-2">
                <?php if ($product['image']): ?>
                <img class="rounded-lg" src="../farmer/<?= htmlspecialchars($product['image']) ?>" alt="image">
                <?php else: ?>
                <div class="flex items-center justify-center h-full text-gray-400">
                  <i class="fas fa-seedling text-6xl"></i>
                </div>
              <?php endif; ?>
                <div class="pt-2">
                  <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                    <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                  </p>
                  <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                    <?= htmlspecialchars($product['name']) ?>
                  </p>
                  <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                    UGX <?= number_format($product['price'], 0) ?> / <?= htmlspecialchars($product['unit'] ?? 'kg') ?>
                  </p>
                </div>
                <div class="mt-4 flex items-center justify-between">
                  <div class="flex space-x-2">
                    <a href="product_details.php?id=<?= $product['id'] ?>">
                      <button class="btn size-7 rounded-full bg-info/10 p-0 text-info hover:bg-info/20 focus:bg-info/20 active:bg-info/25" x-tooltip.info="'Details'">
                        <i class="fas fa-list"></i>
                      </button>
                      <button class="btn size-7 rounded-full bg-success/10 p-0 text-success hover:bg-success/20 focus:bg-success/20 active:bg-success/25" x-tooltip.success="' Make Deal'">
                        <i class="fas fa-handshake"></i>
                      </button>
                    </a>
                    </div>
                </div>
              </div>
              <?php endforeach; ?>

              <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <div class="mt-10 flex justify-center gap-3">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&<?= http_build_query($_GET) ?>" 
               class="px-4 py-2 rounded-lg <?= $page == $i ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
            </div>
          </div>
          <div class="hidden sm:col-span-6 sm:block lg:col-span-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-1">
                <p>
                  <span class="text-base font-medium text-slate-700 dark:text-navy-100">Favorited Items</span>
                  <!-- <span>#001</span> -->
                </p>

                <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>

                  <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                    <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                      <ul>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #001</a>
                        </li>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #002</a>
                        </li>
                        <li>
                          <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #005</a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex space-x-1">
                <button class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"></path>
                  </svg>
                </button>
                <button class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 hover:text-error focus:bg-slate-300/20 focus:text-error active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
                <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
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
            <div class="card mt-5 p-4 sm:p-5">
              <div class="flex flex-col space-y-3.5">
                <!-- <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-4.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        2
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Roast beef
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Lorem ipsum dolor sit.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$12.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-5.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        1
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Tuna salad
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Amet consectetur adip.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$14.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-6.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        3
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Salmon
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Adipisicing elit. Quos?
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$45.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-7.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        1
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          California roll
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Lorem, ipsum dolor.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$22.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-10.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        2
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Duck carpaccio
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Amet consectetur adip.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$18.00</p>
                </div> -->
              </div>
              <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>
             <!--  <div class="space-y-2 font-inter">
                <div class="flex justify-between text-slate-600 dark:text-navy-100">
                  <p>Subtotal</p>
                  <p class="font-medium tracking-wide">55.00$</p>
                </div>
                <div class="flex justify-between text-xs+">
                  <p>Tax</p>
                  <p class="font-medium tracking-wide">5.00$</p>
                </div>
                <div class="flex justify-between text-base font-medium text-primary dark:text-accent-light">
                  <p>Total</p>
                  <p>60.00$</p>
                </div>
              </div> -->
              <!-- <div class="mt-5 grid grid-cols-3 gap-4 text-center">
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Cash
                  </span>
                </button>
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Debit
                  </span>
                </button>
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Scan
                  </span>
                </button>
              </div>
              <button class="btn mt-5 h-11 justify-between bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                <span>Checkout</span>
                <span>$88.00</span>
              </button> -->
            </div>
          </div>
        </div>
      </main>

      <div x-data="{showDrawer:false}" x-show="showDrawer" x-effect="$store.breakpoints.smAndUp && (showDrawer = false)" @show-drawer.window="($event.detail.drawerId === 'pos-card-drawer') && (showDrawer = true)" @keydown.window.escape="showDrawer = false">
        <div class="fixed inset-0 z-[100] bg-slate-900/60 transition-opacity duration-200" @click="showDrawer = false" x-show="showDrawer" x-transition:enter="ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="fixed right-0 bottom-0 z-[101] h-[calc(100%-2.5rem)] w-full">
          <div class="flex h-full w-full flex-col rounded-t-2xl bg-white px-4 py-3 transition-transform duration-200 dark:bg-navy-700" x-show="showDrawer" x-transition:enter="ease-out transform-gpu" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="ease-in transform-gpu" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
            <div class="flex items-center justify-between">
              <div class="-ml-1 flex items-center space-x-1.5">
                <button @click="showDrawer=false" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
                <div class="flex items-center space-x-1">
                  <p>
                    <span class="text-base font-medium text-slate-700 dark:text-navy-100">Draft</span>
                    <span>#001</span>
                  </p>

                  <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                    <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                      </svg>
                    </button>

                    <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                      <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                        <ul>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #001</a>
                          </li>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #002</a>
                          </li>
                          <li>
                            <a href="#" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">Draft #005</a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="-mr-1.5 flex space-x-1">
                <button class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"></path>
                  </svg>
                </button>
                <button class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 hover:text-error focus:bg-slate-300/20 focus:text-error active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
                <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="inline-flex">
                  <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
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

            <div class="flex grow flex-col overflow-y-auto">
              <div class="mt-4 flex grow flex-col space-y-3.5">
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-4.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        2
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Roast beef
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Lorem ipsum dolor sit.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$12.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-5.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        1
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Tuna salad
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Amet consectetur adip.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$14.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-6.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        3
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Salmon
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Adipisicing elit. Quos?
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$45.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-7.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        1
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          California roll
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Lorem, ipsum dolor.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$22.00</p>
                </div>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../images/foods/food-10.jpg" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        2
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          Duck carpaccio
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        Amet consectetur adip.
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">$18.00</p>
                </div>
              </div>
              <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>
              <div class="space-y-2 font-inter">
                <div class="flex justify-between text-slate-600 dark:text-navy-100">
                  <p>Subtotal</p>
                  <p class="font-medium tracking-wide">55.00$</p>
                </div>
                <div class="flex justify-between text-xs+">
                  <p>Tax</p>
                  <p class="font-medium tracking-wide">5.00$</p>
                </div>
                <div class="flex justify-between text-base font-medium text-primary dark:text-accent-light">
                  <p>Total</p>
                  <p>60.00$</p>
                </div>
              </div>
              <div class="mt-5 grid grid-cols-3 gap-4 text-center">
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Cash
                  </span>
                </button>
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Debit
                  </span>
                </button>
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Scan
                  </span>
                </button>
              </div>
              <button class="btn mt-5 h-11 w-full justify-between bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                <span>Checkout</span>
                <span>$88.00</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="fixed right-3 bottom-3 rounded-full bg-white dark:bg-navy-700">
        <button @click="$dispatch('show-drawer', { drawerId: 'pos-card-drawer' })" class="btn size-14 rounded-full bg-warning p-0 font-medium text-white hover:bg-warning-focus focus:bg-warning-focus active:bg-warning-focus/90 sm:hidden">
          $60
        </button>
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
