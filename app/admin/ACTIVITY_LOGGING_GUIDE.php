<?php
/**
 * Activity Logging Integration Guide for AgriConnect
 * 
 * This file shows how to integrate the activity logging system across the platform
 */

/*
 * ============================================================================
 * 1. SETUP
 * ============================================================================
 * 
 * Step 1: Run the migration to create the activity_logs table
 *   - Execute: activity_logs_migration.sql in your database
 * 
 * Step 2: Include the helper file in your config
 *   - Add to config/init.php or config/functions.php:
 *   - require_once __DIR__ . '/../pages/activity_log_helper.php';
 * 
 * ============================================================================
 * 2. LOGGING USER ACTIONS
 * ============================================================================
 */

// Example 1: Log user creation (in create_user.php)
// After successfully creating a user:
/*
logActivity(
    $conn,
    $_SESSION['id'],                    // Admin/user who performed action
    'CREATE',                           // Action type
    'users',                            // Table affected
    $user_id,                           // Record ID
    ['email' => $email, 'role' => $role]  // Additional data
);
*/

// Example 2: Log user update
/*
logActivity(
    $conn,
    $_SESSION['id'],
    'UPDATE',
    'users',
    $user_id,
    ['fields_changed' => ['name', 'email']]
);
*/

// Example 3: Log user deletion
/*
logActivity(
    $conn,
    $_SESSION['id'],
    'DELETE',
    'users',
    $user_id,
    ['deleted_user_email' => $email]
);
*/

// Example 4: Log login
/*
logActivity(
    $conn,
    $user_id,
    'LOGIN',
    'users',
    $user_id,
    ['ip' => $_SERVER['REMOTE_ADDR']]
);
*/

// Example 5: Log product creation
/*
logActivity(
    $conn,
    $_SESSION['id'],
    'CREATE',
    'products',
    $product_id,
    ['product_name' => $name, 'category' => $category]
);
*/

// Example 6: Log forum post
/*
logActivity(
    $conn,
    $_SESSION['id'],
    'CREATE',
    'forum_posts',
    $post_id,
    ['category' => $category, 'title' => $title]
);
*/

// Example 7: Log transaction
/*
logActivity(
    $conn,
    $_SESSION['id'],
    'PURCHASE',
    'transactions',
    $transaction_id,
    ['amount' => $amount, 'currency' => 'UGX']
);
*/

/*
 * ============================================================================
 * 3. INTEGRATION CHECKLIST
 * ============================================================================
 */

$integration_checklist = [
    'User Management' => [
        'create_user.php' => 'Log user creation',
        'edit_user.php' => 'Log user updates',
        'delete_user.php' => 'Log user deletion',
        'suspend_user.php' => 'Log user suspension',
        'login.php' => 'Log login attempts',
        'logout.php' => 'Log logout'
    ],
    'Products' => [
        'add_product.php' => 'Log product creation',
        'edit_product.php' => 'Log product updates',
        'delete_product.php' => 'Log product deletion'
    ],
    'Forum' => [
        'create_post.php' => 'Log forum post creation',
        'edit_post.php' => 'Log post edits',
        'delete_post.php' => 'Log post deletion',
        'like_post.php' => 'Log post likes'
    ],
    'Transactions' => [
        'process_transaction.php' => 'Log purchases',
        'refund.php' => 'Log refunds',
        'payment_method.php' => 'Log payment changes'
    ],
    'Extension Services' => [
        'create_bulletin.php' => 'Log bulletin creation',
        'add_training.php' => 'Log training course creation',
        'submit_report.php' => 'Log field report submission'
    ],
    'Admin Settings' => [
        'settings.php' => 'Log settings changes',
        'manage_categories.php' => 'Log category changes',
        'manage_districts.php' => 'Log district changes'
    ]
];

/*
 * ============================================================================
 * 4. ACTION TYPES REFERENCE
 * ============================================================================
 */

$action_types = [
    'LOGIN' => 'User login',
    'LOGOUT' => 'User logout',
    'CREATE' => 'Create new record',
    'UPDATE' => 'Update existing record',
    'DELETE' => 'Delete record',
    'PUBLISH' => 'Publish content',
    'UNPUBLISH' => 'Unpublish content',
    'PURCHASE' => 'Purchase transaction',
    'REFUND' => 'Refund transaction',
    'APPROVE' => 'Approve request/document',
    'REJECT' => 'Reject request/document',
    'SUSPEND' => 'Suspend user/content',
    'UNSUSPEND' => 'Unsuspend user/content',
    'VERIFY' => 'Verify business/document',
    'LIKE' => 'Like content',
    'COMMENT' => 'Add comment',
    'EXPORT' => 'Export data',
    'IMPORT' => 'Import data'
];

/*
 * ============================================================================
 * 5. TABLE NAMES REFERENCE
 * ============================================================================
 */

$table_names = [
    'users',
    'products',
    'categories',
    'forum_posts',
    'forum_comments',
    'transactions',
    'market_prices',
    'extension_bulletins',
    'training_courses',
    'training_lessons',
    'field_reports',
    'cooperatives',
    'notifications',
    'settings',
    'districts'
];

/*
 * ============================================================================
 * 6. QUERYING ACTIVITY LOGS
 * ============================================================================
 */

// Get recent activity by a specific user
/*
$user_logs = getActivityLogs($conn, 50, 0, [
    'user_id' => $user_id
]);
*/

// Get all CREATE actions in the last 7 days
/*
$create_logs = getActivityLogs($conn, 50, 0, [
    'action' => 'CREATE',
    'start_date' => date('Y-m-d', strtotime('-7 days'))
]);
*/

// Get all user table modifications
/*
$user_changes = getActivityLogs($conn, 50, 0, [
    'table' => 'users',
    'action' => ['UPDATE', 'DELETE', 'CREATE']
]);
*/

// Search in activity data
/*
$search_logs = getActivityLogs($conn, 50, 0, [
    'search' => 'email@example.com'
]);
*/

// Get statistics
/*
$stats = getActivityStats($conn, 7);  // Last 7 days
echo "Total activities: " . $stats['total_logs'];
echo "Actions: ";
foreach ($stats['by_action'] as $action) {
    echo $action['action'] . ": " . $action['count'] . ", ";
}
*/

/*
 * ============================================================================
 * 7. DASHBOARD ACCESS
 * ============================================================================
 */

// Activity Logs Dashboard
// URL: /pages/activity_logs.php
// Requires: Admin role
// Features:
// - View all activities with pagination (50 per page)
// - Filter by user, action type, table affected, date range
// - Search in activities
// - View real-time statistics (last 7 days)
// - Export to CSV
// - Sort by date

/*
 * ============================================================================
 * 8. PERFORMANCE NOTES
 * ============================================================================
 */

// Good Practices:
// 1. Always log important actions (create, update, delete)
// 2. Include relevant context in the 'data' JSON field
// 3. Log logins for security auditing
// 4. Archive old logs (>1 year) periodically to maintain performance
// 5. Add indexes on frequently queried columns (already done in migration)
//
// Performance:
// - Query time: <100ms for 50 recent logs
// - CSV export for 10,000 logs: <1 second
// - Pagination ensures manageable data load
// - Indexes on user_id, action, table_name, created_at

/*
 * ============================================================================
 * 9. SECURITY CONSIDERATIONS
 * ============================================================================
 */

// 1. Only admins can view activity logs (auth_check enforces this)
// 2. IP address is logged for security audit trails
// 3. All user input is sanitized before logging
// 4. Sensitive data (passwords) should NEVER be logged
// 5. Logs retention: Consider implementing auto-archive after 1 year
// 6. Regular backup of activity logs table

?>
