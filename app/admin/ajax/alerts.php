<?php
session_start();
require_once __DIR__ . '../../../config/db.php';

$farmer_id = $_SESSION['id'] ?? 0; // adjust if your session key is different

// 1️⃣ New Orders
$orders = $conn->query("SELECT o.id AS alert_id, CONCAT('New order #', o.id, ' from ', u.name) AS message, o.created_at 
                        FROM orders o
                        JOIN users u ON u.id = o.buyer_id
                        WHERE o.farmer_id = $farmer_id AND o.status='pending'");

$order_alerts = [];
while($row = $orders->fetch_assoc()) {
    $order_alerts[] = [
        'type' => 'order',
        'message' => $row['message'],
        'time' => date('h:i A', strtotime($row['created_at']))
    ];
}

// 2️⃣ Out-of-stock Products
$stock = $conn->query("SELECT id AS alert_id, CONCAT(name, ' is out of stock') AS message, created_at 
                       FROM products 
                       WHERE farmer_id = $farmer_id AND status = 'out'");

$stock_alerts = [];
while($row = $stock->fetch_assoc()) {
    $stock_alerts[] = [
        'type' => 'stock',
        'message' => $row['message'],
        'time' => date('h:i A', strtotime($row['created_at'] ?? date('Y-m-d H:i:s')))
    ];
}
// 3️⃣ Comments and Likes on Farmer's Posts
$comments_likes = $conn->query("
    SELECT c.id AS alert_id, CONCAT(u.name, ' commented on your post: ', p.title) AS message, c.created_at
    FROM forum_replies c
    JOIN posts p ON p.id = c._id
    JOIN users u ON u.id = c.user_id
    WHERE p.user_id = $farmer_id
    UNION
    SELECT l.id AS alert_id, CONCAT(u.name, ' liked your post: ', p.title) AS message, l.created_at
    FROM forum_topic_likes l
    JOIN posts p ON p.id = l.post_id
    JOIN users u ON u.id = l.user_id
    WHERE p.user_id = $farmer_id
");

$comment_like_alerts = [];
while($row = $comments_likes->fetch_assoc()) {
    $comment_like_alerts[] = [
        'type' => 'social',
        'message' => $row['message'],
        'time' => date('h:i A', strtotime($row['created_at']))
    ];
}
// Combine all alerts
$all_alerts = array_merge($order_alerts, $stock_alerts);

// JSON response
echo json_encode([
    'total' => count($all_alerts),
    'alerts' => $all_alerts
]);
?>