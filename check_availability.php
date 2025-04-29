<?php
session_start();
include('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hall_id = $_POST['hall_id'];
    $date = $_POST['date'];
    $timing = $_POST['time_slots'];

    $isAvailable = !checkAvailability($hall_id, $date, $time_slots);
    echo $isAvailable ? 'true' : 'false';
}

function checkAvailability($hall_id, $date, $time_slots) {
    global $conn;
    $query = "SELECT * FROM bookings WHERE hall_id = ? AND from_date = ? AND time_slots = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $hall_id, $date, $time_slots);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}
?>