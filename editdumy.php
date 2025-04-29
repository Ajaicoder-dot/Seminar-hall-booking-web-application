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
        // Check if features are selected
        $features = isset($_POST['features']) && !empty($_POST['features']) ? json_encode($_POST['features']) : null;// JSON encode the features array
    $floor_name = isset($_POST['floor_name']) ? $_POST['floor_name'] : null; // Default to null if not set
    $zone = isset($_POST['zone']) ? $_POST['zone'] : null; // Default to null if not set
    $image_path = $hall['image']; // Keep existing image if no new upload
    $room_availability = isset($_POST['room_availability']) ? $_POST['room_availability'] : null; // Default to null if not set
    $belong_to = isset($_POST['belong_to']) ? $_POST['belong_to'] : null; // Default to null if not set
    $incharge_name = $_POST['incharge_name'];
    $designation = $_POST['designation'];
    $incharge_email = $_POST['incharge_email'];
    $incharge_phone = $_POST['incharge_phone'];
    $school_id = isset($_POST['school_id']) ? $_POST['school_id'] : null;
    $department_id = isset($_POST['department_id']) ? $_POST['department_id'] : null;

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_path = 'C:/xampp/htdocs/university/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
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
    <title>Edit Hall</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
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
        }
        .features-group, .radio-group {
            margin-bottom: 20px;
        }
        .radio-group input {
            margin-right: 10px;
        }
        .button-group {
            display: flex;
            justify-content:space-evenly;
            margin-top: 20px;
        }
        button {
            padding: 8px 15px; 
            border-radius: 5px;
            font-size: 14px; 
            cursor: pointer;
            transition: all  0.3s ease;
            width: 12%;
        }
        .submit-button, .reset-button, .cancel-button {
            background-color: #007BFF;
            color: white;
            border: none;
        }
        .submit-button:hover, .reset-button:hover, .cancel-button:hover {
            background-color: #0056b3;
        }
        .cancel-button {
            background-color: rgb(11, 117, 209);
        }
        .reset-button {
            background-color: #ffc107;
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
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
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
        <label>Hall Type:</label>
        <select name="hall_type" required>
            <option value="">Select Hall Type</option>
            <?php while ($hall_type = $hall_types_result->fetch_assoc()) { ?>
                <option value="<?php echo $hall_type['hall_type_id']; ?>" <?php echo ($hall['hall_type'] == $hall_type['hall_type_id']) ? 'selected' : ''; ?>><?php echo $hall_type['type_name']; ?></option>
            <?php } ?>
        </select>

        <label>Name:</label>
        <input type="text" name="hall_name" value="<?php echo $hall['hall_name']; ?>" required>

        <label>Capacity:</label>
        <input type="number" name="capacity" value="<?php echo $hall['capacity']; ?>" required>

        <div class="features-group">
            <label>Features:</label>
            <?php 
            $features = json_decode($hall['features']);
            $available_features = ['AC', 'Projector', 'WiFi', 'Audio System', 'Smart Board', 'White Board'];
            foreach ($available_features as $feature) {
                $checked = in_array($feature, $features) ? 'checked' : '';
                echo "<input type='checkbox' name='features[]' value='$feature' $checked> $feature";
            }
            ?>
        </div>

        <div class="radio-group">
            <label>Floor Name:</label>
            <input type="radio" name="floor_name" value="Ground Floor" <?php echo ($hall['floor_name'] == 'Ground Floor') ? 'checked' : ''; ?> required> Ground Floor
            <input type="radio" name="floor_name" value="First Floor" <?php echo ($hall['floor_name'] == 'First Floor') ? 'checked' : ''; ?> required> First Floor
            <input type="radio" name="floor_name" value="Second Floor" <?php echo ($hall['floor_name'] == 'Second Floor') ? 'checked' : ''; ?> required> Second Floor
        </div>

        <div class="radio-group">
            <label>Zone:</label>
            <input type="radio" name="zone" value="East" <?php echo ($hall['zone'] == 'East') ? 'checked' : ''; ?> required> East
            <input type="radio" name="zone" value="West" <?php echo ($hall['zone'] == 'West') ? 'checked' : ''; ?> required> West
            <input type="radio" name="zone" value="North" <?php echo ($hall['zone'] == 'North') ? 'checked' : ''; ?> required> North
            <input type="radio" name="zone" value="South" <?php echo ($hall['zone'] == 'South') ? 'checked' : ''; ?> required> South
           
        </div>

        <label>Image:</label>
        <input type="file" name="image">
        <p>Current Image: <img src="<?php echo $hall['image']; ?>" alt="Current Hall Image" style="max-width: 200px;"></p>

        <div class="radio-group">
            <label>Room Availability:</label>
            <input type="radio" name="room_availability" value="Yes" <?php echo ($hall['room_availability'] == 'Yes') ? 'checked' : ''; ?> required> Yes
            <input type="radio" name="room_availability" value="No" <?php echo ($hall['room_availability'] == 'No') ? 'checked' : ''; ?> required> No
        </div>

        <div class="radio-group">
            <label>Belongs to:</label>
            <input type="radio" name="belong_to" value="Department" <?php echo ($hall['belong_to'] == 'Department') ? 'checked' : ''; ?> required> Department
            <input type="radio" name="belong_to" value="School" <?php echo ($hall['belong_to'] == 'School') ? 'checked' : ''; ?> required> School
            <input type="radio" name="belong_to" value="Administration" <?php echo ($hall['belong_to'] == 'Administration') ? 'checked' : ''; ?> required> Administration
        </div>

        <div id="school-container" style="display:<?php echo ($hall['belong_to'] == 'Department') ? 'block' : 'none'; ?>;">
            <label>School:</label>
            <select name="school_id" id="school-dropdown">
                <option value="">Select School</option>
                <?php while ($school = $schools_result->fetch_assoc()) { ?>
                    <option value="<?php echo $school['school_id']; ?>" <?php echo ($hall['school_id'] == $school['school_id']) ? 'selected' : ''; ?>><?php echo $school['school_name']; ?></option>
                <?php } ?>
            </select>
        </div>

        <div id="department-container" style="display:<?php echo ($hall['belong_to'] == 'Department') ? 'block' : 'none'; ?>;">
            <label>Department:</label>
            <select name="department_id" id="department-dropdown">
                <option value="">Select Department</option>
                <!-- Populate departments based on selected school -->
            </select>
        </div>

        <label>Incharge Name:</label>
        <input type="text" name="incharge_name" value="<?php echo $hall['incharge_name']; ?>" required>

        <label>Designation:</label>
        <select name="designation" required>
            <option value="">Select Designation</option>
            <option value="HOD" <?php echo ($hall['designation'] == 'HOD') ? 'selected' : ''; ?>>HOD</option>
            <option value="Admin" <?php echo ($hall['designation'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
            <option value="Dean" <?php echo ($hall['designation'] == 'Dean') ? 'selected' : ''; ?>>Dean</option>
        </select>

        <label>Incharge Email:</label>
        <input type="email" name="incharge_email" value="<?php echo $hall['incharge_email']; ?>" required>

        <label>Incharge Phone:</label>
        <input type="tel" name="incharge_phone" value="<?php echo $hall['incharge_phone']; ?>" required>

        <div class="button-group">
            <button type="submit" class="submit-button">Update Hall</button>
            <button type="reset" class="reset-button">Reset</button>
            <button type="button" class="cancel-button">Cancel</button>
        </div>

        <a href="adminindex.php"><button type="button" class="back-button">Back</button></a>
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
            } else if ( radio.value === 'School') {
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
        document.getElementById('school-dropdown').dispatchEvent(new Event('change'));
    }
</script>
</body>
</html>