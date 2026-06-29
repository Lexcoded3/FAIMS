<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'buyer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

$buyer_id = $_SESSION['id'];

// Handle update / remove (your existing code)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $item_id = (int)($_POST['item_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($action === 'update' && $item_id > 0 && $quantity > 0) {
        $sql = "UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id IN (SELECT id FROM carts WHERE buyer_id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $quantity, $item_id, $buyer_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'remove' && $item_id > 0) {
        $sql = "DELETE FROM cart_items WHERE id = ? AND cart_id IN (SELECT id FROM carts WHERE buyer_id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $item_id, $buyer_id);
        $stmt->execute();
        $stmt->close();
    }

    // NEW: Checkout action
    if ($action === 'checkout') {
        // Safety: ensure cart exists
        $sql_check = "SELECT id FROM carts WHERE buyer_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $buyer_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
            $sql_create = "INSERT INTO carts (buyer_id, created_at) VALUES (?, NOW())";
            $stmt_create = $conn->prepare($sql_create);
            $stmt_create->bind_param("i", $buyer_id);
            $stmt_create->execute();
            $stmt_create->close();
        }
        $stmt_check->close();

        // Reload cart items (fresh)
        $cart_items = [];
        $total = 0;
        $sql = "
            SELECT ci.id, ci.quantity, ci.price_at_add,
                   p.id AS product_id, p.name, p.price, p.image, p.unit
            FROM cart_items ci
            JOIN carts c ON ci.cart_id = c.id
            JOIN products p ON ci.product_id = p.id
            WHERE c.buyer_id = ? AND p.status = 'active'
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $buyer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $price = $row['price'] ?? $row['price_at_add'] ?? 0;
            $subtotal = $price * $row['quantity'];
            $cart_items[] = [
                'item_id' => $row['id'],
                'product' => [
                    'id' => $row['product_id'],
                    'name' => $row['name'],
                    'price' => $price,
                    'image' => $row['image'],
                    'unit' => $row['unit']
                ],
                'quantity' => $row['quantity'],
                'subtotal' => $subtotal
            ];
            $total += $subtotal;
        }
        $stmt->close();

        if (empty($cart_items)) {
            header("Location: cart.php?error=empty_cart");
            exit;
        }

        $conn->begin_transaction();

        try {
            // Create order
            $order_code = 'ORD-' . date('YmdHis') . '-' . rand(1000,9999);
            $payment_method = $_POST['payment_method'] ?? 'mobile_money';

            $sql_order = "
                INSERT INTO orders 
                (order_code, buyer_id, amount, status, payment_method, created_at) 
                VALUES (?, ?, ?, 'pending', ?, NOW())
            ";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->bind_param("sids", $order_code, $buyer_id, $total, $payment_method);
            $stmt_order->execute();
            $order_id = $conn->insert_id;
            $stmt_order->close();

            // Insert items
            $sql_item = "
                INSERT INTO order_items 
                (order_id, product_id, quantity, unit_price, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ";
            $stmt_item = $conn->prepare($sql_item);

            foreach ($cart_items as $item) {
                $stmt_item->bind_param("iiidd", $order_id, $item['product']['id'], $item['quantity'], $item['product']['price'], $item['subtotal']);
                $stmt_item->execute();
            }
            $stmt_item->close();

            // Clear cart
            $sql_clear = "DELETE FROM cart_items WHERE cart_id IN (SELECT id FROM carts WHERE buyer_id = ?)";
            $stmt_clear = $conn->prepare($sql_clear);
            $stmt_clear->bind_param("i", $buyer_id);
            $stmt_clear->execute();
            $stmt_clear->close();

            $conn->commit();

            header("Location: orders.php?success=order_placed");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: cart.php?error=checkout_failed&msg=" . urlencode($e->getMessage()));
            exit;
        }
    }

    // Refresh after update/remove
    header("Location: cart.php");
    exit;
}

// Load cart items (your original code, unchanged)
$cart_items = [];
$total = 0;
$sql = "
    SELECT ci.id, ci.quantity, ci.price_at_add,
           p.id AS product_id, p.name, p.price, p.image, p.unit
    FROM cart_items ci
    JOIN carts c ON ci.cart_id = c.id
    JOIN products p ON ci.product_id = p.id
    WHERE c.buyer_id = ? AND p.status = 'active'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $price = $row['price'] ?? $row['price_at_add'] ?? 0;
    $subtotal = $price * $row['quantity'];
    $cart_items[] = [
        'item_id' => $row['id'],
        'product' => [
            'id' => $row['product_id'],
            'name' => $row['name'],
            'price' => $price,
            'image' => $row['image'],
            'unit' => $row['unit']
        ],
        'quantity' => $row['quantity'],
        'subtotal' => $subtotal
    ];
    $total += $subtotal;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>FAIMS - Cart</title>
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

  <body x-data="" x-bind="$store.global.documentBody">
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
          <div class="col-span-12 sm:col-span-6 lg:col-span-8">
            <div class="swiper" x-init="$nextTick(()=>$el._x_swiper= new Swiper($el,{  slidesPerView: 'auto', spaceBetween: 14,navigation:{nextEl:'.next-btn',prevEl:'.prev-btn'}}))">
              <div class="flex items-center justify-between">
                <p class="text-base font-medium text-slate-700 dark:text-navy-100">
                  Your Cart
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
              <div class="col-span-12 sm:col-span-6 lg:col-span-8 space-y-6">
                <!-- Products Grid -->
               <?php if (empty($cart_items)): ?>
                <div class="col-span-12 sm:col-span-6 lg:col-span-8">
                <div class="text-center py-6 bg-navy-700 rounded-2xl shadow">
                  <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-6"></i>
                  <h2 class="text-2xl font-semibold text-gray-700 mb-3">Your cart is empty</h2>
                  <p class="text-gray-500 mb-8">Looks like you haven't added any farm products yet.</p>
                  <a href="products.php">
                    <button
                    class="btn bg-success font-medium text-white hover:bg-success-focus hover:shadow-lg hover:shadow-success/50 focus:bg-success-focus focus:shadow-lg focus:shadow-success/50 active:bg-success-focus/90"
                  >
                    Browse Marketplace
                  </button>
                </a>
                </div>
              </div>
              <?php else: ?>
              <!-- Cart Items -->
             
        
           <?php foreach ($cart_items as $item): 
            $prod = $item['product'];
          ?>
          <div class="card items-center justify-between lg:flex-row">
            <div class="flex flex-col items-center p-4 text-center sm:p-5 lg:flex-row lg:space-x-4 lg:text-left">
              <div class="avatar size-18 lg:h-12 lg:w-12">
                <img class="rounded-full" src="../farmer/<?= htmlspecialchars($prod['image']) ?>" alt="avatar">
              </div>
              <div class="mt-2 lg:mt-0">
                <div class="flex items-center  space-x-1">
                  <h4 class="text-base font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                    <?= htmlspecialchars($prod['name']) ?>
                  </h4>
                  <button class="btn hidden h-6 rounded-full px-2 text-xs justify-left font-medium text-primary hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:text-accent-light dark:hover:bg-accent-light/20 dark:focus:bg-accent-light/20 dark:active:bg-accent-light/25 lg:inline-flex">
                    Follow
                  </button>

                </div>
                <p class="text-xs+ text-left">UGX <?= number_format($prod['price'], 0) ?> / <?= htmlspecialchars($prod['unit'] ?? 'kg') ?></p>
                <div class="flex items-center gap-4 mb-3">
                  <form method="POST" class="flex items-center gap-2">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                    <label class="text-sm text-gray-600">Qty:</label>
                    <input class="form-input mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-2 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="number" name="quantity" value="<?= (int) $item['quantity'] ?>">                 
                  <div class="flex -space-x-px">
                    <button type="submit" 
                      class="btn rounded-r-none rounded-l-full bg-success/10 font-medium text-success hover:bg-success/20 focus:bg-success/20 active:bg-success/25"
                    >
                      Update
                    </button>
                     </form>
                     <form method="POST" class="text-sm">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                    <button type="submit" 
                      class="btn rounded-l-none rounded-r-full bg-error/10 font-medium text-error hover:bg-error/20 focus:bg-error/20 active:bg-error/25"
                    >
                      Remove
                    </button>
                  </div>
                  </form>
                </div>
              </div>
            </div>
            <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="isShowPopper && (isShowPopper = false)" class="absolute top-0 right-0 m-2 lg:static">
              <button x-ref="popperRef" @click="isShowPopper = !isShowPopper" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
          <?php endforeach; ?>
        </div>

            </div>
          </div>
          <div class="hidden sm:col-span-6 sm:block lg:col-span-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-1">
                <p>
                  <span class="text-base font-medium text-slate-700 dark:text-navy-100">Order Summary</span>
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
            <div class="card mt-5 p-4 sm:p-5 ">
              <div class="flex flex-col space-y-6">
                <?php foreach ($cart_items as $item): 
            $prod = $item['product'];
          ?>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../farmer/<?= htmlspecialchars($prod['image']) ?>" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        <?= (int) $item['quantity'] ?>
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          <?= htmlspecialchars($prod['name']) ?>
                        </p>
                        <button class="btn size-6 rounded-full p-0 opacity-0 hover:bg-slate-300/20 focus:bg-slate-300/20 focus:opacity-100 active:bg-slate-300/25 group-hover:opacity-100 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                          </svg>
                        </button>
                      </div>
                      <p class="text-xs+ text-slate-400 dark:text-navy-300">
                        UGX <?= number_format($prod['price'], 0) ?> × <?= (int)$item['quantity'] ?>
                      </p>
                    </div>
                  </div>
                  <p class="font-inter font-semibold">UGX <?= number_format($item['subtotal'], 0) ?></p>
                </div>
                <?php endforeach; ?>
              </div>
            <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>
              <div class="space-y-2 font-inter">
                <div class="flex justify-between text-slate-600 dark:text-navy-100">
                  <p>Subtotal(<?= count($cart_items) ?> items)</p>
                  <p class="font-medium tracking-wide">UGX <?= number_format($total, 0) ?></p>
                </div>
                <div class="flex justify-between text-xs+">
                  <p>Tax</p>
                  <p class="font-medium tracking-wide">To Be Calculated</p>
                </div>
                <div class="flex justify-between text-base font-medium text-primary dark:text-accent-light">
                  <p>Total</p>
                  <p>UGX <?= number_format($total, 0) ?></p>
                </div>
              </div>
              <div class="mt-5 grid grid-cols-3 gap-4 text-center" x-data="{selected:'slide-1'}">
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500" @click="selected = 'slide-1'">
                  <div :class="selected === 'slide-1' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Cash
                  </span>
                  </div>
                </button>
                <button class="rounded-lg border border-slate-200 p-3 dark:border-navy-500" @click="selected = 'slide-2'">
                  <div :class="selected === 'slide-2' ? 'text-secondary bg-secondary/10  dark:bg-secondary-light/10 dark:text-secondary-light' : 'text-slate-600 dark:text-navy-100' ">
                  <svg xmlns="http://www.w3.org/2000/svg" class="inline size-9" fill="none" viewbox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                  </svg>
                  <span class="mt-1 font-medium text-primary dark:text-accent-light">
                    Debit
                  </span>
                </div>
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
              <!-- Checkout Form with Payment Method -->
        <form method="POST" action="cart.php" class="mt-5">
          <input type="hidden" name="action" value="checkout">

          <div class="mb-4 mt-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-2">
              Payment Method
            </label>
            <select name="payment_method" class="form-select w-full rounded-lg border-slate-300 bg-white dark:border-navy-600 dark:bg-navy-700 mt-2">
              <option value="mobile_money">Mobile Money (MoMo / Airtel)</option>
              <option value="bank">Bank Transfer</option>
              <option value="cod">Cash on Delivery</option>
            </select>
          </div>
             <div class="mb-4 mt-2">
          
              <button class="btn bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                <span>Checkout</span>
                <span><?= number_format($total, 0) ?></span>
              </button>
              </div>
            </form>
            <!-- Success / Error Messages -->
        <?php if (!empty($_GET['success'])): ?>
          <div class="space-y-6">
            <div
              x-data="{isShow:true}"
              :class="!isShow && 'opacity-0 transition-opacity duration-300'"
              class="alert flex items-center justify-between overflow-hidden rounded-lg border border-success text-success"
            >
              <div class="flex">
                <div class="bg-success p-3 text-white">
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
                      d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                    />
                  </svg>
                </div>
                <div class="px-4 py-3 sm:px-5">Order Successful! <a href="orders.php" class="underline font-medium">View</a>.</div>
              </div>
              <div class="px-2">
                <button
                  @click="isShow = false; setTimeout(()=>$root.remove(),300)"
                  class="btn size-7 rounded-full p-0 font-medium text-success hover:bg-success/20 focus:bg-success/20 active:bg-success/25"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($_GET['error'])): ?>
          <div class="mt-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg text-center">
            <?= htmlspecialchars($_GET['error']) === 'empty_cart' ? 'Your cart is empty.' : 'Checkout failed. Please try again.' ?>
          </div>
        <?php endif; ?>

        <p class="mt-4 text-center text-sm text-gray-500">
          Continue shopping? <a href="products.php" class="text-green-600 hover:underline">Browse Marketplace</a>
        </p>
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
                <?php foreach ($cart_items as $item): 
            $prod = $item['product'];
          ?>
                <div class="group flex items-center justify-between space-x-3">
                  <div class="flex items-center space-x-4">
                    <div class="relative flex">
                      <img src="../farmer/<?= htmlspecialchars($prod['image']) ?>" class="mask is-star size-11 origin-center object-cover" alt="image">

                      <div class="absolute top-0 right-0 -m-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-white bg-slate-200 px-1 text-tiny+ font-medium leading-none text-slate-800 dark:border-navy-700 dark:bg-navy-450 dark:text-white">
                        <?= (int) $item['quantity'] ?>
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center space-x-1">
                        <p class="font-medium text-slate-700 line-clamp-1 dark:text-navy-100">
                          <?= htmlspecialchars($prod['name']) ?>
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
                  <p class="font-inter font-semibold"><?= number_format($prod['price'], 0) ?> / <?= htmlspecialchars($prod['unit'] ?? 'kg') ?></p>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>
              <div class="space-y-2 font-inter">
                <div class="flex justify-between text-slate-600 dark:text-navy-100">
                  <p>Subtotal</p>
                  <p class="font-medium tracking-wide">55.00$</p>
                </div>
                <div class="flex justify-between text-xs+">
                  <p>Tax</p>
                  <p class="font-medium tracking-wide">To be Calculated</p>
                </div>
                <div class="flex justify-between text-base font-medium text-primary dark:text-accent-light">
                  <p>Total</p>
                  <p>UGX \</p>
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
                <span>UGX </span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="fixed right-3 bottom-3 rounded-full bg-white dark:bg-navy-700">
        <button @click="$dispatch('show-drawer', { drawerId: 'pos-card-drawer' })" class="btn size-14 rounded-full bg-warning p-0 font-medium text-white hover:bg-warning-focus focus:bg-warning-focus active:bg-warning-focus/90 sm:hidden">
          UGX <?= number_format($total, 0) ?>
        </button>
      </div>
    </div>
             <?php endif; ?>
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
