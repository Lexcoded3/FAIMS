<?php
session_start();
require '../includes/auth.php';
require __DIR__ .'../../../config/db.php';

if(!isset($_POST['title'], $_POST['category_id'], $_POST['content'])){
    header("Location: ../create.php");
    exit;
}

$title = trim($_POST['title']);
$category_id = intval($_POST['category_id']);
$content = trim($_POST['content']);
$user_id = $_SESSION['id'];

// Insert topic
$stmt = $conn->prepare("INSERT INTO forum_topics (category_id, user_id, title, content) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $category_id, $user_id, $title, $content);
$stmt->execute();

$topic_id = $conn->insert_id;

// === Handle images ===
$uploadDir = '../uploads/forum/';
if(!is_dir($uploadDir)){
    mkdir($uploadDir, 0755, true);
}

// Check if files exist
if(!empty($_FILES['images']['name'][0])){
    foreach($_FILES['images']['tmp_name'] as $index => $tmpName){

        $originalName = $_FILES['images']['name'][$index];
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);

        // Generate unique name: topicID_timestamp_random.ext
        $newName = $topic_id.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $destination = $uploadDir.$newName;

        // Compress and save image
        $imageInfo = getimagesize($tmpName);
        if($imageInfo !== false){
            $mime = $imageInfo['mime'];

            switch($mime){
                case 'image/jpeg':
                    $img = imagecreatefromjpeg($tmpName);
                    imagejpeg($img, $destination, 75); // 75% quality
                    break;
                case 'image/png':
                    $img = imagecreatefrompng($tmpName);
                    // Convert PNG to PNG with compression level 6
                    imagepng($img, $destination, 6);
                    break;
                case 'image/jpg':
                    $img = imagecreatefrompng($tmpName);
                    // Convert PNG to PNG with compression level 6
                    imagepng($img, $destination, 6);
                    break;
                case 'image/gif':
                    $img = imagecreatefromgif($tmpName);
                    imagegif($img, $destination);
                    break;
                default:
                    continue; // skip unsupported types
            }
            imagedestroy($img);

            // Insert into DB
                $relativePath = 'uploads/forum/'.$newName;
                $stmt = $conn->prepare("INSERT INTO forum_topic_images (topic_id, image_path) VALUES (?, ?)");
                $stmt->bind_param("is", $topic_id, $relativePath);
                $stmt->execute();
        }
    }
}

// Redirect to topic page
header("Location: ../topic.php?id=".$topic_id);
exit;
?>
