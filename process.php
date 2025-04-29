<?php
$servername = "localhost"; // Change to your database server
$username = "root";        // Your database username
$password = "";            // Your database password
$dbname = "university"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    // Capture school data
    $school_name = $_POST['school_name'];
    $dean_name = $_POST['dean_name'];
    $dean_contact_number = $_POST['dean_contact_number'];
    $dean_email = $_POST['dean_email'];
    $dean_intercom = $_POST['dean_intercom'];
    $dean_status = $_POST['dean_status'];

    // Insert school data
    $sql_school = "INSERT INTO schools (school_name, dean_name, dean_contact_number, dean_email, dean_intercome, dean_status) 
                   VALUES ('$school_name', '$dean_name', '$dean_contact_number', '$dean_email', '$dean_intercom', '$dean_status')";

    if ($conn->query($sql_school) === TRUE) {
        $school_id = $conn->insert_id; // Get the inserted school ID

        // Capture department data
        $department_name = $_POST['department_name'];
        $hod_name = $_POST['hod_name'];
        $hod_contact_mobile = $_POST['hod_contact_mobile'];
        $designation = $_POST['designation'];
        $hod_contact_email = $_POST['hod_contact_email'];
        $hod_intercom = $_POST['hod_intercom'];

        // Insert department data
        $sql_department = "INSERT INTO departments (school_id, department_name, hod_name, hod_contact_mobile, designation, hod_contact_email, hod_intercom) 
                           VALUES ('$school_id', '$department_name', '$hod_name', '$hod_contact_mobile', '$designation', '$hod_contact_email', '$hod_intercom')";

        if ($conn->query($sql_department) === TRUE) {
            echo "School and Department added successfully!";
        } else {
            echo "Error adding department: " . $conn->error;
        }
    } else {
        echo "Error adding school: " . $conn->error;
    }
}

$conn->close();
?>
