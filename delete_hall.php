<?php
// Start the session at the very beginning
session_start();

// Include configuration
include('config.php');

// Ensure user is logged in and is either Admin or HOD
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    // Instead of using header redirect, use JavaScript
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

// Check if hall ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Use JavaScript for redirection
    echo "<script>alert('Invalid hall ID'); window.location.href = 'modify_hall.php';</script>";
    exit();
}

$hall_id = $_GET['id'];

// Delete the hall
$query = "DELETE FROM halls WHERE hall_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $hall_id);

if ($stmt->execute()) {
    // Success - use JavaScript for redirection
    echo "<script>alert('Hall deleted successfully'); window.location.href = 'modify_hall.php';</script>";
} else {
    // Error - use JavaScript for redirection
    echo "<script>alert('Error deleting hall: " . $conn->error . "'); window.location.href = 'modify_hall.php';</script>";
}

$stmt->close();
$conn->close();
?>
