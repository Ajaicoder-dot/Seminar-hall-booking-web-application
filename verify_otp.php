<?php
session_start();
include('config.php'); // Database connection
include('header.php'); // Include header

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['otp'])) {
        $entered_otp = $_POST['otp'];
        $email = $_SESSION['reset_email']; // Get stored email
        $role = $_SESSION['reset_role'];   // Get stored role

        // Check if OTP is correct
        $stmt = $conn->prepare("SELECT otp FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $stmt->bind_result($stored_otp);
        $stmt->fetch();
        $stmt->close();

        if ($entered_otp == $stored_otp) {
            $_SESSION['otp_verified'] = true;
            header("Location: reset_password.php"); // Redirect to reset password page
            exit();
        } else {
            $error_message = "Invalid OTP! Please try again.";
        }
    } elseif (isset($_POST['resend_otp'])) {
        // Generate a new OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;

        // Store new OTP in the database
        $stmt = $conn->prepare("UPDATE users SET otp = ? WHERE email = ? AND role = ?");
        $stmt->bind_param("iss", $otp, $_SESSION['reset_email'], $_SESSION['reset_role']);
        $stmt->execute();
        $stmt->close();

        // Send the new OTP via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ajaiofficial06@gmail.com';  // Replace with your email
            $mail->Password = 'pxqzpxdkdbfgbfah'; // Use App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('ajaiofficial06@gmail.com', 'Seminar Hall Booking');
            $mail->addAddress($_SESSION['reset_email']);
            $mail->Subject = "New OTP for Password Reset";
            $mail->Body = "Your new OTP is: $otp";

            $mail->send();
            $success_message = "A new OTP has been sent to your email.";
        } catch (Exception $e) {
            $error_message = "Error sending email: " . $mail->ErrorInfo;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - University Seminar Hall Booking</title>
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
        
        .otp-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        
        .otp-container h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .otp-description {
            color: #666;
            margin-bottom: 20px;
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .otp-input {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 5px;
            border: 2px solid #ddd;
            border-radius: 5px;
            background-color: #f8f9fa;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .otp-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary {
            background-color: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
        }
        
        .timer {
            font-size: 16px;
            color: var(--accent-color);
            margin: 15px 0;
            font-weight: 600;
        }
        
        .hidden {
            display: none;
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
        
        .otp-digits {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .digit-box {
            width: 50px;
            height: 60px;
            font-size: 24px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        
        .digit-box:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="page-content">
        <div class="otp-container">
            <h2><i class="fas fa-shield-alt"></i> OTP Verification</h2>
            
            <p class="otp-description">
                We've sent a verification code to <strong><?php echo isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : ''; ?></strong>
            </p>
            
            <?php if($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="timer" id="timer">Time left: 60s</div>
            
            <form action="verify_otp.php" method="post">
                <div class="form-group">
                    <input type="text" id="otp-input" name="otp" class="otp-input" required placeholder="Enter 6-digit OTP" maxlength="6" autocomplete="off">
                </div>
                
                <button type="submit" id="submit-btn" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Verify OTP
                </button>
            </form>
            
            <form action="verify_otp.php" method="post">
                <input type="hidden" name="resend_otp" value="1">
                <button type="submit" id="resend-otp" class="btn btn-secondary hidden">
                    <i class="fas fa-sync-alt"></i> Resend OTP
                </button>
            </form>
            
            <a href="forgot_password.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Forgot Password
            </a>
        </div>
    </div>
    
    <script>
        let timeLeft = 60;
        function startTimer() {
            let timerElement = document.getElementById("timer");
            let resendButton = document.getElementById("resend-otp");
            let otpInput = document.getElementById("otp-input");
            let submitBtn = document.getElementById("submit-btn");

            let timerInterval = setInterval(function () {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerElement.innerHTML = "<i class='fas fa-exclamation-triangle'></i> OTP expired! Request a new one.";
                    otpInput.disabled = true;
                    submitBtn.disabled = true;
                    resendButton.classList.remove("hidden");
                } else {
                    timerElement.innerHTML = "<i class='fas fa-clock'></i> Time left: " + timeLeft + "s";
                }
                timeLeft -= 1;
            }, 1000);
        }

        window.onload = startTimer;
    </script>
</body>
<?php include('footer user.php'); ?>
</html>
