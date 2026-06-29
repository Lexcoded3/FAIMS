<?php
session_start();
require '../includes/auth.php';
require __DIR__ .'../../../config/db.php';

if(!isset($_POST['topic_id'], $_POST['content'])){
    header("Location: ../topic.php?id=".$_POST['topic_id']);
    exit;
}

$topic_id = intval($_POST['topic_id']);
$content = trim($_POST['content']);
$user_id = $_SESSION['id'];

$stmt = $conn->prepare("INSERT INTO forum_replies (topic_id, user_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $topic_id, $user_id, $content);
$stmt->execute();

header("Location: ../topic.php?id=".$topic_id);
exit;
