<?php
require_once '../config/db.php';

if(!isset($_SESSION['id'])) exit;

$farmer_id = $_SESSION['id'];

if(isset($_POST['add_product'])){

$name = $_POST['name'];
$category = (int)$_POST['category_id'];
$price = (float)$_POST['price'];
$quantity = (int)$_POST['quantity'];

$image = $_FILES['image']['name'];
$tmp = $_FILES['image']['tmp_name'];

$folder = "../uploads/products/";
if(!is_dir($folder)) mkdir($folder,0777,true);

$filename = time().'_'.$image;
move_uploaded_file($tmp,$folder.$filename);

$imagePath = "uploads/products/".$filename;

$stmt = $conn->prepare("
INSERT INTO products (farmer_id,category_id,name,price,quantity,image)
VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("iisdis", $farmer_id, $category, $name, $price, $quantity, $imagePath);
$stmt->execute();
$stmt->close();

header("Location: products.php");
}
