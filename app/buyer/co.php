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

if (empty($_SESSION['cart'])) {
    header("Location: cart.php?error=empty");
    exit;
}

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Fetch product details for display
$ids = array_column($_SESSION['cart'], 'product_id');
if (empty($ids)) {
    header("Location: cart.php?error=invalid");
    exit;
}
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT id, name, price, unit FROM products WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$cart_items = [];
foreach ($_SESSION['cart'] as $cart_item) {
    foreach ($products as $prod) {
        if ($prod['id'] == $cart_item['product_id']) {
            $cart_items[] = array_merge($prod, ['quantity' => $cart_item['quantity']]);
            break;
        }
    }
}

// Handle final confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $payment_method = $_POST['payment_method'] ?? 'mobile_money';

    // Create order
    $order_code = 'ORD-' . date('YmdHis') . '-' . rand(1000,9999);
    $sql_order = "INSERT INTO orders (order_code, buyer_id, total_amount, status, payment_method, created_at) 
                  VALUES (?, ?, ?, 'pending', ?, NOW())";
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("sids", $order_code, $buyer_id, $total, $payment_method);
    $stmt_order->execute();
    $order_id = $conn->insert_id;
    $stmt_order->close();

    // Insert items
    $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) 
                 VALUES (?, ?, ?, ?, ?)";
    $stmt_item = $conn->prepare($sql_item);

    foreach ($cart_items as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $stmt_item->bind_param("iiidd", $order_id, $item['id'], $item['quantity'], $item['price'], $subtotal);
        $stmt_item->execute();
    }
    $stmt_item->close();

    // Clear cart
    unset($_SESSION['cart']);

    header("Location: orders.php?success=order_placed");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout • FAIMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

  <header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center">
      <a href="cart.php" class="text-green-700 hover:text-green-800 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Back to Cart
      </a>
      <h1 class="text-xl font-bold ml-8">Checkout</h1>
    </div>
  </header>

  <main class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">

      <!-- Order Summary -->
      <div class="p-6 lg:p-8 border-b">
        <h2 class="text-2xl font-bold mb-6">Order Summary</h2>

        <div class="space-y-6">
          <?php foreach ($cart_items as $item): ?>
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-gray-100 rounded-lg flex-shrink-0">
                  <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <i class="fas fa-seedling text-3xl"></i>
                  </div>
                </div>
                <div>
                  <h4 class="font-semibold"><?= htmlspecialchars($item['name']) ?></h4>
                  <p class="text-sm text-gray-600">
                    <?= $item['quantity'] ?> × UGX <?= number_format($item['price'], 0) ?>
                  </p>
                </div>
              </div>
              <p class="font-bold text-green-700">
                UGX <?= number_format($item['price'] * $item['quantity'], 0) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-8 pt-6 border-t">
          <div class="flex justify-between text-lg font-semibold">
            <span>Total</span>
            <span class="text-green-700">UGX <?= number_format($total, 0) ?></span>
          </div>
        </div>
      </div>

      <!-- Payment Method -->
      <div class="p-6 lg:p-8">
        <h3 class="text-xl font-semibold mb-5">Payment Method</h3>

        <form method="POST" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer hover:border-green-500 transition
                           <?= ($_POST['payment_method'] ?? 'mobile_money') === 'mobile_money' ? 'border-green-500 bg-green-50' : 'border-gray-200' ?>">
              <input type="radio" name="payment_method" value="mobile_money" 
                     class="h-5 w-5 text-green-600" <?= ($_POST['payment_method'] ?? 'mobile_money') === 'mobile_money' ? 'checked' : '' ?>>
              <div>
                <p class="font-medium">Mobile Money (MoMo/Airtel)</p>
                <p class="text-sm text-gray-600">Fast & secure</p>
              </div>
            </label>

            <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer hover:border-green-500 transition
                           <?= ($_POST['payment_method'] ?? '') === 'bank' ? 'border-green-500 bg-green-50' : 'border-gray-200' ?>">
              <input type="radio" name="payment_method" value="bank" 
                     class="h-5 w-5 text-green-600" <?= ($_POST['payment_method'] ?? '') === 'bank' ? 'checked' : '' ?>>
              <div>
                <p class="font-medium">Bank Transfer</p>
                <p class="text-sm text-gray-600">Direct deposit</p>
              </div>
            </label>

            <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer hover:border-green-500 transition
                           <?= ($_POST['payment_method'] ?? '') === 'cod' ? 'border-green-500 bg-green-50' : 'border-gray-200' ?>">
              <input type="radio" name="payment_method" value="cod" 
                     class="h-5 w-5 text-green-600" <?= ($_POST['payment_method'] ?? '') === 'cod' ? 'checked' : '' ?>>
              <div>
                <p class="font-medium">Cash on Delivery</p>
                <p class="text-sm text-gray-600">Pay when received</p>
              </div>
            </label>
          </div>

          <!-- Confirm Button -->
          <div class="pt-8 text-center">
            <button type="submit" name="confirm_order" 
                    class="w-full max-w-md mx-auto bg-green-600 hover:bg-green-700 text-white py-4 px-8 rounded-xl font-medium text-lg shadow-lg transition">
              Confirm & Place Order
            </button>
            <p class="mt-4 text-sm text-gray-500">
              By confirming, you agree to our <a href="#" class="text-green-600 hover:underline">Terms & Conditions</a>
            </p>
          </div>
        </form>
      </div>
    </div>
  </main>
</body>
</html>