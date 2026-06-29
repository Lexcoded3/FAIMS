<?php
// approve_loan.php

header('Content-Type: application/json');
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'admin'; // Only admins allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

session_start();

if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'extension'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$loan_id = isset($_POST['loan_id']) ? (int)$_POST['loan_id'] : 0;

if ($loan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid loan ID']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1. Get current loan data
    $stmt = $conn->prepare("
        SELECT 
            status, approved_amount, interest_rate, duration_months, product_id
        FROM loans
        WHERE id = ?
    ");
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $loan = $result->fetch_assoc();

    if (!$loan) {
        throw new Exception("Loan not found");
    }

    if ($loan['status'] !== 'pending') {
        throw new Exception("Loan is not in pending status");
    }

    // 2. Copy interest rate & other defaults from product if missing
    if (!$loan['interest_rate'] || $loan['interest_rate'] <= 0) {
        $pstmt = $conn->prepare("SELECT interest_rate_annual FROM loan_products WHERE id = ?");
        $pstmt->bind_param("i", $loan['product_id']);
        $pstmt->execute();
        $presult = $pstmt->get_result();
        if ($prow = $presult->fetch_assoc()) {
            $loan['interest_rate'] = $prow['interest_rate_annual'];
        }
    }

    // 3. Set approval data
    $approved_amount = $loan['approved_amount'] ?: $loan['requested_amount']; // fallback
    $today = date('Y-m-d H:i:s');
    $admin_id = $_SESSION['id'];

    $update = $conn->prepare("
        UPDATE loans SET
            status = 'approved',
            approved_amount = ?,
            interest_rate = ?,
            approved_by = ?,
            approved_at = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $update->bind_param("ddisi", $approved_amount, $loan['interest_rate'], $admin_id, $today, $loan_id);

    if (!$update->execute()) {
        throw new Exception("Update failed: " . $update->error);
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Loan approved successfully',
        'approved_amount' => $approved_amount
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();