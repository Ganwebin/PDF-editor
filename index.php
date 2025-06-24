<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Uploaded Files</title>
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
                        <h2>Uploaded Files</h2>
                    </div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Filename</th>
                                    <th>Filesize (KB)</th>
                                    <th>Uploaded At</th>
                                    <th>View</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Database connection
                                $servername = "localhost";
                                $username = "root";
                                $password = "Golden&Niuniu05";
                                $dbname = "pdf_db";

                                $conn = new mysqli($servername, $username, $password, $dbname);

                                // Check connection
                                if ($conn->connect_error) {
                                    die("Connection failed: " . $conn->connect_error);
                                }

                                // Handle delete request 
                                if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete"])) {
                                    $idToDelete = intval($_POST["id"]);
                                    $filePath = $_POST["filepath"];

                                    // Delete file from server
                                    if (file_exists($filePath)) {
                                        unlink($filePath);
                                    }

                                    // Delete record from database 
                                    $deleteSQL = "DELETE FROM uploads WHERE id = ?";
                                    $stmt = $conn->prepare($deleteSQL);
                                    $stmt->bind_param("i", $idToDelete);
                                    $stmt->execute();
                                    $stmt->close();

                                    echo "<script>alert('File deleted successfully.'); window.location.href='index.php';</script>";
                                }

                                // Fetch files from the database
                                $sql = "SELECT * FROM uploads";
                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    // Counter for sequential numbering
                                    $counter = 1;
                                    
                                    // Output data of each row
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                                <td>" . $counter . "</td>
                                                <td>" . htmlspecialchars($row["filename"]) . "</td>
                                                <td>" . round($row["filesize"] / 1024, 2) . "</td>
                                                <td>" . $row["uploaded_at"] . "</td>
                                                <td><a href='" . htmlspecialchars($row["filepath"]) . "' target='_blank' class='btn btn-outline'><i class='fas fa-eye'></i> View</a></td>
                                                <td>
                                                    <form method='post' style='margin: 0;'>
                                                        <input type='hidden' name='id' value='" . $row["id"] . "'>
                                                        <input type='hidden' name='filepath' value='" . htmlspecialchars($row["filepath"]) . "'>
                                                        <button type='submit' name='delete' class='btn btn-outline'><i class='fas fa-trash'></i> Delete</button>
                                                    </form>
                                                </td>
                                              </tr>";
                                        
                                        // Increment counter for the next row
                                        $counter++;
                                    }
                                } else {
                                    echo "<tr><td colspan='6'>No files found</td></tr>";
                                }

                                $conn->close();
                                ?>
                            </tbody>
                        </table>
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