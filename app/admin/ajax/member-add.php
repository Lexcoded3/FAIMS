<?php
session_start();
require_once __DIR__ . '../../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

$group_id = (int)($_POST['group_id'] ?? 0);
$user_id  = (int)($_POST['user_id'] ?? 0);
$role     = $_POST['role'] ?? 'member';

if ($group_id <= 0 || $user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Check if already member
$check = $conn->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
$check->bind_param("ii", $group_id, $user_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'User is already a member']);
    exit;
}
$check->close();

$stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $group_id, $user_id, $role);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success]);
exit;