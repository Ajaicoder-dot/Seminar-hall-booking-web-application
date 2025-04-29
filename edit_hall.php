<?php
session_start();
include('config.php');

// Ensure user is logged in and is either Admin or HOD
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Fetch schools, departments, and hall types for dropdowns
$query_schools = "SELECT school_id, school_name FROM schools";
$schools_result = $conn->query($query_schools);

$query_departments = "SELECT department_id, department_name FROM departments";
$departments_result = $conn->query($query_departments);

$query_hall_types = "SELECT hall_type_id, type_name FROM hall_type";
$hall_types_result = $conn->query($query_hall_types);

// Get the hall ID from the URL
$hall_id = isset($_GET['id']) ? $_GET['id'] : null;

// Fetch the existing hall details
if ($hall_id) {
    $query_hall = "SELECT * FROM halls WHERE hall_id = ?";
    $stmt = $conn->prepare($query_hall);
    if ($stmt) {
        $stmt->bind_param("i", $hall_id);
        $stmt->execute();
        $hall_result = $stmt->get_result();
        $hall = $hall_result->fetch_assoc();
        $stmt->close();
    } else {
        $error_message = "Database error: " . $conn->error;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $hall_type_id = $_POST['hall_type'];
    $hall_name = $_POST['hall_name'];
    $capacity = $_POST['capacity'];
    $features = isset($_POST['features']) && !empty($_POST['features']) ? json_encode($_POST['features']) : null;
    $floor_name = isset($_POST['floor_name']) ? $_POST['floor_name'] : null;
    $zone = isset($_POST['zone']) ? $_POST['zone'] : null;
    $image_path = $hall['image'];
    $room_availability = isset($_POST['room_availability']) ? $_POST['room_availability'] : null;
    $belong_to = isset($_POST['belong_to']) ? $_POST['belong_to'] : null;
    $incharge_name = $_POST['incharge_name'];
    $designation = $_POST['designation'];
    $incharge_email = $_POST['incharge_email'];
    $incharge_phone = $_POST['incharge_phone'];
    $school_id = isset($_POST['school_id']) ? $_POST['school_id'] : null;
    $department_id = isset($_POST['department_id']) ? $_POST['department_id'] : null;

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_path = 'images/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    // Set school_id and department_id based on belong_to
    if ($belong_to === 'Administration') {
        $school_id = null;
        $department_id = null;
    } else if ($belong_to === 'School') {
        $department_id = null;
    }

    // Update data in the database
    $query = "UPDATE halls SET hall_type = ?, hall_name = ?, capacity = ?, features = ?, floor_name = ?, zone = ?, image = ?, room_availability = ?, belong_to = ?, incharge_name = ?, designation = ?, incharge_email = ?, incharge_phone = ?, school_id = ?, department_id = ? WHERE hall_id = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("issssssssssssiii", $hall_type_id, $hall_name, $capacity, $features, $floor_name, $zone, $image_path, $room_availability, $belong_to, $incharge_name, $designation, $incharge_email, $incharge_phone, $school_id, $department_id, $hall_id);
        
        if ($stmt->execute()) {
            $success_message = "Hall updated successfully!";
        } else {
            $error_message = "Error updating hall: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Database error: " . $conn->error;
    }
}
?>

<!-- Your HTML Form Goes Here -->

<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="#" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding-top: 0;
            background-color: #f7f7f7;
        }
        .container {
            max-width: 900px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        h1 {
            text-align: center;
            color: #333;
            font-size: 28px;
            margin-bottom: 20px;
            border-bottom: 2px solid #007BFF;
            padding-bottom: 10px;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin: 12px 0 6px;
            font-weight: 500;
            color: #555;
        }
        input, select, textarea {
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #007BFF;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }
        .features-group, .radio-group {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .features-group label, .radio-group label {
            margin-right: 0;
            flex-basis: 100%;
        }
        .features-group input, .radio-group input {
            margin-right: 5px;
        }
        .feature-item, .radio-item {
            display: inline-flex;
            align-items: center;
            margin-right: 15px;
        }
        .button-group {
            display: flex;
            justify-content: space-evenly;
            margin-top: 30px;
            gap: 15px;
        }
        button {
            padding: 12px 20px; 
            border-radius: 5px;
            font-size: 16px; 
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: auto;
            min-width: 120px;
            text-align: center;
        }
        .submit-button, .reset-button, .cancel-button {
            background-color: #007BFF;
            color: white;
            border: none;
        }
        .submit-button:hover, .reset-button:hover, .cancel-button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .cancel-button {
            background-color: #6c757d;
        }
        .reset-button {
            background-color: #ffc107;
            color: #212529;
        }
        .back-button {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .back-button:hover {
            background-color: #138496;
        }
        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
            animation: fadeIn 0.5s ease;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .form-section {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            background-color: #f8f9fa;
        }
        .form-section h3 {
            margin-top: 0;
            color: #007BFF;
            font-size: 20px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .current-image {
            margin: 15px 0;
            text-align: center;
        }
        .current-image img {
            max-width: 200px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 3px;
            background: white;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
            .button-group {
                flex-direction: column;
            }
            button {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container">
    <h1>Edit Hall</h1>
    <?php if (!empty($success_message)) { ?>
        <div class="message success"><?php echo $success_message; ?></div>
    <?php } ?>
    <?php if (!empty($error_message)) { ?>
        <div class="message error"><?php echo $error_message; ?></div>
    <?php } ?>
    <form action="edit_hall.php?id=<?php echo $hall_id; ?>" method="POST" enctype="multipart/form-data">
        <div class="form-section">
            <h3>Basic Information</h3>
            <label for="hall_type">Hall Type:</label>
            <select name="hall_type" id="hall_type" required>
                <option value="">Select Hall Type</option>
                <?php while ($hall_type = $hall_types_result->fetch_assoc()) { ?>
                    <option value="<?php echo $hall_type['hall_type_id']; ?>" <?php echo ($hall['hall_type'] == $hall_type['hall_type_id']) ? 'selected' : ''; ?>><?php echo $hall_type['type_name']; ?></option>
                <?php } ?>
            </select>

            <label for="hall_name">Name:</label>
            <input type="text" name="hall_name" id="hall_name" value="<?php echo $hall['hall_name']; ?>" required>

            <label for="capacity">Capacity:</label>
            <input type="number" name="capacity" id="capacity" value="<?php echo $hall['capacity']; ?>" required>
        </div>

        <div class="form-section">
            <h3>Hall Features</h3>
            <div class="features-group">
                <label>Features:</label>
                <?php 
                $features = json_decode($hall['features']);
                $available_features = ['AC', 'Projector', 'WiFi', 'Audio System', 'Smart Board', 'White Board'];
                foreach ($available_features as $feature) {
                    $checked = in_array($feature, $features) ? 'checked' : '';
                    echo "<div class='feature-item'><input type='checkbox' name='features[]' id='feature_$feature' value='$feature' $checked> <label for='feature_$feature'>$feature</label></div>";
                }
                ?>
            </div>

            <div class="radio-group">
                <label>Floor Name:</label>
                <div class="radio-item"><input type="radio" name="floor_name" id="ground" value="Ground Floor" <?php echo ($hall['floor_name'] == 'Ground Floor') ? 'checked' : ''; ?> required> <label for="ground">Ground Floor</label></div>
                <div class="radio-item"><input type="radio" name="floor_name" id="first" value="First Floor" <?php echo ($hall['floor_name'] == 'First Floor') ? 'checked' : ''; ?> required> <label for="first">First Floor</label></div>
                <div class="radio-item"><input type="radio" name="floor_name" id="second" value="Second Floor" <?php echo ($hall['floor_name'] == 'Second Floor') ? 'checked' : ''; ?> required> <label for="second">Second Floor</label></div>
            </div>

            <div class="radio-group">
                <label>Zone:</label>
                <div class="radio-item"><input type="radio" name="zone" id="east" value="East" <?php echo ($hall['zone'] == 'East') ? 'checked' : ''; ?> required> <label for="east">East</label></div>
                <div class="radio-item"><input type="radio" name="zone" id="west" value="West" <?php echo ($hall['zone'] == 'West') ? 'checked' : ''; ?> required> <label for="west">West</label></div>
                <div class="radio-item"><input type="radio" name="zone" id="north" value="North" <?php echo ($hall['zone'] == 'North') ? 'checked' : ''; ?> required> <label for="north">North</label></div>
                <div class="radio-item"><input type="radio" name="zone" id="south" value="South" <?php echo ($hall['zone'] == 'South') ? 'checked' : ''; ?> required> <label for="south">South</label></div>
            </div>
        </div>

        <div class="form-section">
            <h3>Hall Image</h3>
            <label for="image">Upload New Image:</label>
            <input type="file" name="image" id="image">
            <div class="current-image">
                <p>Current Image:</p>
                <img src="<?php echo $hall['image']; ?>" alt="Current Hall Image">
            </div>
        </div>

        <div class="form-section">
            <h3>Availability & Ownership</h3>
            <div class="radio-group">
                <label>Room Availability:</label>
                <div class="radio-item"><input type="radio" name="room_availability" id="avail_yes" value="Yes" <?php echo ($hall['room_availability'] == 'Yes') ? 'checked' : ''; ?> required> <label for="avail_yes">Yes</label></div>
                <div class="radio-item"><input type="radio" name="room_availability" id="avail_no" value="No" <?php echo ($hall['room_availability'] == 'No') ? 'checked' : ''; ?> required> <label for="avail_no">No</label></div>
            </div>

            <div class="radio-group">
                <label>Belongs to:</label>
                <div class="radio-item"><input type="radio" name="belong_to" id="dept" value="Department" <?php echo ($hall['belong_to'] == 'Department') ? 'checked' : ''; ?> required> <label for="dept">Department</label></div>
                <div class="radio-item"><input type="radio" name="belong_to" id="school" value="School" <?php echo ($hall['belong_to'] == 'School') ? 'checked' : ''; ?> required> <label for="school">School</label></div>
                <div class="radio-item"><input type="radio" name="belong_to" id="admin" value="Administration" <?php echo ($hall['belong_to'] == 'Administration') ? 'checked' : ''; ?> required> <label for="admin">Administration</label></div>
            </div>

            <div id="school-container" style="display:<?php echo ($hall['belong_to'] == 'Department' || $hall['belong_to'] == 'School') ? 'block' : 'none'; ?>;">
                <label for="school-dropdown">School:</label>
                <select name="school_id" id="school-dropdown">
                    <option value="">Select School</option>
                    <?php 
                    // Reset the result pointer
                    $schools_result->data_seek(0);
                    while ($school = $schools_result->fetch_assoc()) { ?>
                        <option value="<?php echo $school['school_id']; ?>" <?php echo ($hall['school_id'] == $school['school_id']) ? 'selected' : ''; ?>><?php echo $school['school_name']; ?></option>
                    <?php } ?>
                </select>
            </div>

            <div id="department-container" style="display:<?php echo ($hall['belong_to'] == 'Department') ? 'block' : 'none'; ?>;">
                <label for="department-dropdown">Department:</label>
                <select name="department_id" id="department-dropdown">
                    <option value="">Select Department</option>
                    <!-- Populated via JavaScript -->
                </select>
            </div>
        </div>

        <div class="form-section">
            <h3>Incharge Information</h3>
            <label for="incharge_name">Incharge Name:</label>
            <input type="text" name="incharge_name" id="incharge_name" value="<?php echo $hall['incharge_name']; ?>" required>

            <label for="designation">Designation:</label>
            <select name="designation" id="designation" required>
                <option value="">Select Designation</option>
                <option value="HOD" <?php echo ($hall['designation'] == 'HOD') ? 'selected' : ''; ?>>HOD</option>
                <option value="Admin" <?php echo ($hall['designation'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="Dean" <?php echo ($hall['designation'] == 'Dean') ? 'selected' : ''; ?>>Dean</option>
            </select>

            <label for="incharge_email">Incharge Email:</label>
            <input type="email" name="incharge_email" id="incharge_email" value="<?php echo $hall['incharge_email']; ?>" required>

            <label for="incharge_phone">Incharge Phone:</label>
            <input type="tel" name="incharge_phone" id="incharge_phone" value="<?php echo $hall['incharge_phone']; ?>" required>
        </div>

        <div class="button-group">
            <button type="submit" class="submit-button">Update Hall</button>
            <button type="reset" class="reset-button">Reset</button>
            <button type="button" class="cancel-button" onclick="window.location.href='modify_hall.php';">Cancel</button>
        </div>

        <a href="adminindex.php"><button type="button" class="back-button">← Back to Dashboard</button></a>
    </form>
</div>

<script>
    document.querySelectorAll('input[name="belong_to"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var schoolContainer = document.getElementById('school-container');
            var departmentContainer = document.getElementById('department-container');

            if (radio.value === 'Department') {
                schoolContainer.style.display = 'block';
                departmentContainer.style.display = 'block';
            } else if (radio.value === 'School') {
                schoolContainer.style.display = 'block';
                departmentContainer.style.display = 'none';
            } else {
                schoolContainer.style.display = 'none';
                departmentContainer.style.display = 'none';
            }
        });
    });

    document.getElementById('school-dropdown').addEventListener('change', function() {
        var school_id = this.value;
        if (school_id) {
            fetch('get_departments.php?school_id=' + school_id)
                .then(response => response.json())
                .then(data => {
                    var departmentDropdown = document.getElementById('department-dropdown');
                    departmentDropdown.innerHTML = '<option value="">Select Department</option>';
                    data.forEach(function(department) {
                        var option = document.createElement('option');
                        option.value = department.department_id;
                        option.text = department.department_name;
                        departmentDropdown.appendChild(option);
                    });
                });
        }
    });

    // Pre-fill department dropdown if a school is selected
    var initialSchoolId = "<?php echo $hall['school_id']; ?>";
    if (initialSchoolId) {
        document.getElementById('school-dropdown').value = initialSchoolId;
        
        // Don't dispatch the change event immediately, fetch departments first
        fetch('get_departments.php?school_id=' + initialSchoolId)
            .then(response => response.json())
            .then(data => {
                var departmentDropdown = document.getElementById('department-dropdown');
                departmentDropdown.innerHTML = '<option value="">Select Department</option>';
                data.forEach(function(department) {
                    var option = document.createElement('option');
                    option.value = department.department_id;
                    option.text = department.department_name;
                    departmentDropdown.appendChild(option);
                    
                    // Now select the correct department
                    if (department.department_id == "<?php echo $hall['department_id']; ?>") {
                        departmentDropdown.value = department.department_id;
                    }
                });
            });
    }
</script>
</body>
<?php include('footer user.php'); ?>
</html>