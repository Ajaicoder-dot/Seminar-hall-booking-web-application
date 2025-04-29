<?php
session_start();
include('config.php'); // Database connection
include('header.php'); // Include header

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $role = $_POST['role']; // Get selected role

    // Check if email & role exist in the database
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $otp = rand(100000, 999999); // Generate OTP
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_role'] = $role; // Store role in session

        // Store OTP in database
        $stmt = $conn->prepare("UPDATE users SET otp = ? WHERE email = ? AND role = ?");
        $stmt->bind_param("iss", $otp, $email, $role);
        $stmt->execute();
        $stmt->close();

        // Send OTP via Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ajaiofficial06@gmail.com';  // Replace with your email
            $mail->Password = 'pxqzpxdkdbfgbfah'; // Use App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('ajaiofficial06@gmail.com', 'Seminar Hall Booking ');
            $mail->addAddress($email);
            $mail->Subject = "Password Reset OTP";
            $mail->Body = "Your OTP for password reset is: $otp";

            $mail->send();
            header("Location: verify_otp.php"); // Redirect to OTP verification page
            exit();
        } catch (Exception $e) {
            $error_message = "Email could not be sent. Error: " . $mail->ErrorInfo;
        }
    } else {
        $error_message = "Email or role not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - University Seminar Hall Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --accent-color: #e74c3c;
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
        
        .forgot-password-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        
        .forgot-password-container h2 {
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
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
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
    </style>
</head>
<body>
    <div class="page-content">
        <div class="forgot-password-container">
            <h2><i class="fas fa-key"></i> Forgot Password</h2>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <form action="forgot_password.php" method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control icon-input" required placeholder="Enter your registered email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="role">Select Your Role</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-user-tag input-icon"></i>
                        <select id="role" name="role" class="form-control icon-input" required>
                            <option value="">-- Select Role --</option>
                            <option value="hod">HOD</option>
                            <option value="professor">Professor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Send OTP
                </button>
            </form>
            
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</body>
<?php include('footer user.php'); ?>
</html>
