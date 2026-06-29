<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'farmer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';
$sqlTotal = "SELECT COUNT(*) AS total_requests
             FROM buyer_requests
             WHERE status = 'open'";
$resTotal = mysqli_query($conn, $sqlTotal);
$total = mysqli_fetch_assoc($resTotal)['total_requests'];
$user_id = $_SESSION['id'];
 $category_filter = $_GET['category'] ?? '';

// Fetch active market products
if ($category_filter && in_array($category_filter, ['seeds', 'fertilizer', 'chemicals', 'equipment', 'feed'])) {
    $stmt = $conn->prepare("SELECT * FROM marketplace_products WHERE is_active = 1 AND category = ? ORDER BY name ASC");
    $stmt->bind_param("s", $category_filter);
} else {
    $stmt = $conn->prepare("SELECT * FROM marketplace_products WHERE is_active = 1 ORDER BY category ASC, name ASC");
}
 $stmt->execute();
 $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch farmer's current stock mapped to product IDs
 $stockStmt = $conn->prepare("SELECT product_id, SUM(quantity) as total_qty FROM farmer_stock WHERE user_id = ? GROUP BY product_id");
 $stockStmt->bind_param("i", $user_id);
 $stockStmt->execute();
 $stockRes = $stockStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Format stock into a simple lookup array: [product_id => quantity]
 $my_stock = [];
foreach ($stockRes as $s) {
    $my_stock[$s['product_id']] = floatval($s['total_qty']);
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Marketplace</title>
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

  <!-- <body x-data="" class="is-header-blur" x-bind="$store.global.documentBody is-sidebar-open"> -->
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
        <?php include 'marketplacesider.php';?>
      </div>

       <!-- Top and right Sidebar Panel -->
        <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <!-- Header -->
            <div class="flex items-center justify-between py-5 lg:py-6">
                <div class="flex items-center space-x-4">
                    <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">Agro-Dealer Store</h2>
                    <div class="hidden h-full py-1 sm:flex"><div class="h-full w-px bg-slate-300 dark:bg-navy-600"></div></div>
                    <ul class="hidden flex-wrap items-center space-x-2 sm:flex">
                        <li class="text-slate-500 dark:text-navy-300">
                            <?= $category_filter ? ucfirst($category_filter) : 'All Products' ?>
                        </li>
                    </ul>
                </div>
                
                <?php if($category_filter): ?>
                    <a href="marketplace.php" class="btn rounded-lg px-4 py-2.5 text-sm font-medium border border-slate-300 dark:border-navy-600 text-slate-700 dark:text-navy-100 hover:bg-slate-100 dark:hover:bg-navy-700">
                        <i class="fas fa-times mr-2"></i> Clear Filter
                    </a>
                <?php endif; ?>
            </div>

            <!-- Products Grid -->
            <?php if(empty($products)): ?>
                <div class="card p-12 text-center">
                    <i class="fas fa-box-open text-4xl text-slate-300 dark:text-navy-600 mb-4 block"></i>
                    <p class="text-slate-500 dark:text-navy-300">No products found in this category.</p>
                </div>
            <?php else: ?>
                <div class= "grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:gap-6">
                    <?php foreach($products as $p): 
                        $categoryColors = [
                            'seeds' => 'bg-success/10 text-success',
                            'fertilizer' => 'bg-info/10 text-info',
                            'chemicals' => 'bg-error/10 text-error',
                            'equipment' => 'bg-warning/10 text-warning',
                            'feed' => 'bg-secondary/10 text-secondary'
                        ];
                        $colorClass = $categoryColors[$p['category']] ?? 'bg-slate-100 text-slate-600';
                        
                        $in_stock_qty = $my_stock[$p['id']] ?? 0;
                    ?>
                    <div class="card overflow-hidden flex flex-col hover:shadow-lg transition-shadow">
                        
                        <!-- Image Placeholder -->
                        <div class="h-36 bg-slate-100 dark:bg-navy-700 flex items-center justify-center relative">
                            <?php if($in_stock_qty > 0): ?>
                                <span class="absolute top-3 right-3 bg-success text-white text-xs font-bold px-2 py-1 rounded-full">
                                    In Stock: <?= $in_stock_qty ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if($p['image_url']): ?>
                                <img src="<?= htmlspecialchars($p['image_url']) ?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($p['name']) ?>">
                            <?php else: ?>
                                <i class="fas fa-box text-4xl text-slate-300 dark:text-navy-600"></i>
                            <?php endif; ?>
                        </div>

                        <!-- Details -->
                        <div class="p-4 flex flex-col grow">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded capitalize <?= $colorClass ?>">
                                    <?= $p['category'] ?>
                                </span>
                                <span class="text-xs text-slate-400 dark:text-navy-300">
                                    Per <?= htmlspecialchars($p['unit_type']) ?>
                                </span>
                            </div>
                            
                            <h4 class="font-bold text-slate-800 dark:text-navy-100 mb-1 line-clamp-2"><?= htmlspecialchars($p['name']) ?></h4>
                            <p class="text-xs text-slate-500 dark:text-navy-300 mb-4 line-clamp-2 grow"><?= htmlspecialchars($p['description'] ?? 'No description available.') ?></p>
                            
                            <div class="flex items-center justify-between mt-auto pt-3 border-t border-slate-100 dark:border-navy-600">
                                <p class="text-lg font-bold text-primary dark:text-accent-light">
                                    UGX <?= number_format($p['base_price']) ?>
                                </p>
                                <button class="btn bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus text-xs font-medium px-3 py-1.5 rounded-lg">
                                    <i class="fas fa-cart-plus mr-1"></i> Order
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
          <!-- div class="col-span-12 lg:order-last lg:col-span-4">
            <div class="card col-span-2 px-4 pb-5 sm:px-5">
                <div x-data="{activeTab:'tabProfile'}" class="tabs flex flex-col">
                  <div class="is-scrollbar-hidden overflow-x-auto">
                    <div class="border-b-2 border-slate-150 dark:border-navy-500">
                      <div class="tabs-list -mb-0.5 flex">
                        <button
                          @click="activeTab = 'tabHome'"
                          :class="activeTab === 'tabHome' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                          class="btn shrink-0 space-x-2 rounded-none border-b-2 px-3 py-2 font-medium"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="size-4.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            />
                          </svg>
                          <span>Home</span>
                        </button>
                        <button
                          @click="activeTab = 'tabProfile'"
                          :class="activeTab === 'tabProfile' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                          class="btn shrink-0 space-x-2 rounded-none border-b-2 px-3 py-2 font-medium"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="size-4.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                          </svg>
                          <span>Profile</span>
                        </button>
                        <button
                          @click="activeTab = 'tabMessages'"
                          :class="activeTab === 'tabMessages' ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                          class="btn shrink-0 space-x-2 rounded-none border-b-2 px-3 py-2 font-medium"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="size-4.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
                          </svg>
                          <span> Messages </span>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="tab-content pt-4">
                    <div
                      x-show="activeTab === 'tabHome'"
                      x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                    >
                      <div>
                        <p id="marketPrices">
                          Pellentesque pulvinar, sapien eget fermentum sodales, felis lacus
                          viverra magna, id pulvinar odio metus non enim. Ut id augue
                          interdum, ultrices felis eu, tincidunt libero.
                        </p>
                        <div class="flex space-x-2 pt-3">
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 1
                          </a>
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 2
                          </a>
                        </div>

                        <p class="pt-3 text-xs text-slate-400 dark:text-navy-300">
                          Lorem ipsum dolor sit amet consectetur adipisicing elit. Tempore
                          dolore non atque?
                        </p>
                      </div>
                    </div>
                    <div
                      x-show="activeTab === 'tabProfile'"
                      x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                    >
                      <div>
                        <p>
                          Cras iaculis ipsum quis lectus faucibus, in mattis nulla molestie.
                          Vestibulum vel tristique libero. Morbi vulputate odio at viverra
                          sodales. Curabitur accumsan justo eu libero porta ultrices vitae eu
                          leo.
                        </p>
                        <div class="flex space-x-2 pt-3">
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 1
                          </a>
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 2
                          </a>
                        </div>

                        <p class="pt-3 text-xs text-slate-400 dark:text-navy-300">
                          Lorem ipsum dolor sit amet consectetur adipisicing elit. Tempore
                          dolore non atque?
                        </p>
                      </div>
                    </div>
                    <div
                      x-show="activeTab === 'tabMessages'"
                      x-transition:enter="transition-all duration-500 easy-in-out"
                      x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                      x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                    >
                      <div>
                        <p>
                          Etiam nec ante eget lacus vulputate egestas non iaculis tellus.
                          Suspendisse tempus ex in tortor venenatis malesuada. Aenean
                          consequat dui vitae nibh lobortis condimentum. Duis vel risus est.
                        </p>
                        <div class="flex space-x-2 pt-3">
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 1
                          </a>
                          <a
                            href="#"
                            class="tag rounded-full border border-primary text-primary dark:border-accent-light dark:text-accent-light"
                          >
                            Tag 2
                          </a>
                        </div>

                        <p class="pt-3 text-xs text-slate-400 dark:text-navy-300">
                          Lorem ipsum dolor sit amet consectetur adipisicing elit. Tempore
                          dolore non atque?
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
           
          </div> -->
          
        </div>
      </main>
    </div>
<script>
function loadPrices(period = 'daily') {
    fetch(`ajax/market_prices.php?period=${period}`)
        .then(res => res.text())
        .then(data => {
            document.getElementById("marketPrices").innerHTML = data;
        });
}
loadPrices();
</script>
<script>
function loadRequests() {
    fetch('ajax/buyer_requests.php')
        .then(res => res.text())
        .then(data => {
            document.getElementById("buyerRequests").innerHTML = data;
        });
}
loadRequests();
</script>
<script>
function percrop() {
    fetch('ajax/requestpercrop.php')
        .then(res => res.text())
        .then(data => {
            document.getElementById("percrop").innerHTML = data;
        });
}
percrop();
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
