<?php
 include('config.php');
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the posted data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Prepare the update statement
    $query = "UPDATE users SET name = ?, role = ? WHERE email = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("sss", $name, $role, $email); // Bind parameters
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Update successful
            header("Location: profile.php?update=success");
        } else {
            // No changes made
            header("Location: profile.php?update=none");
        }
        $stmt->close();
    } else {
        // Query preparation failed
        header("Location: profile.php?update=error");
    }
} else {
    // Redirect if accessed directly
    header("Location: profile.php");
    exit();
}
?>