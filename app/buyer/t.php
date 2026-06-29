<?php
session_start();

file_put_contents(__DIR__ . '/t.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'buyer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$buyer_id = $_SESSION['id'] ?? 0; 
if ($buyer_id == 0) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4' role='alert'>
            <strong>Warning:</strong> No buyer ID in session. Please log in again.
          </div>";
    // You can continue or exit; for now we continue with 0
}

// ────────────────────────────────────────────────
// Fetch active orders
// ────────────────────────────────────────────────
$sql = "SELECT 
            COUNT(*) as order_count, 
            COALESCE(SUM(total_amount), 0) as total_value 
        FROM orders 
        WHERE buyer_id = ? 
        AND status IN ('pending', 'confirmed')";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$activeOrdersCount = (int) ($row['order_count'] ?? 0);
$activeOrdersValue = (float) ($row['total_value'] ?? 0.00);

$stmt->close();
// Fetch wallet data
$sql_wallet = "SELECT balance, held_balance FROM wallets WHERE user_id = ?";
$stmt_wallet = $conn->prepare($sql_wallet);
$stmt_wallet->bind_param("i", $buyer_id);
$stmt_wallet->execute();
$result_wallet = $stmt_wallet->get_result();
$row_wallet = $result_wallet->fetch_assoc();

$walletBalance = (float) ($row_wallet['balance'] ?? 0.00);
// Optional: $heldBalance = (float) ($row_wallet['held_balance'] ?? 0.00);

$stmt_wallet->close();

// Debug (temporary - remove later)
echo "Wallet Debug: Balance = " . number_format($walletBalance) . " UGX";

// ────────────────────────────────────────────────
// Temporary: show debug in browser (remove later)
// ────────────────────────────────────────────────
echo "<div class='bg-gray-100 p-4 mb-6 rounded border border-gray-300 text-sm font-mono'>";
echo "<strong>Debug Info:</strong><br>";
echo "Session buyer_id: " . htmlspecialchars($buyer_id) . "<br>";
echo "Active Orders Count (from DB): $activeOrdersCount<br>";
echo "Active Orders Value (from DB): " . number_format($activeOrdersValue) . " UGX<br>";
echo "Raw row: " . print_r($row, true) . "<br>";
echo "</div>";
?>