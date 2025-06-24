<?php
session_start();

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: insert.php?error=' . urlencode('Invalid request method'));
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "Golden&Niuniu05";
$dbname = "pdf_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header('Location: insert.php?error=' . urlencode('Database connection failed'));
    exit();
}

// Get PDF ID and images data
$pdfId = isset($_POST['pdfId']) ? (int)$_POST['pdfId'] : null;
$imagesData = isset($_POST['imagesData']) ? $_POST['imagesData'] : null;

error_log("Received pdfId: " . var_export($pdfId, true));
error_log("Received imagesData: " . var_export($imagesData, true));

if (!$pdfId || !$imagesData) {
    $conn->close();
    header('Location: insert.php?error=' . urlencode('Missing required parameters'));
    exit();
}

try {
    // Decode images data
    $imagesData = json_decode($imagesData, true);
    $images = $imagesData['images'] ?? [];
    $previewContainerWidth = $imagesData['containerWidth'] ?? 600;
    $previewContainerHeight = $imagesData['containerHeight'] ?? null; // Added this line to get container height
    
    if (!is_array($images) || empty($images)) {
        throw new Exception('Invalid or empty images data');
    }

    // Get PDF information
    $stmt = $conn->prepare("SELECT filepath, filename FROM uploads WHERE id = ?");
    $stmt->bind_param("i", $pdfId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('PDF not found');
    }
    
    $pdfInfo = $result->fetch_assoc();
    $pdfFilePath = $pdfInfo['filepath'];
    $pdfFileName = $pdfInfo['filename'];
    $stmt->close();
    
    if (!file_exists($pdfFilePath)) {
        throw new Exception('PDF file not found at: ' . $pdfFilePath);
    }

    // Create output directory if it doesn't exist
    $outputDir = $_SERVER['DOCUMENT_ROOT'] . "/PDFeditor/Uploads/output/";
    $outputFileName = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', pathinfo($pdfFileName, PATHINFO_FILENAME)) . "_modified_" . time() . ".pdf";
    $outputFilePath = $outputDir . $outputFileName;

    // Ensure output directory exists
    if (!file_exists($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    // Create temporary directory for image processing
    $tempDir = "Uploads/temp/processing_" . time() . "/";
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    // Ensure TCPDF is available
    require_once 'vendor/autoload.php';
    // Create PDF instance with FPDI and disable auto page breaks
    $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
    $pdf->SetAutoPageBreak(false);
    
    // IMPORTANT: Get original page sizes
    $pageCount = $pdf->setSourceFile($pdfFilePath);
    $originalSizes = [];
    
    // First pass: determine page sizes
    for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
        $tplIdx = $pdf->importPage($pageNum);
        $size = $pdf->getTemplateSize($tplIdx);
        $originalSizes[$pageNum] = [
            'width' => $size['width'],
            'height' => $size['height'],
            'orientation' => ($size['width'] > $size['height']) ? 'L' : 'P'
        ];
    }

    // Process each page
    for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
        // Import page with correct orientation and size
        $tplIdx = $pdf->importPage($pageNum);
        $size = $originalSizes[$pageNum];
        
        // Add page with correct size and orientation
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplIdx, 0, 0, $size['width'], $size['height']);
        
        // Find images for this page
        $pageImages = array_filter($images, function($img) use ($pageNum) {
            return (int)$img['pageNumber'] === $pageNum;
        });

        // Add each image to the page
        foreach ($pageImages as $image) {
            // Secure image path
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . $image['path'];
            if (!file_exists($imagePath)) {
                error_log("Image file not found: " . $imagePath);
                continue;
            }
            
            // Get PDF page dimensions
            $pageWidth = $size['width'];
            $pageHeight = $size['height'];

            // Calculate accurate positioning using percentage directly
            $x = (float)$image['xPercentage'] * $pageWidth / 100;
            $y = (float)$image['yPercentage'] * $pageHeight / 100;
            
            // FIXED: Use the actual adjusted dimensions from preview
            $displayWidth = (float)$image['width'];
            $displayHeight = (float)$image['height']; // Use the adjusted height from preview
            
            // Calculate scale factor
            $widthScaleFactor = $pageWidth / $previewContainerWidth;
            
            if ($previewContainerHeight) {
                $heightScaleFactor = $pageHeight / $previewContainerHeight;
                $scaleFactor = ($widthScaleFactor + $heightScaleFactor) / 2;
            } else {
                $scaleFactor = $widthScaleFactor;
            }
            
            // Apply scale factor to BOTH width and height from preview
            $pdfImgWidth = $displayWidth * $scaleFactor;
            $pdfImgHeight = $displayHeight * $scaleFactor; 
            // IMPORTANT: Prevent auto page break by setting it to false temporarily
            $currentAutoPageBreak = $pdf->getAutoPageBreak();
            $pdf->SetAutoPageBreak(false);
            
            // Calculate positions ensuring image stays within page boundaries
            $leftX = max(0, $x - ($pdfImgWidth / 2));
            $topY = max(0, $y - ($pdfImgHeight / 2));
            
            // If image would extend beyond page bottom, adjust Y position to keep it on page
            if ($topY + $pdfImgHeight > $pageHeight) {
                $topY = $pageHeight - $pdfImgHeight;
                if ($topY < 0) { 
                    // If image is too tall for page, scale it down to fit page height
                    $heightRatio = ($pageHeight * 0.95) / $pdfImgHeight;
                    $pdfImgHeight = $pageHeight * 0.95; // 95% of page height
                    $pdfImgWidth = $pdfImgWidth * $heightRatio;
                    $topY = ($pageHeight - $pdfImgHeight) / 2; // Center vertically
                }
            }
            
            // If image would extend beyond page right edge, adjust X position
            if ($leftX + $pdfImgWidth > $pageWidth) {
                $leftX = $pageWidth - $pdfImgWidth;
                if ($leftX < 0) {
                    // If image is too wide for page, scale it down to fit page width
                    $widthRatio = ($pageWidth * 0.95) / $pdfImgWidth;
                    $pdfImgWidth = $pageWidth * 0.95; // 95% of page width
                    $pdfImgHeight = $pdfImgHeight * $widthRatio;
                    $leftX = ($pageWidth - $pdfImgWidth) / 2; // Center horizontally
                }
            }

            // Add image to PDF with boundary-respecting position
            $pdf->Image(
                $imagePath,
                $leftX,
                $topY,
                $pdfImgWidth,
                $pdfImgHeight
            );
            
            // Restore original auto page break setting
            $pdf->SetAutoPageBreak($currentAutoPageBreak);
            
            // Debug log
            error_log("Adding image to page $pageNum at position [$leftX, $topY] with size [$pdfImgWidth x $pdfImgHeight]");
            error_log("Page dimensions: [$pageWidth x $pageHeight], Scale factor: $scaleFactor");
            error_log("Image percentages: x=" . $image['xPercentage'] . "%, y=" . $image['yPercentage'] . "%");
            error_log("Original calculated position: x=" . ($x - ($pdfImgWidth / 2)) . ", y=" . ($y - ($pdfImgHeight / 2)));

        }
    }

    // Save output PDF
    try {
        $pdf->Output($outputFilePath, 'F');
        
        // Verify file was created
        if (!file_exists($outputFilePath)) {
            throw new Exception('Failed to create output PDF file');
        }

        // Force download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $outputFileName . '"');
        header('Content-Length: ' . filesize($outputFilePath));
        readfile($outputFilePath);
        
        // Store in database for future reference
        $relativeFilePath = "Uploads/output/" . $outputFileName;
        $stmt = $conn->prepare("INSERT INTO processed_pdfs (original_pdf_id, filename, filepath) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $pdfId, $outputFileName, $relativeFilePath);
        $stmt->execute();
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Error saving PDF: " . $e->getMessage());
        header('Location: insert.php?error=' . urlencode('Error saving PDF: ' . $e->getMessage()));
        exit();
    }

} catch (Exception $e) {
    error_log("Error processing PDF: " . $e->getMessage());
    header('Location: insert.php?error=' . urlencode('Error processing PDF: ' . $e->getMessage()));
    exit();
} finally {
    // Clean up temporary files BUT NOT the output file itself
    if (isset($tempDir) && file_exists($tempDir)) {
        // Clean up temporary processing directory only
        @rmdir($tempDir);
    }
    
    $conn->close();
}
?>