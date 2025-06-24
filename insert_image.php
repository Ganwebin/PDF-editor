<?php
// Download handler for processed PDFs

// Check if file parameter is provided
if (!isset($_GET['file']) || empty($_GET['file'])) {
    die("Error: No file specified.");
}

$file = urldecode($_GET['file']);

// Security check - ensure the file is in the uploads/processed directory
$allowedPath = realpath('uploads/processed') . DIRECTORY_SEPARATOR;
$filePath = realpath($file);

if ($filePath === false || strpos($filePath, $allowedPath) !== 0) {
    die("Access denied: Invalid file path.");
}

// Check if the file exists
if (!file_exists($filePath)) {
    die("Error: File not found.");
}

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Clear output buffer and output the file
ob_clean();
flush();
readfile($filePath);
exit;
?>