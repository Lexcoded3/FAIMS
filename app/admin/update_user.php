<?php
session_start();
$required_role = 'admin'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, role=?, location=? WHERE id=?");
$stmt->bind_param("sssssi",
    $data['name'],
    $data['email'],
    $data['phone'],
    $data['role'],
    $data['location'],
    $data['id']
);
$stmt->execute();