<?php
if (isset($_POST['hall_id'])) {
    $hallId = $_POST['hall_id'];

    // Database connection
 include 'config.php';
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to fetch hall details
    $sql = "SELECT h.hall_type, s.school_name, d.department_name
            FROM halls h
            LEFT JOIN hall_types ht ON h.hall_type_id = ht.hall_type_id
            LEFT JOIN schools s ON h.school_id = s.school_id
            LEFT JOIN departments d ON h.department_id = d.department_id
            WHERE h.hall_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $hallId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode($data);
    } else {
        echo json_encode(['hall_type' => 'N/A', 'school_name' => 'N/A', 'department_name' => 'N/A']);
    }

    $stmt->close();
    $conn->close();
}
?>
