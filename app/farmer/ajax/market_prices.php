<?php
require_once __DIR__ . '../../../config/db.php';
session_start();

$farmer_id = $_SESSION['id'] ?? 0;

$period = $_GET['period'] ?? 'daily';

if ($period === 'daily') {

    $sql = "SELECT crop, price 
            FROM market_prices 
            WHERE date = CURDATE()
            ORDER BY crop ASC";

} elseif ($period === 'weekly') {

    $sql = "SELECT crop, AVG(price) AS price 
            FROM market_prices 
            WHERE date >= CURDATE() - INTERVAL 7 DAY
            GROUP BY crop
            ORDER BY crop ASC";

} elseif ($period === 'monthly') {

    $sql = "SELECT crop, AVG(price) AS price 
            FROM market_prices 
            WHERE MONTH(date) = MONTH(CURDATE())
            AND YEAR(date) = YEAR(CURDATE())
            GROUP BY crop
            ORDER BY crop ASC";

} else {
    die("Invalid period");
}

$result = mysqli_query($conn, $sql);

$html = "";

while ($row = mysqli_fetch_assoc($result)) {

    $price = number_format($row['price'], 0);

    $html .= "
        <div class='flex justify-between border-b py-2'>
            <span>{$row['crop']}</span>
            <span>UGX {$price}</span>
        </div>
    ";
}

echo $html;

echo $html;
?>