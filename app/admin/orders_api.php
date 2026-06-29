<?php
session_start();
$required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'activity_log_helper.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'stats':
        // Get detailed order statistics
        $stats_query = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status='completed' THEN amount ELSE 0 END) as total_revenue,
                AVG(amount) as avg_order_value,
                MAX(amount) as highest_order,
                COUNT(DISTINCT buyer_id) as unique_buyers
            FROM orders
        ";

        $result = $conn->query($stats_query);
        $stats = $result->fetch_assoc();

        // Get daily revenue for last 30 days
        $daily_query = "
            SELECT 
                DATE(created_at) as order_date,
                COUNT(*) as orders,
                SUM(CASE WHEN status='completed' THEN amount ELSE 0 END) as revenue
            FROM orders
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY order_date DESC
        ";

        $daily_result = $conn->query($daily_query);
        $daily_data = [];
        while ($row = $daily_result->fetch_assoc()) {
            $daily_data[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'summary' => $stats,
            'daily_data' => $daily_data
        ]);
        break;

    case 'export':
        // Export orders to CSV
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
        
        $where = "WHERE 1=1";
        if (!empty($status_filter)) {
            $where .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
        }

        $query = "
            SELECT o.id, o.order_code, o.status, o.amount, o.payment_status,
                   o.payment_method, o.delivery_location, o.created_at,
                   u.name as buyer_name, u.email, u.phone
            FROM orders o
            LEFT JOIN users u ON o.buyer_id = u.id
            $where
            ORDER BY o.created_at DESC
        ";

        $result = $conn->query($query);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d_H-i-s') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order ID', 'Order Code', 'Buyer Name', 'Email', 'Phone', 'Amount (UGX)', 'Status', 'Payment Status', 'Payment Method', 'Delivery Location', 'Created Date']);

        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['order_code'],
                $row['buyer_name'],
                $row['email'],
                $row['phone'],
                number_format($row['amount'], 2),
                $row['status'],
                $row['payment_status'],
                $row['payment_method'],
                $row['delivery_location'],
                $row['created_at']
            ]);
        }

        fclose($output);
        exit;
        break;

    case 'bulk_update':
        // Bulk update order status
        $ids = isset($_POST['ids']) ? json_decode($_POST['ids'], true) : [];
        $new_status = isset($_POST['status']) ? $_POST['status'] : '';

        if (empty($ids) || empty($new_status)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
            exit;
        }

        $valid_statuses = ['pending', 'completed', 'cancelled', 'confirmed', 'processing'];
        if (!in_array($new_status, $valid_statuses)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $update_query = "UPDATE orders SET status = ? WHERE id IN ($placeholders)";
        
        $stmt = $conn->prepare($update_query);
        $params = array_merge([$new_status], $ids);
        $types = str_repeat('i', count($ids));
        $types = 's' . $types;
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Log activity
            foreach ($ids as $order_id) {
                logActivity($conn, $_SESSION['id'], 'UPDATE', 'orders', $order_id, [
                    'action' => 'Bulk status update',
                    'new_status' => $new_status
                ]);
            }

            echo json_encode([
                'status' => 'success',
                'message' => count($ids) . ' orders updated',
                'updated_count' => $stmt->affected_rows
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update failed']);
        }

        $stmt->close();
        break;

    case 'order_details':
        // Get detailed order information
        $order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$order_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
            exit;
        }

        $query = "
            SELECT 
                o.*,
                u.name as buyer_name,
                u.email as buyer_email,
                u.phone as buyer_phone,
                u.location as buyer_location,
                f.name as farmer_name,
                f.email as farmer_email
            FROM orders o
            LEFT JOIN users u ON o.buyer_id = u.id
            LEFT JOIN users f ON o.farmer_id = f.id
            WHERE o.id = ?
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            echo json_encode(['status' => 'error', 'message' => 'Order not found']);
            exit;
        }

        // Get order items
        $items_query = "
            SELECT oi.*, p.name as product_name
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ";

        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        $items_stmt->close();

        echo json_encode([
            'status' => 'success',
            'order' => $order,
            'items' => $items
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>