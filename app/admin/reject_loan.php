<?php
// reject_loan.php

header('Content-Type: application/json');
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

session_start();

if (!isset($_SESSION['Id']) || !in_array($_SESSION['role'], ['admin', 'extension'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$loan_id = isset($_POST['loan_id']) ? (int)$_POST['loan_id'] : 0;
$reason  = isset($_POST['reason'])  ? trim($_POST['reason']) : '';

if ($loan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid loan ID']);
    exit;
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        SELECT status FROM loans WHERE id = ?
    ");
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $loan = $result->fetch_assoc();

    if (!$loan) {
        throw new Exception("Loan not found");
    }

    if ($loan['status'] !== 'pending') {
        throw new Exception("Only pending loans can be rejected");
    }

    $today = date('Y-m-d H:i:s');
    $admin_id = $_SESSION['id'];

    $update = $conn->prepare("
        UPDATE loans SET
            status = 'rejected',
            rejection_reason = ?,
            approved_by = ?,
            approved_at = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $update->bind_param("sisi", $reason, $admin_id, $today, $loan_id);

    if (!$update->execute()) {
        throw new Exception("Update failed: " . $update->error);
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Loan rejected successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();