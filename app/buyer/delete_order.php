<?php
session_start();
require_once '../config/db.php';

$id = (int)$_GET['id'];

$stmt = $conn->prepare("UPDATE orders SET status='completed' WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: orders.php");
