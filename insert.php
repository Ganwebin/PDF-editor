<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Image to PDF</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Page wrapper -->
    <div class="page-wrapper">
        <!-- Include sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Content wrapper -->
        <div class="content-wrapper">
            <!-- Mobile menu toggle button -->
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>

            <div class="container">
                <div class="card">
                    <div class="card-header">
                        <h2>Insert an Image</h2>
                    </div>
                    <div class="card-body">
                        <div id="feedback" class="feedback"></div>
                        
                        <form id="insertForm" action="preview_image.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="pdfToInsert">Select a PDF Document</label>
                                <select id="pdfToInsert" name="pdfToInsert" required>
                                    <option value="">Choose a PDF file</option>
                                    <?php
                                    // Database connection
                                    $servername = "localhost";
                                    $username = "root";
                                    $password = "Golden&Niuniu05";
                                    $dbname = "pdf_db";

                                    $conn = new mysqli($servername, $username, $password, $dbname);

                                    if ($conn->connect_error) {
                                        die("Connection failed: " . $conn->connect_error);
                                    }

                                    // Fetch PDFs from the database
                                    $sql = "SELECT id, filename, filepath FROM uploads WHERE filepath LIKE '%.pdf'";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['filename']) . "</option>";
                                        }
                                    } else {
                                        echo "<option value=''>No PDFs available</option>";
                                    }

                                    $conn->close();
                                    ?>
                                </select>
                                <div class="helper-text">Select from your previously uploaded PDF documents</div>
                            </div>

                            <div class="form-group">
                                <label>Select an Image (Optional)</label>
                                <div class="file-input-wrapper">
                                    <div class="file-input-button">
                                        <i class="fas fa-upload"></i> Choose an image file
                                    </div>
                                    <input type="file" id="imageToUpload" name="imageToUpload" class="file-input" accept="image/*">
                                </div>
                                <div id="fileName" class="file-name">No file selected</div>
                                <div class="helper-text">Supported formats: JPG, PNG, GIF (max 5MB). You can also drag-and-drop an image in the preview.</div>
                            </div>

                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            // File input display
            const imageInput = document.getElementById('imageToUpload');
            if (imageInput) {
                imageInput.addEventListener('change', function() {
                    const fileName = this.files && this.files[0] ? this.files[0].name : 'No file selected';
                    document.getElementById('fileName').textContent = fileName;
                });
            }
        });
    </script>
</body>
</html>