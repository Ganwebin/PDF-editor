<?php
session_start();

// Check if an image was uploaded
if (!isset($_FILES['imageToUpload']) || $_FILES['imageToUpload']['error'] != 0) {
    echo json_encode(['success' => false, 'error' => 'No image uploaded or upload error']);
    exit();
}

// Define upload directory
$targetDir = "Uploads/temp/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Generate a unique filename
$imageFileName = basename($_FILES["imageToUpload"]["name"]);
$tempImagePath = $targetDir . time() . "_" . uniqid() . "_" . $imageFileName;

// Move the uploaded file to the target directory
if (move_uploaded_file($_FILES["imageToUpload"]["tmp_name"], $tempImagePath)) {
    // Convert the path for web access
    $relativePath = str_replace('C:/xampp/htdocs/PDFeditor/', '', $tempImagePath);
    $webPath = "/" . $relativePath;
    
    echo json_encode([
        'success' => true, 
        'path' => $webPath, 
        'filename' => $imageFileName
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to upload image'
    ]);
}
?>