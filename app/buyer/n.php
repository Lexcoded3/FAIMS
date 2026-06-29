<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'buyer'; // Only buyer allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

$buyer_id = $_SESSION['id'];

// Optional: filter by status (all, pending, accepted, etc.)
$filter = $_GET['filter'] ?? 'all';
$where = "n.buyer_id = ?";
$params = [$buyer_id];
$types = "i";

if ($filter === 'pending') {
    $where .= " AND n.status = 'pending'";
} elseif ($filter === 'accepted') {
    $where .= " AND n.status = 'accepted'";
} elseif ($filter === 'rejected') {
    $where .= " AND n.status = 'rejected'";
}

// Fetch all negotiations for this buyer
$sql = "
    SELECT n.id, n.product_id, n.proposed_price, n.proposed_quantity, n.message, n.status, n.created_at,
           p.name AS product_name, p.price AS original_price, p.unit,
           f.name AS farmer_name, f.phone AS farmer_phone
    FROM negotiations n
    JOIN products p ON n.product_id = p.id
    JOIN users f ON n.farmer_id = f.id
    WHERE $where
    ORDER BY n.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$negotiations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Negotiations • FAIMS Buyer</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

  <!-- Header -->
  <header class="bg-white shadow-sm sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      <a href="index.php" class="text-green-700 hover:text-green-800 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Dashboard
      </a>
      <h1 class="text-xl font-bold text-gray-900">My Negotiations</h1>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    <!-- Filter Tabs -->
    <div class="flex flex-wrap gap-3 mb-6">
      <a href="?filter=all" class="px-5 py-2 rounded-full font-medium <?= $filter === 'all' ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">All</a>
      <a href="?filter=pending" class="px-5 py-2 rounded-full font-medium <?= $filter === 'pending' ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">Pending</a>
      <a href="?filter=accepted" class="px-5 py-2 rounded-full font-medium <?= $filter === 'accepted' ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">Accepted</a>
      <a href="?filter=rejected" class="px-5 py-2 rounded-full font-medium <?= $filter === 'rejected' ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">Rejected</a>
    </div>

    <?php if (empty($negotiations)): ?>
      <div class="text-center py-16 bg-white rounded-2xl shadow border border-gray-200">
        <i class="fas fa-handshake text-7xl text-gray-300 mb-6"></i>
        <h2 class="text-2xl font-semibold text-gray-700 mb-3">No negotiations yet</h2>
        <p class="text-gray-500 mb-8">When you make an offer or receive a counter-offer, it will appear here.</p>
        <a href="products.php" class="inline-block bg-green-600 text-white px-8 py-4 rounded-xl hover:bg-green-700">
          Browse Marketplace
        </a>
      </div>
    <?php else: ?>
      <div class="space-y-5">
        <?php foreach ($negotiations as $neg): ?>
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
              <div>
                <h3 class="font-semibold text-lg">
                  <?= htmlspecialchars($neg['product_name']) ?>
                </h3>
                <p class="text-sm text-gray-600">
                  Farmer: <?= htmlspecialchars($neg['farmer_name']) ?>
                  <?php if ($neg['farmer_phone']): ?>
                    • <a href="tel:<?= htmlspecialchars($neg['farmer_phone']) ?>" class="text-green-600 hover:underline">
                      <?= htmlspecialchars($neg['farmer_phone']) ?>
                    </a>
                  <?php endif; ?>
                </p>
              </div>
              <span class="inline-block px-4 py-1.5 text-sm font-medium rounded-full
                           <?= match($neg['status']) {
                               'pending'   => 'bg-yellow-100 text-yellow-800',
                               'accepted'  => 'bg-green-100 text-green-800',
                               'rejected'  => 'bg-red-100 text-red-800',
                               'withdrawn' => 'bg-gray-100 text-gray-800',
                               default     => 'bg-gray-100 text-gray-800'
                           } ?>">
                <?= ucfirst($neg['status']) ?>
              </span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm mb-4">
              <div>
                <span class="text-gray-600">Offered Price:</span><br>
                <span class="font-bold">UGX <?= number_format($neg['proposed_price'], 0) ?> / <?= htmlspecialchars($neg['unit'] ?? 'kg') ?></span>
              </div>
              <div>
                <span class="text-gray-600">Quantity:</span><br>
                <span class="font-bold"><?= number_format($neg['proposed_quantity']) ?> <?= htmlspecialchars($neg['unit'] ?? 'kg') ?></span>
              </div>
            </div>

            <?php if ($neg['message']): ?>
              <div class="bg-gray-50 p-4 rounded-lg text-sm text-gray-700 mb-4">
                <strong>Your Message:</strong> <?= nl2br(htmlspecialchars($neg['message'])) ?>
              </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="flex gap-3">
              <a href="negotiation-details.php?id=<?= $neg['id'] ?>" 
                 class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2.5 px-5 rounded-lg text-center font-medium transition">
                View Thread / Reply
              </a>

              <?php if ($neg['status'] === 'pending'): ?>
                <button onclick="if(confirm('Withdraw this offer?')) alert('Offer withdrawn – feature coming soon');"
                        class="flex-1 bg-red-100 hover:bg-red-200 text-red-700 py-2.5 px-5 rounded-lg font-medium transition">
                  Withdraw Offer
                </button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>