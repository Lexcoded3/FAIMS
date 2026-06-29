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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $item_id  = (int)($_POST['item_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($action === 'update' && $item_id > 0 && $quantity > 0) {
        $sql = "UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id IN (SELECT id FROM carts WHERE buyer_id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $quantity, $item_id, $buyer_id);
        $stmt->execute();
    } elseif ($action === 'remove' && $item_id > 0) {
        $sql = "DELETE FROM cart_items WHERE id = ? AND cart_id IN (SELECT id FROM carts WHERE buyer_id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $item_id, $buyer_id);
        $stmt->execute();
    }

    header("Location: c.php");
    exit;
}

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
        'item_id'   => $row['id'],
        'product'   => [
            'id'    => $row['product_id'],
            'name'  => $row['name'],
            'price' => $price,
            'image' => $row['image'],
            'unit'  => $row['unit']
        ],
        'quantity'  => $row['quantity'],
        'subtotal'  => $subtotal
    ];
    $total += $subtotal;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Cart • FAIMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

  <header class="bg-green-800 text-white p-4 shadow">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
      <h1 class="text-xl font-bold">My Cart</h1>
      <a href="products.php" class="hover:underline flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Continue Shopping
      </a>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    <?php if (empty($cart_items)): ?>
      <div class="text-center py-16 bg-white rounded-2xl shadow">
        <i class="fas fa-shopping-cart text-8xl text-gray-300 mb-6"></i>
        <h2 class="text-2xl font-semibold text-gray-700 mb-3">Your cart is empty</h2>
        <p class="text-gray-500 mb-8">Looks like you haven't added any products yet.</p>
        <a href="products.php" class="inline-block bg-green-600 text-white px-8 py-3 rounded-xl hover:bg-green-700 transition">
          Browse Marketplace
        </a>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Cart Items -->
        <div class="lg:col-span-2 space-y-6">
          <?php foreach ($cart_items as $item): 
            $prod = $item['product'];
          ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col sm:flex-row gap-5">
              <!-- Image -->
              <div class="w-full sm:w-32 h-32 flex-shrink-0">
                <?php if ($prod['image']): ?>
                  <img src="../farmer/<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="w-full h-full object-cover rounded-lg">
                <?php else: ?>
                  <div class="w-full h-full bg-gray-200 rounded-lg flex items-center justify-center">
                    <i class="fas fa-seedling text-4xl text-gray-300"></i>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Details -->
              <div class="flex-1">
                <h3 class="font-semibold text-lg mb-1">
                  <?= htmlspecialchars($prod['name']) ?>
                </h3>
                <p class="text-green-700 font-bold mb-2">
                  UGX <?= number_format($prod['price'], 0) ?> / <?= htmlspecialchars($prod['unit'] ?? 'kg') ?>
                </p>

                <div class="flex items-center gap-4 mb-3">
                  <form method="POST" class="flex items-center gap-2">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                    <label class="text-sm text-gray-600">Qty:</label>
                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" class="w-16 text-center border rounded px-2 py-1">
                    <button type="submit" class="text-sm text-green-600 hover:underline">Update</button>
                  </form>

                  <form method="POST" class="text-sm">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                    <button type="submit" class="text-red-600 hover:underline flex items-center gap-1">
                      <i class="fas fa-trash-alt"></i> Remove
                    </button>
                  </form>
                </div>

                <p class="text-right font-medium">
                  Subtotal: UGX <?= number_format($item['subtotal'], 0) ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-8">
            <h2 class="text-xl font-bold mb-6">Order Summary</h2>
            <div class="space-y-4 mb-6">
              <div class="flex justify-between text-gray-700">
                <span>Subtotal (<?= count($cart_items) ?> items)</span>
                <span class="font-medium">UGX <?= number_format($total, 0) ?></span>
              </div>
              <div class="flex justify-between text-gray-700">
                <span>Delivery Fee</span>
                <span class="text-green-600">To be calculated</span>
              </div>
              <div class="border-t pt-4 flex justify-between text-lg font-bold">
                <span>Total</span>
                <span>UGX <?= number_format($total, 0) ?></span>
              </div>
            </div>

            <button onclick="alert('Checkout flow coming soon – proceed to payment?')"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3.5 px-6 rounded-xl transition">
              Proceed to Checkout
            </button>

            <p class="text-center text-sm text-gray-500 mt-4">
              Continue shopping? <a href="products.php" class="text-green-600 hover:underline">Browse Marketplace</a>
            </p>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>