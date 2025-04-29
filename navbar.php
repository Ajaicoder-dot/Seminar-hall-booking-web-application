<?php
// Start the session and include the database connection
include('config.php');

// Ensure the user is logged in and has the role of either Admin or HOD
if (isset($_SESSION['email']) && isset($_SESSION['user_id'])) {
    $user_email = $_SESSION['email'];
    $user_id = $_SESSION['user_id'];

    // Fetch the user's name and role from the database
    $query = "SELECT name, role FROM users WHERE email = ? AND id = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("si", $user_email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $name = $user_data['name'] ?? 'User'; // Default name if not found
        $role = $user_data['role'] ?? 'Unknown'; // Default role if not found
        $stmt->close();
    } else {
        $name = 'User'; // Default name if query fails
        $role = 'Unknown'; // Default role
    }
} else {
    // If the session email is not set, redirect to login page
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pondicherry University - Hall Booking System</title>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Reset and base styles */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, html {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        /* Enhanced Navbar Styling */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 40px;
            background: linear-gradient(135deg, #6a11cb, #2575fc, #0a84ff);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .navbar.scrolled {
            padding: 8px 40px;
            background: linear-gradient(135deg, #5810a5, #1e60c8, #0064da);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .navbar-logo {
            display: flex;
            align-items: center;
            font-size: 22px;
            font-weight: 700;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            letter-spacing: 0.5px;
        }
        
        .navbar-logo i {
            font-size: 24px;
            margin-right: 10px;
            color: #ffde59;
        }

        .navbar-menu {
            list-style: none;
            display: flex;
            gap: 15px;
            margin: 0;
            padding: 0;
        }

        .navbar-menu li a {
            text-decoration: none;
            color: white;
            padding: 10px 16px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s;
            border-radius: 6px;
            position: relative;
            letter-spacing: 0.2px;
            display: flex;
            align-items: center;
        }
        
        .navbar-menu li a i {
            margin-right: 8px;
        }

        .navbar-menu li a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: #fff;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            transition: width 0.3s;
        }

        .navbar-menu li a:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .navbar-menu li a:hover::after {
            width: 70%;
        }

        /* Active Link Styling */
        .navbar-menu li a.active {
            background-color: rgba(255, 255, 255, 0.2);
            position: relative;
        }
        
        .navbar-menu li a.active::after {
            width: 70%;
        }

        .navbar-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .navbar-right .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 30px;
            background-color: rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        
        .navbar-right .user-info:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .navbar-right .user-avatar {
            width: 36px;
            height: 36px;
            background-color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-right .user-avatar i {
            font-size: 20px;
            color: #6a11cb;
        }

        .navbar-right .user-name {
            font-size: 15px;
            font-weight: 500;
            color: white;
            margin-left: 2px;
        }

        .navbar-right .logout-btn {
            text-decoration: none;
            font-size: 14px;
            padding: 8px 18px;
            border-radius: 30px;
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-right .logout-btn:hover {
            background-color: white;
            color: #6a11cb;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-right .logout-btn i {
            font-size: 14px;
        }

        /* Dropdown Styling */
        .dropdown {
            position: relative;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            list-style: none;
            padding: 10px 0;
            margin-top: 10px;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            transform: translateY(10px) translateX(-50%);
        }
        
        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 50%;
            transform: translateX(-50%);
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 6px solid white;
        }

        .dropdown-menu li {
            padding: 0;
        }

        .dropdown-menu li a {
            text-decoration: none;
            color: #444;
            display: block;
            padding: 10px 20px;
            font-size: 14px;
            transition: all 0.2s;
            border-radius: 0;
        }
        
        .dropdown-menu li a::after {
            display: none;
        }

        .dropdown-menu li a:hover {
            background-color: #f4f7ff;
            color: #6a11cb;
            padding-left: 25px;
        }
        
        .dropdown-menu li a i {
            margin-right: 8px;
            color: #6a11cb;
        }

        .dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateY(0) translateX(-50%);
        }
        
        /* Profile Dropdown Styling */
        .profile-dropdown {
            min-width: 280px;
            padding: 0;
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg,#7d5cf7, #2575fc);
            padding: 20px;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .profile-avatar {
            width: 70px;
            height: 70px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .profile-avatar i {
            font-size: 40px;
            color: white;
        }
        
        .profile-details h4 {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .profile-details p {
            margin: 5px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .profile-actions {
            padding: 0;
        }
        
        .profile-actions a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .profile-actions a:hover {
            background-color: #f0f7ff;
            color: #6a11cb;
        }
        
        .profile-actions a i {
            margin-right: 10px;
            color: #6a11cb;
        }
        
        /* Mobile Menu Icon */
        .mobile-menu-toggle {
            display: none;
            font-size: 24px;
            color: white;
            background: none;
            border: none;
            cursor: pointer;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .navbar {
                padding: 12px 20px;
            }
            
            .navbar-menu {
                gap: 5px;
            }
            
            .navbar-menu li a {
                padding: 8px 12px;
                font-size: 14px;
            }
            
            .navbar-right .user-name {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
            
            .navbar-menu {
                position: fixed;
                flex-direction: column;
                top: 60px;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #6a11cb, #2575fc);
                padding: 20px;
                gap: 15px;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
                transform: translateY(-150%);
                transition: transform 0.3s ease;
                z-index: 999;
                align-items: center;
            }
            
            .navbar-menu.active {
                transform: translateY(0);
            }
            
            .dropdown-menu {
                position: static;
                transform: none;
                width: 100%;
                box-shadow: none;
                margin-top: 10px;
                background-color: rgba(255, 255, 255, 0.1);
            }
            
            .dropdown-menu::before {
                display: none;
            }
            
            .dropdown-menu li a {
                color: white;
                padding: 10px;
            }
            
            .dropdown-menu li a:hover {
                background-color: rgba(255, 255, 255, 0.2);
                color: white;
            }
            
            .navbar-logo {
                font-size: 18px;
            }
            
            .navbar-right {
                gap: 10px;
            }
            
            .navbar-right .logout-btn span {
                display: none;
            }
        }
        
        /* Content spacing for fixed navbar */
        .main-content {
            margin-top: 80px;
            padding: 20px;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-logo">
        <img src="images/logo.png" alt="University Logo" style="height: 45px; margin-right: 10px;">
    </div>
    
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <ul class="navbar-menu">
        <li><a href="adminhome.php"><i class="fas fa-home"></i> Home</a></li>
        
        <!-- Hall Management Dropdown -->
        <li class="dropdown">
            <a href="#" class="dropdown-toggle"><i class="fas fa-door-open"></i> Hall Management <i class="fas fa-chevron-down" style="font-size: 12px; margin-left: 5px;"></i></a>
            <ul class="dropdown-menu">
                <li><a href="add_hall.php"><i class="fas fa-plus-circle"></i> Add Hall</a></li>
                <li><a href="modify_hall.php"><i class="fas fa-edit"></i> Modify/Delete Hall</a></li>
            </ul>
        </li>
        
        <!-- Department/School Management Dropdown -->
        <li class="dropdown">
            <a href="#" class="dropdown-toggle"><i class="fas fa-university"></i> Department/School <i class="fas fa-chevron-down" style="font-size: 12px; margin-left: 5px;"></i></a>
            <ul class="dropdown-menu">
                <li><a href="add_department.php"><i class="fas fa-building"></i> Add Department</a></li>
                <li><a href="modify_dept.php"><i class="fas fa-pencil-alt"></i> Modify Department</a></li>
                <li><a href="modify_school.php"><i class="fas fa-school"></i> Modify School</a></li>
            </ul>
        </li>
        <li class="dropdown">
            <a href="#" class="dropdown-toggle"><i class="fas fa-university"></i> Accept and reject <i class="fas fa-chevron-down" style="font-size: 12px; margin-left: 5px;"></i></a>
            <ul class="dropdown-menu">
            <li><a href="view_bookings.php"><i class="fas fa-clipboard-check"></i> Accept/Reject Bookings</a></li>
                <li><a href="admin_ccc_bookings.php"><i class="fas fa-calendar-alt"></i> CCC </a></li>
                
            </ul>
        </li>
        
       
        
        <!-- Management Dropdown -->
        <li class="dropdown">
            <a href="#" class="dropdown-toggle"><i class="fas fa-cogs"></i> Management <i class="fas fa-chevron-down" style="font-size: 12px; margin-left: 5px;"></i></a>
            <ul class="dropdown-menu">
                <li><a href="Manage_book.php"><i class="fas fa-pencil-alt"></i> Manage Bookings</a></li>
                <li><a href="Manage_bookings.php"><i class="fas fa-tasks"></i> Event Update</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="admin_requests.php"><i class="fas fa-ticket-alt"></i> Request Page</a></li>
            </ul>
        </li>
    </ul>

    <div class="navbar-right">
        <div class="user-info dropdown">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
            
            <!-- User Profile Dropdown -->
            <ul class="dropdown-menu profile-dropdown">
                <li class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="profile-details">
                        <h4><?php echo htmlspecialchars($name); ?></h4>
                        <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($role); ?></p>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_email); ?></p>
                        <?php
                        // Fetch department name if department_id exists
                        $dept_query = "SELECT d.department_name FROM users u 
                                      JOIN departments d ON u.department_id = d.id 
                                      WHERE u.id = ?";
                        $dept_stmt = $conn->prepare($dept_query);
                        if ($dept_stmt) {
                            $dept_stmt->bind_param("i", $user_id);
                            $dept_stmt->execute();
                            $dept_result = $dept_stmt->get_result();
                            if ($dept_row = $dept_result->fetch_assoc()) {
                                echo '<p><i class="fas fa-building"></i> ' . htmlspecialchars($dept_row['department_name']) . '</p>';
                            }
                            $dept_stmt->close();
                        }
                        ?>
                    </div>
                </li>
                <li class="profile-actions">
                    <a href="edit_profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
                </li>
            </ul>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>

<div class="main-content">
    <!-- Your page content goes here -->
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Dropdown functionality
        const dropdownToggles = document.querySelectorAll(".dropdown-toggle");
        
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener("click", function(e) {
                e.preventDefault();
                const menu = this.nextElementSibling;
                menu.classList.toggle("show");
            });
        });
        
        // User profile dropdown functionality
        const userInfo = document.querySelector(".user-info");
        const profileDropdown = document.querySelector(".profile-dropdown");
        
        userInfo.addEventListener("click", function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle("show");
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener("click", function(e) {
            const dropdownMenus = document.querySelectorAll(".dropdown-menu");
            
            dropdownMenus.forEach(menu => {
                if (!menu.previousElementSibling?.contains(e.target) && !menu.contains(e.target)) {
                    menu.classList.remove("show");
                }
            });
        });
        
        // Mobile menu toggle
        const mobileMenuToggle = document.querySelector(".mobile-menu-toggle");
        const navbarMenu = document.querySelector(".navbar-menu");
        
        mobileMenuToggle.addEventListener("click", function() {
            navbarMenu.classList.toggle("active");
        });
        
        // Scroll effect for navbar
        window.addEventListener("scroll", function() {
            if (window.scrollY > 50) {
                document.querySelector(".navbar").classList.add("scrolled");
            } else {
                document.querySelector(".navbar").classList.remove("scrolled");
            }
        });
        
        // Set active menu item based on current page
        const currentPage = window.location.pathname.split("/").pop();
        const menuLinks = document.querySelectorAll(".navbar-menu a");
        
        menuLinks.forEach(link => {
            const linkHref = link.getAttribute("href");
            if (linkHref === currentPage) {
                link.classList.add("active");
            }
        });
    });
</script>

</body>
</html>