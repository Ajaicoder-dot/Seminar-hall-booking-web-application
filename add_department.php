<?php 
session_start();
include 'config.php';

// Success/error message handling
$message = '';
$messageType = '';

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
            $message = "Error preparing statement: " . $conn->error;
            $messageType = "danger";
        } else {
            $stmt->bind_param("ssssss", $school_name, $dean_name, $dean_contact_number, $dean_email, $dean_intercome, $dean_status);
            
            if (!$stmt->execute()) {
                $message = "Error executing statement: " . $stmt->error;
                $messageType = "danger";
            } else {
                $message = "School added successfully!";
                $messageType = "success";
            }
        }
    } else {
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
            $message = "Error preparing statement: " . $conn->error;
            $messageType = "danger";
        } else {
            $stmt->bind_param("issssss", $school_id, $department_name, $hod_name, $hod_contact_mobile, $designation, $hod_email, $hod_intercom);
            
            if (!$stmt->execute()) {
                $message = "Error executing statement: " . $stmt->error;
                $messageType = "danger";
            } else {
                $message = "Department added successfully!";
                $messageType = "success";
            }
        }
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
    
    // Don't redirect, show message on same page
    // $conn->close(); - Don't close here as we need it for the school dropdown
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
    <title>Manage Schools & Departments</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 140, 255, 0.1);
            margin-top: 0;
            margin-top: -120px; 
            position: relative;
            z-index: 1;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-item a {
            color: var(--light-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: white;
        }
        
        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            border-radius: 15px;
            background-color: white;
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .form-control, .btn {
            border-radius: 8px;
            padding: 12px;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
            border-color: var(--primary-color);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-top: 15px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #1a252f;
            border-color: #1a252f;
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        
        .type-selector {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .type-btn {
            padding: 15px 30px;
            border-radius: 30px;
            margin: 0 10px;
            font-weight: 600;
            transition: all 0.3s;
            width: 180px;
        }
        
        .type-btn.active {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px 20px;
        }
        
        .required-field::after {
            content: " *";
            color: var(--accent-color);
        }
        
        .back-btn {
            margin-bottom: 20px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <?php include 'navbar.php' ?>

    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-university me-2"></i>Manage Schools & Departments</h1>
                </div>
                <div class="col-md-4 text-end">
                    <a href="modify_school.php" class="btn btn-light me-2">
                        <i class="fas fa-list me-1"></i> View Schools
                    </a>
                    <a href="modify_dept.php" class="btn btn-light">
                        <i class="fas fa-list-alt me-1"></i> View Departments
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="back-btn">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="type-selector">
            <button type="button" class="btn btn-primary type-btn active" id="school-btn" onclick="selectType('school')">
                <i class="fas fa-university me-2"></i>School
            </button>
            <button type="button" class="btn btn-secondary type-btn" id="department-btn" onclick="selectType('department')">
                <i class="fas fa-building me-2"></i>Department
            </button>
        </div>

        <div class="form-card">
            <form method="POST" id="entityForm">
                <input type="hidden" name="type" id="type-input" value="school">
                
                <div id="school-form" class="form-section active">
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label required-field" for="school">School Name:</label>
                            <input type="text" class="form-control" id="school" name="school" placeholder="Enter School Name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label required-field" for="dean-name">Dean Name:</label>
                            <input type="text" class="form-control" id="dean-name" name="dean-name" placeholder="Enter Dean's Full Name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required-field" for="dean-contact">Dean Contact Number:</label>
                            <input type="text" class="form-control" id="dean-contact" name="dean-contact" placeholder="Enter Contact Number" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label required-field" for="dean-email">Dean Email:</label>
                            <input type="email" class="form-control" id="dean-email" name="dean-email" placeholder="Enter Email Address" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="dean-intercom">Dean Intercom:</label>
                            <input type="text" class="form-control" id="dean-intercom" name="dean-intercom" placeholder="Enter Intercom Number">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label" for="dean-status">Dean Status:</label>
                            <select class="form-control" id="dean-status" name="dean-status">
                                <option value="Active">Active</option>
                                <option value="On Leave">On Leave</option>
                                <option value="Acting">Acting</option>
                                <option value="Retired">Retired</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="department-form" class="form-section">
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label required-field" for="school_name">School Name:</label>
                            <select class="form-control" name="school_name" id="school_name" required>
                                <option value="">Select School</option>
                                <?php
                                    // Reuse existing connection
                                    $sql = "SELECT DISTINCT school_name, school_id FROM schools ORDER BY school_name";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['school_id'] . '">' . $row['school_name'] . '</option>';
                                        }
                                    }
                                    // Now we can close the connection
                                    $conn->close();
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label required-field" for="department-name">Department Name:</label>
                            <input type="text" class="form-control" id="department-name" name="department-name" placeholder="Enter Department Name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label required-field" for="hod-name">HOD Name:</label>
                            <input type="text" class="form-control" id="hod-name" name="hod-name" placeholder="Enter HOD's Full Name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="designation">Designation:</label>
                            <input type="text" class="form-control" id="designation" name="designation" placeholder="Enter Designation">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label required-field" for="hod-contact">HOD Contact Mobile:</label>
                            <input type="text" class="form-control" id="hod-contact" name="hod-contact" placeholder="Enter Contact Number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required-field" for="hod-email">HOD Contact Email:</label>
                            <input type="email" class="form-control" id="hod-email" name="hod-email" placeholder="Enter Email Address" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label" for="hod-intercom">HOD Intercom:</label>
                            <input type="text" class="form-control" id="hod-intercom" name="hod-intercom" placeholder="Enter Intercom Number">
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="button" class="btn btn-danger" onclick="resetForm()">
                        <i class="fas fa-undo me-2"></i>Reset Form
                    </button>
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-save me-2"></i>Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectType(type) {
            // Update hidden input
            document.getElementById('type-input').value = type;
            
            // Update button styles
            if (type === 'school') {
                document.getElementById('school-btn').classList.add('active');
                document.getElementById('school-btn').classList.remove('btn-secondary');
                document.getElementById('school-btn').classList.add('btn-primary');
                
                document.getElementById('department-btn').classList.remove('active');
                document.getElementById('department-btn').classList.remove('btn-primary');
                document.getElementById('department-btn').classList.add('btn-secondary');
            } else {
                document.getElementById('department-btn').classList.add('active');
                document.getElementById('department-btn').classList.remove('btn-secondary');
                document.getElementById('department-btn').classList.add('btn-primary');
                
                document.getElementById('school-btn').classList.remove('active');
                document.getElementById('school-btn').classList.remove('btn-primary');
                document.getElementById('school-btn').classList.add('btn-secondary');
            }
            
            // Toggle form sections
            document.getElementById('school-form').classList.remove('active');
            document.getElementById('department-form').classList.remove('active');
            
            document.getElementById(type + '-form').classList.add('active');
            
            // Update required fields based on active form
            updateRequiredFields();
        }
        
        function updateRequiredFields() {
            const activeType = document.getElementById('type-input').value;
            
            // Remove required attribute from all fields
            const allInputs = document.querySelectorAll('input, select');
            allInputs.forEach(input => {
                input.removeAttribute('required');
            });
            
            // Add required attribute to active form fields that need it
            if (activeType === 'school') {
                document.getElementById('school').setAttribute('required', '');
                document.getElementById('dean-name').setAttribute('required', '');
                document.getElementById('dean-contact').setAttribute('required', '');
                document.getElementById('dean-email').setAttribute('required', '');
            } else {
                document.getElementById('school_name').setAttribute('required', '');
                document.getElementById('department-name').setAttribute('required', '');
                document.getElementById('hod-name').setAttribute('required', '');
                document.getElementById('hod-contact').setAttribute('required', '');
                document.getElementById('hod-email').setAttribute('required', '');
            }
        }
        
        function resetForm() {
            document.getElementById('entityForm').reset();
            
            // If there's an alert, remove it
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }
        
        // Form validation
        document.getElementById('entityForm').addEventListener('submit', function(event) {
            const activeType = document.getElementById('type-input').value;
            let isValid = true;
            
            if (activeType === 'school') {
                const schoolName = document.getElementById('school').value.trim();
                const deanName = document.getElementById('dean-name').value.trim();
                const deanContact = document.getElementById('dean-contact').value.trim();
                const deanEmail = document.getElementById('dean-email').value.trim();
                
                if (!schoolName || !deanName || !deanContact || !deanEmail) {
                    isValid = false;
                }
                
                // Email validation
                if (deanEmail && !validateEmail(deanEmail)) {
                    alert('Please enter a valid email address for the Dean');
                    isValid = false;
                }
                
                // Phone validation
                if (deanContact && !validatePhone(deanContact)) {
                    alert('Please enter a valid phone number for the Dean');
                    isValid = false;
                }
            } else {
                const schoolId = document.getElementById('school_name').value;
                const departmentName = document.getElementById('department-name').value.trim();
                const hodName = document.getElementById('hod-name').value.trim();
                const hodContact = document.getElementById('hod-contact').value.trim();
                const hodEmail = document.getElementById('hod-email').value.trim();
                
                if (!schoolId || !departmentName || !hodName || !hodContact || !hodEmail) {
                    isValid = false;
                }
                
                // Email validation
                if (hodEmail && !validateEmail(hodEmail)) {
                    alert('Please enter a valid email address for the HOD');
                    isValid = false;
                }
                
                // Phone validation
                if (hodContact && !validatePhone(hodContact)) {
                    alert('Please enter a valid phone number for the HOD');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                event.preventDefault();
                alert('Please fill in all required fields correctly');
            }
        });
        
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        function validatePhone(phone) {
            const re = /^[0-9+\-\s()]{10,15}$/;
            return re.test(phone);
        }
        
        window.onload = function() {
            selectType('school');  // Set the default view on page load
        }
    </script>

</body>
<?php include('footer user.php'); ?>
</html>