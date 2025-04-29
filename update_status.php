<?php
session_start();
include 'config.php';

// Ensure the user is logged in
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['booking_id'], $_POST['status'])) {
    $booking_id = intval($_POST['booking_id']); 
    $status = trim($_POST['status']); 

    // Validate status
    if (!in_array($status, ['Approved', 'Rejected'])) {
        die("Invalid status value.");
    }

    // If rejected, ensure a reason is provided
    if ($status == 'Rejected') {
        if (!isset($_POST['reject_reason']) || empty(trim($_POST['reject_reason']))) {
            die("Rejection reason is required.");
        }
        $reject_reason = trim($_POST['reject_reason']);

        // Update status & rejection reason
        $sql = "UPDATE hall_bookings SET status = ?, reject_reason = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $status, $reject_reason, $booking_id);
    } else {
        // Update status only (for approval)
        $sql = "UPDATE hall_bookings SET status = ?, reject_reason = NULL WHERE booking_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $booking_id);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Booking status updated successfully!'); window.location.href='view_bookings.php';</script>";
    } else {
        die("Error updating status: " . $stmt->error);
    }
    $stmt->close();
}

$conn->close();
?>
