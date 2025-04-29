<?php
session_start();
include('config.php');

// Ensure the user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is admin or HOD to use the correct navbar
$is_admin = isset($_SESSION['role']) && ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'HOD');

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch departments for dropdown
$departments = [];
$dept_query = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$dept_result = $conn->query($dept_query);
if ($dept_result) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Fetch current user data
$user_query = "SELECT u.name, u.email, u.role, u.department_id, d.department_name 
               FROM users u 
               LEFT JOIN departments d ON u.department_id = d.department_id 
               WHERE u.id = ?";
$stmt = $conn->prepare($user_query);

// After fetching user data, add this debugging code
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
    
    // Debug user's department_id
    error_log("User department_id: " . ($user_data['department_id'] ?? 'null'));
} else {
    $error_message = "Error fetching user data: " . $conn->error;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    
    // Debug the submitted department_id
    error_log("Submitted department_id: " . ($department_id ?? 'null'));
    
    // Validate inputs
    if (empty($name)) {
        $error_message = "Name is a required field.";
    } else {
        // Check if email already exists for another user with the same role
        $proceed_with_update = true;
        
        if ($email !== $user_data['email']) {
            // Email check code remains the same
        }
        
        // Update user data - ensure department_id is handled correctly
        if ($proceed_with_update || $name !== $user_data['name'] || $department_id != $user_data['department_id']) {
            // If department_id is empty or 0 but user already has a department, keep the existing one
            if (empty($department_id) && !empty($user_data['department_id'])) {
                $department_id = $user_data['department_id'];
                error_log("Using existing department_id: " . $department_id);
            }
            
            $update_query = "UPDATE users SET name = ?, email = ?";
            $params = array($name, $email);
            $types = "ss";
            
            // Only include department_id in the update if it has a valid value
            if (!empty($department_id)) {
                $update_query .= ", department_id = ?";
                $params[] = $department_id;
                $types .= "i";
            }
            
            $update_query .= " WHERE id = ?";
            $params[] = $user_id;
            $types .= "i";
            
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param($types, ...$params);
            
            if ($update_stmt->execute()) {
                // Update session email if it changed and was valid
                if ($email !== $_SESSION['email'] && $proceed_with_update) {
                    $_SESSION['email'] = $email;
                }
                
                if (!$proceed_with_update) {
                    $success_message = "Profile partially updated. Email remains unchanged.";
                } else {
                    $success_message = "Profile updated successfully!";
                }
                
                // Refresh user data
                $stmt = $conn->prepare($user_query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();
                $stmt->close();
            } else {
                $error_message = "Error updating profile: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $success_message = "No changes were made to your profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Pondicherry University</title>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Base styles */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, html {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #0062cc, #0a84ff);
            color: white;
            padding: 20px 30px;
            position: relative;
        }
        
        .card-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #0a84ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23444' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 12px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #0a84ff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #0062cc;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .readonly-field {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php 
    // Include the appropriate navbar based on user role
    if ($is_admin) {
        include('navbar.php');
    } else {
        include('navbar1.php');
    }
    ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-user-edit"></i> Edit Profile</h1>
            </div>
            <div class="card-body">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" id="role" class="form-control readonly-field" value="<?php echo htmlspecialchars($user_data['role'] ?? ''); ?>" readonly>
                        <small>Role cannot be changed. Please contact administrator for role changes.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department_id" class="form-select">
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): 
                                // Debug each department option
                                error_log("Department option: {$dept['department_id']} - Comparing with: {$user_data['department_id']}");
                            ?>
                                <option value="<?php echo $dept['department_id']; ?>" 
                                    <?php echo (isset($user_data['department_id']) && (string)$user_data['department_id'] === (string)$dept['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const nameField = document.getElementById('name');
            const emailField = document.getElementById('email');
            
            if (nameField.value.trim() === '') {
                e.preventDefault();
                alert('Please enter your name');
                nameField.focus();
                return false;
            }
            
            if (emailField.value.trim() === '') {
                e.preventDefault();
                alert('Please enter your email');
                emailField.focus();
                return false;
            }
            
            // Basic email validation
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailField.value)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                emailField.focus();
                return false;
            }
        });
    </script>
</body>
<?php 
// Include the appropriate footer based on user role

    include('footer user.php');

?>
</html>