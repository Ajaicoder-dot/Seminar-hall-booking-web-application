<?php
session_start();
include('config.php');
include('navbar1.php'); 

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'Professor') {
    header("Location: login.php");
    exit();
}

// Form submission handler
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

    $query = "INSERT INTO contact_us (name, email, phone, city, remarks) 
              VALUES ('$name', '$email', '$phone', '$city', '$remarks')";

    if (mysqli_query($conn, $query)) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('Your message has been submitted successfully!', 'success');
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('Error submitting message. Please try again.', 'error');
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Pondicherry University Hall Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .page-title {
            text-align: center;
            margin: 40px 0 20px;
            color: #2575fc;
            font-size: 2.5rem;
            font-weight: 600;
            position: relative;
        }
        
        .page-title::after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            margin: 10px auto;
            border-radius: 2px;
        }
        
        .subtitle {
            text-align: center;
            margin-bottom: 40px;
            color: #666;
            font-size: 1.1rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .container {
            display: flex;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto 100px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .contact-form {
            flex: 1;
            min-width: 300px;
            padding: 50px;
            background: #fff;
        }
        
        .contact-form h2 {
            margin-bottom: 30px;
            font-size: 1.8rem;
            color: #2575fc;
            position: relative;
            padding-bottom: 10px;
        }
        
        .contact-form h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(45deg, #6a11cb, #2575fc);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #2575fc;
            box-shadow: 0 0 0 3px rgba(37, 117, 252, 0.2);
            outline: none;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .submit-btn {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            text-align: center;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .submit-btn:hover {
            background: linear-gradient(45deg, #5a0fb4, #1e5edc);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .contact-info {
            flex: 1;
            min-width: 300px;
            padding: 50px;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            position: relative;
            z-index: 1;
        }
        
        .contact-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDAiIGhlaWdodD0iMTQwIiB2aWV3Qm94PSIwIDAgMTQwIDE0MCI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0ibm9uZSIvPjxyZWN0IHdpZHRoPSIxMCIgaGVpZ2h0PSIxMCIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIvPjwvc3ZnPg==');
            z-index: -1;
            opacity: 0.2;
        }
        
        .contact-info h2 {
            margin-bottom: 30px;
            font-size: 1.8rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .contact-info h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: rgba(255, 255, 255, 0.5);
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        
        .info-icon {
            margin-right: 15px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .info-details h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .info-details p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .social-icons {
            margin-top: 40px;
        }
        
        .social-icons h4 {
            margin-bottom: 15px;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 50%;
            font-size: 1.2rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }
        
        .map-container {
            margin-top: 40px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        iframe {
            width: 100%;
            height: 250px;
            border: none;
            display: block;
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateX(120%);
            transition: transform 0.3s ease-out;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        .notification.error {
            background: linear-gradient(45deg, #dc3545, #e83e8c);
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                margin: 20px;
            }
            
            .contact-form,
            .contact-info {
                padding: 30px;
            }
            
            .page-title {
                font-size: 2rem;
                margin: 30px 0 15px;
            }
            
            .subtitle {
                padding: 0 20px;
            }
        }
    </style>
</head>
<body>

<!-- Page Title -->
<h1 class="page-title">Contact Us</h1>
<p class="subtitle">We're here to help with any questions you may have about hall booking or our services. Fill out the form below and we'll get back to you as soon as possible.</p>

<!-- Contact Container -->
<div class="container">
    <!-- Contact Form Section -->
    <div class="contact-form">
        <h2>Send us a Message</h2>
        <form method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email address" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
            </div>
            
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" placeholder="Enter your city" required>
            </div>
            
            <div class="form-group">
                <label for="remarks">Your Message</label>
                <textarea id="remarks" name="remarks" placeholder="How can we help you?" rows="5" required></textarea>
            </div>
            
            <button type="submit" class="submit-btn">Submit Message</button>
        </form>
    </div>
    
    <!-- Contact Info Section -->
    <div class="contact-info">
        <h2>Get in Touch</h2>
        
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="info-details">
                <h4>Our Location</h4>
                <p>R.V. Nagar, Kalapet, Puducherry - 605014</p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-phone-alt"></i>
            </div>
            <div class="info-details">
                <h4>Phone Number</h4>
                <p>+91 9361685137</p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="info-details">
                <h4>Email Address</h4>
                <p>contact@pondiuni.ac.in</p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="info-details">
                <h4>Working Hours</h4>
                <p>Monday - Friday: 9:00 AM - 5:00 PM</p>
            </div>
        </div>
        
        <div class="social-icons">
            <h4>Connect With Us</h4>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
        
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3916.4795561732656!2d79.85384331471812!3d12.013873391507185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a5361e932f49f7d%3A0x3e5d3d0f6c6d7b6e!2sPondicherry%20University!5e0!3m2!1sen!2sin!4v1709630410823!5m2!1sen!2sin" 
                allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</div>

<!-- Notification Element -->
<div id="notification" class="notification">
    <i class="fas fa-check-circle"></i>
    <span id="notification-message"></span>
</div>

<!-- JavaScript for Notification -->
<script>
    function showNotification(message, type) {
        const notification = document.getElementById('notification');
        const notificationMessage = document.getElementById('notification-message');
        
        notification.className = 'notification ' + type;
        notificationMessage.textContent = message;
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Hide notification after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
        }, 5000);
    }
</script>

<?php include('footer user.php'); ?>

</body>
</html>