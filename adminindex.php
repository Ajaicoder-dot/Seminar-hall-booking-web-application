<?php
session_start(); // Start the session
include('config.php'); // Include the database connection

// Ensure the user is logged in and has the role of either Admin or HOD
if (!isset($_SESSION['email']) || !in_array($_SESSION['role'], ['Admin', 'HOD'])) {
    header("Location: login.php");
    exit();
}

// Get user email from the session
$user_email = $_SESSION['email'];

// Fetch the user's name and role from the database
$query = "SELECT name, role FROM users WHERE email = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("s", $user_email); // Bind the correct email parameter
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $name = $user_data['name'] ?? 'User'; // Default to "User" if no name found
    $role = $user_data['role']; // Get the role (Admin or HOD)
    $stmt->close(); // Close the statement
} else {
    $name = 'User'; // Default if query fails
    $role = ''; // Default role
}

$conn->close(); // Close the database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="#" />
    <title>Pondicherry University - Dashboard</title>
    <style>
        /* General reset */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Navbar styles */
        .navbar {
            background: linear-gradient(45deg, #6a11cb, #2575fc); /* Blue color for the navbar */
            overflow: hidden;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            padding: 10px 20px;
            align-items: center;
        }

        .navbar-left {
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .navbar-menu {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex: 1; /* Make navbar menu take up remaining space */
            justify-content: center;
        }

        .navbar-menu li {
            position: relative;
        }

        .navbar-menu li a {
            display: block;
            color: white;
            text-align: center;
            padding: 14px 20px;
            text-decoration: none;
        }

        .navbar-menu li a:hover {
            background-color: #ffffff; /* White hover background */
            color: #007BFF; /* Blue text color on hover */
            border-radius: 10px;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .navbar-right a {
            color: white;
            text-decoration: none;
            margin-left: 10px; /* Space out logout button */
            padding: 10px 20px;
            border-radius: 5px; /* Rounded corners for a nicer look */
        }

        .navbar-right a:hover {
            background-color: #ffffff; /* White background on hover */
            color: #007BFF; /* Blue text on hover */
        }

        .navbar-right span { /* Style for the user name span */
            color: white;
            margin-right: 10px; /* Add margin-right to create space */
            font-weight: bold; /* Make the user name bold */
            display: inline-block; /* Make the span display inline */
        }

        /* Main page styles */
        .container {
            margin: 80px auto; /* Push content below the fixed navbar */
            padding: 20px;
            max-width: 800px;
            text-align: center;
        }

        /* Content boxes */
        .content-box {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .content-box h3 {
            margin-top: 0;
        }

        /* Button styles */
        .btn-primary {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">Pondicherry University</div>
        <ul class="navbar-menu">
            <li><a href="add_hall.php">Add Hall</a></li>
            <li><a href="modify_hall.php">Modify/Delete Hall</a></li>
            <li><a href="add_department.php">Add Department</a></li>
            <li><a href="modify_dept.php">Modify Department</a></li>
        <li><a href="modify_school.php">Modify school</a></li>
            <li><a href="view_bookings.php">Accept/Reject Bookings</a></li>
            <li><a href="Manage_bookings.php">Manage Bookings</a></li>
        </ul>
        <div class="navbar-right">
            <span><?php echo "Hi! ".htmlspecialchars($name); ?></span> 
            <a href="logout.php" class="logout-btn">Logout</a> 
        </div>
    </nav>

    <main class="dashboard-page">
        <div class="container">
            <h2>Welcome to the Dashboard, <?php echo "Hi! ".htmlspecialchars($name); ?>!</h2>
            <?php if ($role == 'Admin') { ?>
                <div class="content-box">
                    <h3>Admin Functions</h3>
                    <p><a href="add_hall.php" class="btn-primary">Add Hall</a></p>
                    <p><a href="modify_hall.php" class="btn-primary">Modify/Delete Hall</a></p>
                    <p><a href="add_department.php" class="btn-primary">Add Department</a></p>
                    <p><a href="modify_dept.php"    class="btn-primary">Modify Department</a></p>
                    <p><a href="modify_school.php"    class="btn-primary">Modify school</a></p>
        
                    <p><a href="view_bookings.php" class="btn-primary">view bookings</a></p>
                    <p><a href="Manage_bookings.php" class="btn-primary">Manage Booking</a></p>
                </div>
            <?php } ?>

           
        </div>
    </main>
</body>
</html>