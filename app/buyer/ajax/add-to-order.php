<?php
// app/buyer/add-to-order.php

session_start();
require_once __DIR__ . '../../../config/db.php';
require_once __DIR__ . '../../../helpers/cart.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../products.php");
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity   = (int)($_POST['quantity'] ?? 1); // add quantity input later

if ($product_id <= 0 || $quantity <= 0) {
    header("Location: ../product_details.php?id=$product_id&error=invalid");
    exit;
}

$buyer_id = $_SESSION['id'];

if (add_to_cart($conn, $buyer_id, $product_id, $quantity)) {
    header("Location: ../cart.php?success=added");
} else {
    header("Location: ../product_details.php?id=$product_id&error=failed");
}

exit;