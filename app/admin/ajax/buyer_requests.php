<?php
session_start();
require_once __DIR__ . '../../../config/db.php';

$farmer_id = $_SESSION['id'] ?? 0;

$period = $_GET['period'] ?? 'daily';

$sql = "SELECT br.id,
               c.name AS crop,
               br.quantity,
               br.price_offer,
               br.location,
               u.name AS buyer
        FROM buyer_requests br
        JOIN categories c ON br.category_id = c.id
        JOIN users u ON br.buyer_id = u.id
        WHERE br.status = 'open'
        ORDER BY br.created_at DESC";

$result = mysqli_query($conn, $sql);

$html = "";

while ($row = mysqli_fetch_assoc($result)) {

    $price = number_format($row['price_offer'], 0);

    $html .= "
        
            <div class='flex items-center space-x-3'>
                <div class='avatar'>
                        <img class='rounded-full' src='../images/avatar/avatar-20.jpg' alt='avatar'>
                </div>
            <div>
                        <p class='text-slate-700 dark:text-navy-100'>
                          {$row['buyer']}
                        </p>
                        <p class='text-xs text-slate-400 line-clamp-1 dark:text-navy-200'>
                          {$row['crop']} - {$row['quantity']} KG
                        </p>
            </div>
        </div>
        <p class='font-medium text-success'>UGX {$price}</p>
        
    ";
}

echo $html;

?>
