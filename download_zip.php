<?php
// download_zip.php - Simple script to serve ZIP file
if (isset($_GET['file'])) {
    $filename = basename($_GET['file']); // Prevent directory traversal
    $filepath = 'temp_downloads/' . $filename;
    
    if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'zip') {
        // Clear output buffer
        ob_clean();
        
        // Force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: ' . filesize($filepath));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($filepath);
        exit;
    } else {
        echo "File not found or invalid file type.";
    }
} else {
    echo "No file specified.";
}
?>