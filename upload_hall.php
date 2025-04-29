<?php
// Include database connection
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a file was uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Get the file details
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Get the upload directory from the form
        $uploadDir = $_POST['university/images']; // university/images/
        $uploadDir = rtrim($uploadDir, '/') . '/'; // Ensure the path ends with a slash
        $destPath = $uploadDir . $fileName;

        // Define allowed file types
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedExtensions)) {
            // Ensure the upload directory exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
            }

            // Move the uploaded file to the destination directory
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Save the file path to the database
                $sql = "INSERT INTO halls (image) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $destPath);
                $stmt->execute();

                echo "Image uploaded and saved to: $destPath";
            } else {
                echo "Error moving the uploaded file.";
            }
        } else {
            echo "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    } else {
        echo "No file uploaded or there was an upload error.";
    }
}
?>
