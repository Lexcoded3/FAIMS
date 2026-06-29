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
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($product['name']) ?> • FAIMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans antialiased">

  <!-- Top Bar -->
  <header class="bg-white shadow-sm sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
      <a href="products.php" class="flex items-center text-green-700 hover:text-green-800 font-medium">
        <i class="fas fa-arrow-left mr-2"></i> Back to Marketplace
      </a>
      <h1 class="text-lg font-semibold text-gray-900 truncate max-w-[60%]">
        <?= htmlspecialchars($product['name']) ?>
      </h1>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

      <!-- Product Image & Gallery -->
      <div class="space-y-4">
        <div class="bg-white rounded-2xl shadow overflow-hidden border border-gray-200">
          <?php if ($product['image']): ?>
            <img src="../farmer/<?= htmlspecialchars($product['image']) ?>" 
                 alt="<?= htmlspecialchars($product['name']) ?>" 
                 class="w-full h-80 lg:h-[520px] object-cover">
          <?php else: ?>
            <div class="h-80 lg:h-[520px] bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
              <i class="fas fa-seedling text-8xl text-gray-300"></i>
            </div>
          <?php endif; ?>
        </div>

        <!-- Trust signal -->
        <div class="text-sm text-gray-500 text-center">
          Posted <?= date('d M Y', strtotime($product['created_at'])) ?> • 
          <span class="text-green-600 font-medium">Active</span>
        </div>
      </div>

      <!-- Product Info -->
      <div class="space-y-6 lg:space-y-8">

        <!-- Title, Price, Availability -->
        <div class="space-y-4">
          <h1 class="text-3xl lg:text-4xl font-bold text-gray-900">
            <?= htmlspecialchars($product['name']) ?>
          </h1>

          <div class="flex items-baseline gap-3">
            <span class="text-4xl lg:text-5xl font-extrabold text-green-700">
              UGX <?= number_format($product['price'], 0) ?>
            </span>
            <span class="text-xl text-gray-600 font-medium">
              per <?= htmlspecialchars($product['unit'] ?? 'kg') ?>
            </span>
          </div>

          <div class="flex flex-wrap gap-6 text-base">
            <div>
              <span class="font-semibold text-gray-700">Available:</span><br>
              <span class="text-xl font-bold text-gray-900">
                <?= number_format($product['quantity']) ?> <?= htmlspecialchars($product['unit'] ?? 'kg') ?>
              </span>
            </div>
            <?php if (!empty($product['harvest_date'])): ?>
            <div>
              <span class="font-semibold text-gray-700">Harvest:</span><br>
              <?= date('d M Y', strtotime($product['harvest_date'])) ?>
            </div>
            <?php endif; ?>
          </div>

          <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-green-100 text-green-800 rounded-full text-sm font-medium">
            <i class="fas fa-tag"></i>
            <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
          </div>
        </div>

        <!-- Farmer Card -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
          <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
            <i class="fas fa-user-circle text-green-600"></i>
            Farmer / Seller
          </h3>
          <div class="space-y-3 text-gray-700">
            <p><strong>Name:</strong> <?= htmlspecialchars($product['farmer_name'] ?? 'N/A') ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($product['farmer_location'] ?? 'N/A') ?></p>

            <?php if (!empty($product['farmer_phone'])): ?>
            <p>
              <strong>Contact:</strong> 
              <a href="tel:<?= htmlspecialchars($product['farmer_phone']) ?>" 
                 class="text-green-600 hover:underline">
                <?= htmlspecialchars($product['farmer_phone']) ?>
              </a>
            </p>
            <?php endif; ?>

            <div class="pt-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-medium">
               Verified Farmer
              </span>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="prose prose-green max-w-none">
          <h3 class="text-xl font-semibold mb-3">Product Description</h3>
          <?= nl2br(htmlspecialchars($product['description'] ?: 'No additional description provided.')) ?>
        </div>

        <!-- Call to Action Buttons -->
        <div class="pt-6 border-t border-gray-200 flex flex-col sm:flex-row gap-4">
          <!-- Negotiate Button (opens modal) -->
          <button 
            @click="$refs.negotiateModal.showModal()"
            class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3.5 px-6 rounded-xl transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 flex items-center justify-center gap-2 shadow-sm">
            <i class="fas fa-handshake"></i> Negotiate / Make Offer
          </button>

          <!-- Add to Order / Cart -->
          <button 
            onclick="alert('Added to your order – proceed to checkout?')"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3.5 px-6 rounded-xl transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center gap-2 shadow-sm">
            <i class="fas fa-cart-plus"></i> Add to Order
          </button>

          <!-- Favorite -->
          <button 
            class="size-14 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 transition shadow-sm">
            <i class="far fa-heart text-2xl text-red-500 hover:text-red-600"></i>
          </button>
        </div>
      </div>
    </div>
  </main>

  <!-- Negotiation Modal -->
  <dialog id="negotiateModal" class="p-0 bg-transparent backdrop:bg-black/60">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden">
      <div class="bg-green-700 text-white px-6 py-4 flex items-center justify-between">
        <h3 class="text-xl font-semibold">Negotiate with Farmer</h3>
        <button @click="$refs.negotiateModal.close()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
      </div>

      <form method="POST" action="negotiate-submit.php" class="p-6 space-y-5">
        <input type="hidden" name="product_id" value="<?= $product_id ?>">

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Your Offer Price (UGX per <?= htmlspecialchars($product['unit'] ?? 'kg') ?>)</label>
          <input type="number" name="offered_price" min="1" step="0.01" required
                 class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Quantity You Want (<?= htmlspecialchars($product['unit'] ?? 'kg') ?>)</label>
          <input type="number" name="quantity" min="1" max="<?= $product['quantity'] ?>" required
                 class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Message to Farmer (optional)</label>
          <textarea name="message" rows="4" 
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                    placeholder="e.g. Can you deliver to Kampala next week?"></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="$refs.negotiateModal.close()"
                  class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium">
            Cancel
          </button>
          <button type="submit"
                  class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
            Send Offer
          </button>
        </div>
      </form>
    </div>
  </dialog>

</body>
</html>