<?php
// Include database connection (this makes $conn available in the header file)
include('config.php');  // Start the session for managing login status
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pondicherry University Seminar Hall Booking</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <!-- Link to your CSS file -->
</head>
<body>

<!-- Header Section with Pondicherry University logo/brand on the left and page title on the right -->
<header>
    <div class="header-content">
        <!-- Left Side: Pondicherry University Logo -->
        <div class="navbar-logo">
            <a href="index.php">
                <img src="images/logo.png" alt="University Logo" style="height: 60px; margin-right: 15px;">
            </a>
        </div>

        <!-- Center: Navigation Links -->
        <nav class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
            <a href="contacts.php"><i class="fas fa-envelope"></i> Contact Us</a>
        </nav>

        <!-- Right Side: Login & Register Buttons -->
        <div class="auth-buttons">
            <a href="login.php" class="btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="register.php" class="btn-secondary"><i class="fas fa-user-plus"></i> Register</a>
        </div>
    </div>
</header>

