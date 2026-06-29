<?php
session_start();
require '../includes/auth.php';
require __DIR__ .'../../../config/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $topic_id = 1; // fake topic ID for testing

    $uploadDir = '../uploads/forum/';
    if(!is_dir($uploadDir)){
        mkdir($uploadDir, 0755, true);
    }

    if(!empty($_FILES['images']['name'][0])){
        foreach($_FILES['images']['tmp_name'] as $index => $tmpName){
            $originalName = $_FILES['images']['name'][$index];
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);

            // Unique name
            $newName = $topic_id.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
            $destination = $uploadDir.$newName;

            // Compress image
            $imageInfo = getimagesize($tmpName);
            if($imageInfo !== false){
                $mime = $imageInfo['mime'];

                switch($mime){
                    case 'image/jpeg':
                        $img = imagecreatefromjpeg($tmpName);
                        imagejpeg($img, $destination, 75);
                        break;
                    case 'image/jpg':
                        $img = imagecreatefromjpeg($tmpName);
                        imagejpeg($img, $destination, 75);
                        break;    
                    case 'image/png':
                        $img = imagecreatefrompng($tmpName);
                        imagepng($img, $destination, 6);
                        break;
                    case 'image/gif':
                        $img = imagecreatefromgif($tmpName);
                        imagegif($img, $destination);
                        break;
                    default:
                        continue 2; // continue the foreach loop

                }
                imagedestroy($img);

                // Insert into DB
                $relativePath = 'uploads/forum/'.$newName;
                $stmt = $conn->prepare("INSERT INTO forum_topic_images (topic_id, image_path) VALUES (?, ?)");
                $stmt->bind_param("is", $topic_id, $relativePath);
                $stmt->execute();
            }
        }

        echo "Images uploaded successfully!";
    } else {
        echo "No files selected.";
    }
}
?>
