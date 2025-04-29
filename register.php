<?php
include('config.php'); // Include database configuration
include('header.php'); // Include the header and navigation bar

// Fetch departments from the database
$departments = [];
$query = "SELECT department_id, department_name FROM departments";
$result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Function to validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to generate a random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Function to send email using PHPMailer
// Add this line at the top after includes
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($email, $name, $token) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ajaiofficial06@gmail.com';
        $mail->Password   = 'pxqzpxdkdbfgbfah';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('ajaiofficial06@gmail.com', 'University Portal');
        $mail->addAddress($email, $name);
        
        // Content
        // Modify the verification link to use IP address
        $serverIP = "10.83.112.194"; // Your current IP address Replace with your computer's IP address
        $verificationLink = "http://" . $serverIP . "/university/verify.php?token=" . $token;
        
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - University Portal';
        $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4a90e2; color: white; padding: 15px; text-align: center; }
                    .content { padding: 20px; border: 1px solid #ddd; border-top: none; }
                    .button { display: inline-block; padding: 10px 20px; background-color: #4a90e2; color: white; 
                              text-decoration: none; border-radius: 4px; margin: 20px 0; }
                    .footer { font-size: 12px; color: #777; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>University Portal - Email Verification</h2>
                    </div>
                    <div class='content'>
                        <p>Dear $name,</p>
                        <p>Thank you for registering with our University Portal. Please click the button below to verify your email address:</p>
                        <p style='text-align: center;'>
                            <a href='$verificationLink' class='button'>Verify Email Address</a>
                        </p>
                        <p>If the button doesn't work, you can also click on the link below or copy it to your browser:</p>
                        <p><a href='$verificationLink'>$verificationLink</a></p>
                        <p>This link will expire in 24 hours.</p>
                        <p>Regards,<br>University Administration</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message, please do not reply to this email.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Dear $name,\n\nThank you for registering with our University Portal. Please click the link below to verify your email address:\n\n$verificationLink\n\nThis link will expire in 24 hours.\n\nRegards,\nUniversity Administration";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $role = $_POST['role']; // Role selection
    $department_id = $_POST['department']; // Selected department
    
    // Validate email format
    if (!isValidEmail($email)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if the (email, role) combination already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered with the selected role!";
        } else {
            // Generate verification token
            $token = generateToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Store user data and verification token in temporary table
            $stmt = $conn->prepare("INSERT INTO email_verification (name, email, password, role, department_id, token, expiry) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiss", $name, $email, $password, $role, $department_id, $token, $expiry);
            
            if ($stmt->execute()) {
                // Send verification email using PHPMailer
                if (sendVerificationEmail($email, $name, $token)) {
                    $success = "Verification email sent! Please check your email to complete registration.";
                } else {
                    $error = "Failed to send verification email. Please try again.";
                }
            } else {
                $error = "Error processing registration.";
            }
        }
    }
}
?>

<!-- Registration Page Content remains the same -->
<div class="register-wrapper">
    <div class="register-container">
        <div class="register-box">
            <div class="register-header">
                <h2>Create an Account</h2>
                <p>Please fill in the details to register</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="register-error">
                    <span class="error-icon"><i class="fas fa-exclamation-circle"></i></span>
                    <span class="error-text"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="register-success">
                    <span class="success-icon"><i class="fas fa-check-circle"></i></span>
                    <span class="success-text"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="register.php" class="register-form">
                <div class="form-field">
                    <label for="name">Full Name</label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <input type="text" id="name" name="name" placeholder="Enter your name" required>
                    </div>
                </div>
                
                <div class="form-field">
                    <label for="email">Email Address</label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-field">
                    <label for="password">Password</label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <div class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </div>
                    </div>
                </div>
                
                <div class="form-field">
                    <label for="role">Your Role</label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="fas fa-user-tag"></i>
                        </div>
                        <select id="role" name="role" required>
                            <option value="" disabled selected>Select your role</option>
                            <option value="HOD">HOD</option>
                            <option value="Dean">Dean</option>
                            <option value="Professor">Professor</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-field">
                    <label for="department">Your Department</label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <select id="department" name="department" required>
                            <option value="" disabled selected>Select your department</option>
                            <?php foreach ($departments as $dept) { ?>
                                <option value="<?= $dept['department_id']; ?>"><?= htmlspecialchars($dept['department_name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <div class="terms-check">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I accept all terms & conditions</label>
                    </div>
                </div>
                
                <button type="submit" class="register-button">
                    <span class="button-icon"><i class="fas fa-user-plus"></i></span>
                    <span class="button-text">Register Now</span>
                </button>
            </form>
            
            <div class="login-prompt">
                <p>Already have an account? <a href="login.php">Login now</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Add custom CSS that matches the login page design -->
<style>
/* Reset some basic elements to prevent conflicts */
.register-wrapper *, 
.register-wrapper *:before, 
.register-wrapper *:after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* Main wrapper that works with your header and footer */
.register-wrapper {
    width: 100%;
    padding: 50px 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 200px); /* Adjust based on header/footer height */
    background-color: transparent;
    position: relative;
    z-index: 1;
    overflow: hidden; /* Prevent animation overflow */
}

/* Animated background */
.register-wrapper::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(74, 144, 226, 0.3), rgba(46, 82, 164, 0.4)), 
                url('images/2.jpg');
    background-size: cover;
    background-position: center;
    z-index: -2;
    animation: gradientBG 15s ease infinite;
}

/* Add floating elements animation */
.register-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('images/3.png');
    background-size: 500px;
    opacity: 0.1;
    z-index: -1;
    animation: floatingBG 60s linear infinite;
}

/* Animation keyframes */
@keyframes gradientBG {
    0% {
        background-position: 0% 50%;
        filter: hue-rotate(0deg);
    }
    50% {
        background-position: 100% 50%;
        filter: hue-rotate(10deg);
    }
    100% {
        background-position: 0% 50%;
        filter: hue-rotate(0deg);
    }
}

@keyframes floatingBG {
    0% {
        background-position: 0px 0px;
    }
    100% {
        background-position: 1000px 500px;
    }
}

/* The main register box - adding some opacity to make it stand out from the background */
.register-box {
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    padding: 35px;
    width: 100%;
    position: relative;
    z-index: 2;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    animation: fadeIn 1s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
/* Success message styling */
.register-success {
    background-color: #e8f5e9;
    border-left: 4px solid #4caf50;
    color: #2e7d32;
    padding: 12px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.success-icon {
    margin-right: 10px;
    font-size: 18px;
}

/* Register button styling with animation */
.register-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 45px;
    background: linear-gradient(45deg, #4a90e2, #3a7bc8);
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.register-button::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0) 100%);
    transform: rotate(45deg);
    transition: all 0.5s;
}

.register-button:hover {
    background: linear-gradient(45deg, #3a7bc8, #2e52a4);
    box-shadow: 0 5px 15px rgba(74, 144, 226, 0.4);
    transform: translateY(-2px);
}

.register-button:hover::after {
    left: 100%;
}

/* Add the blur effect overlay */
.register-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.7); /* White overlay with opacity */
    backdrop-filter: blur(8px); /* Blur effect */
    -webkit-backdrop-filter: blur(8px); /* For Safari */
    z-index: -1;
}

/* The main register box - adding some opacity to make it stand out from the background */
.register-box {
    background-color: rgba(255, 255, 255, 0.9); /* Slightly transparent white */
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); /* Enhanced shadow for better contrast */
    padding: 30px;
    width: 100%;
    position: relative; /* Ensure it's above the blur */
    z-index: 2;
}

/* Container for the register box */
.register-container {
    width: 100%;
    max-width: 500px;
}

/* The main register box */
.register-box {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 30px;
    width: 100%;
}

/* Header styles */
.register-header {
    text-align: center;
    margin-bottom: 25px;
}

.register-header h2 {
    font-size: 24px;
    color: #333;
    margin-bottom: 8px;
    font-weight: 600;
}

.register-header p {
    color: #666;
    font-size: 16px;
}

/* Error message styling */
.register-error {
    background-color: #ffebee;
    border-left: 4px solid #f44336;
    color: #d32f2f;
    padding: 12px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.error-icon {
    margin-right: 10px;
    font-size: 18px;
}

/* Form field styling */
.form-field {
    margin-bottom: 20px;
}

.form-field label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
}

.input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 12px;
    color: #666;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
}

.input-container input,
.input-container select {
    width: 100%;
    height: 45px;
    padding: 10px 12px 10px 40px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 15px;
    color: #333;
    background-color: #fff;
}

.input-container input:focus,
.input-container select:focus {
    border-color: #4a90e2;
    outline: none;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
}

.input-container select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23666666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    padding-right: 40px;
}

.password-toggle {
    position: absolute;
    right: 12px;
    color: #666;
    cursor: pointer;
}

/* Form actions styling */
.form-actions {
    margin-bottom: 20px;
}

.terms-check {
    display: flex;
    align-items: center;
}

.terms-check input[type="checkbox"] {
    margin-right: 8px;
    width: 16px;
    height: 16px;
}

.terms-check label {
    font-size: 14px;
    color: #666;
}

/* Register button styling */
.register-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 45px;
    background-color: #4a90e2;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.register-button:hover {
    background-color: #3a7bc8;
}

.button-icon {
    margin-right: 8px;
}

/* Login prompt styling */
.login-prompt {
    text-align: center;
    margin-top: 20px;
    color: #666;
    font-size: 14px;
}

.login-prompt a {
    color: #4a90e2;
    text-decoration: none;
    font-weight: 500;
}

.login-prompt a:hover {
    text-decoration: underline;
}

/* Media queries for responsive design */
@media screen and (max-width: 480px) {
    .register-box {
        padding: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.querySelector('.password-toggle');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
});
</script>

<?php include('footer user.php'); ?>