<?php
// app/farmer/ajax/notifications-fetch.php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'farmer') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$farmer_id = (int)$_SESSION['id'];

// Get total unread count (for badge)
$sql_unread = "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt_unread = $conn->prepare($sql_unread);
$stmt_unread->bind_param("i", $farmer_id);
$stmt_unread->execute();
$total = $stmt_unread->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_unread->close();

// Get recent notifications (focus on negotiations + others)
$sql = "
    SELECT id, title, message, created_at, is_read, reference_id, reference_type
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$alerts = [];
foreach ($rows as $row) {
    $alerts[] = [
        'id'      => $row['id'],
        'title'   => htmlspecialchars($row['title']),
        'message' => htmlspecialchars($row['message']),
        'time'    => date('h:i A • d M', strtotime($row['created_at'])),
        'is_read' => $row['is_read'],
        'type'    => $row['reference_type'] ?? 'system',
        'link'    => $row['reference_type'] === 'negotiation' 
                     ? "negotiations.php?id={$row['reference_id']}" 
                     : null
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'total'  => $total,
    'alerts' => $alerts
]);
exit;