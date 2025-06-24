// Permissions check script (save as check_permissions.php)
<?php
// Define paths to check
$paths = [
    'uploads/',
    'uploads/temp/'
];

echo "<h1>Directory Permissions Check</h1>";

foreach ($paths as $path) {
    echo "<h2>Checking: $path</h2>";
    
    // Check if exists
    if (file_exists($path)) {
        echo "✓ Path exists<br>";
    } else {
        echo "❌ Path does not exist<br>";
        continue;
    }
    
    // Check if directory
    if (is_dir($path)) {
        echo "✓ Is a directory<br>";
    } else {
        echo "❌ Not a directory<br>";
        continue;
    }
    
    // Check read permissions
    if (is_readable($path)) {
        echo "✓ Is readable<br>";
    } else {
        echo "❌ Not readable<br>";
    }
    
    // Check write permissions
    if (is_writable($path)) {
        echo "✓ Is writable<br>";
    } else {
        echo "❌ Not writable<br>";
    }
    
    // Check web server user
    echo "Web server user: " . exec('whoami') . "<br>";
    
    // List files
    echo "<h3>Files in directory:</h3>";
    $files = scandir($path);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            echo "<li>$file - ";
            if (is_readable($path.$file)) {
                echo "readable";
            } else {
                echo "not readable";
            }
            echo "</li>";
        }
    }
    echo "</ul>";
}

// Check if we can access a test file via HTTP
$testUrl = "/PDFeditor/uploads/temp/";
echo "<h2>Testing URL access to: $testUrl</h2>";
echo "Try opening this URL in your browser to verify access:<br>";
echo "<a href='$testUrl' target='_blank'>$testUrl</a>";

?>