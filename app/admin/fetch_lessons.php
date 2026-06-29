<?php
require '../config/db.php';
// require_once '../config/db.php';

$course_id = (int)$_GET['course_id'];

$stmt = $conn->prepare("
  SELECT id, title, content
  FROM training_lessons
  WHERE course_id = ?
  ORDER BY id ASC
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

$lessons = [];

while ($row = $result->fetch_assoc()) {
  $lessons[] = $row;
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($lessons);
