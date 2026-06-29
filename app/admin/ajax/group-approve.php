<?php
session_start();
require_once __DIR__ . '../../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) exit;

$admin_id = $_SESSION['id'];

$stmt = $conn->prepare("
    UPDATE groups 
    SET approved = 1, approved_by = ?, approved_at = NOW()
    WHERE id = ?
");
$stmt->bind_param("ii", $admin_id, $id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
exit;