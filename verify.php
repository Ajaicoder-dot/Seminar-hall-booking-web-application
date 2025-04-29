<?php
include('config.php'); // Include database configuration
include('header.php'); // Include the header and navigation bar

$message = '';
$status = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists and is valid
    $stmt = $conn->prepare("SELECT * FROM email_verification WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        
        // Insert user into the users table
        $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role, department_id, email_verified) 
                                      VALUES (?, ?, ?, ?, ?, 1)");
        $insertStmt->bind_param("ssssi", 
            $userData['name'], 
            $userData['email'], 
            $userData['password'], 
            $userData['role'], 
            $userData['department_id']
        );
        
        if ($insertStmt->execute()) {
            // Delete the verification record
            $deleteStmt = $conn->prepare("DELETE FROM email_verification WHERE token = ?");
            $deleteStmt->bind_param("s", $token);
            $deleteStmt->execute();
            
            $status = 'success';
            $message = 'Your email has been verified successfully! You can now login to your account.';
        } else {
            $status = 'error';
            $message = 'Error registering your account. Please try again.';
        }
    } else {
        // Check if token exists but expired
        $stmt = $conn->prepare("SELECT * FROM email_verification WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $status = 'error';
            $message = 'Verification link has expired. Please register again.';
            
            // Delete expired verification record
            $deleteStmt = $conn->prepare("DELETE FROM email_verification WHERE token = ?");
            $deleteStmt->bind_param("s", $token);
            $deleteStmt->execute();
        } else {
            $status = 'error';
            $message = 'Invalid verification link.';
        }
    }
} else {
    $status = 'error';
    $message = 'No verification token provided.';
}
?>

<div class="verify-wrapper">
    <div class="verify-container">
        <div class="verify-box">
            <div class="verify-header">
                <h2>Email Verification</h2>
            </div>
            
            <div class="verify-message <?php echo $status; ?>">
                <span class="message-icon">
                    <i class="fas <?php echo $status === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                </span>
                <span class="message-text"><?php echo $message; ?></span>
            </div>
            
            <div class="verify-actions">
                <?php if ($status === 'success'): ?>
                    <a href="login.php" class="action-button">
                        <span class="button-icon"><i class="fas fa-sign-in-alt"></i></span>
                        <span class="button-text">Login Now</span>
                    </a>
                <?php else: ?>
                    <a href="register.php" class="action-button">
                        <span class="button-icon"><i class="fas fa-user-plus"></i></span>
                        <span class="button-text">Register Again</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Verification page styling */
.verify-wrapper {
    width: 100%;
    padding: 50px 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 200px);
    background-color: transparent;
    position: relative;
    z-index: 1;
    overflow: hidden;
}

.verify-wrapper::after {
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

.verify-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: -1;
}

.verify-container {
    width: 100%;
    max-width: 500px;
}

.verify-box {
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

.verify-header {
    text-align: center;
    margin-bottom: 25px;
}

.verify-header h2 {
    font-size: 24px;
    color: #333;
    margin-bottom: 8px;
    font-weight: 600;
}

.verify-message {
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
}

.verify-message.success {
    background-color: #e8f5e9;
    border-left: 4px solid #4caf50;
    color: #2e7d32;
}

.verify-message.error {
    background-color: #ffebee;
    border-left: 4px solid #f44336;
    color: #d32f2f;
}

.message-icon {
    margin-right: 15px;
    font-size: 24px;
}

.message-text {
    font-size: 16px;
    line-height: 1.5;
}

.verify-actions {
    display: flex;
    justify-content: center;
}

.action-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 24px;
    background: linear-gradient(45deg, #4a90e2, #3a7bc8);
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.action-button:hover {
    background: linear-gradient(45deg, #3a7bc8, #2e52a4);
    box-shadow: 0 5px 15px rgba(74, 144, 226, 0.4);
    transform: translateY(-2px);
}

.button-icon {
    margin-right: 8px;
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
</style>

<?php include('footer.php'); ?>