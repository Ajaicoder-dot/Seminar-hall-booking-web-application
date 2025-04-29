<?php 
session_start();
include('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Check user credentials and role
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_id'] = $user['id'];
            
            // Redirect based on role
            if ($role === 'Admin' || $role === 'HOD') {
                header("Location: adminhome.php");
            } elseif ($role === 'Professor') {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Email or role not registered.";
    }
}

// Include header after processing the form
include('header.php');
?>

<!-- Login Page Content -->
<div class="login-wrapper">
    <!-- Animated background elements -->
    <div class="animated-bg"></div>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>
    
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h2 class="animate-title">Welcome Back</h2>
                <p class="animate-subtitle">Please sign in to continue</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="login-error animate-error">
                    <span class="error-icon"><i class="fas fa-exclamation-circle"></i></span>
                    <span class="error-text"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-field animate-field">
                    <label for="email">Email Address</label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-field animate-field" style="animation-delay: 0.1s;">
                    <label for="password">Password</label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <div class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </div>
                    </div>
                </div>
                
                <div class="form-field animate-field" style="animation-delay: 0.2s;">
                    <label for="role">Your Role</label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="fas fa-user-tag"></i>
                        </div>
                        <select id="role" name="role" required>
                            <option value="" disabled selected>Select your role</option>
                            <option value="Admin">Admin</option>
                            <option value="HOD">HOD</option>
                            <option value="Professor">Professor</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions animate-field" style="animation-delay: 0.3s;">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" required>
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-button animate-button" style="animation-delay: 0.4s;">
                    <span class="button-icon"><i class="fas fa-sign-in-alt"></i></span>
                    <span class="button-text">Sign In</span>
                </button>
            </form>
            
            <div class="register-prompt animate-field" style="animation-delay: 0.5s;">
                <p>Don't have an account? <a href="register.php">Sign up now</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Add custom CSS that works with your existing header/footer -->
<style>
/* Reset some basic elements to prevent conflicts */
.login-wrapper *, 
.login-wrapper *:before, 
.login-wrapper *:after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* Main wrapper that works with your header and footer */
.login-wrapper {
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

/* Enhanced animated background */
.animated-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
   
    background: linear-gradient(135deg, rgba(74, 144, 226, 0.3), rgba(46, 82, 164, 0.4)), 
                linear-gradient(135deg, rgba(34, 139, 34, 0.3), rgba(0, 128, 0, 0.4)), 
                url('images/3.jpg');

    background-size: cover;
    background-position: center;
    z-index: -2;
    animation: gradientBG 15s ease infinite;
}

/* Floating shapes animation */
.floating-shapes {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    overflow: hidden;
}

.shape {
    position: absolute;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    animation: float 20s linear infinite;
}

.shape-1 {
    width: 150px;
    height: 150px;
    top: 10%;
    left: 10%;
    animation-duration: 35s;
    transform-origin: center center;
    animation-name: float-1;
    background: linear-gradient(45deg, rgba(34, 139, 34, 0.2), rgba(0, 128, 0, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.shape-2 {
    width: 100px;
    height: 100px;
    top: 20%;
    right: 10%;
    animation-duration: 25s;
    animation-name: float-2;
    background: linear-gradient(45deg, rgba(0, 128, 0, 0.2), rgba(34, 139, 34, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.shape-3 {
    width: 80px;
    height: 80px;
    bottom: 15%;
    left: 15%;
    animation-duration: 30s;
    animation-name: float-3;
    background: linear-gradient(45deg, rgba(34, 139, 34, 0.2), rgba(0, 128, 0, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.shape-4 {
    width: 120px;
    height: 120px;
    bottom: 10%;
    right: 15%;
    animation-duration: 40s;
    animation-name: float-4;
    background: linear-gradient(45deg, rgba(0, 128, 0, 0.2), rgba(34, 139, 34, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.2);
}

@keyframes float-1 {
    0% {
        transform: translate(0, 0) rotate(0deg);
    }
    25% {
        transform: translate(100px, 50px) rotate(90deg);
    }
    50% {
        transform: translate(50px, 150px) rotate(180deg);
    }
    75% {
        transform: translate(-50px, 100px) rotate(270deg);
    }
    100% {
        transform: translate(0, 0) rotate(360deg);
    }
}

@keyframes float-2 {
    0% {
        transform: translate(0, 0) rotate(0deg);
    }
    33% {
        transform: translate(-80px, 100px) rotate(120deg);
    }
    66% {
        transform: translate(-30px, -50px) rotate(240deg);
    }
    100% {
        transform: translate(0, 0) rotate(360deg);
    }
}

@keyframes float-3 {
    0% {
        transform: translate(0, 0) rotate(0deg);
    }
    33% {
        transform: translate(70px, -70px) rotate(-120deg);
    }
    66% {
        transform: translate(140px, 40px) rotate(-240deg);
    }
    100% {
        transform: translate(0, 0) rotate(-360deg);
    }
}

@keyframes float-4 {
    0% {
        transform: translate(0, 0) rotate(0deg);
    }
    25% {
        transform: translate(-120px, -60px) rotate(-90deg);
    }
    50% {
        transform: translate(-60px, -120px) rotate(-180deg);
    }
    75% {
        transform: translate(60px, -60px) rotate(-270deg);
    }
    100% {
        transform: translate(0, 0) rotate(-360deg);
    }
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

/* The main login box - adding some opacity to make it stand out from the background */
.login-box {
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
    transition: all 0.3s ease;
}

.login-box:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
    transform: translateY(-5px);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Form field animations */
.animate-field {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Login button styling with animation */
.login-button {
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
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
}

.login-button::after {
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

.login-button:hover {
    background: linear-gradient(45deg, #3a7bc8, #2e52a4);
    box-shadow: 0 5px 15px rgba(74, 144, 226, 0.4);
    transform: translateY(-2px);
}

.login-button:hover::after {
    left: 100%;
}

/* Title animations */
.animate-title {
    opacity: 0;
    transform: translateY(-20px);
    animation: dropDown 0.7s ease forwards;
}

.animate-subtitle {
    opacity: 0;
    animation: fadeIn 0.7s ease 0.3s forwards;
}

@keyframes dropDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Error message animation */
.animate-error {
    animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}

@keyframes shake {
    10%, 90% {
        transform: translate3d(-1px, 0, 0);
    }
    20%, 80% {
        transform: translate3d(2px, 0, 0);
    }
    30%, 50%, 70% {
        transform: translate3d(-4px, 0, 0);
    }
    40%, 60% {
        transform: translate3d(4px, 0, 0);
    }
}

/* Container for the login box */
.login-container {
    width: 100%;
    max-width: 450px;
    perspective: 1000px;
}

/* Input field focus animation */
.input-container input:focus,
.input-container select:focus {
    border-color: #4a90e2;
    outline: none;
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(74, 144, 226, 0.4);
    }
    70% {
        box-shadow: 0 0 0 5px rgba(74, 144, 226, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(74, 144, 226, 0);
    }
}

/* Header styles */
.login-header {
    text-align: center;
    margin-bottom: 25px;
}

.login-header h2 {
    font-size: 24px;
    color: #333;
    margin-bottom: 8px;
    font-weight: 600;
}

.login-header p {
    color: #666;
    font-size: 16px;
}

/* Error message styling */
.login-error {
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
    transition: all 0.3s ease;
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
    transition: all 0.3s ease;
}

.password-toggle:hover {
    color: #4a90e2;
    transform: scale(1.1);
}

/* Form actions styling */
.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.remember-me {
    display: flex;
    align-items: center;
}

.remember-me input[type="checkbox"] {
    margin-right: 8px;
    width: 16px;
    height: 16px;
}

.remember-me label {
    font-size: 14px;
    color: #666;
}

.forgot-password {
    color: #4a90e2;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.forgot-password:after {
    content: '';
    position: absolute;
    width: 100%;
    transform: scaleX(0);
    height: 1px;
    bottom: -2px;
    left: 0;
    background-color: #4a90e2;
    transform-origin: bottom right;
    transition: transform 0.3s ease-out;
}

.forgot-password:hover:after {
    transform: scaleX(1);
    transform-origin: bottom left;
}

/* Register prompt styling */
.register-prompt {
    text-align: center;
    margin-top: 20px;
    color: #666;
    font-size: 14px;
}

.register-prompt a {
    color: #4a90e2;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
}

.register-prompt a:after {
    content: '';
    position: absolute;
    width: 100%;
    transform: scaleX(0);
    height: 1px;
    bottom: -2px;
    left: 0;
    background-color: #4a90e2;
    transform-origin: bottom right;
    transition: transform 0.3s ease-out;
}

.register-prompt a:hover:after {
    transform: scaleX(1);
    transform-origin: bottom left;
}

/* Media queries for responsive design */
@media screen and (max-width: 480px) {
    .login-box {
        padding: 20px;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .forgot-password {
        margin-top: 10px;
    }
    
    .shape {
        display: none;
    }
}
@keyframes leafFloat {
    0% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
    100% {
        transform: translateY(0);
    }
}

.shape {
    animation: leafFloat 5s ease-in-out infinite;
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
    
    // Add input focus effects
    const inputs = document.querySelectorAll('.input-container input, .input-container select');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('input-focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('input-focused');
        });
    });
    
    // Add hover effect to login box
    const loginBox = document.querySelector('.login-box');
    
    if (loginBox) {
        loginBox.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 12px 40px rgba(0, 0, 0, 0.25)';
        });
        
        loginBox.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.2)';
        });
    }
    
    // Add button hover effect for mobile
    const loginButton = document.querySelector('.login-button');
    
    if (loginButton) {
        loginButton.addEventListener('touchstart', function() {
            this.classList.add('button-touched');
        });
        
        loginButton.addEventListener('touchend', function() {
            this.classList.remove('button-touched');
        });
    }
});
</script>
<?php include('footer user.php'); ?>