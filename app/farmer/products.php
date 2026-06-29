<?php
session_start();
$required_role = 'farmer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$farmer_id = $_SESSION['id'];

// Fetch farmer products
$stmt = $conn->prepare("SELECT * FROM products WHERE farmer_id=? ORDER BY id DESC");
$stmt->bind_param("i",$farmer_id);
$stmt->execute();
$result = $stmt->get_result();

$sql = "
SELECT c.id, c.name, c.image, COUNT(p.id) AS total
FROM categories c
JOIN products p ON p.category_id = c.id
WHERE p.farmer_id = $farmer_id
GROUP BY c.id
";

$categories = mysqli_query($conn, $sql);

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Products/crops</title>
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
            <div class="mt-6 flex flex-col items-center justify-between space-y-2 text-center sm:flex-row sm:space-y-0 sm:text-left">
          <div>
            <h3 class="text-xl font-semibold text-slate-700 dark:text-navy-100">
              Product Dashboard
            </h3>
            <p class="mt-1 hidden sm:block">List of your Products and Categories</p>
          </div>
            <div class="flex -space-x-px">
              <!-- <button
                class="btn rounded-l-full rounded-r-none border border-primary font-medium text-primary hover:bg-primary hover:text-white focus:bg-primary focus:text-white active:bg-primary/90"
              >
                First
              </button>
              <button
                class="btn rounded-none bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
              >
                Second
              </button> -->
              <div x-data="{showModal:false}">
              <button @click="showModal = true"
                class="btn rounded-l-none rounded-r-full border border-primary font-medium text-primary hover:bg-primary hover:text-white focus:bg-primary focus:text-white active:bg-primary/90"
              >
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-indigo-50" fill="none" viewbox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span> New Product </span>
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
           <div class="flex items-center space-x-2">
                  <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 p-1 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                    <i class="fa-solid fa-layer-group"></i>
                  </div>
                  <h4 class="text-lg font-medium text-slate-700 dark:text-navy-100">
                    Add Product
                  </h4>
                </div>
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
        <form method="POST" action="add_product.php" enctype="multipart/form-data">
          <div class="space-y-4 p-4 sm:p-5">
                <label class="block">
                  <span>Product name</span>

                  <input name="name" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Enter product name" type="text">
                </label>
                <?php
                $cats = mysqli_query($conn,"SELECT * FROM categories");

                  if(!$cats){
                      die(mysqli_error($conn));
                  }
                  ?>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <label class="block">
                    <span>Category</span>
                    <select name="category_id" class="mt-1.5 w-full form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"  required >
                      <?php while($cat = mysqli_fetch_assoc($cats)): ?>
                      <option value="<?= $cat['id'] ?>">
                      <?= htmlspecialchars($cat['name']) ?>
                      </option>
                      <?php endwhile; ?>
                      </select>
                  </label>

                  <div class="grid grid-cols-2 gap-4">
                    <label class="block">
                      <span>Price</span>
                      <input name="price" type="number" step="0.01" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Price"  required >
                    </label>
                    <label class="block">
                      <span>Quantity</span>
                      <input  name="quantity" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Qty" type="number" required >
                    </label>
                  </div>
                </div>
                <div>
                  <span>Images</span>
                  <div class="filepond fp-bordered fp-grid mt-1.5 [--fp-grid:2]">
                    <input name="image" type="file" x-init="$el._x_filepond = FilePond.create($el)" multiple="">
                  </div>
                </div>
              </div>
            <div class="px-4 py-4 sm:px-5">
              
            <!-- <div class="mt-4 space-y-4"> -->
              <div class="space-x-2 text-right">
                <button
                  @click="showModal = false"
                  class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-800 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80 dark:border-navy-450 dark:text-navy-50 dark:hover:bg-navy-500 dark:focus:bg-navy-500 dark:active:bg-navy-500/90"
                >
                  Cancel
                </button>
                <button button type="submit" name="add_product"
                  class="btn min-w-[7rem] rounded-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90"
                >
                  Save
                </button>
              </div>
              </form>
            <!-- </div> -->
          </div>
        </div>
      </div>
    </template>
            
  </div>
            </div>
        </div>
        <div class="mt-5 grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
          <div class="col-span-12 sm:col-span-6 lg:col-span-8">
            <div class="swiper" x-init="$nextTick(()=>$el._x_swiper= new Swiper($el,{  slidesPerView: 'auto', spaceBetween: 14,navigation:{nextEl:'.next-btn',prevEl:'.prev-btn'}}))">
              <div class="flex items-center justify-between">
                <p class="text-base font-medium text-slate-700 dark:text-navy-100">
                  Product Categories <span>
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

              <div class="swiper-wrapper mt-5" x-data="{selected:'slide-1'}">
                <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                <div class="card swiper-slide w-24 shrink-0 cursor-pointer" @click="selected = '<?php echo htmlspecialchars($cat['id']); ?>'">
                  <div class="flex flex-col items-center rounded-lg px-2 py-4" :class="selected === '<?php echo htmlspecialchars($cat['id']); ?>' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                    <img class="w-12" src="../<?php echo $cat['image']; ?>">
                    <h3 class="pt-2 font-medium tracking-wide line-clamp-1">
                      <?php echo htmlspecialchars($cat['name']); ?>
                    </h3>
                  </div>
                </div>
                <?php endwhile; ?>
              </div>
            </div>
            <div class="flex items-center justify-between">
                <p class="text-base font-medium text-slate-700 dark:text-navy-100">
                  Products <span>
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
            <div class="mt-4 grid grid-cols-2 gap-4 sm:mt-5 sm:grid-cols-2 sm:gap-5 lg:mt-6 lg:grid-cols-3 xl:grid-cols-4">
              <?php $i=1; while($row=$result->fetch_assoc()): ?>
              <div class="card p-2">
                <img class="rounded-lg" src="<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                <div class="pt-2">
                  <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                    <?= $row['name'] ?>
                  </p>
                  <p class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">
                    <?php echo htmlspecialchars($row['description']); ?>
                  </p>
                  <p class="text-right font-medium text-primary dark:text-accent-light">
                    <?= number_format($row['price']) ?> UGX$
                  </p>
                </div>
                <div class="mt-4 flex items-center justify-between">
                  <div class="flex space-x-2">
                      <button class="btn size-7 rounded-full bg-info/10 p-0 text-info hover:bg-info/20 focus:bg-info/20 active:bg-info/25" x-tooltip.info="'Edit'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                      </button>
                      <button class="btn size-7 rounded-full bg-error/10 p-0 text-error hover:bg-error/20 focus:bg-error/20 active:bg-error/25" x-tooltip.error="'Make Deal'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                        </svg>
                      </button>
                    </div>
                </div>
              </div>
              <?php endwhile; ?>
            </div>
          </div>
          <?php
          // ... (DB connection already there)

          if (!isset($_SESSION['id'])) {
              // Optional: show message if not logged in
              echo '<p class="text-center py-8 text-gray-500 dark:text-gray-400">Please log in to see your products.</p>';
          } else {
              $farmer_id = (int)$_SESSION['id'];

              $sql = "
                  SELECT 
                      p.id,
                      p.name,
                      p.description,
                      p.price,
                      p.image,
                      p.status,
                      p.created_at,
                      c.name AS category_name
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.farmer_id = ?
                    -- AND p.status = 'active'
                  ORDER BY p.created_at DESC
                  LIMIT 4
              ";

              $stmt = $conn->prepare($sql);
              $stmt->bind_param("i", $farmer_id);
              $stmt->execute();
              $result = $stmt->get_result();
            }
          ?>
          <div class="hidden sm:col-span-6 sm:block lg:col-span-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-1">
                <p>
                  <span class="text-base font-medium text-slate-700 dark:text-navy-100">My latest</span>
                  <span></span>
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
                 <button  class="btn size-7 rounded-full p-0 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
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
              <?php if ($result && $result->num_rows > 0): ?>
              <div class="flex flex-col space-y-3.5">                
                <?php while ($product = $result->fetch_assoc()): ?>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="<?= htmlspecialchars($product['image'] ?: '/images/placeholder-product.jpg') ?>" class="mask is-star size-11 origin-center object-cover" alt="image">

                    <?php if ($product['status'] === 'active'): ?>
                    <span class="absolute -top-px -right-px flex size-3 items-center justify-center">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-success opacity-80"></span>
                    <span class="inline-flex size-2 rounded-full bg-success"></span>
                  </span>
                  <?php else: ?>
                    <span class="absolute -top-px -right-px flex size-3 items-center justify-center">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-error opacity-80"></span>
                    <span class="inline-flex size-2 rounded-full bg-error"></span>
                  </span> 
                  <?php endif; ?>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          <?= htmlspecialchars($product['name']) ?>
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        <?= htmlspecialchars(substr($product['description'] ?? '', 0, 15)) . (strlen($product['description'] ?? '') > 15 ? '...' : '') ?>
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">UGX <?= number_format($product['price'], 0) ?></p>
                </div>
                 <?php endwhile; ?>
              </div>
                  <?php else: ?>
                  <div class="alert flex space-x-2 rounded-lg border border-error px-4 py-4 text-error"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="size-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      <p>No latest products available.</p>
                    </div>
                <?php endif; ?>
              <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>
              
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
