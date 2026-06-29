<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'buyer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$buyer_id   = $_SESSION['id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: products.php?error=invalid");
    exit;
}

// Fetch product + farmer
$sql = "
    SELECT p.*, c.name AS category_name,
           u.name AS farmer_name, u.phone AS farmer_phone, u.location AS farmer_location,
           u.email AS farmer_email, u.status AS farmer_status
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.farmer_id = u.id
    WHERE p.id = ? AND p.status = 'active'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc() ?? null;
$stmt->close();

if (!$product) {
    header("Location: products.php?error=notfound");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Product Detail</title>
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
      <main class="main-content w-full place-items-center px-[var(--margin-x)] pb-12">
        <div class="py-5 text-center lg:py-6">
          <p class="text-sm uppercase"><?= htmlspecialchars($product['name']) ?></p>
          <h3 class="mt-1 text-xl font-semibold text-slate-600 dark:text-navy-100">
            <?= nl2br(htmlspecialchars($product['description'] ?: 'No additional description provided.')) ?>
          </h3>
        </div>
        <div class="mx-auto w-full grid max-w-5xl grid-cols-1 gap-4 sm:grid-cols-2 md:gap-5 2xl:gap-6">
          <div class="card">
            <div class="rounded-t-lg bg-slate-100 p-4 text-center dark:bg-navy-800 sm:p-4">
              <div class="card">
                <?php if ($product['image']): ?>
            <img class="h-72 w-full rounded-lg object-cover object-center" src="../farmer/<?= htmlspecialchars($product['image']) ?>" alt="image">
            <?php else: ?>
            <div class="h-80 lg:h-[520px] bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
              <i class="fas fa-seedling text-8xl text-gray-300"></i>
            </div>
          <?php endif; ?>
            <div class="absolute inset-0 flex h-full w-full flex-col justify-end">
              <div class="space-y-1.5 rounded-lg bg-gradient-to-t from-[#19213299] via-[#19213266] to-transparent px-4 pb-3 pt-12">
                <div class="line-clamp-2">
                  <a href="#" class="text-base font-medium text-white">
                    <?= nl2br(htmlspecialchars($product['description'] ?: 'No additional description provided.')) ?>
                  </a>
                </div>
                <div class="flex items-center justify-between">
                  <div class="flex items-center text-xs text-slate-200">
                    <p class="flex items-center space-x-1">
                      <i class="fas fa-seedling text-2xl text-gray-300"></i>
                      <span class="line-clamp-1"><?= htmlspecialchars($product['name']) ?></span>
                    </p>
                    <div class="mx-3 my-0.5 w-px self-stretch bg-white/20"></div>
                    <p class="shrink-0 text-tiny+"><?= date('d M Y', strtotime($product['created_at'])) ?>
                    </p>
                    <div class="mx-3 my-0.5 w-px self-stretch bg-white/20"></div>
                    <span class="text-success font-medium">Active</span>
                  </div>
                  <div class="-mr-1.5 flex">
                    <button 
                        id="favoriteBtn"
                        data-product-id="<?= $product_id ?>"
                        x-tooltip.secondary="'Add to favorites'"
                        class="btn h-7 w-7 rounded-full p-0 text-secondary-light hover:bg-secondary/20 focus:bg-secondary/20 active:bg-secondary/25 dark:hover:bg-secondary-light/20 dark:focus:bg-secondary-light/20 dark:active:bg-secondary-light/25 transition-all duration-200">
                        <svg 
                          id="heartIcon" 
                          xmlns="http://www.w3.org/2000/svg" 
                          class="size-4.5 transition-all duration-200" 
                          fill="none" 
                          viewBox="0 0 24 24" 
                          stroke="currentColor" 
                          stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                      </button>

                    <button x-tooltip="'Save'" class="btn h-7 w-7 rounded-full p-0 text-navy-100 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
            </div>
          
          </div>
          <div class="card">
            <div class="rounded-t-lg bg-primary p-4 text-center dark:bg-accent sm:p-5">
              <p class="text-xl font-medium text-white"><?= htmlspecialchars($product['name']) ?></p>
              <p class="mt-3 text-3xl font-semibold text-white">UGX <?= number_format($product['price'], 0) ?><span class="mt-3">/<?= htmlspecialchars($product['unit'] ?? 'kg') ?></span></p>
            </div>
            <div class="p-4 sm:p-5">
              <div class="mt-3 space-y-4 text-left">
                <div class="flex items-start space-x-3">
                  <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light">
                    <i class="fas fa-tag"></i>
                  </div>
                  <span class="font-medium"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                </div>
                <div class="flex items-start space-x-3">
                  <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light">
                    <i class="fas fa-user-circle text-green-600"></i>
                  </div>
                  <span class="font-medium"><?= htmlspecialchars($product['farmer_name'] ?? 'N/A') ?> </span>
                </div>
                <div class="flex items-start space-x-3">
                  <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>

                  </div>
                  <span class="font-medium"><?= htmlspecialchars($product['farmer_location'] ?? 'N/A') ?> </span>
                </div>
                <div class="flex items-start space-x-3">
                  <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light">
                    <i class="fas fa-phone text-green-600"></i>
                  </div>
                  <?php if (!empty($product['farmer_phone'])): ?>
                  <span class="font-medium">
                    <a href="tel:<?= htmlspecialchars($product['farmer_phone']) ?>" 
                 class="text-green-600 hover:underline">
                    <?= htmlspecialchars($product['farmer_phone']) ?>
                 </a>
                  </span>
                  <?php endif; ?>
                </div>
                <div class="flex items-start space-x-3">
                  <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-success/10 text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="size-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"></path>
                    </svg>
                  </div>
                  <div
                    class="badge space-x-2.5 rounded-full bg-success/10 text-success dark:bg-success/15"
                  >
                    <div class="size-2 rounded-full bg-current"></div>
                    <span>Verified Farmer</span>
                  </div>
                </div>
              </div>
              <div class="mt-8 flex flex-col sm:flex-row gap-4">
                <div x-data="{showModal:false}">
                <button
                    @click="showModal = true"
                    class="btn space-x-2 bg-success font-medium text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                  >
                    <i class="fas fa-handshake"></i>
                    <span>Make Offer</span>
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
                                        class="relative w-full max-w-lg origin-top rounded-lg bg-white transition-all duration-300 dark:bg-navy-700"
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
                                            Negotiate with Farmer
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
                                        <div class="px-4 py-4 sm:px-5">
                                           To negotiate for <?= $product['name'] ?> with the farmer, select the following
                                          </p>
                                          <form method="POST" action="ajax/negotiate-submit.php">
                                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                          <div class="mt-4 space-y-4">
                                            <div class="grid grid-cols-2 gap-4">
                                                  <label class="block">
                                                    <span class="text-sm font-medium text-slate-600 dark:text-navy-100">Your Offer Price(UGX per <?= htmlspecialchars($product['unit'] ?? 'kg') ?>)</span>
                                                    <input type="number" name="offered_price" min="1" step="0.01" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Price"  required >
                                                  </label>
                                                  <label class="block">
                                                    <span class="text-sm font-medium text-slate-600 dark:text-navy-100">Quantity You Want (<?= htmlspecialchars($product['unit'] ?? 'kg') ?>)</span>
                                                    <input
                                                      type="number"
                                                      name="quantity"
                                                      min="1"
                                                      max="<?= (int)$product['quantity'] ?>"
                                                      class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                                                      placeholder="Qty"
                                                      required
                                                    >
                                                  </label>
                                                </div>
                                            <label class="block">
                                              <span>Message to Farmer (optional)</span>
                                              <textarea
                                                rows="4"name="message"
                                                placeholder=" Enter Text"
                                                class="form-textarea mt-1.5 w-full resize-none rounded-lg border border-slate-300 bg-transparent p-2.5 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                                              ></textarea>
                                            </label>
                                            <div class="space-x-2 text-right">
                                              <button
                                                @click="showModal = false"
                                                class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90"
                                              >
                                                Cancel
                                              </button>
                                              <button
                                                type="submit"
                                                class="btn min-w-[7rem] rounded-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90"
                                              >
                                                Send Offer
                                              </button>
                                            </div>
                                          </div>
                                        </form>
                                        </div>
                                      </div>
                                    </div>
                                  </template>
                                </div>
                                  <form action="ajax/add-to-order.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= $product_id ?>">
                              <input type="hidden" name="price" value="<?= $product['price'] ?>">
                  <button type="submit"
                    class="btn space-x-2 bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                  >
                    <i class="fas fa-shopping-cart"></i> 
                    <span> Add to Order</span>
                  </button>
                </form>
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
    <script>
    document.getElementById('favoriteBtn').addEventListener('click', async function(e) {
      e.preventDefault();
      
      const btn = this;
      const icon = document.getElementById('heartIcon');
      const productId = btn.dataset.productId;

      // Visual feedback: pulse + slight scale
      icon.classList.add('animate-pulse', 'scale-125');

      try {
        const response = await fetch('/FAIMS/app/buyer/ajax/toggle-favorite.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ product_id: productId })
        });

        if (!response.ok) {
          throw new Error(`Server error: ${response.status} ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success) {
          // Toggle heart style
          if (data.action === 'added') {
            icon.classList.remove('far');
            icon.classList.add('fas');
            icon.classList.add('text-red-600'); // filled red
          } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            icon.classList.remove('text-red-600');
            icon.classList.add('text-secondary-light'); // back to default
          }
        } else {
          alert('Failed to update favorite: ' + (data.message || 'Unknown error'));
        }
      } catch (err) {
        console.error('Favorite toggle failed:', err);
        alert('Network or server error: ' + err.message);
      } finally {
        // Remove animation
        icon.classList.remove('animate-pulse', 'scale-125');
      }
    });
    </script>
  </body>
</html>
