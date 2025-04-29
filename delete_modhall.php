<?php
session_start();
include('config.php'); // Include database connection

// Check if the user is logged in and is a Professor
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'Professor') {
    header("Location: login.php");
    exit();
}

// Check if booking_id is set in the URL
if (isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']); // Sanitize input

    // Prepare the DELETE statement
    $query = "DELETE FROM hall_bookings WHERE booking_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $booking_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Booking deleted successfully.');
                window.location.href = 'delete_modify_hall.php';
              </script>";
    } else {
        echo "<script>
                alert('Error deleting booking.');
                window.location.href = 'delete_modify_hall.php';
              </script>";
    }

    mysqli_stmt_close($stmt);
} else {
    echo "<script>
            alert('Invalid request.');
            window.location.href = 'delete_modify_hall.php';
          </script>";
}

mysqli_close($conn);
?>
