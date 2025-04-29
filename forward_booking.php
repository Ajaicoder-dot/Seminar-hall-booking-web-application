<?php
session_start();
include 'config.php';

// Ensure user is logged in and is an HOD
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'HOD') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['booking_id']) && isset($_POST['forward'])) {
    // When processing the forward action
    $booking_id = $_POST['booking_id'];
    $user_id = $_SESSION['user_id']; // Current HOD's ID
    
    // Get the hall department ID
    $hall_query = "SELECT h.department_id, d.department_name 
                   FROM hall_bookings hb 
                   JOIN halls h ON hb.hall_id = h.hall_id 
                   JOIN departments d ON h.department_id = d.department_id
                   WHERE hb.booking_id = '$booking_id'";
    $hall_result = $conn->query($hall_query);
    
    if ($hall_result && $hall_result->num_rows > 0) {
        $hall_row = $hall_result->fetch_assoc();
        $hall_dept_id = $hall_row['department_id'];
        $hall_dept_name = $hall_row['department_name'];
        
        // Update the booking - only do this once
        $update_sql = "UPDATE hall_bookings SET 
                      forwarded = 1, 
                      forwarded_to = '$hall_dept_id', 
                      forwarded_by_id = '$user_id' 
                      WHERE booking_id = '$booking_id'";
        
        if ($conn->query($update_sql)) {
            $_SESSION['success_msg'] = "Booking successfully forwarded to $hall_dept_name department.";
        } else {
            $_SESSION['error_msg'] = "Error forwarding booking: " . $conn->error;
        }
    } else {
        $_SESSION['error_msg'] = "Could not find hall department information.";
    }
    
    header("Location: view_bookings.php");
    exit();
} else {
    header("Location: view_bookings.php");
    exit();
}
?>