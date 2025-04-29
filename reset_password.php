<?php
session_start();
include('config.php'); // Database connection
include('header.php'); // Include header

if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
    header("Location: forgot_password.php");
    exit("Unauthorized access!");
}

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['password']) && isset($_POST['confirm_password'])) {
        if ($_POST['password'] === $_POST['confirm_password']) {
            $new_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $email = $_SESSION['reset_email'];
            $role = $_SESSION['reset_role'];

            // Update password in database
            $stmt = $conn->prepare("UPDATE users SET password = ?, otp = NULL WHERE email = ? AND role = ?");
            $stmt->bind_param("sss", $new_password, $email, $role);
            $result = $stmt->execute();
            $stmt->close();

            if ($result) {
                // Clear session
                session_unset();
                session_destroy();
                $success_message = "Password reset successful!";
            } else {
                $error_message = "Failed to update password. Please try again.";
            }
        } else {
            $error_message = "Passwords do not match!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - University Seminar Hall Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --text-color: #333;
            --light-bg: #f9f9f9;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            color: var(--text-color);
        }
        
        .page-content {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
            padding: 20px;
        }
        
        .reset-password-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        
        .reset-password-container h2 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .alert-danger {
            background-color: #fde8e8;
            color: #e53e3e;
            border: 1px solid #f8d7d7;
        }
        
        .alert-success {
            background-color: #e6f7ef;
            color: var(--success-color);
            border: 1px solid #d1f0e0;
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .strength-weak {
            background-color: var(--accent-color);
            width: 30%;
        }
        
        .strength-medium {
            background-color: #f39c12;
            width: 60%;
        }
        
        .strength-strong {
            background-color: var(--success-color);
            width: 100%;
        }
        
        .password-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .input-icon-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        .icon-input {
            padding-left: 40px;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
        }
        
        .login-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="page-content">
        <div class="reset-password-container">
            <h2><i class="fas fa-key"></i> Reset Password</h2>
            
            <?php if($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                <p>Please <a href="login.php" class="login-link">login here</a> with your new password.</p>
            </div>
            <?php else: ?>
            
            <form action="reset_password.php" method="post" id="resetForm">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control icon-input" required placeholder="Enter new password">
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="password-info" id="passwordInfo">Password should be at least 8 characters</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control icon-input" required placeholder="Confirm new password">
                        <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const icon = this;
            
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                confirmPasswordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const passwordInfo = document.getElementById('passwordInfo');
            
            // Remove all classes
            strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
                passwordInfo.textContent = 'Password should be at least 8 characters';
                return;
            }
            
            // Check password strength
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            if (password.match(/\d+/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            // Update strength bar
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                passwordInfo.textContent = 'Weak password';
            } else if (strength === 3) {
                strengthBar.classList.add('strength-medium');
                passwordInfo.textContent = 'Medium strength password';
            } else {
                strengthBar.classList.add('strength-strong');
                passwordInfo.textContent = 'Strong password';
            }
        });
        
        // Confirm password validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
<?php include('footer user.php'); ?>
</html>
