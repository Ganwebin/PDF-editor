<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Handler</title>
    <script>
        function showDialog(message) {
            alert(message);
            window.location.href = "upload.php";
        }
    </script>
</head>
<body>
<?php
$targetDir = "uploads/";
$targetFile = $targetDir . basename(path: $_FILES["fileToUpload"]["name"]);
$fileType = strtolower(string: pathinfo(path: $targetFile, flags: PATHINFO_EXTENSION));
$fileSize = $_FILES["fileToUpload"]["size"];

// Database connection
$servername = "localhost";
$username = "root";
$password = "Golden&Niuniu05";
$dbname = "pdf_db"; 
$conn = new mysqli(hostname: $servername, username: $username, password: $password, database: $dbname);

// Check connection
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if (isset($_FILES["fileToUpload"])) {
    if ($fileType != "pdf") {
        exit;
    }

    if ($_FILES["fileToUpload"]["error"] !== UPLOAD_ERR_OK) {
        echo "<script>showDialog('Error uploading file. Error code: " . $_FILES["fileToUpload"]["error"] . "');</script>";
        exit;
    }

    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
        $filename = basename($_FILES["fileToUpload"]["name"]);

        $stmt = $conn->prepare("INSERT INTO uploads (filename, filepath, filesize) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $filename, $targetFile, $fileSize);

        if ($stmt->execute()) {
            echo "<script>showDialog('The file has been uploaded successfully and saved to the database!');</script>";
        } else {
            echo "<script>showDialog('The file was uploaded but could not be saved to the database.');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>showDialog('Sorry, there was an error uploading your file.');</script>";
    }
} else {
    echo "<script>showDialog('No file was uploaded.');</script>";
}

$conn->close();
?>
</body>
</html>
