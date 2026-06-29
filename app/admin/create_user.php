<?php
session_start();
require_once '../config/auth_check.php';
require_once '../config/db.php';

header('Content-Type: application/json');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $location_name = trim($_POST['location_name'] ?? '');
    $tin = trim($_POST['tin'] ?? '');
    $business_type = trim($_POST['business_type'] ?? '');
    $preferred_districts = trim($_POST['preferred_districts'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email) || empty($role) || empty($phone) || empty($location_name)) {
        throw new Exception('Name, email, role, phone, and location are required');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }
    $stmt->close();

    // Validate role
    $valid_roles = ['admin', 'farmer', 'buyer', 'extension'];
    if (!in_array($role, $valid_roles)) {
        throw new Exception('Invalid role');
    }

    // Validate business_type if provided
    if (!empty($business_type)) {
        $valid_types = ['wholesaler', 'processor', 'exporter', 'retailer', 'cooperative', 'other'];
        if (!in_array($business_type, $valid_types)) {
            throw new Exception('Invalid business type');
        }
    }

    // Generate password
    $temp_password = bin2hex(random_bytes(8));
    $hashed_password = password_hash($temp_password, PASSWORD_BCRYPT);

    // Handle file uploads (optional)
    $image_paths = '';
    $upload_dir = '../uploads/users/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!empty($_FILES['user_images']) && $_FILES['user_images']['error'][0] !== 4) {
        $uploaded_files = [];
        
        for ($i = 0; $i < count($_FILES['user_images']['tmp_name']); $i++) {
            $file = $_FILES['user_images']['tmp_name'][$i];
            $filename = $_FILES['user_images']['name'][$i];
            
            if ($_FILES['user_images']['error'][$i] !== 0) {
                continue;
            }

            // Validate file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file);
            finfo_close($finfo);

            if (!in_array($mime, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP allowed');
            }

            if ($_FILES['user_images']['size'][$i] > 5000000) { // 5MB limit
                throw new Exception('File too large. Max 5MB');
            }

            // Generate unique filename
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $unique_name = uniqid('user_') . '.' . $ext;
            $upload_path = $upload_dir . $unique_name;

            if (!move_uploaded_file($file, $upload_path)) {
                throw new Exception('Failed to upload file');
            }

            $uploaded_files[] = $unique_name;
        }

        if (!empty($uploaded_files)) {
            $image_paths = implode(',', $uploaded_files);
        }
    }

    // Insert user - adjusted to match actual schema
    $stmt = $conn->prepare("
        INSERT INTO users (
            name, email, phone, role, password, 
            company_name, location_name, image_paths, 
            tin, business_type, preferred_districts, 
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ");

    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $status = 'active';
    $stmt->bind_param(
        "sssssssssss", 
        $name, $email, $phone, $role, $hashed_password,
        $company_name, $location_name, $image_paths,
        $tin, $business_type, $preferred_districts
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to create user: ' . $stmt->error);
    }

    $user_id = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => "User $name created successfully",
        'user_id' => $user_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>