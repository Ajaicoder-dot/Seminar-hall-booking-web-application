<?php
session_start();
// Include database connection
include 'config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Check if department_id is provided
if (isset($_POST['department_id'])) {
    $department_id = $_POST['department_id'];

    // Prepare and execute the deletion query
    try {
        // Use a prepared statement to prevent SQL injection
        $stmt = $conn->prepare("DELETE FROM departments WHERE department_id = ?");
        $stmt->bind_param("i", $department_id);

        // Execute the statement
        if ($stmt->execute()) {
            // Redirect to the modify departments page with a success message
            $_SESSION['message'] = "Department deleted successfully!";
            header("Location: modify_dept.php");
            exit();
        } else {
            // If execution fails
            $_SESSION['message'] = "Error deleting department.";
            header("Location: modify_dept.php");
            exit();
        }
    } catch (Exception $e) {
        // Handle any errors
        $_SESSION['message'] = "Error: " . $e->getMessage();
        header("Location: modify_dept.php");
        exit();
    }
} else {
    // If no department_id is provided, redirect back to the modify departments page
    $_SESSION['message'] = "No department ID provided.";
    header("Location: modify_dept.php");
    exit();
}
?>
