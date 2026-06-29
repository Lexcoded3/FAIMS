<?php
require_once '../../../config/db.php';
session_start();

$buyer_id = $_SESSION['id'] ?? 0;

$buyer_id = $_SESSION['user_id'];

$category_id = $_POST['category_id'];
$quantity = $_POST['quantity'];
$price_offer = $_POST['price_offer'];
$location = $_POST['location'];

$sql = "INSERT INTO buyer_requests
        (buyer_id, category_id, quantity, price_offer, location)
        VALUES (?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iiids",
    $buyer_id,
    $category_id,
    $quantity,
    $price_offer,
    $location
);

mysqli_stmt_execute($stmt);

header("Location: marketplace.php");
exit;
?>