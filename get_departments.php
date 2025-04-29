<?php
include('config.php');

if (isset($_GET['school_id'])) {
    $school_id = $_GET['school_id'];
    
    // Fetch departments based on the selected school
    $query = "SELECT department_id, department_name FROM departments WHERE school_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    echo json_encode($departments);
}
?>




