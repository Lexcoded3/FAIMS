<?php
session_start();
require_once __DIR__ . '../../../config/db.php';

$farmer_id = $_SESSION['id'] ?? 0;
$sql = "SELECT c.name AS crop,
               COUNT(br.id) AS request_count
        FROM buyer_requests br
        JOIN categories c ON br.category_id = c.id
        WHERE br.status = 'open'
        GROUP BY br.category_id
        ORDER BY c.name ASC";

$result = mysqli_query($conn, $sql);

$html = "";

while ($row = mysqli_fetch_assoc($result)) {
    $html .= "
    <div class='flex items-center space-x-3'>
            <div>
                        <p class='text-slate-700 dark:text-navy-100'>
                          {$row['crop']}
                        </p>
                        
            </div>
        </div>
        <p class='font-medium text-success'>Open Requests: {$row['request_count']}</p>
    
    ";
}

echo $html;
?>