<?php
// Set content type to JSON for AJAX response
header('Content-Type: application/json');

// Check if filepath parameter exists
if (!isset($_GET['filepath'])) {
    echo json_encode(['error' => 'No filepath provided']);
    exit;
}

$filepath = $_GET['filepath'];

// Basic security check - ensure the file is in the uploads directory
$allowedPath = 'uploads/';
if (strpos($filepath, $allowedPath) !== 0) {
    echo json_encode(['error' => 'Invalid file path']);
    exit;
}

// Check if file exists
if (!file_exists($filepath)) {
    echo json_encode(['error' => 'File not found: ' . $filepath]);
    exit;
}

// Include the required libraries
require_once 'vendor/autoload.php';

try {
    // Use FPDI to get page count
    $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
    $pageCount = $pdf->setSourceFile($filepath);
    
    // Handle edge cases - ensure we have at least 1 page
    if ($pageCount < 1) {
        $pageCount = 1; // Default to 1 page if we can't detect correctly
    }
    
    // Return the page count
    echo json_encode(['pageCount' => $pageCount]);
} catch (Exception $e) {
    // If there's an error, try to determine if it's a valid PDF but we just can't parse it
    // In that case, assume it has at least 1 page
    if (file_exists($filepath) && filesize($filepath) > 0) {
        // Check if file starts with %PDF (magic number for PDF files)
        $handle = fopen($filepath, "r");
        $header = fread($handle, 4);
        fclose($handle);
        
        if ($header == "%PDF") {
            // It's a valid PDF, assume at least 1 page
            echo json_encode(['pageCount' => 1]);
            exit;
        }
    }
    
    // Detailed error message for debugging
    echo json_encode([
        'error' => 'Error reading PDF: ' . $e->getMessage(),
        'filepath' => $filepath,
        'exists' => file_exists($filepath) ? 'yes' : 'no',
        'size' => file_exists($filepath) ? filesize($filepath) . ' bytes' : 'n/a'
    ]);
}
?>