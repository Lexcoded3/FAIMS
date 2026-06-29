<?php
// toggle-favorite.php
header('Content-Type: application/json');
session_start();
file_put_contents(__DIR__.'/fav_debug.txt', file_get_contents('php://input'));
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = (int)($data['product_id'] ?? 0);
$buyer_id   = $_SESSION['id'];

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

// Check if already favorited
$sql = "SELECT id FROM buyer_favorites WHERE buyer_id = ? AND product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $buyer_id, $product_id);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($exists) {
    // Remove
    $sql = "DELETE FROM buyer_favorites WHERE buyer_id = ? AND product_id = ?";
    $action = 'removed';
} else {
    // Add
    $sql = "INSERT INTO buyer_favorites (buyer_id, product_id, created_at) VALUES (?, ?, NOW())";
    $action = 'added';
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $buyer_id, $product_id);
$success = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $success,
    'action'  => $action
]);