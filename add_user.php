<?php
session_start();
include 'config.php';
// Move the navbar include after all potential header redirects
// include 'navbar.php'; - This line is moved below

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit();
}

// Require PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is properly installed via Composer

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($role) || empty($password)) {
        $_SESSION['message'] = "All fields are required";
        $_SESSION['message_type'] = "danger";
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $_SESSION['message'] = "Email already exists";
            $_SESSION['message_type'] = "danger";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                // Send email to the new user
                $email_sent = sendUserEmail($name, $email, $role, $password);
                
                $message = "User added successfully!";
                if ($email_sent) {
                    $message .= " Account details have been emailed to the user.";
                } else {
                    $message .= " However, there was an issue sending the email notification.";
                }
                
                $_SESSION['message'] = $message;
                $_SESSION['message_type'] = "success";
                header('Location: manage_users.php');
                exit();
            } else {
                $_SESSION['message'] = "Error adding user: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
        }
    }
}

// Now include the navbar after all potential header redirects
include 'navbar.php';

/**
 * Send email to new user with their account details using PHPMailer
 * 
 * @param string $name User's name
 * @param string $email User's email
 * @param string $role User's role
 * @param string $password User's password
 * @return bool Whether the email was sent successfully
 */
function sendUserEmail($name, $email, $role, $password) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();                                           // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                      // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                  // Enable SMTP authentication
        $mail->Username   = 'ajaiofficial06@gmail.com';            // SMTP username
        $mail->Password   = 'pxqzpxdkdbfgbfah';                    // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;        // Enable TLS encryption
        $mail->Port       = 587;                                   // TCP port to connect to
    
        // Recipients
        $mail->setFrom('ajaiofficial06@gmail.com', 'University Management System');
        $mail->addAddress($email, $name);                          // Add recipient
    
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your University Management System Account';
        $mail->Body = "
        <html>
        <head>
            <title>Your University Management System Account</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4a69bd; color: white; padding: 10px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                .credentials { background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #4a69bd; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Welcome to University Management System</h2>
                </div>
                <div class='content'>
                    <p>Dear $name,</p>
                    <p>Your account has been created in the University Management System. You can now log in using the credentials below:</p>
                    
                    <div class='credentials'>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Role:</strong> $role</p>
                        <p><strong>Password:</strong> $password</p>
                    </div>
                    
                    <p>For security reasons, please change your password after your first login.</p>
                    <p>If you have any questions, please contact the system administrator.</p>
                    
                    <p>Thank you,<br>University Management System Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User | University Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            padding-top: 0; /* Added padding to account for fixed navbar */
        }
        
        .main-container {
            min-height: calc(100vh - 120px); /* Adjusted to account for navbar and footer */
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .form-container {
            max-width: 700px;
            width: 100%; /* Added to ensure full width within max-width */
            margin: 0 auto;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .form-header {
            background: linear-gradient(135deg, #4a69bd 0%, #5c7cfa 100%);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        .form-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .form-header p {
            margin-top: 8px;
            opacity: 0.9;
            font-weight: 300;
        }
        
        .form-body {
            padding: 30px;
        }
        
        .floating-label-group {
            position: relative;
            margin-bottom: 25px;
            width: 100%; /* Ensure full width */
        }
        
        .floating-label-group .form-control {
            width: 100%; /* Ensure full width */
            padding: 12px 15px;
            border: none;
            border-radius: 8px;
            background-color: #f1f3f9;
            transition: all 0.3s ease;
            height: auto; /* Prevent height inconsistencies */
        }
        
        .floating-label-group .form-control:focus {
            box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.2);
            background-color: #fff;
        }
        
        .floating-label-group label {
            position: absolute;
            top: 12px;
            left: 15px;
            color: #6c757d;
            transition: all 0.2s ease;
            pointer-events: none;
        }
        
        .floating-label-group .form-control:focus + label,
        .floating-label-group .form-control:not(:placeholder-shown) + label {
            top: -10px;
            left: 10px;
            font-size: 12px;
            padding: 0 5px;
            background-color: #fff;
            color: #4a69bd;
            font-weight: 500;
        }
        
        .select-group select {
            width: 100%;
            padding: 12px 15px;
            border: none;
            border-radius: 8px;
            background-color: #f1f3f9;
            appearance: none;
            cursor: pointer;
            height: 48px; /* Match height with other inputs */
        }
        
        .select-group {
            position: relative;
        }
        
        .select-group::after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 12px;
            color: #6c757d;
            pointer-events: none;
        }
        
        .select-group select:focus {
            box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.2);
            background-color: #fff;
            outline: none;
        }
        
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 35px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-secondary {
            background-color: #f1f3f9;
            border: none;
            color: #4a4a4a;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4a69bd 0%, #5c7cfa 100%);
            border: none;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #3a59ad 0%, #4c6cea 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 105, 189, 0.4);
        }
        
        .btn-secondary:hover {
            background-color: #e1e5f0;
            transform: translateY(-2px);
        }
        
        .email-notification {
            margin-top: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #4a69bd;
            border-radius: 0 8px 8px 0;
            display: flex;
            align-items: center;
        }
        
        .email-notification i {
            font-size: 1.5rem;
            color: #4a69bd;
            margin-right: 15px;
        }
        
        .email-notification p {
            margin: 0;
            color: #555;
        }
        
        .password-group {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 12px;
            cursor: pointer;
            color: #6c757d;
            z-index: 2;
        }
        
        .form-divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
        }
        
        .form-divider span {
            flex: 1;
            height: 1px;
            background: #e0e0e0;
        }
        
        .form-divider p {
            margin: 0 15px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 25px;
            padding: 15px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        /* Animation for alerts */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade.show {
            animation: fadeIn 0.3s ease forwards;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container">
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-user-plus mr-2"></i> Add New User</h2>
                    <p>Create a new account for university staff</p>
                </div>
                
                <div class="form-body">
                    <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i>
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    endif; 
                    ?>
                    
                    <form method="post" action="">
                        <div class="floating-label-group">
                            <input type="text" class="form-control" id="name" name="name" placeholder=" " value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                            <label for="name"><i class="fas fa-user mr-2"></i>Full Name</label>
                        </div>
                        
                        <div class="floating-label-group">
                            <input type="email" class="form-control" id="email" name="email" placeholder=" " value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                            <label for="email"><i class="fas fa-envelope mr-2"></i>Email Address</label>
                        </div>
                        
                        <div class="select-group mb-4">
                            <select class="form-control" id="role" name="role" required>
                                <option value="" disabled selected>Select Role</option>
                                <option value="HOD" <?= isset($_POST['role']) && $_POST['role'] == 'HOD' ? 'selected' : '' ?>>Head of Department (HOD)</option>
                                <option value="Dean" <?= isset($_POST['role']) && $_POST['role'] == 'Dean' ? 'selected' : '' ?>>Dean</option>
                                <option value="Professor" <?= isset($_POST['role']) && $_POST['role'] == 'Professor' ? 'selected' : '' ?>>Professor</option>
                                <option value="Admin" <?= isset($_POST['role']) && $_POST['role'] == 'Admin' ? 'selected' : '' ?>>System Administrator</option>
                            </select>
                        </div>
                        
                        <div class="floating-label-group password-group">
                            <input type="password" class="form-control" id="password" name="password" placeholder=" " required>
                            <label for="password"><i class="fas fa-lock mr-2"></i>Password</label>
                            <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                        
                        <div class="email-notification">
                            <i class="fas fa-envelope-open-text"></i>
                            <div>
                                <p><strong>Automatic notification:</strong> An email with login credentials will be sent to the user upon account creation.</p>
                            </div>
                        </div>
                        
                        <div class="form-divider">
                            <span></span>
                            <p>User Information</p>
                            <span></span>
                        </div>
                        
                        <div class="btn-container">
                            <a href="manage_users.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Users
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-user-plus mr-2"></i> Create Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('#togglePassword').click(function() {
                const passwordField = $('#password');
                const passwordFieldType = passwordField.attr('type');
                
                if (passwordFieldType === 'password') {
                    passwordField.attr('type', 'text');
                    $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordField.attr('type', 'password');
                    $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
            
            // Highlight the floating labels on input focus
            $('.form-control').focus(function() {
                $(this).parent().addClass('focused');
            }).blur(function() {
                $(this).parent().removeClass('focused');
            });
            
            // Auto-close alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
</body>
<?php include 'footer user.php';?>
</html>