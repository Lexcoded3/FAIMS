<?php
require_once '../config/db.php';

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT * FROM products WHERE 1";
$params = [];
$types = "";

if($search) {
    $sql .= " AND (name LIKE ? OR category LIKE ?)";
    $like = "%$search%";
    $types .= "ss";
    $params[] = $like;
    $params[] = $like;
}

if($status) {
    $sql .= " AND status = ?";
    $types .= "s";
    $params[] = $status;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
while($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);