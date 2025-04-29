<?php 
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $type = $_POST['type'];
    
    if ($type == 'school') {
        $school_name = $_POST['school']; // Updated to match the input name
        $dean_name = $_POST['dean-name'];
        $dean_contact_number = $_POST['dean-contact']; // Updated to match the input name
        $dean_email = $_POST['dean-email'];
        $dean_intercome = $_POST['dean-intercom']; // Updated to match the input name
        $dean_status = $_POST['dean-status'];

        // Insert into schools table
        $sql = "INSERT INTO schools (school_name, dean_name, dean_contact_number, dean_email, dean_intercome, dean_status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("ssssss", $school_name, $dean_name, $dean_contact_number, $dean_email, $dean_intercome, $dean_status);
        
        if (!$stmt->execute()) {
            die("Error executing statement: " . $stmt->error);
        }
    }else {
        $school_id = $_POST['school_name']; // This should match the select name
        $department_name = $_POST['department-name']; // Ensure this matches the input name
        $hod_name = $_POST['hod-name']; // Ensure this matches the input name
        $hod_contact_mobile = $_POST['hod-contact']; // Ensure this matches the input name
        $designation = $_POST['designation']; // Ensure this matches the input name
        $hod_email = $_POST['hod-email']; // Ensure this matches the input name
        $hod_intercom = $_POST['hod-intercom']; // Ensure this matches the input name
    
        // Insert into departments table
        $sql = "INSERT INTO departments (school_id, department_name, hod_name, hod_contact_mobile, designation, hod_contact_email, hod_intercom) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
    
        $stmt->bind_param("issssss", $school_id, $department_name, $hod_name, $hod_contact_mobile, $designation, $hod_email, $hod_intercom);
        
        if (!$stmt->execute()) {
            die("Error executing statement: " . $stmt->error);
        }
    }
    $stmt->close();
    $conn->close();
    // Redirect or display a success message
    header("Location: success.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/design.css" />
     
    <title>Add Hall</title>
    <style>
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        form {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 50px;
            border-radius: 15px;
            background-color: white;
        }
        .btn {
            margin-top: 20px; /* Space between form and submit button */
        }
        select.form-control {
            padding : 12px; /* Match dropdown style with other fields */
        }
    </style>
</head>
<body>

    <?php include 'navbar.php' ?>

    <div class="main-content mt-3">
    
    <h2 style="text-align: center;">Add School or Department</h2>

    <form method="POST" style="margin:30px 100px;">
        <label for="type" style="margin:0 0 10px 0; font-size: 20px; font-weight: 600;">Choose Type:</label><br>
        <label style="margin-left: 50px;">
            <input type="radio" name="type" value="school" onclick="toggleForm()" checked>
            School
            <input type="radio" style="margin-left: 25px;" name="type" value="department" onclick="toggleForm()">
            Department
        </label>

        <div id="school-form" class="form-section active mt-3">
            <label class="form-label" for="school">School Name:</label>
            <input type="text" class="form-control" id="school" name="school" placeholder="Enter School Name">

            <label class="form-label" for="dean-name">Dean Name:</label>
            <input type="text" class="form-control" id="dean-name" name="dean-name">

            <label class="form-label" for="dean-contact">Dean Contact Number:</label>
            <input type="text" class="form-control" id="dean-contact" name="dean-contact">

            <label class="form-label" for="dean-email">Dean Email:</label>
            <input type="email" class="form-control" id="dean-email" name="dean-email">

            <label class="form-label" for="dean-intercom">Dean Intercom:</label>
            <input type="text" class="form-control" id="dean-intercom" name="dean-intercom">

            <label class="form-label" for="dean-status">Dean Status:</label>
            <input type="text" class="form-control" id="dean-status" name="dean-status">
        </div>

        <div id="department-form" class="form-section mt-3">
    <div id="departmentFields">
        <label class="form-label" style="margin-top: 8px;">School Name:</label>
        <select class="form-control" name="school_name" id="school_name">
            <option value="">Select School</option>
            <?php
                include 'config.php';
                $sql = "SELECT DISTINCT school_name, school_id FROM schools";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row['school_id'] . '">' . $row['school_name'] . '</option>';
                    }
                }
            ?>
        </select>
    </div>
    <label class="form-label" for="department-name">Department Name:</label>
    <input type="text" class="form-control" id="department-name" name="department-name">

    <label class="form-label" for="hod-name">HOD Name:</label>
    <input type="text" class="form-control" id="hod-name" name="hod-name">

    <label class="form-label" for="hod-contact">HOD Contact Mobile:</label>
    <input type="text" class="form-control" id="hod-contact" name="hod-contact">

    <label class="form-label" for="designation">Designation:</label>
    <input type="text" class="form-control" id="designation" name="designation">

    <label class="form-label" for="hod-email">HOD Contact Email:</label>
    <input type="email" class ="form-control" id="hod-email" name="hod-email">

    <label class="form-label" for="hod-intercom">HOD Intercom:</label>
    <input type="text" class="form-control" id="hod-intercom" name="hod-intercom">

    <button class="btn btn-primary" style="width: 30%; padding:10px; margin-left:35%;" type="submit">Submit</button>
    </form>   
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
 function toggleForm() {
    const selectedType = document.querySelector('input[name="type"]:checked').value;
    document.getElementById('school-form').classList.remove('active');
    document.getElementById('department-form').classList.remove('active');
    
    if (selectedType === 'school') {
        document.getElementById('school-form').classList.add('active');
    } else {
        document.getElementById('department-form').classList.add('active');
    }
}
    
    window.onload = function() {
        toggleForm();  // Set the default view on page load
    }
</script>

</body>
</html>