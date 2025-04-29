<?php
session_start();
include('config.php');

// Ensure user is logged in and is either Admin or HOD
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $hall_id = $_GET['id'];
    $action = $_GET['action'];
    
    // Validate hall_id
    if (!is_numeric($hall_id)) {
        $_SESSION['error'] = "Invalid hall ID.";
        header("Location: modify_hall.php");
        exit();
    }
    
    // Set is_archived based on action
    $is_archived = ($action == 'archive') ? 1 : 0;
    
    // Update the hall's archive status
    $query = "UPDATE halls SET is_archived = ?, archived_at = ? WHERE hall_id = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $archived_at = ($action == 'archive') ? date('Y-m-d H:i:s') : NULL;
        $stmt->bind_param("isi", $is_archived, $archived_at, $hall_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = ($action == 'archive') 
                ? "Hall has been archived successfully." 
                : "Hall has been restored successfully.";
        } else {
            $_SESSION['error'] = "Failed to update hall status: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error: " . $conn->error;
    }
} else {
    $_SESSION['error'] = "Missing required parameters.";
}

header("Location: modify_hall.php" . (isset($_GET['show_archived']) ? "?show_archived=1" : ""));
exit();
?>