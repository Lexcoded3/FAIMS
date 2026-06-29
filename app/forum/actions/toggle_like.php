<?php
session_start();
require '../includes/auth.php';
require __DIR__ .'../../../config/db.php';

header('Content-Type: application/json');

if (!isset($_POST['topic_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$topic_id = intval($_POST['topic_id']);
$user_id = $_SESSION['id'];

// Check if already liked
$stmt = $conn->prepare("SELECT id FROM forum_topic_likes WHERE topic_id=? AND user_id=?");
$stmt->bind_param("ii", $topic_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Unlike
    $stmt = $conn->prepare("DELETE FROM forum_topic_likes WHERE topic_id=? AND user_id=?");
    $stmt->bind_param("ii", $topic_id, $user_id);
    $stmt->execute();
    $liked = false;
} else {
    // Like
    $stmt = $conn->prepare("INSERT INTO forum_topic_likes (topic_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $topic_id, $user_id);
    $stmt->execute();
    $liked = true;
}

// Get updated like count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM forum_topic_likes WHERE topic_id=?");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'total_likes' => $data['total']
]);
exit;
