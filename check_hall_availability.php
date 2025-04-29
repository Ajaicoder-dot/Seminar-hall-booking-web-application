<?php
session_start();
include('config.php');

// Ensure the user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$halls = [];
$available_halls = [];
$search_performed = false;
$error_message = '';
$from_date = '';
$end_date = '';
$start_time = '';
$end_time = '';

// Fetch all halls for the dropdown
$halls_query = "SELECT h.hall_id, h.hall_name, h.capacity, h.features, h.floor_name, h.zone, 
                h.belong_to, h.incharge_name, h.room_availability,
                d.department_name, s.school_name
                FROM halls h
                LEFT JOIN departments d ON h.department_id = d.department_id
                LEFT JOIN schools s ON h.school_id = s.school_id
                WHERE h.room_availability = 'Yes'
                ORDER BY h.hall_name";
$halls_result = $conn->query($halls_query);

if ($halls_result) {
    while ($hall = $halls_result->fetch_assoc()) {
        $halls[] = $hall;
    }
}

// Process search form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_performed = true;
    $from_date = $_POST['from_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    
    // Validate inputs
    if (empty($from_date) || empty($end_date) || empty($start_time) || empty($end_time)) {
        $error_message = "All fields are required.";
    } elseif ($from_date > $end_date) {
        $error_message = "End date cannot be before start date.";
    } elseif ($from_date == $end_date && $start_time >= $end_time) {
        $error_message = "End time must be after start time.";
    } else {
        // Query to find booked halls during the specified time period
        $booked_halls_query = "SELECT DISTINCT hall_id FROM hall_bookings 
                              WHERE (
                                  (from_date <= ? AND end_date >= ?) OR
                                  (from_date <= ? AND end_date >= ?) OR
                                  (from_date >= ? AND end_date <= ?)
                              ) AND (
                                  (start_time <= ? AND end_time > ?) OR
                                  (start_time < ? AND end_time >= ?) OR
                                  (? <= start_time AND ? >= end_time)
                              ) AND status = 'Approved'";
        
        $stmt = $conn->prepare($booked_halls_query);
        $stmt->bind_param("ssssssssssss", 
            $from_date, $from_date,  // First condition: booking starts before and ends after/on from_date
            $end_date, $end_date,    // Second condition: booking starts before and ends after/on end_date
            $from_date, $end_date,   // Third condition: booking is completely within the search period
            $end_time, $start_time,  // Time condition 1: booking ends after search starts
            $end_time, $start_time,  // Time condition 2: booking starts before search ends
            $start_time, $end_time   // Time condition 3: search period completely covers booking
        );
        
        $stmt->execute();
        $booked_result = $stmt->get_result();
        
        $booked_hall_ids = [];
        while ($row = $booked_result->fetch_assoc()) {
            $booked_hall_ids[] = $row['hall_id'];
        }
        
        // Filter available halls
        foreach ($halls as $hall) {
            if (!in_array($hall['hall_id'], $booked_hall_ids)) {
                $available_halls[] = $hall;
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Hall Availability - Pondicherry University</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- AOS - Animate On Scroll Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1a5f7a;
            --secondary-color: #159895;
            --accent-color: #57c5b6;
            --light-accent: #bff4ef;
            --dark-bg: #0f3c4c;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f4f7f6 0%, #e3f2fd 100%);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }
        
        main {
            flex: 1;
            padding: 30px 0;
        }
        
        .container {
            max-width: 1200px;
        }
        
        /* Page title with animation */
        .page-title {
            position: relative;
            text-align: center;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 40px;
            padding-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            width: 80px;
            height: 4px;
            background: var(--accent-color);
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        /* Search form styling */
        .search-form {
            padding: 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            margin-bottom: 40px;
            border-top: 5px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .search-form:hover {
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e1e5ea;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(26, 95, 122, 0.1);
            border-color: var(--accent-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(26, 95, 122, 0.2);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 95, 122, 0.3);
        }
        
        /* Hall cards styling */
        .hall-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            transition: all 0.5s cubic-bezier(0.25, 1, 0.5, 1);
            height: 100%;
            position: relative;
            z-index: 1;
            background: white;
        }
        
        .hall-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .hall-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 0;
            background: linear-gradient(135deg, rgba(26, 95, 122, 0.05) 0%, rgba(87, 197, 182, 0.05) 100%);
            z-index: -1;
            transition: height 0.6s ease;
        }
        
        .hall-card:hover:before {
            height: 100%;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            font-weight: bold;
            padding: 18px 20px;
            font-size: 1.2rem;
            text-shadow: 0 1px 3px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .card-header:after {
            content: '';
            position: absolute;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            right: -50px;
            top: -50px;
            border-radius: 50%;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .hall-capacity {
            font-size: 1.1rem;
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .hall-icon {
            background: var(--light-accent);
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: var(--primary-color);
            margin-right: 5px;
            font-size: 0.9rem;
        }
        
        .hall-info {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        
        .hall-features {
            color: #555;
            border-top: 1px dashed #e1e5ea;
            border-bottom: 1px dashed #e1e5ea;
            padding: 12px 0;
            margin: 15px 0;
        }
        
        .feature-tag {
            display: inline-block;
            padding: 4px 10px;
            background-color: var(--light-accent);
            color: var(--primary-color);
            border-radius: 20px;
            font-size: 0.8rem;
            margin: 2px;
        }
        
        .btn-book {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            padding: 12px 25px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-book:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 95, 122, 0.2);
            color: white;
        }
        
        /* No halls message */
        .no-halls-message {
            text-align: center;
            padding: 60px 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        
        .search-title {
            background: var(--light-accent);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            color: var(--primary-color);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .search-title:before {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0) 100%);
            border-radius: 50%;
            top: -60px;
            left: -60px;
        }
        
        /* Loader animation */
        .loader-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .loader {
            width: 60px;
            height: 60px;
            border: 5px solid var(--light-accent);
            border-radius: 50%;
            border-top: 5px solid var(--primary-color);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Pulse animation for initial page message */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        /* Feature badges animation */
        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Empty state illustration */
        .empty-state {
            max-width: 250px;
            margin: 0 auto 20px;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <?php include('navbar1.php'); ?>
    
    <!-- Loader animation -->
    <div class="loader-container">
        <div class="loader"></div>
    </div>
    
    <main>
        <div class="container">
            <h1 class="page-title animate__animated animate__fadeInDown">Check Hall Availability</h1>
            
            <div class="search-form animate__animated animate__fadeIn" data-aos="fade-up">
                <form method="POST" action="" id="availability-form">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <label for="from_date" class="form-label">
                                <i class="fas fa-calendar-alt me-2"></i>From Date
                            </label>
                            <input type="date" class="form-control" id="from_date" name="from_date" 
                                   value="<?php echo $from_date; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">
                                <i class="fas fa-calendar-check me-2"></i>To Date
                            </label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo $end_date; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="start_time" class="form-label">
                                <i class="fas fa-clock me-2"></i>Start Time
                            </label>
                            <input type="time" class="form-control" id="start_time" name="start_time" 
                                   value="<?php echo $start_time; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_time" class="form-label">
                                <i class="fas fa-hourglass-end me-2"></i>End Time
                            </label>
                            <input type="time" class="form-control" id="end_time" name="end_time" 
                                   value="<?php echo $end_time; ?>" required>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-primary px-5 py-2" id="check-button">
                                <i class="fas fa-search me-2"></i>Check Availability
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger animate__animated animate__shakeX" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($search_performed && empty($error_message)): ?>
                <div class="search-title animate__animated animate__fadeIn">
                    <h2 class="mb-0 fs-4">
                        <i class="fas fa-calendar-day me-2"></i>Available Halls for 
                        <?php echo date('d M Y', strtotime($from_date)); ?> 
                        <?php if ($from_date != $end_date): ?> to <?php echo date('d M Y', strtotime($end_date)); ?><?php endif; ?>, 
                        <?php echo date('h:i A', strtotime($start_time)); ?> - <?php echo date('h:i A', strtotime($end_time)); ?>
                    </h2>
                </div>
                
                <?php if (empty($available_halls)): ?>
                    <div class="no-halls-message animate__animated animate__fadeIn" data-aos="zoom-in">
                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486754.png" alt="No halls available" class="empty-state">
                        <h3 class="text-muted mb-3">No halls available for the selected time period</h3>
                        <p class="text-muted mb-4">Please try different dates or times.</p>
                        <button onclick="resetForm()" class="btn btn-outline-primary">
                            <i class="fas fa-redo me-2"></i>Try Different Dates
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php 
                        $delay = 0;
                        foreach ($available_halls as $index => $hall): 
                            $delay = $index * 100;
                            
                            // Generate feature tags if features exist
                            $featureTags = '';
                            if (!empty($hall['features'])) {
                                $featuresArray = explode(',', $hall['features']);
                                foreach ($featuresArray as $feature) {
                                    $feature = trim($feature);
                                    if (!empty($feature)) {
                                        $featureTags .= '<span class="feature-tag"><i class="fas fa-check-circle me-1"></i>' . htmlspecialchars($feature) . '</span> ';
                                    }
                                }
                            }
                        ?>
                            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                                <div class="card hall-card animate__animated animate__fadeIn">
                                    <div class="card-header">
                                        <i class="fas fa-building me-2"></i> <?php echo htmlspecialchars($hall['hall_name']); ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="hall-capacity">
                                            <div class="hall-icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <span>Capacity: <?php echo $hall['capacity']; ?> people</span>
                                        </div>
                                        
                                        <div class="hall-info">
                                            <div class="hall-icon">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </div>
                                            <div>
                                                <?php echo $hall['floor_name'] ? htmlspecialchars($hall['floor_name']) . ', ' : ''; ?>
                                                <?php echo $hall['zone'] ? htmlspecialchars($hall['zone']) : 'Location not specified'; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="hall-features">
                                            <strong><i class="fas fa-clipboard-list me-2"></i>Features:</strong>
                                            <div class="mt-2">
                                                <?php echo !empty($featureTags) ? $featureTags : '<span class="text-muted">None specified</span>'; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="hall-info">
                                            <div class="hall-icon">
                                                <i class="fas fa-university"></i>
                                            </div>
                                            <div>
                                                <strong>Belongs to:</strong> 
                                                <?php 
                                                if ($hall['belong_to'] == 'Department' && !empty($hall['department_name'])) {
                                                    echo htmlspecialchars($hall['department_name']);
                                                } elseif ($hall['belong_to'] == 'School' && !empty($hall['school_name'])) {
                                                    echo htmlspecialchars($hall['school_name']);
                                                } else {
                                                    echo htmlspecialchars($hall['belong_to']);
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <div class="hall-info mb-3">
                                            <div class="hall-icon">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div>
                                                <strong>Incharge:</strong> <?php echo htmlspecialchars($hall['incharge_name']); ?>
                                            </div>
                                        </div>
                                        
                                        <a href="book_hall.php?hall_id=<?php echo urlencode($hall['hall_name']); ?>" class="btn btn-book">
                                            <i class="fas fa-calendar-check me-2"></i>Book This Hall
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php elseif (!$search_performed): ?>
                <div class="card animate__animated animate__fadeIn pulse-animation" data-aos="zoom-in">
                    <div class="card-body text-center p-5">
                        <img src="https://cdn-icons-png.flaticon.com/512/4221/4221834.png" alt="Select dates" class="mb-4" style="max-width: 150px; opacity: 0.7;">
                        <h3 class="text-muted mb-4">Select dates and times to check hall availability</h3>
                        <p class="text-muted">Enter your desired booking period above to see which halls are available.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include('footer user.php'); ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS - Animate On Scroll Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS animation library
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
            
            // Add animation to feature tags
            const featureTags = document.querySelectorAll('.feature-tag');
            featureTags.forEach((tag, index) => {
                tag.style.animation = `fadeInRight 0.3s ease forwards ${index * 0.1}s`;
                tag.style.opacity = '0';
            });
            
            // Form submission handling with loader
            const form = document.getElementById('availability-form');
            const loader = document.querySelector('.loader-container');
            
            if (form) {
                form.addEventListener('submit', function() {
                    loader.style.display = 'flex';
                });
            }
            
            // Hover effects for hall cards
            const hallCards = document.querySelectorAll('.hall-card');
            hallCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.classList.add('animate__pulse');
                });
                
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('animate__pulse');
                });
            });
        });
        
        // Reset form function
        function resetForm() {
            document.getElementById('from_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('start_time').value = '';
            document.getElementById('end_time').value = '';
            
            // Scroll to form
            document.querySelector('.search-form').scrollIntoView({
                behavior: 'smooth'
            });
        }
    </script>
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</body>
</html>