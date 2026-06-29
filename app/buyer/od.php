<?php
session_start();
$required_role = 'buyer'; // Only buyers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

$buyer_id  = $_SESSION['id'];
$order_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header("Location: orders.php?error=invalid");
    exit;
}

// Fetch order header
$sql = "
    SELECT * FROM orders 
    WHERE id = ? AND buyer_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $buyer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: orders.php?error=notfound");
    exit;
}

// Fetch order items
$sql_items = "
    SELECT oi.*, p.name AS product_name, p.unit
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order #<?= htmlspecialchars($order['order_code']) ?> • FAIMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

  <header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center">
      <a href="orders.php" class="text-green-700 hover:text-green-800 mr-6">
        <i class="fas fa-arrow-left"></i> Back
      </a>
      <h1 class="text-xl font-bold">Order #<?= htmlspecialchars($order['order_code']) ?></h1>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">

      <!-- Status Banner -->
      <div class="bg-gradient-to-r from-green-600 to-green-800 text-white px-6 py-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="text-2xl font-bold">Status: <?= ucfirst($order['status']) ?></h2>
            <p class="mt-1 opacity-90">
              Placed on <?= date('d M Y • H:i', strtotime($order['created_at'])) ?>
            </p>
          </div>
          <div class="text-right">
            <p class="text-3xl font-bold">
              UGX <?= number_format($order['amount'], 0) ?>
            </p>
            <p class="text-sm opacity-90">Total</p>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="p-6">
        <h3 class="text-xl font-semibold mb-5">Order Items</h3>
        <div class="space-y-6">
          <?php foreach ($items as $item): ?>
            <div class="flex gap-5 border-b pb-5 last:border-b-0 last:pb-0">
              <div class="w-24 h-24 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden">
                <!-- Placeholder image; replace with real product image if linked -->
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                  <i class="fas fa-seedling text-4xl"></i>
                </div>
              </div>
              <div class="flex-1">
                <h4 class="font-semibold text-lg"><?= htmlspecialchars($item['product_name']) ?></h4>
                <p class="text-sm text-gray-600 mt-1">
                  <?= number_format($item['quantity']) ?> × UGX <?= number_format($item['unit_price'], 0) ?>
                </p>
                <p class="font-bold text-green-700 mt-2">
                  Subtotal: UGX <?= number_format($item['subtotal'], 0) ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Delivery & Payment Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-gray-50 border-t">
        <div>
          <h4 class="font-semibold mb-3">Delivery Information</h4>
          <p><strong>Location:</strong> <?= htmlspecialchars($order['delivery_location'] ?: 'Not specified') ?></p>
          <?php if ($order['delivery_window_start']): ?>
            <p><strong>Window:</strong> 
              <?= date('d M Y', strtotime($order['delivery_window_start'])) ?> – 
              <?= date('d M Y', strtotime($order['delivery_window_end'])) ?>
            </p>
          <?php endif; ?>
        </div>
        <div>
          <h4 class="font-semibold mb-3">Payment</h4>
          <p><strong>Status:</strong> <?= ucfirst($order['payment_status']) ?></p>
          <p><strong>Method:</strong> <?= ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'mobile_money')) ?></p>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex flex-wrap gap-4">
      <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
        <button onclick="alert('Proceed to payment – MoMo integration coming soon')"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 px-6 rounded-xl font-medium">
          Pay Now
        </button>
      <?php endif; ?>

      <?php if ($order['status'] === 'pending'): ?>
        <button onclick="if(confirm('Cancel order?')) alert('Cancellation request sent – feature coming soon')"
                class="flex-1 bg-red-100 hover:bg-red-200 text-red-700 py-3 px-6 rounded-xl font-medium">
          Cancel Order
        </button>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>