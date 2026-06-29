<?php
session_start();
$required_role = 'admin'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
echo json_encode($result->fetch_assoc());