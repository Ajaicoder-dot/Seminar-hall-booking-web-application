<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "User ID is required";
    $_SESSION['message_type'] = "danger";
    header('Location: manage_users.php');
    exit();
}

$user_id = $_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($role)) {
        $_SESSION['message'] = "All fields are required except password";
        $_SESSION['message_type'] = "danger";
    } else {
        // Update user without checking for email duplicates
        if (!empty($password)) {
            // Update with new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssi", $name, $email, $role, $hashed_password, $user_id);
        } else {
            // Update without changing password
            $update_query = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssi", $name, $email, $role, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "User updated successfully!";
            $_SESSION['message_type'] = "success";
            header('Location: manage_users.php');
            exit();
        } else {
            $_SESSION['message'] = "Error updating user: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
    }
}

// Fetch user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "User not found";
    $_SESSION['message_type'] = "danger";
    header('Location: manage_users.php');
    exit();
}

$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | University Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
            padding: 20px;
            margin-top: 60px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            background-color: var(--primary-color);
            color: white;
            font-size: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 20px;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-floating > .form-control {
            padding: 1rem 0.75rem;
        }
        
        .form-floating > label {
            padding: 1rem 0.75rem;
        }
    </style>
</head>
<body>
    <!-- Include navbar -->
    <?php include('navbar.php'); ?>

    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                                <?= $_SESSION['message'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php 
                                unset($_SESSION['message']);
                                unset($_SESSION['message_type']);
                            endif; 
                            ?>
                            
                            <div class="text-center mb-4">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                                <h4><?= htmlspecialchars($user['name']) ?></h4>
                                <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                            
                            <form method="post" action="" class="needs-validation" novalidate>
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" placeholder="Full Name" required>
                                    <label for="name">Full Name</label>
                                    <div class="invalid-feedback">Please provide a name.</div>
                                </div>
                                
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Email Address" required>
                                    <label for="email">Email Address</label>
                                    <div class="invalid-feedback">Please provide a valid email.</div>
                                </div>
                                
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="HOD" <?= $user['role'] == 'HOD' ? 'selected' : '' ?>>HOD</option>
                                        <option value="Dean" <?= $user['role'] == 'Dean' ? 'selected' : '' ?>>Dean</option>
                                        <option value="Professor" <?= $user['role'] == 'Professor' ? 'selected' : '' ?>>Professor</option>
                                        <option value="Admin" <?= $user['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <label for="role">User Role</label>
                                </div>
                                
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password">
                                    <label for="password">Password (Leave blank to keep current)</label>
                                    <div class="form-text">Enter a new password only if you want to change it.</div>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="manage_users.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Users
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
<?php include('footer user.php'); ?>
</html>