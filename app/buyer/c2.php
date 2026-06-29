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
                (order_code, buyer_id, total_amount, status, payment_method, created_at) 
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

<!-- Your existing HTML here -->

<!-- Replace your old Checkout button/form with this -->
<form method="POST" action="cart.php" class="mt-5">
  <input type="hidden" name="action" value="checkout">

  <div class="mb-4">
    <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-2">
      Payment Method
    </label>
    <select name="payment_method" class="form-select w-full rounded-lg border-slate-300 bg-white dark:border-navy-600 dark:bg-navy-700">
      <option value="mobile_money">Mobile Money (MoMo / Airtel)</option>
      <option value="bank">Bank Transfer</option>
      <option value="cod">Cash on Delivery</option>
    </select>
  </div>

  <button type="submit" 
          class="btn w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90"
          <?= empty($cart_items) ? 'disabled' : '' ?>>
    Checkout - UGX <?= number_format($total, 0) ?>
  </button>
</form>

<!-- Success / Error Messages -->
<?php if (!empty($_GET['success'])): ?>
  <div class="mt-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg text-center">
    Order placed successfully! <a href="orders.php" class="underline font-medium">View your orders</a>.
  </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
  <div class="mt-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg text-center">
    <?php
      $msg = $_GET['msg'] ?? '';
      if ($_GET['error'] === 'empty_cart') {
        echo 'Your cart is empty.';
      } elseif ($msg) {
        echo htmlspecialchars($msg);
      } else {
        echo 'Checkout failed. Please try again.';
      }
    ?>
  </div>
<?php endif; ?>