<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "Golden&Niuniu05";
$dbname = "pdf_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$pdfId = null;
$tempImagePaths = [];
$pdfFilename = null;
$pdfFilepath = null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdfId = isset($_POST['pdfToInsert']) ? (int)$_POST['pdfToInsert'] : null;

    if (!$pdfId) {
        header("Location: insert.php?error=" . urlencode("Please select a PDF."));
        exit();
    }

    // Handle image uploads (multiple)
    if (isset($_FILES['imagesToUpload']) && is_array($_FILES['imagesToUpload']['name'])) {
        $targetDir = "Uploads/temp/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Process each uploaded image
        for ($i = 0; $i < count($_FILES['imagesToUpload']['name']); $i++) {
            if ($_FILES['imagesToUpload']['error'][$i] == 0) {
                $imageFileName = basename($_FILES["imagesToUpload"]["name"][$i]);
                $tempImagePath = $targetDir . time() . "_" . $i . "_" . $imageFileName;
                
                if (move_uploaded_file($_FILES["imagesToUpload"]["tmp_name"][$i], $tempImagePath)) {
                    $tempImagePaths[] = "/" . str_replace('C:/xampp/htdocs/PDFeditor/', '', $tempImagePath);
                }
            }
        }
        
        if (empty($tempImagePaths)) {
            header("Location: insert.php?error=" . urlencode("No valid images were uploaded."));
            exit();
        }
    } else if (isset($_FILES['imageToUpload']) && $_FILES['imageToUpload']['error'] == 0) {
        // For backward compatibility - single image upload
        $targetDir = "Uploads/temp/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $imageFileName = basename($_FILES["imageToUpload"]["name"]);
        $tempImagePath = $targetDir . time() . "_" . $imageFileName;
        if (move_uploaded_file($_FILES["imageToUpload"]["tmp_name"], $tempImagePath)) {
            $tempImagePaths[] = "/" . str_replace('C:/xampp/htdocs/PDFeditor/', '', $tempImagePath);
        } else {
            header("Location: insert.php?error=" . urlencode("Failed to upload image."));
            exit();
        }
    }

    // Fetch PDF details
    $sql = "SELECT filename, filepath FROM uploads WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pdfId);
    $stmt->execute();
    $stmt->bind_result($pdfFilename, $pdfFilepath);
    if ($stmt->fetch()) {
        if (!file_exists($pdfFilepath)) {
            $stmt->close();
            $conn->close();
            die("Error: PDF file not found at $pdfFilepath.");
        }
        $pdfUrl = str_replace('C:/xampp/htdocs/PDFeditor/', 'http://localhost/PDFeditor/', $pdfFilepath);
    } else {
        $stmt->close();
        $conn->close();
        die("Error: Invalid PDF selected.");
    }
    $stmt->close();
} else {
    header("Location: insert.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Image Placement</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js"></script>
    
</head>
<body>
    <div class="container">
        <div class="card_preview">
            <div class="card-header">
                <h2>Image Placement Preview</h2>
                <div><?php echo htmlspecialchars($pdfFilename); ?></div>
            </div>
            <div class="card-body">
                <button class="mobile-menu-toggle" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
            <div class="sidebar">
                <div class="sidebar-header">
                    <h3>Tools</h3>
                </div>
                <div class="sidebar-body">
                    <div class="sidebar-nav">
                        <div class="sidebar-item">
                            <button id="addImagesBtn" class="sidebar-link">
                                <i class="fas fa-plus"></i> Add Images
                            </button>
                        </div>
                        <input type="file" id="imageFileInput" multiple accept="image/*" style="display: none">
                        <div class="sidebar-item">
                            <button id="aspectRatioButton" class="sidebar-link btn-locked">
                                <i class="fas fa-compress"></i> Unlock Aspect Ratio
                            </button>
                        </div>
                        <div class="sidebar-item">
                            <a href="insert.php" class="sidebar-link">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                        <div class="sidebar-item">
                            <form id="saveForm" action="process_image.php" method="post">
                                <input type="hidden" name="pdfId" value="<?php echo htmlspecialchars($pdfId); ?>">
                                <input type="hidden" id="imagesData" name="imagesData" value="">
                                <button type="submit" class="sidebar-link btn-success">
                                    <i class="fas fa-download"></i> Save & Download
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="image-list-container">
                        <span>Selected Images:</span>
                        <div class="image-list" id="imageThumbnails"></div>
                    </div>
                </div>
                <div class="sidebar-footer">
                    
                </div>
            </div>
                
                <div class="preview-container" id="previewContainer">
                    <div id="pagesContainer"></div>
                    <?php foreach ($tempImagePaths as $index => $path): ?>
                    <div class="image-container" data-index="<?php echo $index; ?>" style="display: block;">
                        <img
                            class="preview-image"
                            src="<?php echo htmlspecialchars($path); ?>"
                            style="width: 150px; height: auto;"
                            draggable="false"
                        >
                        <div class="resize-handle bottom-right" data-handle="bottom-right"></div>
                        <div class="delete-button">×</div>
                    </div>
                    <?php endforeach; ?>
                </div>
               
            </div>
        </div>
    </div>

    <script>
        
        
        // Mobile menu toggle
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const sidebar = document.querySelector('.sidebar');

        if (!mobileMenuToggle || !sidebar) {
            console.error('Mobile menu toggle or sidebar not found in DOM');
        } else {
            mobileMenuToggle.addEventListener('click', (e) => {
                console.log('Mobile menu toggle clicked');
                const isSidebarVisible = sidebar.classList.contains('show');
                
                // Toggle sidebar visibility
                sidebar.classList.toggle('show');
                
                // Update icon
                const icon = mobileMenuToggle.querySelector('i');
                if (icon) {
                    icon.className = isSidebarVisible ? 'fas fa-bars' : 'fas fa-times';
                } else {
                    console.warn('Icon not found in mobile menu toggle button');
                }
                
                // Prevent event propagation to avoid conflicts
                e.stopPropagation();
            });

            // Close sidebar when clicking outside in mobile view
            document.addEventListener('click', (e) => {
                const isMobile = window.innerWidth <= 768;
                if (!isMobile) return;
                
                const isSidebar = e.target.closest('.sidebar');
                const isToggleButton = e.target.closest('.mobile-menu-toggle');
                
                if (!isSidebar && !isToggleButton && sidebar.classList.contains('show')) {
                    console.log('Closing sidebar due to outside click');
                    sidebar.classList.remove('show');
                    const icon = mobileMenuToggle.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-bars';
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', async () => {
            // DOM elements
            const previewContainer = document.getElementById('previewContainer');
            const pagesContainer = document.getElementById('pagesContainer');
            const imageThumbnails = document.getElementById('imageThumbnails');
            const addImagesBtn = document.getElementById('addImagesBtn');
            const imageFileInput = document.getElementById('imageFileInput');
            const resetAllButton = document.getElementById('resetAllButton');
            const saveForm = document.getElementById('saveForm');
            const imagesDataInput = document.getElementById('imagesData');
        
            
            // State
            const state = {
                pages: [],
                pageWidths: [],
                pageHeights: [],
                images: [],
                activeImageIndex: -1,
                isDragging: false,
                isResizing: false,
                startX: 0,
                startY: 0,
                startWidth: 0,
                startHeight: 0, 
                lockAspectRatio: true 
            };

            // Initialize images from PHP
            <?php foreach ($tempImagePaths as $index => $path): ?>
            state.images.push({
                path: <?php echo json_encode($path); ?>,
                pageNumber: 1,
                xPercentage: 50,
                yPercentage: 50,
                width: 150,
                height: 0,
                ratio: 1,
                pdfX: 0,
                pdfY: 0
            });
            <?php endforeach; ?>

            // Set first image as active if available
            if (state.images.length > 0) {
                state.activeImageIndex = 0;
            }

            // Load and initialize images
            function initializeImages() {
                const imageContainers = document.querySelectorAll('.image-container');
                imageContainers.forEach((container, idx) => {
                    const img = container.querySelector('.preview-image');
                    const deleteBtn = container.querySelector('.delete-button');
                    
                    img.onload = () => {
                        const ratio = img.naturalWidth / img.naturalHeight;
                        state.images[idx].ratio = ratio;
                        state.images[idx].width = 150; // Default width
                        state.images[idx].height = 150 / ratio; // Set height based on ratio
                        img.style.width = '150px';
                        img.style.height = `${state.images[idx].height}px`;
                        
                        // Only center if the image is new and doesn't have position data yet
                        if (!state.images[idx].hasOwnProperty('positioned') || !state.images[idx].positioned) {
                            centerImageOnPage(idx, 1);
                            state.images[idx].positioned = true;
                        } else {
                            // Use existing position data if available
                            positionImageFromData(idx);
                        }
                    };
                    
                    // Force load if image is already cached
                    if (img.complete && img.naturalWidth !== 0) {
                        const ratio = img.naturalWidth / img.naturalHeight;
                        state.images[idx].ratio = ratio;
                        state.images[idx].width = 150;
                        state.images[idx].height = 150 / ratio;
                        img.style.width = '150px';
                        img.style.height = `${state.images[idx].height}px`;
                        
                        // Only center if the image is new and doesn't have position data yet
                        if (!state.images[idx].hasOwnProperty('positioned') || !state.images[idx].positioned) {
                            centerImageOnPage(idx, 1);
                            state.images[idx].positioned = true;
                        } else {
                            // Use existing position data if available
                            positionImageFromData(idx);
                        }
                    }
                    
                    // Rest of the event listeners remain the same
                    img.addEventListener('mousedown', (e) => {
                        if (e.target !== img) return;
                        setActiveImage(idx);
                        state.isDragging = true;
                        state.startX = e.clientX;
                        state.startY = e.clientY;
                        container.style.transition = 'none';
                        e.preventDefault();
                    });
                    
                    container.querySelector('.resize-handle').addEventListener('mousedown', (e) => {
                        setActiveImage(idx);
                        state.isResizing = true;
                        state.startWidth = parseFloat(img.style.width) || state.images[idx].width || 150;
                        state.startHeight = parseFloat(img.style.height) || state.images[idx].height || 150 / state.images[idx].ratio;
                        state.startX = e.clientX;
                        state.startY = e.clientY;
                        img.style.transition = 'none';
                        e.preventDefault();
                    });
                    
                    deleteBtn.addEventListener('click', () => removeImage(idx));
                    
                    // For touch devices
                    img.addEventListener('touchstart', (e) => {
                        if (e.target !== img) return;
                        setActiveImage(idx);
                        state.isDragging = true;
                        state.startX = e.touches[0].clientX;
                        state.startY = e.touches[0].clientY;
                        container.style.transition = 'none';
                        e.preventDefault();
                    }, { passive: false });
                });
                
                updateThumbnails();
            }
            
            function positionImageFromData(imageIndex) {
                if (imageIndex < 0 || imageIndex >= state.images.length) return;
                
                const imageData = state.images[imageIndex];
                const containers = document.querySelectorAll('.image-container');
                if (!containers[imageIndex]) return;
                
                const container = containers[imageIndex];
                const img = container.querySelector('.preview-image');
                
                const pageNum = imageData.pageNumber || 1;
                const pageContainer = state.pages[pageNum - 1];
                if (!pageContainer) return;
                
                const xPercent = imageData.xPercentage || 50;
                const yPercent = imageData.yPercentage || 50;
                
                // Get page position
                const pageRect = pageContainer.getBoundingClientRect();
                const pagesRect = pagesContainer.getBoundingClientRect();
                
                // Calculate absolute Y position based on page position and percentage
                const absoluteY = pageRect.top - pagesRect.top + (pageRect.height * yPercent / 100);
                
                // Update element style
                container.style.left = xPercent + '%';
                container.style.top = absoluteY + 'px';
                container.style.transform = 'translate(-50%, -50%)';
                
                // Ensure width and height are set
                img.style.width = `${imageData.width || 150}px`;
                img.style.height = `${imageData.height || 150 / imageData.ratio}px`;
                
                // Update PDF coordinates
                updatePdfCoordinates(imageIndex);
            }

            // Set active image
            function setActiveImage(index) {
                if (index < 0 || index >= state.images.length) return;
                
                state.activeImageIndex = index;
                
                // Update UI to show active image
                document.querySelectorAll('.image-container').forEach((container, idx) => {
                    const img = container.querySelector('.preview-image');
                    if (idx === index) {
                        img.classList.add('image-selected');
                    } else {
                        img.classList.remove('image-selected');
                    }
                });
                
                // Update thumbnails
                updateThumbnails();
            }

            // Update thumbnail selection
            function updateThumbnails() {
                imageThumbnails.innerHTML = '';
                
                state.images.forEach((image, idx) => {
                    const thumbnail = document.createElement('img');
                    thumbnail.src = image.path;
                    thumbnail.className = 'image-thumbnail' + (idx === state.activeImageIndex ? ' active' : '');
                    thumbnail.addEventListener('click', () => setActiveImage(idx));
                    imageThumbnails.appendChild(thumbnail);
                });
            }

            // Remove image
            function removeImage(index) {
                if (index < 0 || index >= state.images.length) return;
                
                // Remove from DOM
                const containers = document.querySelectorAll('.image-container');
                if (containers[index]) {
                    containers[index].remove();
                }
                
                // Remove from state
                state.images.splice(index, 1);
                
                // Update active index
                if (state.activeImageIndex === index) {
                    state.activeImageIndex = state.images.length > 0 ? 0 : -1;
                } else if (state.activeImageIndex > index) {
                    state.activeImageIndex--;
                }
                
                updateThumbnails();
            }

            // Add new image
            async function addNewImage(file, targetPageNum = 1) {
                const formData = new FormData();
                formData.append('imageToUpload', file);
                
                try {
                    const response = await fetch('upload_temp_image.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    
                    if (data.success) {
                        const imageSrc = data.path.startsWith('/') ? data.path : '/' + data.path;
                        const index = state.images.length;
                        
                        // Add to state with default values and mark as not positioned yet
                        state.images.push({
                            path: imageSrc,
                            pageNumber: targetPageNum, // Use the specified target page
                            xPercentage: 50,
                            yPercentage: 50,
                            width: 150,
                            height: 0,
                            ratio: 1,
                            pdfX: 0,
                            pdfY: 0,
                            positioned: false // Add this flag
                        });
                        
                        const container = document.createElement('div');
                        container.className = 'image-container';
                        container.dataset.index = index;
                        container.style.display = 'block';
                        
                        const img = document.createElement('img');
                        img.className = 'preview-image';
                        img.src = imageSrc;
                        img.style.width = '150px';
                        img.style.height = 'auto'; // Let browser calculate height initially
                        img.draggable = false;
                        
                        const resizeHandle = document.createElement('div');
                        resizeHandle.className = 'resize-handle bottom-right';
                        resizeHandle.dataset.handle = 'bottom-right';
                        
                        const deleteBtn = document.createElement('div');
                        deleteBtn.className = 'delete-button';
                        deleteBtn.textContent = '×';
                        
                        container.appendChild(img);
                        container.appendChild(resizeHandle);
                        container.appendChild(deleteBtn);
                        previewContainer.appendChild(container);
                        
                        // Setup event listeners
                        img.onload = () => {
                            const ratio = img.naturalWidth / img.naturalHeight;
                            state.images[index].ratio = ratio;
                            state.images[index].height = state.images[index].width / ratio;
                            img.style.height = `${state.images[index].height}px`;
                            centerImageOnPage(index, targetPageNum); // Center on the targeted page
                            state.images[index].positioned = true; // Mark as positioned after centering
                        };
                        
                        // Handle cached images
                        if (img.complete && img.naturalWidth !== 0) {
                            const ratio = img.naturalWidth / img.naturalHeight;
                            state.images[index].ratio = ratio;
                            state.images[index].height = state.images[index].width / ratio;
                            img.style.height = `${state.images[index].height}px`;
                            centerImageOnPage(index, targetPageNum); // Center on the targeted page
                            state.images[index].positioned = true; // Mark as positioned after centering
                        }
                        
                        img.addEventListener('mousedown', (e) => {
                            if (e.target !== img) return;
                            setActiveImage(index);
                            state.isDragging = true;
                            state.startX = e.clientX;
                            state.startY = e.clientY;
                            container.style.transition = 'none';
                            e.preventDefault();
                        });
                        
                        resizeHandle.addEventListener('mousedown', (e) => {
                            setActiveImage(index);
                            state.isResizing = true;
                            state.startWidth = parseFloat(img.style.width) || state.images[index].width || 150;
                            state.startHeight = parseFloat(img.style.height) || state.images[index].height || 150 / state.images[index].ratio;
                            state.startX = e.clientX;
                            state.startY = e.clientY;
                            img.style.transition = 'none';
                            e.preventDefault();
                        });
                        
                        deleteBtn.addEventListener('click', () => removeImage(index));
                        
                        // Touch events
                        img.addEventListener('touchstart', (e) => {
                            if (e.target !== img) return;
                            setActiveImage(index);
                            state.isDragging = true;
                            state.startX = e.touches[0].clientX;
                            state.startY = e.touches[0].clientY;
                            container.style.transition = 'none';
                            e.preventDefault();
                        }, { passive: false });
                        
                        resizeHandle.addEventListener('touchstart', (e) => {
                            setActiveImage(index);
                            state.isResizing = true;
                            state.startWidth = parseFloat(img.style.width) || state.images[index].width || 150;
                            state.startHeight = parseFloat(img.style.height) || state.images[index].height || 150 / state.images[index].ratio;
                            state.startX = e.touches[0].clientX;
                            state.startY = e.touches[0].clientY;
                            img.style.transition = 'none';
                            e.preventDefault();
                        }, { passive: false });
                        
                        // Set as active
                        setActiveImage(index);
                        
                        return true;
                    } else {
                        console.error('Image upload failed:', data.error);
                        alert('Image upload failed: ' + (data.error || 'Unknown error'));
                        return false;
                    }
                } catch (error) {
                    console.error('Image upload error:', error);
                    alert('Failed to upload image.');
                    return false;
                }
            }

            // Load and render PDF
            async function loadPdf() {
            try {
                const response = await fetch(`get_pdf_page_count.php?filepath=${encodeURIComponent(<?php echo json_encode($pdfFilepath); ?>)}`);
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                const pageCount = data.pageCount || 1;

                const pdf = await pdfjsLib.getDocument({
                    url: <?php echo json_encode($pdfUrl); ?>,
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/cmaps/',
                    cMapPacked: true
                }).promise;

                pagesContainer.innerHTML = '';
                state.pages = [];
                state.pageWidths = [];
                state.pageHeights = [];

                for (let pageNum = 1; pageNum <= pageCount; pageNum++) {
                    const page = await pdf.getPage(pageNum);
                    const pageContainer = document.createElement('div');
                    pageContainer.className = 'page-placeholder';
                    pageContainer.dataset.page = pageNum;
                    const canvas = document.createElement('canvas');
                    pageContainer.appendChild(canvas);
                    pagesContainer.appendChild(pageContainer);
                    state.pages.push(pageContainer);

                    // Get the actual dimensions of the PDF page
                    const viewport = page.getViewport({ scale: 1 });
                    state.pageWidths[pageNum - 1] = viewport.width;
                    state.pageHeights[pageNum - 1] = viewport.height;

                    // Calculate scale for display with higher resolution
                    const containerWidth = pagesContainer.clientWidth || 600; // Fallback to 600px if clientWidth is 0
                    const baseScale = 2; // Increase resolution by 2x (adjust to 3 or more for even higher quality)
                    const devicePixelRatio = window.devicePixelRatio || 1; // Account for high-DPI displays
                    const scale = (containerWidth / viewport.width) * baseScale * devicePixelRatio;
                    const scaledViewport = page.getViewport({ scale });

                    // Set canvas dimensions with higher resolution
                    canvas.width = scaledViewport.width;
                    canvas.height = scaledViewport.height;

                    // Set container height proportional to page aspect ratio
                    pageContainer.style.height = (containerWidth * (viewport.height / viewport.width)) + 'px';
                    pageContainer.dataset.scale = scale;

                    // Render the page with high resolution
                    const context = canvas.getContext('2d', { alpha: false }); // Disable alpha for performance
                    await page.render({ canvasContext: context, viewport: scaledViewport }).promise;
                }

                // Position images on their pages - use saved positions when available
                state.images.forEach((image, idx) => {
                    // Use positionImageFromData instead of centerImageOnPage to respect existing positions
                    positionImageFromData(idx);
                });
            } catch (error) {
                console.error('PDF loading failed:', error);
                alert('Failed to load PDF: ' + error.message);
            }
        }

        function showPageSelectionDialog() {
            // Create a dialog for page selection
            const pageCount = state.pages.length;
            if (pageCount <= 1) return 1; // If only one page, no dialog needed
            
            const dialog = document.createElement('div');
            dialog.className = 'page-selection-dialog';
            dialog.style.position = 'fixed';
            dialog.style.top = '50%';
            dialog.style.left = '50%';
            dialog.style.transform = 'translate(-50%, -50%)';
            dialog.style.padding = '20px';
            dialog.style.backgroundColor = 'white';
            dialog.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
            dialog.style.borderRadius = '8px';
            dialog.style.zIndex = '1000';
            dialog.style.width = '300px';
            dialog.style.maxWidth = '90%';
            
            // Add heading
            const heading = document.createElement('h3');
            heading.textContent = 'Select page to add image to:';
            heading.style.marginTop = '0';
            dialog.appendChild(heading);
            
            // Create page buttons
            const buttonsContainer = document.createElement('div');
            buttonsContainer.style.display = 'flex';
            buttonsContainer.style.flexWrap = 'wrap';
            buttonsContainer.style.gap = '10px';
            buttonsContainer.style.marginTop = '15px';
            
            // Create a promise to return the selected page
            return new Promise(resolve => {
                for (let i = 1; i <= pageCount; i++) {
                    const button = document.createElement('button');
                    button.textContent = `Page ${i}`;
                    button.style.padding = '8px 12px';
                    button.style.cursor = 'pointer';
                    button.style.backgroundColor = '#f0f0f0';
                    button.style.border = '1px solid #ddd';
                    button.style.borderRadius = '4px';
                    
                    button.addEventListener('click', () => {
                        document.body.removeChild(dialog);
                        resolve(i);
                    });
                    
                    button.addEventListener('mouseover', () => {
                        button.style.backgroundColor = '#e0e0e0';
                    });
                    
                    button.addEventListener('mouseout', () => {
                        button.style.backgroundColor = '#f0f0f0';
                    });
                    
                    buttonsContainer.appendChild(button);
                }
                
                dialog.appendChild(buttonsContainer);
                document.body.appendChild(dialog);
                
                // Add a cancel button
                const cancelButton = document.createElement('button');
                cancelButton.textContent = 'Cancel';
                cancelButton.style.padding = '8px 12px';
                cancelButton.style.cursor = 'pointer';
                cancelButton.style.backgroundColor = '#f0f0f0';
                cancelButton.style.border = '1px solid #ddd';
                cancelButton.style.borderRadius = '4px';
                cancelButton.style.marginTop = '15px';
                cancelButton.style.width = '100%';
                
                cancelButton.addEventListener('click', () => {
                    document.body.removeChild(dialog);
                    resolve(1); // Default to page 1 if canceled
                });
                
                dialog.appendChild(cancelButton);
            });
        }

            // Center image on a specific page
            function centerImageOnPage(imageIndex, pageNum) {
                if (imageIndex < 0 || imageIndex >= state.images.length) return;
                if (pageNum < 1 || pageNum > state.pages.length) {
                    console.warn(`Invalid page number: ${pageNum}, defaulting to page 1`);
                    pageNum = 1;
                }
                
                const imageData = state.images[imageIndex];
                const containers = document.querySelectorAll('.image-container');
                if (!containers[imageIndex]) return;
                
                const container = containers[imageIndex];
                const img = container.querySelector('.preview-image');
                
                const pageContainer = state.pages[pageNum - 1];
                if (!pageContainer) {
                    console.error(`Page container not found for page ${pageNum}`);
                    return;
                }
                
                const rect = pageContainer.getBoundingClientRect();
                const pagesRect = pagesContainer.getBoundingClientRect();
                const xPercent = 50;
                const yPercent = 50;
                
                // Calculate the absolute Y position based on the page's position
                const absoluteY = rect.top - pagesRect.top + (rect.height / 2);
                
                console.log(`Centering image ${imageIndex} on page ${pageNum} at ${xPercent}%, ${yPercent}% (${absoluteY}px absolute Y)`);
                
                container.style.left = xPercent + '%';
                container.style.top = absoluteY + 'px';
                container.style.transform = 'translate(-50%, -50%)';
                
                imageData.xPercentage = xPercent;
                imageData.yPercentage = yPercent;
                imageData.pageNumber = pageNum;
                
                updatePdfCoordinates(imageIndex);
            }

           
            function updatePdfCoordinates(imageIndex) {
            if (imageIndex < 0 || imageIndex >= state.images.length) return;
            
            const imageData = state.images[imageIndex];
            const container = document.querySelectorAll('.image-container')[imageIndex];
            const img = container.querySelector('.preview-image');
            
            if (!container || !img) return;
            
            // Find which page the image is on
            const pageNum = imageData.pageNumber;
            const pageIndex = pageNum - 1;
            
            if (pageIndex < 0 || pageIndex >= state.pages.length) return;
            
            const pageContainer = state.pages[pageIndex];
            const pageRect = pageContainer.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            
            // Calculate center point of the image
            const centerX = containerRect.left + containerRect.width / 2;
            const centerY = containerRect.top + containerRect.height / 2;
            
            // Calculate percentage positions within the page
            let xPercentage = ((centerX - pageRect.left) / pageRect.width) * 100;
            let yPercentage = ((centerY - pageRect.top) / pageRect.height) * 100;
            
            // Get current image dimensions in pixels
            const imgWidth = parseFloat(img.style.width);
            const imgHeight = parseFloat(img.style.height);
            
            // Calculate image boundaries as percentages of page dimensions
            const halfWidthPercent = (imgWidth / 2 / pageRect.width) * 100;
            const halfHeightPercent = (imgHeight / 2 / pageRect.height) * 100;
            
            // Constrain positions to keep image within page boundaries
            xPercentage = Math.max(halfWidthPercent, Math.min(100 - halfWidthPercent, xPercentage));
            yPercentage = Math.max(halfHeightPercent, Math.min(100 - halfHeightPercent, yPercentage));
            
            // Update container position to reflect constraints
            container.style.left = xPercentage + '%';
            const absoluteY = pageRect.top - pagesContainer.getBoundingClientRect().top + (pageRect.height * yPercentage / 100);
            container.style.top = absoluteY + 'px';
            container.style.transform = 'translate(-50%, -50%)';
            
            // Update image data with percentages
            imageData.xPercentage = parseFloat(xPercentage.toFixed(1));
            imageData.yPercentage = parseFloat(yPercentage.toFixed(1));
            
            // Update dimensions
            imageData.width = imgWidth;
            imageData.height = imgHeight;
            
            // Calculate PDF coordinates correctly based on PDF dimensions
            // This is critical for proper positioning in the final PDF
            const pdfPageWidth = state.pageWidths[pageIndex] || 612;  // Default letter width
            const pdfPageHeight = state.pageHeights[pageIndex] || 792; // Default letter height
            
            // PDF coordinate system starts from bottom-left, while browser is top-left
            // Convert Y coordinate to match PDF coordinate system
            imageData.pdfX = (xPercentage / 100) * pdfPageWidth;
            imageData.pdfY = ((100 - yPercentage) / 100) * pdfPageHeight; // Invert Y-axis
            
            // Store actual physical dimensions in PDF units
            // Calculate the physical width and height based on percentages of the PDF
            imageData.pdfWidth = (imgWidth / pageRect.width) * pdfPageWidth;
            imageData.pdfHeight = (imgHeight / pageRect.height) * pdfPageHeight;
            
            console.log(`Image ${imageIndex} updated: page=${pageNum}, pos=[${xPercentage.toFixed(1)}%, ${yPercentage.toFixed(1)}%], size=[${imageData.width}px x ${imageData.height}px], PDF pos=[${imageData.pdfX.toFixed(1)}, ${imageData.pdfY.toFixed(1)}], PDF size=[${imageData.pdfWidth.toFixed(1)} x ${imageData.pdfHeight.toFixed(1)}]`);
        }

            // Update form data with all image information
           function updateFormData() {
                // First update all coordinates
                state.images.forEach((image, idx) => {
                    updatePdfCoordinates(idx);
                });
                
                // Create a full data object with all needed information
                const formData = {
                    images: state.images.map(img => ({
                        path: img.path,
                        pageNumber: img.pageNumber,
                        xPercentage: img.xPercentage,
                        yPercentage: img.yPercentage,
                        width: img.width,
                        height: img.height,
                        // Include the actual PDF coordinates and dimensions
                        pdfX: img.pdfX,
                        pdfY: img.pdfY,
                        pdfWidth: img.pdfWidth,
                        pdfHeight: img.pdfHeight,
                        ratio: img.ratio
                    })),
                    // Send physical dimensions
                    pageWidths: state.pageWidths,
                    pageHeights: state.pageHeights,
                    containerWidth: pagesContainer.clientWidth || 600
                };
                
                // Update the form input value
                imagesDataInput.value = JSON.stringify(formData);
            }
            // Handle drag move
            
            document.addEventListener('mousemove', (e) => {
                if (!state.isDragging || state.activeImageIndex < 0) return;

                const imageData = state.images[state.activeImageIndex];
                const containers = document.querySelectorAll('.image-container');
                const container = containers[state.activeImageIndex];
                const img = container.querySelector('.preview-image');
                const pagesRect = pagesContainer.getBoundingClientRect();
                const imgRect = img.getBoundingClientRect();
                const dx = e.clientX - state.startX;
                const dy = e.clientY - state.startY;

                const centerX = imgRect.left + imgRect.width / 2 + dx;
                const centerY = imgRect.top + imgRect.height / 2 + dy;

                // Find target page
                let targetPage = 1;
                let pageOffsetY = 0;
                let currentPageRect = null;
                
                for (let i = 0; i < state.pages.length; i++) {
                    const pageRect = state.pages[i].getBoundingClientRect();
                    if (centerY >= pageRect.top && centerY <= pageRect.bottom) {
                        targetPage = i + 1;
                        pageOffsetY = pageRect.top - pagesRect.top;
                        currentPageRect = pageRect;
                        break;
                    }
                }

                // Handle out-of-bounds pages
                if (!currentPageRect) {
                    if (centerY < state.pages[0].getBoundingClientRect().top) {
                        targetPage = 1;
                        currentPageRect = state.pages[0].getBoundingClientRect();
                    } else {
                        targetPage = state.pages.length;
                        currentPageRect = state.pages[state.pages.length - 1].getBoundingClientRect();
                    }
                }

                // Calculate position with constraints
                const halfWidth = imgRect.width / 2;
                const halfHeight = imgRect.height / 2;
                
                let xPercent = ((centerX - currentPageRect.left) / currentPageRect.width) * 100;
                let relativeY = centerY - currentPageRect.top;
                let yPercent = (relativeY / currentPageRect.height) * 100;
                
                // Constrain percentages
                const halfWidthPercent = (halfWidth / currentPageRect.width) * 100;
                const halfHeightPercent = (halfHeight / currentPageRect.height) * 100;
                xPercent = Math.max(halfWidthPercent, Math.min(100 - halfWidthPercent, xPercent));
                yPercent = Math.max(halfHeightPercent, Math.min(100 - halfHeightPercent, yPercent));
                
                // Update container position
                const absoluteY = currentPageRect.top - pagesRect.top + (currentPageRect.height * yPercent / 100);
                container.style.left = xPercent + '%';
                container.style.top = absoluteY + 'px';
                
                // Update image data
                imageData.xPercentage = parseFloat(xPercent.toFixed(1));
                imageData.yPercentage = parseFloat(yPercent.toFixed(1));
                imageData.pageNumber = targetPage;
                
                // Debug log
                console.log(`Dragging image to page ${targetPage}, position: [${xPercent.toFixed(1)}%, ${yPercent.toFixed(1)}%]`);
                
                updatePdfCoordinates(state.activeImageIndex);
                updateFormData();

                state.startX = e.clientX;
                state.startY = e.clientY;
                
                e.preventDefault();
            }, { passive: false });

            document.addEventListener('click', (e) => {
                const isMobile = window.innerWidth <= 768;
                if (!isMobile) return;
                
                const isSidebar = e.target.closest('.sidebar');
                const isToggleButton = e.target.closest('.mobile-menu-toggle');
                
                if (!isSidebar && !isToggleButton && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                    mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>'; // Reset to hamburger icon
                }
            });
        

            // Handle drag end
            document.addEventListener('mouseup', () => {
                state.isDragging = false;
                state.isResizing = false;
                
                const containers = document.querySelectorAll('.image-container');
                containers.forEach(container => {
                    container.style.transition = '';
                });
                
                const images = document.querySelectorAll('.preview-image');
                images.forEach(img => {
                    img.style.transition = '';
                });
            });

            
            // Handle resize
           document.addEventListener('mousemove', (e) => {
                if (!state.isResizing || state.activeImageIndex < 0) return;
                
                const imageData = state.images[state.activeImageIndex];
                const containers = document.querySelectorAll('.image-container');
                const container = containers[state.activeImageIndex];
                const img = container.querySelector('.preview-image');
                
                const dx = e.clientX - state.startX;
                const newWidth = Math.max(50, state.startWidth + dx);
                let newHeight;
                
                if (state.lockAspectRatio) {
                    // Maintain original aspect ratio
                    newHeight = newWidth / imageData.ratio;
                } else {
                    // Allow independent height resizing
                    const dy = e.clientY - state.startY;
                    newHeight = Math.max(50, state.startHeight + dy);
                }
                
                img.style.width = `${newWidth}px`;
                img.style.height = `${newHeight}px`;
                
                imageData.width = newWidth;
                imageData.height = newHeight;
                
                updatePdfCoordinates(state.activeImageIndex);
                updateFormData(); // Update form data after resize
            });

            // Touch-based resizing for mobile
            document.addEventListener('touchmove', (e) => {
                if (!state.isDragging || state.activeImageIndex < 0) return;
                
                const imageData = state.images[state.activeImageIndex];
                const containers = document.querySelectorAll('.image-container');
                const container = containers[state.activeImageIndex];
                const img = container.querySelector('.preview-image');
                const pagesRect = pagesContainer.getBoundingClientRect();
                const imgRect = img.getBoundingClientRect();
                const dx = e.touches[0].clientX - state.startX;
                const dy = e.touches[0].clientY - state.startY;

                const centerX = imgRect.left + imgRect.width / 2 + dx;
                const centerY = imgRect.top + imgRect.height / 2 + dy;

                // Find target page
                let targetPage = 1;
                let currentPageRect = null;
                
                for (let i = 0; i < state.pages.length; i++) {
                    const pageRect = state.pages[i].getBoundingClientRect();
                    if (centerY >= pageRect.top && centerY <= pageRect.bottom) {
                        targetPage = i + 1;
                        currentPageRect = pageRect;
                        break;
                    }
                }
                
                // Handle out-of-bounds pages
                if (!currentPageRect) {
                    if (centerY < state.pages[0].getBoundingClientRect().top) {
                        targetPage = 1;
                        currentPageRect = state.pages[0].getBoundingClientRect();
                    } else {
                        targetPage = state.pages.length;
                        currentPageRect = state.pages[state.pages.length - 1].getBoundingClientRect();
                    }
                }
                
                // Calculate position with constraints
                const halfWidth = imgRect.width / 2;
                const halfHeight = imgRect.height / 2;
                
                let xPercent = ((centerX - currentPageRect.left) / currentPageRect.width) * 100;
                let relativeY = centerY - currentPageRect.top;
                let yPercent = (relativeY / currentPageRect.height) * 100;
                
                // Constrain percentages
                const halfWidthPercent = (halfWidth / currentPageRect.width) * 100;
                const halfHeightPercent = (halfHeight / currentPageRect.height) * 100;
                xPercent = Math.max(halfWidthPercent, Math.min(100 - halfWidthPercent, xPercent));
                yPercent = Math.max(halfHeightPercent, Math.min(100 - halfHeightPercent, yPercent));
                
                // Update container position
                const absoluteY = currentPageRect.top - pagesRect.top + (currentPageRect.height * yPercent / 100);
                container.style.left = xPercent + '%';
                container.style.top = absoluteY + 'px';
                
                // Update image data
                imageData.xPercentage = parseFloat(xPercent.toFixed(1));
                imageData.yPercentage = parseFloat(yPercent.toFixed(1));
                imageData.pageNumber = targetPage;
                
                updatePdfCoordinates(state.activeImageIndex);
                updateFormData();
                
                state.startX = e.touches[0].clientX;
                state.startY = e.touches[0].clientY;
                
                e.preventDefault();
            }, { passive: false });

            function updatePageHighlight() {
            // Remove highlight from all pages
            state.pages.forEach(page => {
                page.classList.remove('active-page');
            });
            
            // If we have an active image, highlight its page
            if (state.activeImageIndex >= 0) {
                const pageNum = state.images[state.activeImageIndex].pageNumber;
                if (pageNum >= 1 && pageNum <= state.pages.length) {
                    state.pages[pageNum - 1].classList.add('active-page');
                }
            }
        }
                          
            // Add images button
            addImagesBtn.addEventListener('click', () => {
                imageFileInput.click();
            });
            aspectRatioButton.addEventListener('click', () => {
                state.lockAspectRatio = !state.lockAspectRatio;
                aspectRatioButton.classList.toggle('btn-locked', state.lockAspectRatio);
                aspectRatioButton.innerHTML = `<i class="fas fa-compress"></i> ${state.lockAspectRatio ? 'Unlock Aspect Ratio' : 'Lock Aspect Ratio'}`;
            });

            // Handle file input change
            imageFileInput.addEventListener('change', async () => {
                if (imageFileInput.files.length > 0) {
                    // Show page selection dialog only if there are multiple pages
                    const targetPage = state.pages.length > 1 ? await showPageSelectionDialog() : 1;
                    
                    for (let i = 0; i < imageFileInput.files.length; i++) {
                        await addNewImage(imageFileInput.files[i], targetPage);
                    }
                    // Clear the input
                    imageFileInput.value = '';
                }
            });

            // Drag-and-drop image upload
            previewContainer.addEventListener('dragover', (e) => {
                e.preventDefault();
                previewContainer.classList.add('dragover');
            });

            previewContainer.addEventListener('dragleave', () => {
                previewContainer.classList.remove('dragover');
            });

            previewContainer.addEventListener('drop', async (e) => {
                e.preventDefault();
                previewContainer.classList.remove('dragover');
                
                if (e.dataTransfer.files.length > 0) {
                    // Get drop coordinates
                    const dropY = e.clientY;
                    const pagesRect = pagesContainer.getBoundingClientRect();
                    
                    // Determine which page was dropped on
                    let targetPage = 1;
                    for (let i = 0; i < state.pages.length; i++) {
                        const pageRect = state.pages[i].getBoundingClientRect();
                        if (dropY >= pageRect.top && dropY <= pageRect.bottom) {
                            targetPage = i + 1;
                            break;
                        }
                    }
                    
                    for (let i = 0; i < e.dataTransfer.files.length; i++) {
                        const file = e.dataTransfer.files[i];
                        if (file && file.type.startsWith('image/')) {
                            await addNewImage(file, targetPage);
                        }
                    }
                }
            });

            // Form submission
            saveForm.addEventListener('submit', (e) => {
                // Make sure form data is updated before submitting
                updateFormData();
                
                // Check if we have images to process
                if (state.images.length === 0) {
                    e.preventDefault();
                    alert('Please add at least one image to insert into the PDF.');
                    return;
                }
                
                // Set the flag to prevent multiple submissions
                if (saveForm.dataset.submitting === "true") {
                    e.preventDefault();
                    return;
                }
                
                saveForm.dataset.submitting = "true";
                
                const submitBtn = saveForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
            
            
        });

            // Initialize
            await loadPdf();
            initializeImages();
            updateFormData();
            
            // Touch event handlers (for mobile support)
            document.addEventListener('touchmove', (e) => {
                if (!state.isDragging || state.activeImageIndex < 0) return;
                
                const imageData = state.images[state.activeImageIndex];
                const containers = document.querySelectorAll('.image-container');
                const container = containers[state.activeImageIndex];
                const img = container.querySelector('.preview-image');
                const pagesRect = pagesContainer.getBoundingClientRect();
                const imgRect = img.getBoundingClientRect();
                const dx = e.touches[0].clientX - state.startX;
                const dy = e.touches[0].clientY - state.startY;

                const centerX = imgRect.left + imgRect.width / 2 + dx;
                const centerY = imgRect.top + imgRect.height / 2 + dy;

                // Find target page
                let targetPage = 1;
                let pageOffsetY = 0;
                let currentPageRect = null;
                
                for (let i = 0; i < state.pages.length; i++) {
                    const pageRect = state.pages[i].getBoundingClientRect();
                    if (centerY >= pageRect.top && centerY <= pageRect.bottom) {
                        targetPage = i + 1;
                        pageOffsetY = pageRect.top - pagesRect.top;
                        currentPageRect = pageRect;
                        break;
                    }
                }
                
                // Handle out-of-bounds pages
                if (!currentPageRect) {
                    if (centerY < state.pages[0].getBoundingClientRect().top) {
                        targetPage = 1;
                        currentPageRect = state.pages[0].getBoundingClientRect();
                        pageOffsetY = currentPageRect.top - pagesRect.top;
                    } else if (centerY > state.pages[state.pages.length - 1].getBoundingClientRect().bottom) {
                        targetPage = state.pages.length;
                        currentPageRect = state.pages[state.pages.length - 1].getBoundingClientRect();
                        pageOffsetY = currentPageRect.top - pagesRect.top;
                    }
                }
                
                // Calculate position with constraints
                const halfWidth = imgRect.width / 2;
                const halfHeight = imgRect.height / 2;
                
                let xPercent = ((centerX - currentPageRect.left) / currentPageRect.width) * 100;
                let relativeY = centerY - currentPageRect.top;
                let yPercent = (relativeY / currentPageRect.height) * 100;
                
                // Constrain percentages
                const halfWidthPercent = (halfWidth / currentPageRect.width) * 100;
                const halfHeightPercent = (halfHeight / currentPageRect.height) * 100;
                xPercent = Math.max(halfWidthPercent, Math.min(100 - halfWidthPercent, xPercent));
                yPercent = Math.max(halfHeightPercent, Math.min(100 - halfHeightPercent, yPercent));
                
                // Update container position
                const absoluteY = currentPageRect.top - pagesRect.top + (currentPageRect.height * yPercent / 100);
                container.style.left = xPercent + '%';
                container.style.top = absoluteY + 'px';
                container.style.transform = 'translate(-50%, -50%)';
                
                // Update image data
                imageData.xPercentage = xPercent.toFixed(1);
                imageData.yPercentage = yPercent.toFixed(1);
                imageData.pageNumber = targetPage;
                
                updatePdfCoordinates(state.activeImageIndex);
                updateFormData();
                
                state.startX = e.touches[0].clientX;
                state.startY = e.touches[0].clientY;
                
                e.preventDefault();
            }, { passive: false });
            
            document.addEventListener('touchend', () => {
                state.isDragging = false;
                state.isResizing = false;
                
                const containers = document.querySelectorAll('.image-container');
                containers.forEach(container => {
                    container.style.transition = '';
                });
                
                const images = document.querySelectorAll('.preview-image');
                images.forEach(img => {
                    img.style.transition = '';
                });
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>