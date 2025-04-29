<?php
// Include database connection
session_start(); // Start the session
include('config.php'); // Include the database connection

// Ensure the user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Get user email from the session
$user_email = $_SESSION['email'];
$user_role = $_SESSION['role'] ?? '';

// Fetch the user's name from the database
$query = "SELECT name FROM users WHERE email = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $name = $user_data['name'] ?? 'User';
    $stmt->close();
} else {
    $name = 'User';
}

// Check if hall_id is provided
if (!isset($_GET['hall_id'])) {
    header("Location: view_hall.php");
    exit();
}

$hall_name = $_GET['hall_id'];

// Fetch hall details
$hall_query = "
    SELECT 
        h.*,
        d.department_name, 
        ht.type_name AS hall_type_name,
        s.school_name
    FROM 
        halls h
    LEFT JOIN 
        departments d ON h.department_id = d.department_id
    LEFT JOIN 
        hall_type ht ON h.hall_type = ht.hall_type_id
    LEFT JOIN
        schools s ON h.school_id = s.school_id
    WHERE 
        h.hall_name = ?
";

$hall_stmt = $conn->prepare($hall_query);
$hall_stmt->bind_param("s", $hall_name);
$hall_stmt->execute();
$hall_result = $hall_stmt->get_result();

if ($hall_result->num_rows === 0) {
    header("Location: view_hall.php");
    exit();
}

$hall = $hall_result->fetch_assoc();
$hall_id = $hall['hall_id'];

// Fetch hall bookings for the calendar
$bookings_query = "
    SELECT 
        from_date, 
        end_date, 
        start_time, 
        end_time, 
        program_name
    FROM 
        hall_bookings
    WHERE 
        hall_id = ?
";

$bookings_stmt = $conn->prepare($bookings_query);
$bookings_stmt->bind_param("i", $hall_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();

$bookings = [];
while ($booking = $bookings_result->fetch_assoc()) {
    $start_date = new DateTime($booking['from_date']);
    $end_date = new DateTime($booking['end_date']);
    $interval = new DateInterval('P1D');
    $date_range = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));
    
    foreach ($date_range as $date) {
        $date_str = $date->format('Y-m-d');
        if (!isset($bookings[$date_str])) {
            $bookings[$date_str] = [];
        }
        $bookings[$date_str][] = [
            'start_time' => $booking['start_time'],
            'end_time' => $booking['end_time'],
            'program_name' => $booking['program_name']
        ];
    }
}

// Convert bookings to JSON for JavaScript
$bookings_json = json_encode($bookings);

// Parse features
$features = explode(',', $hall['features']);
$amenities = [
    'ac' => 'Air Conditioning',
    'projector' => 'Projector',
    'smart_board' => 'Smart Board',
    'white_board' => 'White Board',
    'audio_system' => 'Audio System',
    'wifi' => 'WiFi',
    'computer_lab' => 'Computer Lab',
    'charging_points' => 'Charging Points'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hall['hall_name']); ?> - Details</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.css" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e54c8;
            --secondary-color: #8f94fb;
            --accent-color: #38b6ff;
            --text-primary: #333;
            --text-secondary: #666;
            --white: #ffffff;
            --bg-gradient: linear-gradient(135deg, #4e54c8, #8f94fb);
            --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 20px 45px rgba(0, 0, 0, 0.15);
            --transition-speed: 0.4s;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .bg-animation li {
            position: absolute;
            display: block;
            list-style: none;
            width: 40px;
            height: 40px;
            background: rgba(78, 84, 200, 0.1);
            animation: animate 25s linear infinite;
            bottom: -150px;
            border-radius: 50%;
        }

        .bg-animation li:nth-child(1) {
            left: 25%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }

        .bg-animation li:nth-child(2) {
            left: 10%;
            width: 20px;
            height: 20px;
            animation-delay: 2s;
            animation-duration: 12s;
        }

        .bg-animation li:nth-child(3) {
            left: 70%;
            width: 20px;
            height: 20px;
            animation-delay: 4s;
        }

        .bg-animation li:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-delay: 0s;
            animation-duration: 18s;
        }

        .bg-animation li:nth-child(5) {
            left: 65%;
            width: 20px;
            height: 20px;
            animation-delay: 0s;
        }

        .bg-animation li:nth-child(6) {
            left: 75%;
            width: 110px;
            height: 110px;
            animation-delay: 3s;
        }

        .bg-animation li:nth-child(7) {
            left: 35%;
            width: 150px;
            height: 150px;
            animation-delay: 7s;
        }

        .bg-animation li:nth-child(8) {
            left: 50%;
            width: 25px;
            height: 25px;
            animation-delay: 15s;
            animation-duration: 45s;
        }

        .bg-animation li:nth-child(9) {
            left: 20%;
            width: 15px;
            height: 15px;
            animation-delay: 2s;
            animation-duration: 35s;
        }

        .bg-animation li:nth-child(10) {
            left: 85%;
            width: 150px;
            height: 150px;
            animation-delay: 0s;
            animation-duration: 11s;
        }

        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.5;
                border-radius: 30%;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }

        .hall-detail-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-top: 40px;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
            transition: all var(--transition-speed) ease;
        }

        .hall-detail-container:hover {
            box-shadow: var(--hover-shadow);
            transform: translateY(-5px);
        }

        .hall-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 20px 20px 0 0;
            height: 450px;
        }

        .hall-image {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform 1.5s ease;
        }

        .hall-image-container:hover .hall-image {
            transform: scale(1.1);
        }

        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            padding: 30px;
            color: var(--white);
        }

        .hall-info {
            padding: 40px;
        }

        .hall-title {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 25px;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
            position: relative;
            display: inline-block;
        }

        .hall-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 80px;
            height: 4px;
            background: var(--bg-gradient);
            border-radius: 2px;
        }

        .info-section {
            margin-bottom: 35px;
            transition: all var(--transition-speed) ease;
            border-radius: 15px;
            padding: 20px;
            background-color: #f8f9fa;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .info-section:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .info-title {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 2px solid rgba(78, 84, 200, 0.2);
            padding-bottom: 10px;
            position: relative;
        }

        .info-title::before {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 50px;
            height: 2px;
            background: var(--primary-color);
        }

        .info-item {
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: var(--text-secondary);
            transition: all 0.3s ease;
            padding: 10px;
            border-radius: 8px;
        }

        .info-item:hover {
            background: rgba(78, 84, 200, 0.05);
            transform: translateX(5px);
        }

        .info-icon {
            color: var(--accent-color);
            margin-right: 15px;
            width: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .info-item:hover .info-icon {
            transform: scale(1.2);
        }

        .amenity-badge {
            background: linear-gradient(45deg, rgba(78, 84, 200, 0.1), rgba(143, 148, 251, 0.1));
            color: var(--primary-color);
            margin-right: 12px;
            margin-bottom: 12px;
            padding: 10px 18px;
            border-radius: 50px;
            font-size: 0.95rem;
            display: inline-block;
            transition: all 0.3s ease;
            border: 1px solid rgba(78, 84, 200, 0.2);
        }

        .amenity-badge:hover {
            background: linear-gradient(45deg, rgba(78, 84, 200, 0.2), rgba(143, 148, 251, 0.2));
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.2);
        }

        .btn-book {
            background: var(--bg-gradient);
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 50px;
            font-weight: 600;
            letter-spacing: 1px;
            transition: all 0.4s ease;
            margin-top: 30px;
            position: relative;
            overflow: hidden;
            z-index: 1;
            box-shadow: 0 10px 20px rgba(78, 84, 200, 0.3);
        }

        .btn-book:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background: linear-gradient(135deg, #8f94fb, #4e54c8);
            transition: all 0.4s ease;
            z-index: -1;
        }

        .btn-book:hover {
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(78, 84, 200, 0.4);
        }

        .btn-book:hover:before {
            width: 100%;
        }

        .calendar-container {
            padding: 40px;
            background-color: #f8f9fa;
            border-radius: 20px;
            margin-top: 40px;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed) ease;
        }

        .calendar-container:hover {
            box-shadow: var(--hover-shadow);
        }

        .calendar-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 25px;
            color: var(--primary-color);
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .calendar-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--bg-gradient);
            border-radius: 3px;
        }

        #calendar {
            height: 600px;
        }

        .fc-day-past {
            background-color: rgba(17, 17, 17, 0.7) !important;
        }

        .fc-day-available {
            background-color: rgba(40, 167, 69, 0.2) !important;
            transition: all 0.3s ease;
        }

        .fc-day-available:hover {
            background-color: rgba(40, 167, 69, 0.4) !important;
        }

        .fc-day-booked {
            background-color: rgba(220, 53, 69, 0.2) !important;
            transition: all 0.3s ease;
        }

        .fc-day-booked:hover {
            background-color: rgba(220, 53, 69, 0.4) !important;
        }

        .fc-button {
            background: var(--bg-gradient) !important;
            border: none !important;
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3) !important;
            transition: all 0.3s ease !important;
        }

        .fc-button:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 20px rgba(78, 84, 200, 0.4) !important;
        }

        .fc-event-main {
            padding: 5px !important;
            border-radius: 4px !important;
            font-weight: 600 !important;
        }

        .legend {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin: 10px 15px;
            transition: all 0.3s ease;
        }

        .legend-item:hover {
            transform: translateY(-3px);
        }

        .legend-color {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            margin-right: 10px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .past-color {
            background-color: rgba(17, 17, 17, 0.7);
        }

        .available-color {
            background-color: rgba(40, 167, 69, 0.2);
        }

        .booked-color {
            background-color: rgba(220, 53, 69, 0.2);
        }

        /* Tooltip styles */
        .tooltip-container {
            position: relative;
            display: inline-block;
        }

        .tooltip-container .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip-container .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* 360 Viewer Styles */
        .tour-btn {
            background: linear-gradient(135deg, #38b6ff, #4e54c8);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(56, 182, 255, 0.3);
        }

        .tour-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(56, 182, 255, 0.4);
        }

        .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }

        .modal-header {
            background: var(--bg-gradient);
            color: white;
            border: none;
            padding: 20px 30px;
        }

        .modal-body {
            padding: 0;
        }

        .modal-footer {
            border: none;
            padding: 20px;
        }

        /* Quick Info Card */
        .quick-info-card {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 10;
            transition: all 0.3s ease;
            max-width: 300px;
        }

        .quick-info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }

        .quick-info-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .quick-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .quick-info-icon {
            color: var(--accent-color);
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Booking Animation */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(78, 84, 200, 0.7);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(78, 84, 200, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(78, 84, 200, 0);
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        /* Scroll to top button */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--bg-gradient);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 99;
            opacity: 0;
            transition: all 0.4s ease;
            box-shadow: 0 8px 20px rgba(78, 84, 200, 0.3);
        }

        .scroll-to-top.active {
            opacity: 1;
        }

        .scroll-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(78, 84, 200, 0.4);
        }

        /* Media Queries */
        @media (max-width: 992px) {
            .hall-image-container {
                height: 350px;
            }
            
            .quick-info-card {
                position: relative;
                top: 0;
                right: 0;
                margin: 20px auto;
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .hall-title {
                font-size: 2.2rem;
            }
            
            .info-section {
                padding: 15px;
            }
            
            .hall-info {
                padding: 25px;
            }
            
            .calendar-container {
                padding: 25px;
            }
            
            .amenity-badge {
                font-size: 0.85rem;
                padding: 8px 15px;
            }
        }

        /* Loading Animation */
        .loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: all 0.5s ease;
        }

        .loader.fade-out {
            opacity: 0;
            visibility: hidden;
        }

        .loader-circle {
            width: 60px;
            height: 60px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading animation -->
    <div class="loader">
        <div class="loader-circle"></div>
    </div>

    <!-- Background Animation -->
    <ul class="bg-animation">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
    </ul>

    <?php include('navbar1.php'); ?>
    
    <div class="container hall-detail-container" data-aos="fade-up" data-aos-duration="1000">
        <div class="row">
            <div class="col-md-12">
                <div class="hall-image-container">
                    <img src="<?php echo htmlspecialchars($hall['image']); ?>" alt="<?php echo htmlspecialchars($hall['hall_name']); ?>" class="hall-image">
                    <div class="image-overlay">
                        <h1 class="display-4 fw-bold"><?php echo htmlspecialchars($hall['hall_name']); ?></h1>
                        <p class="lead"><?php echo htmlspecialchars($hall['hall_type_name']); ?></p>
                    </div>
                    
                    <!-- Quick Info Card -->
                    <div class="quick-info-card" data-aos="fade-left" data-aos-delay="300">
                        <div class="quick-info-title">Quick Info</div>
                        <div class="quick-info-item">
                            <i class="fas fa-users quick-info-icon"></i>
                            <span><?php echo htmlspecialchars($hall['capacity']); ?> people</span>
                        </div>
                        <div class="quick-info-item">
                            <i class="fas fa-map-marker-alt quick-info-icon"></i>
                            <span>Floor: <?php echo htmlspecialchars($hall['floor_name'] ?? 'Not specified'); ?></span>
                        </div>
                        <div class="quick-info-item">
                            <i class="fas fa-user quick-info-icon"></i>
                            <span>Contact: <?php echo htmlspecialchars($hall['incharge_name']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="hall-info">
                    <h1 class="hall-title" data-aos="fade-right"><?php echo htmlspecialchars($hall['hall_name']); ?></h1>
                    
                    <div class="info-section" data-aos="fade-up" data-aos-delay="100">
                        <h2 class="info-title">Basic Information</h2>
                        <div class="info-item">
                            <i class="fas fa-users info-icon"></i>
                            <strong>Capacity:</strong> <?php echo htmlspecialchars($hall['capacity']); ?> people
                        </div>
                        <div class="info-item">
                            <i class="fas fa-building info-icon"></i>
                            <strong>Type:</strong> <?php echo htmlspecialchars($hall['hall_type_name']); ?>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt info-icon"></i>
                            <strong>Floor:</strong> <?php echo htmlspecialchars($hall['floor_name'] ?? 'Not specified'); ?>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-compass info-icon"></i>
                            <strong>Zone:</strong> <?php echo htmlspecialchars($hall['zone'] ?? 'Not specified'); ?>
                        </div>
                    </div>
                    
                    <div class="info-section" data-aos="fade-up" data-aos-delay="200">
                        <h2 class="info-title">Affiliation</h2>
                        <div class="info-item">
                            <i class="fas fa-university info-icon"></i>
                            <strong>Belongs to:</strong> <?php echo htmlspecialchars($hall['belong_to'] ?? 'Not specified'); ?>
                        </div>
                        <?php if (!empty($hall['department_name'])): ?>
                        <div class="info-item">
                            <i class="fas fa-graduation-cap info-icon"></i>
                            <strong>Department:</strong> <?php echo htmlspecialchars($hall['department_name']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($hall['school_name'])): ?>
                        <div class="info-item">
                            <i class="fas fa-school info-icon"></i>
                            <strong>School:</strong> <?php echo htmlspecialchars($hall['school_name']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-section" data-aos="fade-up" data-aos-delay="300">
                        <h2 class="info-title">Contact Information</h2>
                        <div class="info-item">
                            <i class="fas fa-user info-icon"></i>
                            <strong>Incharge:</strong> <?php echo htmlspecialchars($hall['incharge_name']); ?>
                            </div>
                        <div class="info-item">
                            <i class="fas fa-id-badge info-icon"></i>
                            <strong>Designation:</strong> <?php echo htmlspecialchars($hall['designation'] ?? 'Not specified'); ?>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope info-icon"></i>
                            <strong>Email:</strong> 
                            <a href="mailto:<?php echo htmlspecialchars($hall['incharge_email']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($hall['incharge_email']); ?>
                            </a>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone info-icon"></i>
                            <strong>Phone:</strong> 
                            <a href="tel:<?php echo htmlspecialchars($hall['incharge_phone']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($hall['incharge_phone']); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="info-section" data-aos="fade-up" data-aos-delay="400">
                        <h2 class="info-title">Amenities</h2>
                        <div class="amenities-container">
                            <?php foreach ($features as $feature): ?>
                                <?php if (isset($amenities[trim($feature)])): ?>
                                    <span class="amenity-badge">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?php echo htmlspecialchars($amenities[trim($feature)]); ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- 360 Degree View Section -->
                    <div class="info-section" data-aos="fade-up" data-aos-delay="500">
                        <h2 class="info-title">Virtual Tour</h2>
                        <div class="mb-3">
                            <button type="button" class="tour-btn" data-bs-toggle="modal" data-bs-target="#tour360Modal">
                                <i class="fas fa-vr-cardboard me-2"></i>Experience 360° Virtual Tour
                            </button>
                        </div>
                        <p class="text-muted mt-3">
                            <i class="fas fa-info-circle me-2"></i> Get a feel for the hall with our interactive 360° virtual tour. Explore the space as if you were there!
                        </p>
                    </div>
                    
                    <a href="book_hall.php?hall_id=<?php echo urlencode($hall['hall_name']); ?>" class="btn btn-book pulse-animation" data-aos="zoom-in" data-aos-delay="600">
                        <i class="fas fa-calendar-check me-2"></i>Book This Hall Now
                    </a>
                </div>
            </div>
            <!-- Replace the existing calendar-container div with this code -->
<div class="calendar-container" data-aos="fade-left" data-aos-delay="200">
    <h2 class="calendar-title">Availability Calendar</h2>
    
    <!-- Month Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button id="prevMonth" class="btn btn-sm btn-primary">
            <i class="fas fa-chevron-left me-1"></i> Previous Month
        </button>
        <h3 id="currentMonthYear" class="m-0">April 2025</h3>
        <button id="nextMonth" class="btn btn-sm btn-primary">
            Next Month <i class="fas fa-chevron-right ms-1"></i>
        </button>
    </div>
    
    <!-- Theater Screen -->
    <div class="theater-screen">
        <div class="screen-text">CALENDAR VIEW</div>
    </div>
    
    <!-- Days of Week Header -->
    <div class="days-header">
        <div class="day-name">Sun</div>
        <div class="day-name">Mon</div>
        <div class="day-name">Tue</div>
        <div class="day-name">Wed</div>
        <div class="day-name">Thu</div>
        <div class="day-name">Fri</div>
        <div class="day-name">Sat</div>
    </div>
    
    <!-- Theater Seating Calendar -->
    <div id="theaterCalendar" class="theater-calendar">
        <!-- Calendar days will be generated here by JavaScript -->
    </div>
    
    <!-- Legend -->
    <div class="seat-legend">
        <div class="legend-item">
            <div class="seat seat-past"><span>20</span></div>
            <span>Past Date</span>
        </div>
        <div class="legend-item">
            <div class="seat seat-available"><span>12</span></div>
            <span>Available</span>
        </div>
        <div class="legend-item">
            <div class="seat seat-booked"><span>25</span></div>
            <span>Booked</span>
        </div>
    </div>
    
    <!-- Selected Date Info -->
    <div id="dateInfoPanel" class="date-info-panel mt-4 d-none">
        <h4 id="selectedDate" class="date-info-title">April 15, 2025</h4>
        <div id="dateStatus" class="date-status available">Available for Booking</div>
        <div id="bookingDetails" class="booking-details d-none">
            <div class="booking-item">
                <div class="booking-time">10:00 AM - 12:00 PM</div>
                <div class="booking-name">Department Meeting</div>
            </div>
            <!-- More booking items would be added here if multiple bookings exist -->
        </div>
        <button id="bookSelectedDate" class="btn btn-success mt-3">
            <i class="fas fa-calendar-check me-2"></i>Book This Date
        </button>
    </div>
</div>

<style>
/* Theater Calendar Styles */
.theater-screen {
    background: linear-gradient(135deg, #333, #111);
    height: 50px;
    border-radius: 5px 5px 50% 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    margin-bottom: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    text-transform: uppercase;
    letter-spacing: 2px;
    position: relative;
    overflow: hidden;
}

.theater-screen::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
    animation: scanLine 2s linear infinite;
}

@keyframes scanLine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.screen-text {
    text-shadow: 0 0 10px rgba(56, 182, 255, 0.8);
}

.days-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    margin-bottom: 15px;
    text-align: center;
}

.day-name {
    font-weight: 600;
    color: var(--primary-color);
    padding: 5px;
    font-size: 0.9rem;
}

.theater-calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    margin-bottom: 30px;
}

.seat {
    width: 100%;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
}

.seat span {
    position: relative;
    z-index: 2;
}

.seat::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.1);
    opacity: 0;
    transition: all 0.3s ease;
}

.seat:hover::before {
    opacity: 1;
}

.seat-past {
    background: #868e96;
    cursor: not-allowed;
}

.seat-available {
    background: #28a745;
    transform-origin: center bottom;
}

.seat-available:hover {
    transform: translateY(-5px) scale(1.05);
    box-shadow: 0 10px 20px rgba(40, 167, 69, 0.4);
}

.seat-booked {
    background: #dc3545;
    cursor: pointer;
}

.seat-booked:hover {
    animation: wiggle 0.5s ease;
}

.seat-inactive {
    background: transparent;
    box-shadow: none;
    cursor: default;
}

.seat.selected {
    animation: pulse 1.5s infinite;
    box-shadow: 0 0 0 5px rgba(56, 182, 255, 0.5);
    transform: translateY(-5px) scale(1.1);
    z-index: 3;
}

.seat-past.selected {
    animation: none;
    box-shadow: 0 0 0 5px rgba(134, 142, 150, 0.5);
    transform: none;
}

.seat-with-event {
    position: relative;
}

.seat-with-event::after {
    content: '';
    position: absolute;
    bottom: 10%;
    left: 10%;
    width: 80%;
    height: 6px;
    background: rgba(255,255,255,0.7);
    border-radius: 3px;
}

@keyframes wiggle {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.seat-legend {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 30px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.legend-item .seat {
    width: 40px;
    height: 40px;
    font-size: 0.8rem;
}

.date-info-panel {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 20px;
    margin-top: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.date-info-title {
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--primary-color);
}

.date-status {
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: 500;
    margin-bottom: 15px;
}

.date-status.available {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.date-status.booked {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.date-status.past {
    background: rgba(134, 142, 150, 0.1);
    color: #868e96;
}

.booking-details {
    margin-top: 20px;
}

.booking-item {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.booking-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.booking-time {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 5px;
}

.booking-name {
    color: var(--text-secondary);
}

/* Animation for the selected seat */
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(56, 182, 255, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(56, 182, 255, 0); }
    100% { box-shadow: 0 0 0 0 rgba(56, 182, 255, 0); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .theater-calendar {
        gap: 5px;
    }
    
    .days-header {
        gap: 5px;
    }
    
    .day-name {
        font-size: 0.8rem;
        padding: 3px;
    }
    
    .seat span {
        font-size: 0.9rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Parse bookings data from PHP
    const bookingsData = <?php echo $bookings_json; ?>;
    
    // Get current date information
    const currentDate = new Date();
    const currentDay = currentDate.getDate();
    const currentMonth = currentDate.getMonth();
    const currentYear = currentDate.getFullYear();
    
    // Initialize calendar variables
    let displayMonth = currentMonth;
    let displayYear = currentYear;
    
    // Function to update month display
    function updateMonthYearDisplay() {
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'];
        document.getElementById('currentMonthYear').textContent = `${months[displayMonth]} ${displayYear}`;
    }
    
    // Generate the calendar
    function generateCalendar(month, year) {
        const theaterCalendar = document.getElementById('theaterCalendar');
        theaterCalendar.innerHTML = '';
        
        // Get the first day of the month (0 = Sunday, 1 = Monday, etc.)
        const firstDay = new Date(year, month, 1).getDay();
        
        // Get the number of days in the month
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        // Create empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            const emptySeat = document.createElement('div');
            emptySeat.className = 'seat seat-inactive';
            theaterCalendar.appendChild(emptySeat);
        }
        
        // Create cells for each day of the month
        for (let day = 1; day <= daysInMonth; day++) {
            // Create date string in YYYY-MM-DD format for bookings check
            const monthStr = (month + 1).toString().padStart(2, '0');
            const dayStr = day.toString().padStart(2, '0');
            const dateStr = `${year}-${monthStr}-${dayStr}`;
            
            const seat = document.createElement('div');
            seat.innerHTML = `<span>${day}</span>`;
            seat.dataset.date = dateStr;
            
            // Check if date is in the past
            const isDatePast = new Date(year, month, day) < new Date(currentYear, currentMonth, currentDay);
            
            // Check if date has bookings
            const hasBookings = bookingsData[dateStr] && bookingsData[dateStr].length > 0;
            
            // Set appropriate class based on availability
            if (isDatePast) {
                seat.className = 'seat seat-past';
            } else if (hasBookings) {
                seat.className = 'seat seat-booked';
                if (bookingsData[dateStr].length > 1) {
                    seat.classList.add('seat-with-event');
                }
            } else {
                seat.className = 'seat seat-available';
            }
            
            // Add click event to show details
            seat.addEventListener('click', function() {
                // Remove selected class from all seats
                document.querySelectorAll('.seat.selected').forEach(s => {
                    s.classList.remove('selected');
                });
                
                // Add selected class to clicked seat
                this.classList.add('selected');
                
                // Show date info panel
                const dateInfoPanel = document.getElementById('dateInfoPanel');
                dateInfoPanel.classList.remove('d-none');
                
                // Format the selected date
                const selectedDate = new Date(year, month, day);
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('selectedDate').textContent = selectedDate.toLocaleDateString('en-US', options);
                
                // Update status
                const dateStatus = document.getElementById('dateStatus');
                const bookingDetails = document.getElementById('bookingDetails');
                const bookButton = document.getElementById('bookSelectedDate');
                
                if (isDatePast) {
                    dateStatus.className = 'date-status past';
                    dateStatus.textContent = 'Past Date';
                    bookingDetails.classList.add('d-none');
                    bookButton.style.display = 'none';
                } else if (hasBookings) {
                    dateStatus.className = 'date-status booked';
                    dateStatus.textContent = 'Booked';
                    
                    // Populate booking details
                    bookingDetails.innerHTML = '';
                    bookingsData[dateStr].forEach(booking => {
                        const bookingItem = document.createElement('div');
                        bookingItem.className = 'booking-item';
                        bookingItem.innerHTML = `
                            <div class="booking-time">${booking.start_time} - ${booking.end_time}</div>
                            <div class="booking-name">${booking.program_name}</div>
                        `;
                        bookingDetails.appendChild(bookingItem);
                    });
                    
                    bookingDetails.classList.remove('d-none');
                    bookButton.style.display = 'none';
                } else {
                    dateStatus.className = 'date-status available';
                    dateStatus.textContent = 'Available for Booking';
                    bookingDetails.classList.add('d-none');
                    bookButton.style.display = 'block';
                    
                    // Update book button href with the date
                    bookButton.addEventListener('click', function() {
                        window.location.href = `book_hall.php?hall_id=<?php echo urlencode($hall['hall_name']); ?>&date=${dateStr}`;
                    });
                }
            });
            
            theaterCalendar.appendChild(seat);
        }
        
        // Add empty cells to complete the grid to a multiple of 7
        const totalCells = firstDay + daysInMonth;
        const remainingCells = 7 - (totalCells % 7);
        if (remainingCells < 7) {
            for (let i = 0; i < remainingCells; i++) {
                const emptySeat = document.createElement('div');
                emptySeat.className = 'seat seat-inactive';
                theaterCalendar.appendChild(emptySeat);
            }
        }
    }
    
    // Initialize calendar
    updateMonthYearDisplay();
    generateCalendar(displayMonth, displayYear);
    
    // Add event listeners for previous and next month buttons
    document.getElementById('prevMonth').addEventListener('click', function() {
        displayMonth--;
        if (displayMonth < 0) {
            displayMonth = 11;
            displayYear--;
        }
        updateMonthYearDisplay();
        generateCalendar(displayMonth, displayYear);
    });
    
    document.getElementById('nextMonth').addEventListener('click', function() {
        displayMonth++;
        if (displayMonth > 11) {
            displayMonth = 0;
            displayYear++;
        }
        updateMonthYearDisplay();
        generateCalendar(displayMonth, displayYear);
    });
    
    // Add animation to seats
    setTimeout(() => {
        const seats = document.querySelectorAll('.seat:not(.seat-inactive)');
        seats.forEach((seat, index) => {
            seat.style.opacity = '0';
            seat.style.transform = 'translateY(20px)';
            seat.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                seat.style.opacity = '1';
                seat.style.transform = '';
            }, index * 20);
        });
    }, 500);
});
</script>
                <!-- Hall Statistics Card -->
                <div class="calendar-container mt-4" data-aos="fade-left" data-aos-delay="300">
                    <h2 class="calendar-title">Hall Statistics</h2>
                    <div class="stats-container">
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Booking Rate</h6>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 65%;" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">65% booked this month</small>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Popular Booking Times</h6>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 80%;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">10:00 AM - 2:00 PM (80%)</small>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Average Event Duration</h6>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 70%;" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">2.5 hours average</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Similar Halls Section -->
        <div class="row mt-5" data-aos="fade-up" data-aos-delay="300">
            <div class="col-12">
                <h2 class="info-title text-center">Similar Halls You May Like</h2>
                <div class="row mt-4">
                    <!-- These would ideally be populated from the database -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-img-top" style="height: 180px; background: url('images/adu.jpg') center/cover; border-radius: 8px 8px 0 0;"></div>
                            <div class="card-body">
                                <h5 class="card-title">Main Auditorium</h5>
                                <p class="card-text"><i class="fas fa-users text-primary me-2"></i>Capacity: 500</p>
                                <a href="#" class="btn btn-sm btn-outline-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-img-top" style="height: 180px; background: url('images/adu.jpg') center/cover; border-radius: 8px 8px 0 0;"></div>
                            <div class="card-body">
                                <h5 class="card-title">Conference Hall B</h5>
                                <p class="card-text"><i class="fas fa-users text-primary me-2"></i>Capacity: 120</p>
                                <a href="#" class="btn btn-sm btn-outline-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-img-top" style="height: 180px; background: url('images/adu.jpg') center/cover; border-radius: 8px 8px 0 0;"></div>
                            <div class="card-body">
                                <h5 class="card-title">Lecture Hall 5</h5>
                                <p class="card-text"><i class="fas fa-users text-primary me-2"></i>Capacity: 80</p>
                                <a href="#" class="btn btn-sm btn-outline-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll to top button -->
    <div class="scroll-to-top">
        <i class="fas fa-arrow-up"></i>
    </div>

    <?php include('footer user.php'); ?>
    
    <!-- 360 Degree View Modal -->
    <div class="modal fade" id="tour360Modal" tabindex="-1" aria-labelledby="tour360ModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tour360ModalLabel">
                        <i class="fas fa-vr-cardboard me-2"></i>
                        360° Virtual Tour - <?php echo htmlspecialchars($hall['hall_name']); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="panorama" style="width: 100%; height: 600px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn tour-btn">
                        <i class="fas fa-download me-2"></i>Download Tour
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailsModalLabel">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="event-details-content">
                        <h4 id="event-title"></h4>
                        <p><strong>Date:</strong> <span id="event-date"></span></p>
                        <p><strong>Time:</strong> <span id="event-time"></span></p>
                        <p><strong>Organizer:</strong> <span id="event-organizer">-</span></p>
                        <p><strong>Description:</strong> <span id="event-description">-</span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Pannellum for 360 view -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    
    <script>
        // Initialize AOS animation library
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Page loader
        window.addEventListener('load', function() {
            const loader = document.querySelector('.loader');
            setTimeout(function() {
                loader.classList.add('fade-out');
            }, 500);
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            // Parse bookings data from PHP
            const bookingsData = <?php echo $bookings_json; ?>;
            
            // Get current date
            const currentDate = new Date();
            const currentDateStr = currentDate.toISOString().split('T')[0];
            
            // Process booking data into events array
            const events = [];
            for (const dateStr in bookingsData) {
                bookingsData[dateStr].forEach(booking => {
                    events.push({
                        title: booking.program_name,
                        start: `${dateStr}T${booking.start_time}`,
                        end: `${dateStr}T${booking.end_time}`,
                        backgroundColor: 'rgba(220, 53, 69, 0.8)',
                        borderColor: '#dc3545',
                        classNames: ['booked-event'],
                        extendedProps: {
                            description: 'Event details for ' + booking.program_name,
                            organizer: 'Event Organizer'
                        }
                    });
                });
            }
            
            // Initialize calendar
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listMonth'
                },
                height: 'auto',
                dayMaxEvents: true,
                events: events,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                viewDidMount: function(info) {
                    applyColorCoding();
                },
                datesSet: function(info) {
                    applyColorCoding();
                },
                eventClick: function(info) {
                    // Show event details in modal
                    document.getElementById('event-title').textContent = info.event.title;
                    document.getElementById('event-date').textContent = info.event.start.toLocaleDateString();
                    document.getElementById('event-time').textContent = `${info.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${info.event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
                    document.getElementById('event-organizer').textContent = info.event.extendedProps.organizer || '-';
                    document.getElementById('event-description').textContent = info.event.extendedProps.description || '-';
                    
                    // Show the modal
                    const eventModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
                    eventModal.show();
                },
                eventMouseEnter: function(info) {
                    // Add hover effect
                    info.el.style.transform = 'scale(1.05)';
                    info.el.style.transition = 'all 0.3s ease';
                    info.el.style.zIndex = '5';
                    info.el.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
                },
                eventMouseLeave: function(info) {
                    // Remove hover effect
                    info.el.style.transform = '';
                    info.el.style.zIndex = '';
                    info.el.style.boxShadow = '';
                }
            });
            
            calendar.render();
            
            // Function to apply color coding to days
            function applyColorCoding() {
                // Only apply to month view
                if (calendar.view.type !== 'dayGridMonth') return;
                
                // Clear any existing classes
                document.querySelectorAll('.fc-day').forEach(day => {
                    day.classList.remove('fc-day-available', 'fc-day-booked', 'fc-day-past');
                });
                
                // Add classes based on bookings
                document.querySelectorAll('.fc-day').forEach(day => {
                    const dateAttr = day.getAttribute('data-date');
                    
                    // Skip if no date attribute
                    if (!dateAttr) return;
                    
                    // Check if date is in the past
                    if (dateAttr < currentDateStr) {
                        day.classList.add('fc-day-past');
                    } else if (bookingsData[dateAttr]) {
                        // Date is booked
                        day.classList.add('fc-day-booked');
                    } else {
                        // Date is available
                        day.classList.add('fc-day-available');
                    }
                });
            }
            
            // Handle view changes
            document.querySelectorAll('.fc-button').forEach(button => {
                button.addEventListener('click', function() {
                    // Allow time for view to change
                    setTimeout(applyColorCoding, 100);
                });
            });
            
            // Initialize 360 viewer when modal is shown
            document.getElementById('tour360Modal').addEventListener('shown.bs.modal', function () {
                // You would typically get this URL from your database
                const panoramaUrl = "<?php echo !empty($hall['panorama_url']) ? 
                    htmlspecialchars($hall['panorama_url']) : 
                    'uploads/hall_images/hall_360.jpg'; // Default hall panorama image
                ?>";
                
                pannellum.viewer('panorama', {
                    type: "equirectangular",
                    panorama: panoramaUrl,
                    autoLoad: true,
                    compass: true,
                    hotSpotDebug: false,
                    showControls: false,
                    sceneFadeDuration: 1000,
                    hotSpots: [
                        // Add hotspot for the projector screen
                        {
                            pitch: -5,
                            yaw: 0,
                            type: "info",
                            text: "Projector Screen",
                            cssClass: "custom-hotspot"
                        },
                        // Add hotspot for the seating area
                        {
                            pitch: -10,
                            yaw: 180,
                            type: "info",
                            text: "Seating Area",
                            cssClass: "custom-hotspot"
                        },
                        // Add hotspot for the entrance
                        {
                            pitch: -5,
                            yaw: 90,
                            type: "info",
                            text: "Entrance",
                            cssClass: "custom-hotspot"
                        },
                        // Add hotspot for the speaker podium
                        {
                            pitch: -15,
                            yaw: -90,
                            type: "info",
                            text: "Speaker Podium",
                            cssClass: "custom-hotspot"
                        }
                    ]
                });
            });
            
            // Scroll to top button functionality
            const scrollBtn = document.querySelector('.scroll-to-top');
            
            // Show/hide the button based on scroll position
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollBtn.classList.add('active');
                } else {
                    scrollBtn.classList.remove('active');
                }
            });
            
            // Scroll to top when button is clicked
            scrollBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Add animation to amenity badges
            const animateAmenities = () => {
                const badges = document.querySelectorAll('.amenity-badge');
                badges.forEach((badge, index) => {
                    setTimeout(() => {
                        badge.style.opacity = '0';
                        badge.style.transform = 'translateY(20px)';
                        badge.style.transition = 'all 0.5s ease';
                        
                        setTimeout(() => {
                            badge.style.opacity = '1';
                            badge.style.transform = 'translateY(0)';
                        }, 100);
                    }, index * 150);
                });
            };
            
            // Animate amenities on page load
            setTimeout(animateAmenities, 1000);
            
            // Re-animate amenities when section is clicked
            document.querySelector('.amenities-container').addEventListener('click', animateAmenities);
            
            // Add animations to info sections on hover
            document.querySelectorAll('.info-section').forEach(section => {
                section.addEventListener('mouseenter', function() {
                    const title = this.querySelector('.info-title');
                    if (title) {
                        title.style.transform = 'translateY(-5px)';
                        title.style.transition = 'all 0.3s ease';
                    }
                });
    
                section.addEventListener('mouseleave', function() {
                    const title = this.querySelector('.info-title');
                    if (title) {
                        title.style.transform = '';
                    }
                });
            });
        });
    </script>
</body>
</html>