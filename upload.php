<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload PDF</title>
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
                        <h2>Upload PDF File</h2>
                    </div>
                    <div class="card-body">
                        <form action="upload_handler.php" method="post" enctype="multipart/form-data" class="upload-form">
                            <input type="file" name="fileToUpload" accept=".pdf" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for mobile menu toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
        });
    </script>
</body>
</html>