<?php
session_start();
// Include database connection
include 'config.php';

// Check if the user is logged in and authorized
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Check if school_id is provided in the POST request
if (isset($_POST['school_id'])) {
    $school_id = $_POST['school_id'];

    try {
        // Prepare the DELETE statement
        $stmt = $conn->prepare("DELETE FROM schools WHERE school_id = ?");
        $stmt->bind_param("i", $school_id);

        // Execute the statement
        if ($stmt->execute()) {
            echo "<script>alert('School deleted successfully!'); window.location.href='modify_school.php';</script>";
        } else {
            echo "<script>alert('Failed to delete the school.'); window.location.href='modify_school.php';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='modify_school.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request. School ID not provided.'); window.location.href='modify_school.php';</script>";
}
?>
