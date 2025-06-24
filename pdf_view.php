<?php
// pdf_view.php
if (!isset($_GET['id']) || !isset($_GET['page'])) {
    http_response_code(400);
    die("Error: Missing parameters.");
}

$id = (int)$_GET['id'];
$page = (int)$_GET['page'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "Golden&Niuniu05";
$dbname = "pdf_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}

// Fetch PDF filepath
$sql = "SELECT filepath FROM uploads WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($filepath);
if ($stmt->fetch() && file_exists($filepath)) {
    $stmt->close();
    $conn->close();
    
    // Convert filepath to URL
    $pdfUrl = str_replace('C:/xampp/htdocs/PDFeditor/', 'http://localhost/PDFeditor/', $filepath);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PDF Preview</title>
        <style>
            body { margin: 0; padding: 0; }
            embed { width: 100%; height: 100vh; }
        </style>
    </head>
    <body>
        <embed src="<?php echo htmlspecialchars($pdfUrl); ?>#page=<?php echo $page; ?>" type="application/pdf">
    </body>
    </html>
    <?php
} else {
    $stmt->close();
    $conn->close();
    http_response_code(404);
    die("Error: PDF not found.");
}
?>