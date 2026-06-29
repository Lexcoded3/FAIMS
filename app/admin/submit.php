<?php
// submit.php

// 1. Headers
header('Content-Type: application/json');
session_start();
require 'includes/auth.php';
require __DIR__ .'../../config/db.php';


// 3. Helper Functions
function sendResponse($success, $message, $data = []) {
    echo json_encode(['status' => $success ? 'success' : 'error', 'message' => $message, 'data' => $data]);
    exit;
}

// 4. Main Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- A. Validate Text Inputs ---
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $district = trim($_POST['location'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Basic Validation
    if (empty($name)) sendResponse(false, 'Name is required.');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) sendResponse(false, 'Valid email is required.');
    if (!in_array($role, ['admin', 'farmer', 'buyer', 'extension'])) sendResponse(false, 'Invalid role selected.');
    if (empty($district)) sendResponse(false, 'District is required.');
    if (empty($phone)) sendResponse(false, 'Phone number is required.');

    // --- B. Handle File Uploads ---
    $uploadedFiles = [];
    // Note: The input name in HTML must be 'user_images[]' (see JS section below)
    if (isset($_FILES['user_images']) && !empty($_FILES['user_images']['name'][0])) {
        
        $uploadDir = 'uploads/';
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $files = $_FILES['user_images'];
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = $files['name'][$i];
            $fileTmp  = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileError = $files['error'][$i];

            if ($fileError === UPLOAD_ERR_OK) {
                // Validate File Size (e.g., 5MB max)
                if ($fileSize > 5 * 1024 * 1024) {
                    continue; // Skip too large files or send error
                }

                // Validate File Type (MIME)
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($fileTmp);
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if (in_array($mime, $allowedMimes)) {
                    // Generate unique name to prevent overwriting
                    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                    $newFileName = uniqid('img_', true) . '.' . $ext;
                    $destination = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmp, $destination)) {
                        $uploadedFiles[] = $destination;
                    }
                }
            }
        }
    }

    // Convert file array to JSON for DB
    $filesJson = !empty($uploadedFiles) ? json_encode($uploadedFiles) : null;

    // --- C. Insert into Database ---
    try {
        $sql = "INSERT INTO users (name, email, role, district, phone, image_paths) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $email, $role, $district, $phone, $filesJson]);

        sendResponse(true, 'User saved successfully!', ['id' => $pdo->lastInsertId()]);

    } catch (PDOException $e) {
        // Check for duplicate email
        if ($e->getCode() == 23000) {
            sendResponse(false, 'This email is already registered.');
        }
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }

} else {
    sendResponse(false, 'Invalid request method.');
}
?>