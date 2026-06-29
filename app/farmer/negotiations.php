<?php
// app/farmer/negotiations.php
session_start();
require_once __DIR__ . '../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../../login.php");
    exit;
}

$farmer_id = $_SESSION['id'];

// Fetch all pending/active negotiations for this farmer
$sql = "
    SELECT n.*, 
           p.name AS product_name, p.price AS original_price, p.unit,
           b.name AS buyer_name, b.phone AS buyer_phone
    FROM negotiations n
    JOIN products p ON n.product_id = p.id
    JOIN users b ON n.buyer_id = b.id
    WHERE n.farmer_id = ?
    ORDER BY n.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$negotiations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Negotiations • FAIMS Farmer</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

  <header class="bg-green-800 text-white p-4">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
      <h1 class="text-xl font-bold">My Negotiations & Offers</h1>
      <a href="index.php" class="hover:underline">Dashboard</a>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    <?php if (empty($negotiations)): ?>
      <div class="text-center py-12 bg-white rounded-xl shadow">
        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
        <p class="text-xl text-gray-600">No negotiations yet.</p>
        <p class="text-gray-500 mt-2">When buyers make offers, they will appear here.</p>
      </div>
    <?php else: ?>
      <div class="space-y-5">
        <?php foreach ($negotiations as $neg): ?>
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
            <div class="flex justify-between items-start mb-3">
              <div>
                <h3 class="font-semibold text-lg">
                  <?= htmlspecialchars($neg['product_name']) ?>
                </h3>
                <p class="text-sm text-gray-600">
                  Buyer: <?= htmlspecialchars($neg['buyer_name']) ?>
                  <?php if ($neg['buyer_phone']): ?>
                    • <a href="tel:<?= $neg['buyer_phone'] ?>" class="text-green-600 hover:underline">
                      <?= $neg['buyer_phone'] ?>
                    </a>
                  <?php endif; ?>
                </p>
              </div>
              <span class="inline-block px-3 py-1 text-xs font-medium rounded-full 
                           <?= $neg['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                <?= ucfirst($neg['status']) ?>
              </span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm mb-4">
              <div>
                <span class="text-gray-600">Offered price:</span><br>
                <span class="font-bold">UGX <?= number_format($neg['proposed_price'], 0) ?> / <?= $neg['unit'] ?? 'kg' ?></span>
              </div>
              <div>
                <span class="text-gray-600">Quantity:</span><br>
                <span class="font-bold"><?= number_format($neg['proposed_quantity']) ?> <?= $neg['unit'] ?? 'kg' ?></span>
              </div>
            </div>

            <?php if ($neg['message']): ?>
              <div class="bg-gray-50 p-3 rounded-lg text-sm text-gray-700 mb-4">
                <strong>Message:</strong> <?= nl2br(htmlspecialchars($neg['message'])) ?>
              </div>
            <?php endif; ?>

            <!-- Farmer actions -->
            <div class="flex gap-3">
              <a href="#" onclick="alert('Reply feature coming soon'); return false;"
                 class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg text-center hover:bg-green-700">
                Reply / Counter-offer
              </a>
              <a href="#" onclick="alert('Accept offer – feature coming soon'); return false;"
                 class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg text-center hover:bg-blue-700">
                Accept
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>