<?php
session_start();
$required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'activity_log_helper.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$order_id) {
    header("Location: orders.php?error=invalid_order");
    exit;
}

// Get order details
$order_stmt = $conn->prepare("
    SELECT o.*, u.name as buyer_name, u.email
    FROM orders o
    LEFT JOIN users u ON o.buyer_id = u.id
    WHERE o.id = ?
");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();
$order_stmt->close();

if (!$order) {
    header("Location: orders.php?error=order_not_found");
    exit;
}

// Update order status and completed_at timestamp
$update_stmt = $conn->prepare("
    UPDATE orders 
    SET status = 'completed', 
        completed_at = NOW(),
        payment_status = 'paid'
    WHERE id = ?
");
$update_stmt->bind_param("i", $order_id);

if ($update_stmt->execute()) {
    // Log activity
    logActivity($conn, $_SESSION['id'], 'UPDATE', 'orders', $order_id, [
        'action' => 'Mark order as completed',
        'previous_status' => $order['status'],
        'new_status' => 'completed',
        'payment_status' => $order['payment_status'],
        'order_code' => $order['order_code'],
        'amount' => $order['amount'],
        'buyer' => $order['buyer_name']
    ]);
    
    header("Location: orders.php?success=order_completed&order=" . $order['order_code']);
} else {
    header("Location: orders.php?error=update_failed");
}

$update_stmt->close();
?>