<?php

require_once __DIR__ . '../../../config/db.php';
session_start();

$farmer_id = (int)($_SESSION['id'] ?? 0);
$period = $_GET['period'] ?? 'weekly';

switch ($period) {
    case 'daily':
        $title = 'Daily Order Chart';
        $condition = "DATE(created_at)=CURDATE()";
        break;

    case 'monthly':
        $title = 'Monthly Order Chart';
        $condition = "MONTH(created_at)=MONTH(CURDATE())
                      AND YEAR(created_at)=YEAR(CURDATE())";
        break;

    case 'yearly':
        $title = 'Yearly Order Chart';
        $condition = "YEAR(created_at)=YEAR(CURDATE())";
        break;

    default:
        $title = 'Weekly Order Chart';
        $condition = "YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)";
}

$stmt = $conn->prepare("
  SELECT COALESCE(SUM(amount),0) AS total
  FROM orders
  WHERE farmer_id = ?
  AND status='completed'
  AND $condition
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$q = $stmt->get_result();
$row = $q->fetch_assoc();

echo json_encode([
  'title' => $title,
  'total' => number_format($row['total'])
]);
?>