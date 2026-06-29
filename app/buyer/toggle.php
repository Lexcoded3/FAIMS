<?php
// app/buyer/toggle-favorite.php

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$buyer_id = (int)$_SESSION['id'];

$input = json_decode(file_get_contents('php://input'), true);
$product_id = (int)($input['product_id'] ?? 0);

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Check if already favorited
$sql = "SELECT 1 FROM buyer_favorites WHERE buyer_id = ? AND product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $buyer_id, $product_id);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($exists) {
    $sql = "DELETE FROM buyer_favorites WHERE buyer_id = ? AND product_id = ?";
    $action = 'removed';
} else {
    $sql = "INSERT INTO buyer_favorites (buyer_id, product_id) VALUES (?, ?)";
    $action = 'added';
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $buyer_id, $product_id);
$success = $stmt->execute();

if ($success) {
    echo json_encode([
        'success' => true,
        'action'  => $action
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
exit;