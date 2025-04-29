<?php
session_start();
include('config.php');

// Ensure the user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';

// Check if request ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Request ID is required']);
    exit();
}

$request_id = (int)$_GET['id'];

// Prepare query based on user role
if ($is_admin) {
    // Admin can see all requests
    $query = "SELECT r.*, u.name as user_name, u.email as user_email 
              FROM user_requests r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
} else {
    // Regular users can only see their own requests
    $query = "SELECT * FROM user_requests WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $request_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Request not found or access denied']);
    exit();
}

$request_data = $result->fetch_assoc();
$stmt->close();

// Return the request data as JSON
header('Content-Type: application/json');
echo json_encode($request_data);
?>