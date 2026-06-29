<?php
session_start();
$required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'activity_log_helper.php';

// Build filters from GET parameters
$filters = [
    'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
    'action' => isset($_GET['action']) ? $_GET['action'] : null,
    'table' => isset($_GET['table']) ? $_GET['table'] : null,
    'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : null,
    'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : null,
    'search' => isset($_GET['search']) ? $_GET['search'] : null,
];

// Fetch all matching logs (without pagination limit)
$logs = getActivityLogs($conn, 10000, 0, $filters);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, [
    'Date',
    'Time',
    'User Name',
    'User Role',
    'User Email',
    'Action',
    'Table Affected',
    'Record ID',
    'IP Address',
    'Additional Data'
]);

// Write data rows
foreach ($logs as $log) {
    $created_at = new DateTime($log['created_at']);
    
    fputcsv($output, [
        $created_at->format('Y-m-d'),
        $created_at->format('H:i:s'),
        $log['user_name'] ?? 'Unknown',
        $log['user_role'] ?? '',
        $log['user_email'] ?? '',
        $log['action'],
        $log['table_name'],
        $log['record_id'] ?? '',
        $log['ip_address'] ?? '',
        $log['data'] ?? ''
    ]);
}

fclose($output);
exit;
?>
