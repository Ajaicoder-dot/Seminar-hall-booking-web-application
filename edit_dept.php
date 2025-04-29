<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$department = null;
$success_message = '';
$error_message = '';

// Check if department_id is set
if (isset($_POST['department_id']) || isset($_GET['id'])) {
    $department_id = isset($_POST['department_id']) ? $_POST['department_id'] : $_GET['id'];

    // Fetch existing department data
    $stmt = $conn->prepare("SELECT * FROM departments WHERE department_id = ?");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $department = $result->fetch_assoc();

    if (!$department) {
        $error_message = "Department not found.";
    }
} else {
    $error_message = "No department ID provided.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $school_id = $_POST['school_name'];
    $department_name = $_POST['department-name'];
    $hod_name = $_POST['hod-name'];
    $hod_contact_mobile = $_POST['hod-contact'];
    $designation = $_POST['designation'];
    $hod_email = $_POST['hod-email'];
    $hod_intercom = $_POST['hod-intercom'];
    $department_id = $_POST['department_id'];

    // Update the department in the database
    $sql = "UPDATE departments SET school_id = ?, department_name = ?, hod_name = ?, hod_contact_mobile = ?, designation = ?, hod_contact_email = ?, hod_intercom = ? WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        $error_message = "Error preparing statement: " . $conn->error;
    } else {
        $stmt->bind_param("issssssi", $school_id, $department_name, $hod_name, $hod_contact_mobile, $designation, $hod_email, $hod_intercom, $department_id);
        
        if (!$stmt->execute()) {
            $error_message = "Error executing statement: " . $stmt->error;
        } else {
            $success_message = "Department updated successfully!";
            
            // Refresh department data
            $stmt = $conn->prepare("SELECT * FROM departments WHERE department_id = ?");
            $stmt->bind_param("i", $department_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $department = $result->fetch_assoc();
        }
        $stmt->close();
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
    <title>Edit Department</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 0;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 0;
            margin-top: -120px; 
            position: relative;
            z-index: 1;
        }
        
        .form-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .btn {
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            transform: translateY(-2px);
        }
        
        .required-field::after {
            content: " *";
            color: var(--accent-color);
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .section-title {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
            border-bottom: 2px solid var(--light-color);
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-edit me-2"></i>Edit Department</h1>
                </div>
                <div class="col-md-6 text-end">
                    <a href="modify_dept.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Back to Departments
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($department): ?>
            <div class="form-card">
                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <input type="hidden" name="department_id" value="<?= htmlspecialchars($department['department_id']); ?>">
                    
                    <h4 class="section-title"><i class="fas fa-university me-2"></i>School Information</h4>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label for="school_name" class="form-label required-field">School Name</label>
                            <select class="form-select" name="school_name" id="school_name" required>
                                <option value="">Select School</option>
                                <?php
                                    // Fetch all schools for the dropdown
                                    $sql = "SELECT school_id, school_name FROM schools ORDER BY school_name";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $selected = ($row['school_id'] == $department['school_id']) ? 'selected' : '';
                                            echo '<option value="' . $row['school_id'] . '" ' . $selected . '>' . htmlspecialchars($row['school_name']) . '</option>';
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <h4 class="section-title"><i class="fas fa-building me-2"></i>Department Information</h4>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label for="department-name" class="form-label required-field">Department Name</label>
                            <input type="text" class="form-control" id="department-name" name="department-name" value="<?= htmlspecialchars($department['department_name']); ?>" required>
                        </div>
                    </div>
                    
                    <h4 class="section-title"><i class="fas fa-user me-2"></i>HOD Information</h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="hod-name" class="form-label required-field">HOD Name</label>
                            <input type="text" class="form-control" id="hod-name" name="hod-name" value="<?= htmlspecialchars($department['hod_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="designation" class="form-label">Designation</label>
                            <input type="text" class="form-control" id="designation" name="designation" value="<?= htmlspecialchars($department['designation']); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="hod-contact" class="form-label required-field">Contact Mobile</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                                <input type="text" class="form-control" id="hod-contact" name="hod-contact" value="<?= htmlspecialchars($department['hod_contact_mobile']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="hod-email" class="form-label required-field">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="hod-email" name="hod-email" value="<?= htmlspecialchars($department['hod_contact_email']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="hod-intercom" class="form-label">Intercom Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" class="form-control" id="hod-intercom" name="hod-intercom" value="<?= htmlspecialchars($department['hod_intercom']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="modify_dept.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Department
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Department information could not be loaded. Please go back and try again.
                <div class="mt-3">
                    <a href="modify_dept.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Departments
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            if (form) {
                form.addEventListener('submit', function(event) {
                    let isValid = true;
                    
                    // Validate department name
                    const departmentName = document.getElementById('department-name').value.trim();
                    if (!departmentName) {
                        isValid = false;
                        document.getElementById('department-name').classList.add('is-invalid');
                    } else {
                        document.getElementById('department-name').classList.remove('is-invalid');
                    }
                    
                    // Validate HOD name
                    const hodName = document.getElementById('hod-name').value.trim();
                    if (!hodName) {
                        isValid = false;
                        document.getElementById('hod-name').classList.add('is-invalid');
                    } else {
                        document.getElementById('hod-name').classList.remove('is-invalid');
                    }
                    
                    // Validate email
                    const email = document.getElementById('hod-email').value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!email || !emailRegex.test(email)) {
                        isValid = false;
                        document.getElementById('hod-email').classList.add('is-invalid');
                    } else {
                        document.getElementById('hod-email').classList.remove('is-invalid');
                    }
                    
                    // Validate phone
                    const phone = document.getElementById('hod-contact').value.trim();
                    const phoneRegex = /^[0-9+\-\s()]{10,15}$/;
                    if (!phone || !phoneRegex.test(phone)) {
                        isValid = false;
                        document.getElementById('hod-contact').classList.add('is-invalid');
                    } else {
                        document.getElementById('hod-contact').classList.remove('is-invalid');
                    }
                    
                    if (!isValid) {
                        event.preventDefault();
                        alert('Please fill in all required fields correctly.');
                    }
                });
            }
        });
    </script>
</body>
<?php include('footer user.php'); ?>
</html>
