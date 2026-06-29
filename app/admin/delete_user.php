<?php
session_start();
$required_role = 'admin'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(['success' => true]);