<?php
/**
 * Activity Logging Helper for AgriConnect
 * Logs user actions across the platform
 * 
 * Usage: logActivity($conn, $user_id, 'action_type', 'table_affected', $record_id, $additional_data);
 */

function logActivity($conn, $user_id, $action, $table, $record_id = null, $data = null) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent, data, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            error_log("Activity log error: " . $conn->error);
            return false;
        }
        
        $data_json = $data ? json_encode($data) : null;
        $stmt->bind_param("ississs", $user_id, $action, $table, $record_id, $ip_address, $user_agent, $data_json);
        
        if (!$stmt->execute()) {
            error_log("Activity log insert error: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        return true;
        
    } catch (Exception $e) {
        error_log("Activity log exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Get activity logs with pagination
 */
function getActivityLogs($conn, $limit = 50, $offset = 0, $filters = []) {
    $where = "WHERE 1=1";
    $params = [];
    $types = "";
    
    // Filter by user_id
    if (!empty($filters['user_id'])) {
        $where .= " AND user_id = ?";
        $params[] = $filters['user_id'];
        $types .= "i";
    }
    
    // Filter by action type
    if (!empty($filters['action'])) {
        $where .= " AND action = ?";
        $params[] = $filters['action'];
        $types .= "s";
    }
    
    // Filter by table
    if (!empty($filters['table'])) {
        $where .= " AND table_name = ?";
        $params[] = $filters['table'];
        $types .= "s";
    }
    
    // Filter by date range
    if (!empty($filters['start_date'])) {
        $where .= " AND created_at >= ?";
        $params[] = $filters['start_date'] . " 00:00:00";
        $types .= "s";
    }
    
    if (!empty($filters['end_date'])) {
        $where .= " AND created_at <= ?";
        $params[] = $filters['end_date'] . " 23:59:59";
        $types .= "s";
    }
    
    // Search in data field
    if (!empty($filters['search'])) {
        $where .= " AND (data LIKE ? OR action LIKE ?)";
        $search = "%{$filters['search']}%";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    $query = "
        SELECT al.id, al.user_id, al.action, al.table_name, al.record_id, 
               al.ip_address, al.created_at, al.data,
               u.name as user_name, u.role as user_role, u.email as user_email
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        $where
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Activity log query error: " . $conn->error);
        return [];
    }
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = [];
    
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $stmt->close();
    return $logs;
}

/**
 * Get total count for pagination
 */
function getActivityLogsCount($conn, $filters = []) {
    $where = "WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($filters['user_id'])) {
        $where .= " AND user_id = ?";
        $params[] = $filters['user_id'];
        $types .= "i";
    }
    
    if (!empty($filters['action'])) {
        $where .= " AND action = ?";
        $params[] = $filters['action'];
        $types .= "s";
    }
    
    if (!empty($filters['table'])) {
        $where .= " AND table_name = ?";
        $params[] = $filters['table'];
        $types .= "s";
    }
    
    if (!empty($filters['start_date'])) {
        $where .= " AND created_at >= ?";
        $params[] = $filters['start_date'] . " 00:00:00";
        $types .= "s";
    }
    
    if (!empty($filters['end_date'])) {
        $where .= " AND created_at <= ?";
        $params[] = $filters['end_date'] . " 23:59:59";
        $types .= "s";
    }
    
    if (!empty($filters['search'])) {
        $where .= " AND (data LIKE ? OR action LIKE ?)";
        $search = "%{$filters['search']}%";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    $query = "SELECT COUNT(*) as total FROM activity_logs $where";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        return 0;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['total'] ?? 0;
}

/**
 * Get activity statistics
 */
function getActivityStats($conn, $days = 7) {
    $date_from = date('Y-m-d H:i:s', strtotime("-$days days"));
    
    $queries = [
        'total_logs' => "SELECT COUNT(*) as count FROM activity_logs WHERE created_at >= ?",
        'by_action' => "SELECT action, COUNT(*) as count FROM activity_logs WHERE created_at >= ? GROUP BY action ORDER BY count DESC",
        'by_user' => "SELECT user_id, u.name, COUNT(*) as count FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id WHERE al.created_at >= ? GROUP BY user_id ORDER BY count DESC LIMIT 10"
    ];
    
    $stats = [];
    
    foreach ($queries as $key => $query) {
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("s", $date_from);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($key === 'total_logs') {
                $row = $result->fetch_assoc();
                $stats[$key] = $row['count'] ?? 0;
            } else {
                $stats[$key] = [];
                while ($row = $result->fetch_assoc()) {
                    $stats[$key][] = $row;
                }
            }
            
            $stmt->close();
        }
    }
    
    return $stats;
}
?>
