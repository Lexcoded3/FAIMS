<?php
// negotiate-submit.php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Security: must be logged in buyer
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

$buyer_id = (int)$_SESSION['id'];
$buyer_name = $_SESSION['name'] ?? 'Buyer'; // fallback

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../products.php?error=invalid_request_method");
    exit;
}

// ────────────────────────────────────────
// Get & validate form data (support both POST and JSON)
// ────────────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$product_id     = (int)($data['product_id'] ?? 0);
$offered_price  = (float)($data['offered_price'] ?? 0);
$quantity       = (float)($data['quantity'] ?? 0);
$message        = trim($data['message'] ?? '');

if ($product_id <= 0 || $offered_price <= 0 || $quantity <= 0) {
    header("Location: ../product_details.php?id=$product_id&error=invalid_input");
    exit;
}

// ────────────────────────────────────────
// Get farmer_id + product name + validation
// ────────────────────────────────────────
$sql = "SELECT farmer_id, name AS product_name FROM products WHERE id = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed (product check): " . $conn->error);
    header("Location: ../product_details.php?id=$product_id&error=system_error");
    exit;
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: ../products.php?error=product_not_found_or_inactive");
    exit;
}

$farmer_id     = (int)$product['farmer_id'];
$product_name  = $product['product_name'];

// ────────────────────────────────────────
// Insert negotiation
// ────────────────────────────────────────
$sql = "
    INSERT INTO negotiations
    (
        product_id,
        buyer_id,
        farmer_id,
        initiator,
        proposed_quantity,
        proposed_price,
        proposed_unit,
        message,
        status,
        created_at
    )
    VALUES (?, ?, ?, 'buyer', ?, ?, 'kg', ?, 'pending', NOW())
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed (insert): " . $conn->error);
    header("Location: ../product_details.php?id=$product_id&error=system_error");
    exit;
}

$stmt->bind_param(
    "iiidds",
    $product_id,
    $buyer_id,
    $farmer_id,
    $quantity,         // proposed_quantity
    $offered_price,    // proposed_price
    $message
);

if ($stmt->execute()) {
    $negotiation_id = $conn->insert_id;

    // Create notification for farmer
    $notif_title = "New Offer on " . $product_name;
    $notif_message = "Buyer " . htmlspecialchars($buyer_name) . " offered UGX " . number_format($offered_price, 0) .
                     " for " . $quantity . " kg. Check your negotiations.";

    $sql_notif = "
        INSERT INTO notifications
        (user_id, type, title, message, reference_id, reference_type, created_at)
        VALUES (?, 'new_offer', ?, ?, ?, ?, NOW())
    ";
    $stmt_notif = $conn->prepare($sql_notif);
    if ($stmt_notif) {
        $ref_type = 'negotiation';

        $stmt_notif->bind_param(
            "issis",
            $farmer_id,
            $notif_title,
            $notif_message,
            $negotiation_id,
            $ref_type
        );
        $stmt_notif->execute();
        $stmt_notif->close();
    } else {
        error_log("Notification prepare failed: " . $conn->error);
    }

    header("Location: ../product_details.php?id=$product_id&success=offer_sent");
    exit;
} else {
    error_log("Negotiation insert failed: " . $stmt->error);
    header("Location: ../product_details.php?id=$product_id&error=insert_failed");
    exit;
}
$stmt->close();
$conn->close();
exit;