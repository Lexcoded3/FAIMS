<?php
// session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../../login.php");
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['id'];
?>
