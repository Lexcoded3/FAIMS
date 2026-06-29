<?php
session_start();
$required_role = 'buyer'; // Only buyers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

$buyer_id = $_SESSION['id'];

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = "o.buyer_id = ?";
$params = [$buyer_id];
$types = "i";

if ($filter === 'active') {
    $where .= " AND o.status IN ('pending', 'confirmed', 'processing')";
} elseif ($filter === 'completed') {
    $where .= " AND o.status IN ('delivered', 'completed', 'cancelled')";
}

// Fetch orders
$sql = "
    SELECT o.id, o.order_code, o.amount, o.status, o.created_at, 
           o.delivery_location, o.payment_status,
           COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Orders • FAIMS Buyer</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

  <!-- Header -->
  <header class="bg-white shadow-sm sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
      <a href="index.php" class="flex items-center text-green-700 hover:text-green-800 font-medium">
        <i class="fas fa-arrow-left mr-2"></i> Dashboard
      </a>
      <h1 class="text-xl font-semibold text-gray-900">My Orders</h1>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Filter Tabs -->
    <div class="flex flex-wrap gap-3 mb-6">
      <a href="?filter=all" 
         class="px-5 py-2 rounded-full font-medium <?= $filter === 'all' ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
        All
      </a>
      <a href="?filter=active" 
         class="px-5 py-2 rounded-full font-medium <?= $filter === 'active' ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
        Active
      </a>
      <a href="?filter=completed" 
         class="px-5 py-2 rounded-full font-medium <?= $filter === 'completed' ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
        Completed
      </a>
    </div>

    <?php if (empty($orders)): ?>
      <div class="text-center py-16 bg-white rounded-2xl shadow border border-gray-200">
        <i class="fas fa-box-open text-7xl text-gray-300 mb-6"></i>
        <h2 class="text-2xl font-semibold text-gray-700 mb-3">No orders yet</h2>
        <p class="text-gray-500 mb-6">When you place an order, it will appear here.</p>
        <a href="products.php" class="inline-block bg-green-600 text-white px-6 py-3 rounded-xl hover:bg-green-700">
          Browse Marketplace
        </a>
      </div>
    <?php else: ?>
      <!-- Orders List -->
      <div class="space-y-5 lg:space-y-6">
        <?php foreach ($orders as $order): ?>
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
            <!-- Header -->
            <div class="bg-gray-50 px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b">
              <div>
                <h3 class="font-semibold text-lg">
                  Order #<?= htmlspecialchars($order['order_code']) ?>
                </h3>
                <p class="text-sm text-gray-500">
                  Placed on <?= date('d M Y • H:i', strtotime($order['created_at'])) ?>
                </p>
              </div>
              <div class="flex items-center gap-3">
                <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                             <?= match($order['status']) {
                                 'pending'     => 'bg-yellow-100 text-yellow-800',
                                 'confirmed'   => 'bg-blue-100 text-blue-800',
                                 'processing'  => 'bg-purple-100 text-purple-800',
                                 'delivered'   => 'bg-green-100 text-green-800',
                                 'completed'   => 'bg-green-700 text-white',
                                 'cancelled'   => 'bg-red-100 text-red-800',
                                 default       => 'bg-gray-100 text-gray-800'
                             } ?>">
                  <?= ucfirst($order['status']) ?>
                </span>
                <?php if ($order['payment_status'] === 'paid'): ?>
                  <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full bg-green-700 text-white">
                    Paid
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Body -->
            <div class="p-5">
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-5">
                <div>
                  <p class="text-sm text-gray-600">Items</p>
                  <p class="text-xl font-semibold"><?= $order['item_count'] ?></p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Total Amount</p>
                  <p class="text-xl font-bold text-green-700">
                    UGX <?= number_format($order['amount'], 0) ?>
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Delivery</p>
                  <p class="font-medium">
                    <?= htmlspecialchars($order['delivery_location'] ?: 'Not specified') ?>
                  </p>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex flex-wrap gap-3">
                <a href="od.php?id=<?= $order['id'] ?>" 
                   class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2.5 px-5 rounded-lg text-center font-medium transition">
                  View Details
                </a>

                <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                  <button onclick="alert('Payment – coming soon (MoMo integration)')"
                          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-5 rounded-lg font-medium transition">
                    Pay Now
                  </button>
                <?php endif; ?>

                <?php if (in_array($order['status'], ['pending'])): ?>
                  <button onclick="if(confirm('Cancel this order?')) alert('Order cancelled – feature coming soon')"
                          class="flex-1 bg-red-100 hover:bg-red-200 text-red-700 py-2.5 px-5 rounded-lg font-medium transition">
                    Cancel
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

</body>
</html>