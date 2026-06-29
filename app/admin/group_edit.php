<?php
// app/admin/group-edit.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$group = [];
if ($group_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $group = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Include leaders too if needed
$leaders = $conn->query("SELECT id, name FROM users WHERE role IN ('farmer', 'buyer') ORDER BY name")->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'group' => $group,
    'leaders' => $leaders
]);