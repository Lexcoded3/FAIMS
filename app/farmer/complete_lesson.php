<?php
require_once '../config/db.php';

$lesson = (int)$_GET['id'];
$farmer = (int)$_SESSION['id'];

$stmt = $conn->prepare("
INSERT IGNORE INTO lesson_progress (farmer_id, lesson_id)
VALUES (?, ?)
");
$stmt->bind_param("ii", $farmer, $lesson);
$stmt->execute();
$stmt->close();

header("Location: ".$_SERVER['HTTP_REFERER']);
