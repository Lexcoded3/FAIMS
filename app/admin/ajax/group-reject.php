<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) exit;

$stmt = $conn->prepare("DELETE FROM groups WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
exit;