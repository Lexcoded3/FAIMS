<?php
session_start();
require_once __DIR__ . '../../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

$group_id = (int)($_POST['group_id'] ?? 0);
$user_id  = (int)($_POST['user_id'] ?? 0);

if ($group_id <= 0 || $user_id <= 0) exit;

$stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
exit;