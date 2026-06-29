<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'farmer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../../login.php");
    exit;
}

$farmer_id = $_SESSION['id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id   = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];

    // Security: only allow valid statuses and only if order belongs to this farmer
    $allowed = ['confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $allowed)) {
        $sql = "UPDATE orders o 
                JOIN order_items oi ON oi.order_id = o.id
                JOIN products p ON p.id = oi.product_id
                SET o.status = ?
                WHERE o.id = ? AND p.farmer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $new_status, $order_id, $farmer_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch orders for this farmer
$sql = "
    SELECT o.id, o.order_code, o.amount, o.status, o.created_at, 
           o.delivery_location, o.payment_status, 
           COUNT(oi.id) AS item_count,
           b.name AS buyer_name, b.phone AS buyer_phone
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN products p ON p.id = oi.product_id
    JOIN users b ON o.buyer_id = b.id
    WHERE p.farmer_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders • FAIMS Farmer</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

  <header class="bg-green-800 text-white p-4">
    <div class="max-w-7xl mx-auto flex justify-between">
      <h1 class="text-xl font-bold">My Orders (Incoming)</h1>
      <a href="index.php" class="hover:underline">Dashboard</a>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    <?php if (empty($orders)): ?>
      <div class="text-center py-16 bg-white rounded-2xl shadow">
        <i class="fas fa-boxes-stacked text-7xl text-gray-300 mb-6"></i>
        <h2 class="text-2xl font-semibold text-gray-700">No incoming orders yet</h2>
        <p class="text-gray-500 mt-3">When buyers place orders for your produce, they'll appear here.</p>
      </div>
    <?php else: ?>
      <div class="space-y-6">
        <?php foreach ($orders as $order): ?>
          <div class="bg-white rounded-xl shadow border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
              <div>
                <h3 class="font-bold text-lg">Order #<?= htmlspecialchars($order['order_code']) ?></h3>
                <p class="text-sm text-gray-600">
                  From: <?= htmlspecialchars($order['buyer_name']) ?>
                  <?php if ($order['buyer_phone']): ?>
                    • <a href="tel:<?= $order['buyer_phone'] ?>" class="text-green-600 hover:underline">
                      <?= $order['buyer_phone'] ?>
                    </a>
                  <?php endif; ?>
                </p>
                <p class="text-sm text-gray-500 mt-1">
                  Placed: <?= date('d M Y • H:i', strtotime($order['created_at'])) ?>
                </p>
              </div>
              <span class="inline-block px-4 py-1.5 text-sm font-medium rounded-full
                           <?= match($order['status']) {
                               'pending'     => 'bg-yellow-100 text-yellow-800',
                               'confirmed'   => 'bg-blue-100 text-blue-800',
                               'processing'  => 'bg-purple-100 text-purple-800',
                               'shipped'     => 'bg-indigo-100 text-indigo-800',
                               'delivered'   => 'bg-green-100 text-green-800',
                               'completed'   => 'bg-green-700 text-white',
                               'cancelled'   => 'bg-red-100 text-red-800',
                               default       => 'bg-gray-100 text-gray-800'
                           } ?>">
                <?= ucfirst($order['status']) ?>
              </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6 text-sm">
              <div>
                <span class="text-gray-600">Items</span><br>
                <span class="font-bold"><?= $order['item_count'] ?></span>
              </div>
              <div>
                <span class="text-gray-600">Total</span><br>
                <span class="font-bold text-green-700">UGX <?= number_format($order['amount'], 0) ?></span>
              </div>
              <div>
                <span class="text-gray-600">Delivery</span><br>
                <span><?= htmlspecialchars($order['delivery_location'] ?: 'N/A') ?></span>
              </div>
              <div>
                <span class="text-gray-600">Payment</span><br>
                <span class="font-medium"><?= ucfirst($order['payment_status']) ?></span>
              </div>
            </div>

            <!-- Farmer Actions -->
            <div class="flex flex-wrap gap-3">
              <?php if ($order['status'] === 'pending'): ?>
                <form method="POST" class="inline">
                  <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                  <input type="hidden" name="new_status" value="confirmed">
                  <button type="submit" class="bg-green-600 hover:bg-green-700 text-white py-2 px-5 rounded-lg font-medium">
                    Accept Order
                  </button>
                </form>

                <form method="POST" class="inline">
                  <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                  <input type="hidden" name="new_status" value="cancelled">
                  <button type="submit" onclick="return confirm('Reject/cancel this order?')" 
                          class="bg-red-100 hover:bg-red-200 text-red-700 py-2 px-5 rounded-lg font-medium">
                    Reject
                  </button>
                </form>
              <?php endif; ?>

              <?php if (in_array($order['status'], ['confirmed', 'processing'])): ?>
                <form method="POST" class="inline">
                  <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                  <input type="hidden" name="new_status" value="shipped">
                  <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-5 rounded-lg font-medium">
                    Mark as Shipped
                  </button>
                </form>
              <?php endif; ?>

              <?php if ($order['status'] === 'shipped'): ?>
                <form method="POST" class="inline">
                  <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                  <input type="hidden" name="new_status" value="delivered">
                  <button type="submit" class="bg-green-600 hover:bg-green-700 text-white py-2 px-5 rounded-lg font-medium">
                    Mark as Delivered
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>