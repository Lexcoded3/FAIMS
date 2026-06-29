<?php
session_start();
require_once __DIR__ . '../../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
// Read JSON body
$data = json_decode(file_get_contents('php://input'), true);

$id          = (int)($data['id'] ?? 0);
$name        = trim($data['name'] ?? '');
$description = trim($data['description'] ?? '');
$type        = $data['type'] ?? 'other';
$leader_id   = (int)($data['leader_id'] ?? 0);
$location    = trim($data['location'] ?? '');
$is_active   = !empty($data['is_active']) ? 1 : 0;

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Group name is required']);
    exit;
}

if ($id > 0) {
    // Update
    $stmt = $conn->prepare("
        UPDATE groups SET 
            name = ?, description = ?, type = ?, leader_id = ?, location = ?, is_active = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("sssisii", $name, $description, $type, $leader_id, $location, $is_active, $id);
} else {
    // Create
    $stmt = $conn->prepare("
        INSERT INTO groups (name, description, type, leader_id, location, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssisii", $name, $description, $type, $leader_id, $location, $is_active);
}

$success = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Group saved successfully' : 'Failed to save group',
    'id'      => $id > 0 ? $id : $conn->insert_id
]);
exit;