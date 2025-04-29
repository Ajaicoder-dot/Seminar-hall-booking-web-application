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
    $image_path = '';
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
        $image_path = 'images/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    // Insert data into the database
    $query = "INSERT INTO halls (hall_type, hall_name, capacity, features, floor_name, zone, image, room_availability, belong_to, incharge_name, designation, incharge_email, incharge_phone, school_id, department_id)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("isssssssssssssi", $hall_type_id, $hall_name, $capacity, $features, $floor_name, $zone, $image_path, $room_availability, $belong_to, $incharge_name, $designation, $incharge_email, $incharge_phone, $school_id, $department_id);
        if ($stmt->execute()) {
            $success_message = "Hall added successfully!";
        } else {
            $error_message = "Error adding hall: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Database error: " . $conn->error;
    }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Add Hall</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding-top: 0;
            background-color: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto 60px; /* Changed from 30px to 0 to reduce top margin */
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            position: relative;
        }
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            position: relative;
        }
        .page-header h1 {
            color: #2c3e50;
            font-size: 32px;
            font-weight: 600;
        }
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: #3498db;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
            display: flex;
            align-items: center;
        }
        .section-title i {
            margin-right: 10px;
            color: #3498db;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        input[type="text"], 
        input[type="number"], 
        input[type="email"], 
        input[type="tel"], 
        select, 
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #fff;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        .form-check {
            margin-bottom: 10px;
        }
        .form-check-input {
            margin-right: 8px;
        }
        .form-check-label {
            font-weight: normal;
        }
        .features-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .feature-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .feature-item:hover {
            border-color: #3498db;
            background-color: #f0f7fc;
        }
        .feature-item input {
            margin-right: 8px;
        }
        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .radio-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .radio-item:hover {
            border-color: #3498db;
            background-color: #f0f7fc;
        }
        .radio-item input {
            margin-right: 8px;
        }
        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .file-upload-label {
            display: block;
            padding: 12px 15px;
            background: #fff;
            border: 1px dashed #3498db;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload-label:hover {
            background-color: #f0f7fc;
        }
        .file-upload-label i {
            margin-right: 8px;
            color: #3498db;
        }
        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-warning {
            background-color: #f39c12;
            color: white;
        }
        .btn-warning:hover {
            background-color: #e67e22;
        }
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .btn-info:hover {
            background-color: #138496;
        }
        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .message i {
            margin-right: 10px;
            font-size: 20px;
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
        .hidden {
            display: none;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px 10px;
            }
            .button-group {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            .radio-group, .features-container {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-door-open"></i> Add New Hall</h1>
    </div>
    
    <?php if (!empty($success_message)) { ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php } ?>
    
    <?php if (!empty($error_message)) { ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php } ?>
    
    <form action="add_hall.php" method="POST" enctype="multipart/form-data">
        
        <!-- Basic Information Section -->
        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-info-circle"></i> Basic Information
            </div>
            
            <div class="form-group">
                <label for="hall_type">Hall Type:</label>
                <select name="hall_type" id="hall_type" required>
                    <option value="">Select Hall Type</option>
                    <?php while ($hall_type = $hall_types_result->fetch_assoc()) { ?>
                        <option value="<?php echo $hall_type['hall_type_id']; ?>"><?php echo $hall_type['type_name']; ?></option>
                    <?php } ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="hall_name">Hall Name:</label>
                <input type="text" name="hall_name" id="hall_name" placeholder="Enter hall name" required>
            </div>
            
            <div class="form-group">
                <label for="capacity">Capacity:</label>
                <input type="number" name="capacity" id="capacity" placeholder="Enter hall capacity" required>
            </div>
        </div>
        
        <!-- Features Section -->
        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-list-check"></i> Features & Amenities
            </div>
            
            <div class="form-group">
                <label>Available Features:</label>
                <div class="features-container">
                    <div class="feature-item">
                        <input type="checkbox" name="features[]" id="feature_ac" value="AC">
                        <label for="feature_ac">Air Conditioning</label>
                    </div>
                    <div class="feature-item">
                        <input type="checkbox" name="features[]" id="feature_projector" value="Projector">
                        <label for="feature_projector">Projector</label>
                    </div>
                    <div class="feature-item">
                        <input type="checkbox" name="features[]" id="feature_wifi" value="WiFi">
                        <label for="feature_wifi">WiFi</label>
                    </div>
                    <div class="feature-item">
                        <input type="checkbox" name="features[]" id="feature_audio" value="Audio System">
                        <label for="feature_audio">Audio System</label>
                    </div>
                    <div class="feature-item">
                        <input type="checkbox" name="features[]" id="feature_smartboard" value="Smart Board">
                        <label for="feature_smartboard">Smart Board</label>
                    </div>
                    <div class="feature-item">
                        <input type="checkbox" name="features[]" id="feature_whiteboard" value="White Board">
                        <label for="feature_whiteboard">White Board</label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Location Section -->
        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-map-marker-alt"></i> Location Details
            </div>
            
            <div class="form-group">
                <label>Floor Name:</label>
                <div class="radio-group">
                    <div class="radio-item">
                        <input type="radio" name="floor_name" id="floor_ground" value="Ground Floor" required>
                        <label for="floor_ground">Ground Floor</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="floor_name" id="floor_first" value="First Floor">
                        <label for="floor_first">First Floor</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="floor_name" id="floor_second" value="Second Floor">
                        <label for="floor_second">Second Floor</label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Zone:</label>
                <div class="radio-group">
                    <div class="radio-item">
                        <input type="radio" name="zone" id="zone_east" value="East" required>
                        <label for="zone_east">East</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="zone" id="zone_west" value="West">
                        <label for="zone_west">West</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="zone" id="zone_north" value="North">
                        <label for="zone_north">North</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="zone" id="zone_south" value="South">
                        <label for="zone_south">South</label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Image Upload Section -->
        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-image"></i> Hall Image
            </div>
            
            <div class="form-group">
                <div class="file-upload">
                    <label for="image" class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i> Click to upload hall image
                    </label>
                    <input type="file" name="image" id="image" accept="image/*">
                </div>
                <div id="file-name" class="mt-2 text-center text-muted"></div>
            </div>
        </div>
        
        <!-- Availability Section -->
        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-check-circle"></i> Availability & Ownership
            </div>
            
            <div class="form-group">
                <label>Room Availability:</label>
                <div class="radio-group">
                    <div class="radio-item">
                        <input type="radio" name="room_availability" id="avail_yes" value="Yes" required>
                        <label for="avail_yes">Available</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="room_availability" id="avail_no" value="No">
                        <label for="avail_no">Not Available</label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Belongs to:</label>
                <div class="radio-group">
                    <div class="radio-item">
                        <input type="radio" name="belong_to" id="belong_dept" value="Department" required>
                        <label for="belong_dept">Department</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="belong_to" id="belong_school" value="School">
                        <label for="belong_school">School</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" name="belong_to" id="belong_admin" value="Administration">
                        <label for="belong_admin">Administration</label>
                    </div>
                </div>
            </div>
            
            <div id="school-container" class="form-group hidden">
                <label for="school-dropdown">School:</label>
                <select name="school_id" id="school-dropdown">
                    <option value="">Select School</option>
                    <?php 
                    // Reset the result pointer
                    if ($schools_result) {
                        $schools_result->data_seek(0);
                        while ($school = $schools_result->fetch_assoc()) { 
                    ?>
                        <option value="<?php echo $school['school_id']; ?>"><?php echo $school['school_name']; ?></option>
                    <?php 
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div id="department-container" class="form-group hidden">
                <label for="department-dropdown">Department:</label>
                <select name="department_id" id="department-dropdown">
                    <option value="">Select Department</option>
                </select>
            </div>
        </div>
        
        <!-- Incharge Information Section -->
        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-user-tie"></i> Incharge Information
            </div>
            
            <div class="form-group">
                <label for="incharge_name">Incharge Name:</label>
                <input type="text" name="incharge_name" id="incharge_name" placeholder="Enter incharge name" required>
            </div>
            
            <div class="form-group">
                <label for="designation">Designation:</label>
                <select name="designation" id="designation" required>
                    <option value="">Select Designation</option>
                    <option value="HOD">HOD</option>
                    <option value="Admin">Admin</option>
                    <option value="Dean">Dean</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="incharge_email">Incharge Email:</label>
                <input type="email" name="incharge_email" id="incharge_email" placeholder="Enter incharge email" required>
            </div>
            
            <div class="form-group">
                <label for="incharge_phone">Incharge Phone:</label>
                <input type="tel" name="incharge_phone" id="incharge_phone" placeholder="Enter incharge phone number" required>
            </div>
        </div>
        
        <!-- Form Buttons -->
        <div class="button-group">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add Hall
            </button>
            <button type="reset" class="btn btn-warning">
                <i class="fas fa-redo"></i> Reset
            </button>
            <button type="button" class="btn btn-danger" onclick="window.location.href='add_hall.php';">
                <i class="fas fa-times-circle"></i> Cancel
            </button>
        </div>
        
        <a href="adminindex.php" class="btn btn-info">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </form>
</div>

<script>


    document.querySelectorAll('input[name="belong_to"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var schoolContainer = document.getElementById('school-container');
            var departmentContainer = document.getElementById('department-container');

            if (radio.value === 'Department') {
                schoolContainer.classList.remove('hidden');
                departmentContainer.classList.remove('hidden');
            } else if (radio.value === 'School') {
                schoolContainer.classList.remove('hidden');
                departmentContainer.classList.add('hidden');
            } else {
                schoolContainer.classList.add('hidden');
                departmentContainer.classList.add('hidden');
            }
        });
    });

    // Load departments based on selected school
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
    
    // Display selected file name
    document.getElementById('image').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : 'No file selected';
        document.getElementById('file-name').textContent = fileName;
    });
</script>

</body>
<?php include('footer user.php'); ?>
</html>