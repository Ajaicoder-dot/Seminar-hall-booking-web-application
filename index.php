<?php
session_start();
include('config.php');

// Ensure the user is logged in and is a Professor
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'Professor') {
    header("Location: login.php");
    exit();
}

// Get user email from the session
$user_email = $_SESSION['email'];

// Fetch the user's name from the database
$query = "SELECT name FROM users WHERE email = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $name = $user_data['name'] ?? 'Professor';
    $stmt->close();
} else {
    $name = 'Professor';
}

// Fetch recent events and circulars
$events_query = "SELECT title, description, DATE_FORMAT(date, '%d %M %Y') as formatted_date 
                 FROM university_events 
                 ORDER BY date DESC 
                 LIMIT 10";
$events_result = $conn->query($events_query);

$circulars_query = "SELECT title, description, DATE_FORMAT(issue_date, '%d %M %Y') as formatted_date 
                    FROM university_circulars 
                    ORDER BY issue_date DESC 
                    LIMIT 10";
$circulars_result = $conn->query($circulars_query);

// Pagination setup for PhD notifications
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Fetch total number of PhD notifications
$total_phd_query = "SELECT COUNT(*) as total FROM phd_notifications";
$total_result = $conn->query($total_phd_query);
$total_phd = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_phd / $results_per_page);

// Fetch PhD notifications with pagination
$phd_query = "SELECT id, title, description, 
              DATE_FORMAT(date, '%W, %d %M %Y at %h:%i %p') as formatted_date, 
              DATE_FORMAT(date, '%Y-%m-%d') as sort_date
              FROM phd_notifications 
              ORDER BY sort_date DESC 
              LIMIT $offset, $results_per_page";
$phd_result = $conn->query($phd_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Swiper Slider CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <title>Pondicherry University - Professor Dashboard</title>
    
    <style>
        :root {
            --primary-color: #1a5f7a;
            --secondary-color: #159895;
            --accent-color: #57c5b6;
            --light-color: #f9f9f9;
            --dark-color: #333;
            --success-color: #4caf50;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --info-color: #2196f3;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4eff9 100%);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('images/pattern.png');
            opacity: 0.05;
            z-index: -1;
            pointer-events: none;
        }

        /* Header Animation */
        .navbar-brand {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .navbar-brand::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--accent-color);
            transition: width 0.5s ease;
        }

        .navbar-brand:hover::after {
            width: 100%;
        }

        /* Enhanced Swiper Slider */
        .swiper-container {
            position: relative;
            margin: 20px 0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .swiper {
            width: 100%;
            height: 60vh;
            border-radius: 20px;
        }

        .swiper-slide {
            position: relative;
            overflow: hidden;
        }

        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .swiper-slide:hover img {
            transform: scale(1.05);
        }

        .swiper-slide::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40%;
            background: linear-gradient(to top, rgba(0,0,0,0.7), rgba(0,0,0,0));
        }

        .swiper-slide-caption {
            position: absolute;
            bottom: 30px;
            left: 30px;
            color: white;
            z-index: 2;
            max-width: 80%;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        .swiper-slide-active .swiper-slide-caption {
            opacity: 1;
            transform: translateY(0);
        }

        .swiper-pagination-bullet {
            width: 12px;
            height: 12px;
            background: rgba(255,255,255,0.7);
            opacity: 0.7;
        }

        .swiper-pagination-bullet-active {
            background: var(--accent-color);
            opacity: 1;
        }

        .swiper-button-next, .swiper-button-prev {
            color: white;
            background: rgba(0,0,0,0.3);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .swiper-button-next:hover, .swiper-button-prev:hover {
            background: var(--primary-color);
            transform: scale(1.1);
        }

        .swiper-button-next::after, .swiper-button-prev::after {
            font-size: 20px;
        }

        /* News Ticker Enhanced */
        .news-ticker {
    background-color: var(--primary-color);
    color: white;
    padding: 10px 0;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
    border-radius: 5px;
    margin-top: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.news-ticker::before {
    content: 'UPDATES';
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: var(--accent-color);
    padding: 3px 10px;
    border-radius: 3px;
    font-size: 0.8rem;
    font-weight: 500;
    z-index: 5;
}

.news-ticker-content {
    display: inline-block;
    padding-left: 100%;
    animation: news-scroll 30s linear infinite;
    margin-left: 80px;
}

.news-ticker-content:hover {
    animation-play-state: paused;
}

.news-item {
    display: inline-block;
    padding: 0 25px;
    font-size: 0.9rem;
    position: relative;
}

.news-item::after {
    content: '•';
    position: absolute;
    right: 10px;
    color: var(--accent-color);
}

.news-item:last-child::after {
    content: none;
}

.news-item-events, .news-item-circulars {
    color: white;
}

        /* Dashboard Section */
        .dashboard-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .dashboard-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color));
        }

        .dashboard-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }

        .welcome-text {
            color: var(--primary-color);
            position: relative;
            display: inline-block;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .welcome-text::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--accent-color);
            border-radius: 3px;
        }

        /* Quick Actions Cards */
        .quick-actions .card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .quick-actions .card:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .quick-actions .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 500;
            padding: 20px;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .quick-actions .card-header::before {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -75px;
            right: -75px;
        }

        .quick-actions .card-body {
            padding: 25px;
            position: relative;
            z-index: 1;
            background: white;
        }

        .quick-actions .card-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5rem;
            color: var(--accent-color);
            opacity: 0.7;
        }

        .quick-actions .btn {
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .quick-actions .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            z-index: -1;
        }

        .quick-actions .btn:hover::before {
            left: 0;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            box-shadow: 0 5px 15px rgba(26, 95, 122, 0.3);
        }

        .btn-secondary {
            background: var(--secondary-color);
            border: none;
            box-shadow: 0 5px 15px rgba(21, 152, 149, 0.3);
        }

        .btn-success {
            background: var(--success-color);
            border: none;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        /* PhD Notifications */
        .phd-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            position: relative;
        }

        .phd-container h2 {
            color: var(--primary-color);
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .phd-container h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--accent-color);
            border-radius: 3px;
        }

        .phd-item {
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid var(--accent-color);
            background: #f9f9f9;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .phd-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--accent-color);
            transition: width 0.3s ease;
        }

        .phd-item:hover {
            transform: translateX(10px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .phd-item:hover::before {
            width: 10px;
        }

        .phd-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
            position: relative;
            padding-left: 25px;
        }

        .phd-title::before {
            content: '\f15c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            left: 0;
            color: var(--accent-color);
        }

        .phd-date {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
            padding-left: 25px;
            position: relative;
        }

        .phd-date::before {
            content: '\f073';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            left: 0;
            color: var(--secondary-color);
        }

        .phd-description {
            color: #555;
            line-height: 1.7;
            margin-top: 15px;
        }

        /* Pagination */
        .pagination {
            margin-top: 30px;
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-link {
            color: var(--primary-color);
            border-radius: 5px;
            margin: 0 5px;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
            transform: scale(1.1);
        }

        /* Footer Enhancement */
        footer {
            margin-top: 50px;
            background: linear-gradient(135deg, #2c3e50, #1a5f7a);
            color: white;
            padding: 30px 0;
            position: relative;
            overflow: hidden;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-color), var(--secondary-color), var(--primary-color));
        }

        /* Animations */
        @keyframes news-scroll {
    0% {
        transform: translate(0, 0);
    }
    100% {
        transform: translate(-100%, 0);
    }
}

        @keyframes floating {
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

        .float-animation {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
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
            transition: opacity 0.5s, visibility 0.5s;
        }

        .loader-content {
            text-align: center;
        }

        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--accent-color);
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .loader.hidden {
            opacity: 0;
            visibility: hidden;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .swiper {
                height: 50vh;
            }
            
            .dashboard-section, .phd-container {
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            .swiper {
                height: 40vh;
            }
            
            .news-ticker::before {
                display: none;
            }
            
            .news-ticker-content {
                margin-left: 20px;
            }
            
            .quick-actions .card:hover {
                transform: translateY(-10px);
            }
        }

        @media (max-width: 576px) {
            .swiper {
                height: 30vh;
            }
            
            .welcome-text {
                font-size: 1.5rem;
            }
            
            .dashboard-section, .phd-container {
                padding: 20px;
            }
            
            .swiper-slide-caption {
                bottom: 10px;
                left: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Page Loader -->
    <div class="loader">
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <h3>Loading Pondicherry University Dashboard</h3>
        </div>
    </div>

    <?php include('navbar1.php'); ?>

    <!-- News Ticker -->
    <!-- News Ticker - Updated with normal size and no flash -->
<div class="container">
    <div class="news-ticker" data-aos="fade-up">
        <div class="news-ticker-content">
            <?php
            // Combine and shuffle events and circulars
            $all_news = [];
            
            if ($events_result && $events_result->num_rows > 0) {
                while ($event = $events_result->fetch_assoc()) {
                    $all_news[] = [
                        'type' => 'event',
                        'title' => $event['title'],
                        'date' => $event['formatted_date']
                    ];
                }
            }
            
            if ($circulars_result && $circulars_result->num_rows > 0) {
                while ($circular = $circulars_result->fetch_assoc()) {
                    $all_news[] = [
                        'type' => 'circular',
                        'title' => $circular['title'],
                        'date' => $circular['formatted_date']
                    ];
                }
            }
            
            // Shuffle the news items
            shuffle($all_news);
            
            // Display news items
            foreach ($all_news as $news) {
                $class = $news['type'] == 'event' ? 'news-item-events' : 'news-item-circulars';
                echo "<span class='news-item {$class}'>";
                echo "[" . ucfirst($news['type']) . "] " . htmlspecialchars($news['title']) . " - " . $news['date'];
                echo "</span>";
            }
            ?>
        </div>
    </div>
</div>

    <main>
        <div class="container">
            <!-- University Image Slider -->
            <div class="swiper-container" data-aos="zoom-in">
                <div class="swiper mySwiper">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <img src="images/1.jpeg" alt="Pondicherry University Campus">
                            <div class="swiper-slide-caption">
                                <h2>Welcome to Pondicherry University</h2>
                                <p>A premier educational institution fostering excellence in learning and research</p>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <img src="images/2.jpg" alt="University Building">
                            <div class="swiper-slide-caption">
                                <h2>State-of-the-art Infrastructure</h2>
                                <p>Modern facilities designed to enhance the learning experience</p>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <img src="images/3.jpg" alt="Campus Architecture">
                            <div class="swiper-slide-caption">
                                <h2>Scenic Campus Environment</h2>
                                <p>A perfect blend of nature and academia to inspire creativity</p>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="dashboard-section" data-aos="fade-up">
                <h2 class="welcome-text text-center">Welcome, <?php echo htmlspecialchars($name); ?>!</h2>
                <p class="lead mb-4 text-center">Manage your Seminar Hall Bookings with ease and efficiency</p>

                <!-- Quick Actions -->
                <div class="row quick-actions">
                    <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Book a Hall</h5>
                                <div class="card-icon"><i class="fas fa-calendar-plus"></i></div>
                            </div>
                            <div class="card-body">
                                <p>Reserve a seminar hall for your upcoming events, conferences, or meetings.</p>
                                <a href="view_hall.php" class="btn btn-primary">Book Now <i class="fas fa-arrow-right ms-2"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">My Bookings</h5>
                                <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                            </div>
                            <div class="card-body">
                                <p>View and manage your existing seminar hall bookings with real-time updates.</p>
                                <a href="view_my_booking.php" class="btn btn-secondary">View Bookings <i class="fas fa-list ms-2"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Hall Availability</h5>
                                <div class="card-icon"><i class="fas fa-search"></i></div>
                            </div>
                            <div class="card-body">
                                <p>Check seminar hall availability status for specific dates and times.</p>
                                <a href="check_hall_availability.php" class="btn btn-success">Check Halls <i class="fas fa-clock ms-2"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PhD Notifications Section -->
            <div class="phd-container" data-aos="fade-up">
                <h2 class="mb-4">PhD Notifications</h2>
                
                <?php if ($phd_result && $phd_result->num_rows > 0): ?>
                    <?php $delay = 100; while($phd = $phd_result->fetch_assoc()): ?>
                        <div class="phd-item" data-aos="fade-right" data-aos-delay="<?php echo $delay; ?>">
                            <h5 class="phd-title"><?php echo htmlspecialchars($phd['title']); ?></h5>
                            <div class="phd-date"><?php echo $phd['formatted_date']; ?></div>
                            <p class="phd-description"><?php echo htmlspecialchars($phd['description']); ?></p>
                        </div>
                    <?php $delay += 100; endwhile; ?>
                    
                    <!-- Pagination -->
                    <nav aria-label="PhD notification pages" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php else: ?>
                    <div class="alert alert-info text-center pulse-animation">
                        <i class="fas fa-info-circle me-2"></i> No PhD notifications found at the moment.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include('footer user.php'); ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Page Loader
        window.addEventListener('load', function() {
            const loader = document.querySelector('.loader');
            setTimeout(function() {
                loader.classList.add('hidden');
            }, 1000);
        });
        
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: false,
            mirror: true
        });
        
        // Initialize Swiper
        var swiper = new Swiper(".mySwiper", {
            spaceBetween: 30,
            centeredSlides: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            effect: "fade", // Add fade effect
            fadeEffect: {
                crossFade: true
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
                dynamicBullets: true
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            loop: true});
        
        // Add hover effects to cards
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                const icon = this.querySelector('.card-icon i');
                if (icon) {
                    icon.classList.add('fa-beat');
                    setTimeout(() => {
                        icon.classList.remove('fa-beat');
                    }, 1000);
                }
            });
        });
        
        // Enhance PhD notifications on click
        const phdItems = document.querySelectorAll('.phd-item');
        phdItems.forEach(item => {
            item.addEventListener('click', function() {
                // Toggle expanded class for more details
                this.classList.toggle('expanded');
                
                // If expanded, show full description
                if (this.classList.contains('expanded')) {
                    const description = this.querySelector('.phd-description');
                    const fullText = description.getAttribute('data-full-text') || description.textContent;
                    description.textContent = fullText;
                    this.style.maxHeight = 'none';
                } else {
                    // Reset to collapsed state
                    this.style.maxHeight = '';
                }
            });
        });
        
        // Interactive welcome message
        const welcomeText = document.querySelector('.welcome-text');
        if (welcomeText) {
            welcomeText.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.color = 'var(--secondary-color)';
            });
            
            welcomeText.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.color = 'var(--primary-color)';
            });
        }
        
        // Add notification counter animation
        function addNotificationCounter() {
            const notificationBadge = document.createElement('span');
            notificationBadge.className = 'position-absolute translate-middle badge rounded-pill bg-danger pulse-animation';
            notificationBadge.style.top = '0';
            notificationBadge.style.right = '0';
            notificationBadge.innerHTML = '3 <span class="visually-hidden">unread notifications</span>';
            
            const myBookingsCard = document.querySelector('.card:nth-child(2) .card-header');
            if (myBookingsCard) {
                myBookingsCard.style.position = 'relative';
                myBookingsCard.appendChild(notificationBadge);
            }
        }
        
        // Call the function after a delay to add notification badge
        setTimeout(addNotificationCounter, 2000);
        
        // Add dynamic current date in footer
        function updateFooterDate() {
            const footerDate = document.createElement('div');
            footerDate.className = 'footer-date text-center mt-3';
            
            const today = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            footerDate.innerHTML = '<small>Today is: ' + today.toLocaleDateString('en-US', options) + '</small>';
            
            const footer = document.querySelector('footer .container');
            if (footer) {
                footer.appendChild(footerDate);
            }
        }
        
        // Call function to add today's date to footer
        updateFooterDate();
        
        // Scroll to top button
        function createScrollToTopButton() {
            const scrollBtn = document.createElement('button');
            scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            scrollBtn.className = 'btn-scroll-top';
            scrollBtn.style.position = 'fixed';
            scrollBtn.style.bottom = '20px';
            scrollBtn.style.right = '20px';
            scrollBtn.style.width = '50px';
            scrollBtn.style.height = '50px';
            scrollBtn.style.borderRadius = '50%';
            scrollBtn.style.backgroundColor = 'var(--primary-color)';
            scrollBtn.style.color = 'white';
            scrollBtn.style.border = 'none';
            scrollBtn.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
            scrollBtn.style.cursor = 'pointer';
            scrollBtn.style.display = 'none';
            scrollBtn.style.zIndex = '99';
            scrollBtn.style.transition = 'all 0.3s ease';
            
            document.body.appendChild(scrollBtn);
            
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    scrollBtn.style.display = 'block';
                    scrollBtn.style.opacity = '1';
                } else {
                    scrollBtn.style.opacity = '0';
                    setTimeout(() => {
                        scrollBtn.style.display = 'none';
                    }, 300);
                }
            });
            
            scrollBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            scrollBtn.addEventListener('mouseenter', () => {
                scrollBtn.style.backgroundColor = 'var(--secondary-color)';
                scrollBtn.style.transform = 'translateY(-5px)';
            });
            
            scrollBtn.addEventListener('mouseleave', () => {
                scrollBtn.style.backgroundColor = 'var(--primary-color)';
                scrollBtn.style.transform = 'translateY(0)';
            });
        }
        
        // Create scroll to top button
        createScrollToTopButton();
        
        // Add weather widget to page
        function addWeatherWidget() {
            const weatherWidget = document.createElement('div');
            weatherWidget.className = 'weather-widget';
            weatherWidget.style.position = 'fixed';
            weatherWidget.style.top = '100px';
            weatherWidget.style.right = '20px';
            weatherWidget.style.backgroundColor = 'white';
            weatherWidget.style.borderRadius = '10px';
            weatherWidget.style.padding = '15px';
            weatherWidget.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
            weatherWidget.style.zIndex = '90';
            weatherWidget.style.maxWidth = '180px';
            weatherWidget.style.display = 'none';
            
            weatherWidget.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="m-0">Pondicherry</h6>
                    <span class="close-widget" style="cursor:pointer">&times;</span>
                </div>
                <div class="text-center">
                    <i class="fas fa-sun fa-2x text-warning mb-2"></i>
                    <h5>32°C</h5>
                    <p class="small mb-0">Sunny</p>
                </div>
            `;
            
            document.body.appendChild(weatherWidget);
            
            // Show weather widget after delay
            setTimeout(() => {
                weatherWidget.style.display = 'block';
                weatherWidget.style.animation = 'fadeInRight 0.5s forwards';
            }, 3000);
            
            // Add close functionality
            const closeBtn = weatherWidget.querySelector('.close-widget');
            closeBtn.addEventListener('click', () => {
                weatherWidget.style.animation = 'fadeOutRight 0.5s forwards';
                setTimeout(() => {
                    weatherWidget.style.display = 'none';
                }, 500);
            });
            
            // Add fadeIn animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeInRight {
                    from {
                        opacity: 0;
                        transform: translateX(50px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                
                @keyframes fadeOutRight {
                    from {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(50px);
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Add weather widget
        addWeatherWidget();
        
        // Add typing animation to welcome message
        function addTypingEffect() {
            const welcomeMsg = document.querySelector('.lead');
            if (welcomeMsg) {
                const text = welcomeMsg.textContent;
                welcomeMsg.textContent = '';
                welcomeMsg.style.minHeight = '24px';
                
                let i = 0;
                const typeWriter = () => {
                    if (i < text.length) {
                        welcomeMsg.textContent += text.charAt(i);
                        i++;
                        setTimeout(typeWriter, 50);
                    }
                };
                
                // Start typing effect
                setTimeout(typeWriter, 1500);
            }
        }
        
        // Add typing effect to welcome message
        addTypingEffect();
        
        // Enhance swiper with fancy transitions
        document.addEventListener('DOMContentLoaded', function() {
            // Add text reveal animation
            const slideCaptions = document.querySelectorAll('.swiper-slide-caption');
            slideCaptions.forEach(caption => {
                const h2 = caption.querySelector('h2');
                const p = caption.querySelector('p');
                
                if (h2) {
                    h2.style.opacity = '0';
                    h2.style.transform = 'translateY(20px)';
                    h2.style.transition = 'opacity 0.5s ease 0.5s, transform 0.5s ease 0.5s';
                }
                
                if (p) {
                    p.style.opacity = '0';
                    p.style.transform = 'translateY(20px)';
                    p.style.transition = 'opacity 0.5s ease 0.8s, transform 0.5s ease 0.8s';
                }
            });
            
            // Listen for slide change and animate captions
            swiper.on('slideChangeTransitionStart', function() {
                slideCaptions.forEach(caption => {
                    const h2 = caption.querySelector('h2');
                    const p = caption.querySelector('p');
                    
                    if (h2) {
                        h2.style.opacity = '0';
                        h2.style.transform = 'translateY(20px)';
                    }
                    
                    if (p) {
                        p.style.opacity = '0';
                        p.style.transform = 'translateY(20px)';
                    }
                });
            });
            
            swiper.on('slideChangeTransitionEnd', function() {
                const activeCaption = document.querySelector('.swiper-slide-active .swiper-slide-caption');
                if (activeCaption) {
                    const h2 = activeCaption.querySelector('h2');
                    const p = activeCaption.querySelector('p');
                    
                    if (h2) {
                        h2.style.opacity = '1';
                        h2.style.transform = 'translateY(0)';
                    }
                    
                    if (p) {
                        p.style.opacity = '1';
                        p.style.transform = 'translateY(0)';
                    }
                }
            });
            
            // Trigger for first slide
            setTimeout(() => {
                const activeCaption = document.querySelector('.swiper-slide-active .swiper-slide-caption');
                if (activeCaption) {
                    const h2 = activeCaption.querySelector('h2');
                    const p = activeCaption.querySelector('p');
                    
                    if (h2) {
                        h2.style.opacity = '1';
                        h2.style.transform = 'translateY(0)';
                    }
                    
                    if (p) {
                        p.style.opacity = '1';
                        p.style.transform = 'translateY(0)';
                    }
                }
            }, 500);
        });
        
        // Add interactive particles background to header
        function addParticlesBackground() {
            const particlesContainer = document.createElement('div');
            particlesContainer.id = 'particles-js';
            particlesContainer.style.position = 'absolute';
            particlesContainer.style.top = '0';
            particlesContainer.style.left = '0';
            particlesContainer.style.width = '100%';
            particlesContainer.style.height = '300px';
            particlesContainer.style.zIndex = '-1';
            
            // Add container after navbar
            const navbar = document.querySelector('nav');
            if (navbar) {
                navbar.parentNode.insertBefore(particlesContainer, navbar.nextSibling);
                
                // Load particles.js from CDN
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js';
                script.onload = function() {
                    // Initialize particles
                    particlesJS('particles-js', {
                        "particles": {
                            "number": {
                                "value": 30,
                                "density": {
                                    "enable": true,
                                    "value_area": 800
                                }
                            },
                            "color": {
                                "value": "#1a5f7a"
                            },
                            "shape": {
                                "type": "circle",
                                "stroke": {
                                    "width": 0,
                                    "color": "#000000"
                                }
                            },
                            "opacity": {
                                "value": 0.3,
                                "random": true
                            },
                            "size": {
                                "value": 5,
                                "random": true
                            },
                            "line_linked": {
                                "enable": true,
                                "distance": 150,
                                "color": "#57c5b6",
                                "opacity": 0.2,
                                "width": 1
                            },
                            "move": {
                                "enable": true,
                                "speed": 2,
                                "direction": "none",
                                "random": true,
                                "straight": false,
                                "out_mode": "out",
                                "bounce": false
                            }
                        },
                        "interactivity": {
                            "detect_on": "canvas",
                            "events": {
                                "onhover": {
                                    "enable": true,
                                    "mode": "grab"
                                },
                                "onclick": {
                                    "enable": true,
                                    "mode": "push"
                                },
                                "resize": true
                            }
                        },
                        "retina_detect": true
                    });
                };
                document.body.appendChild(script);
            }
        }
        
        // Add particles background if browser supports it
        if (window.innerWidth > 768) {
            addParticlesBackground();
        }
    </script>
</body>
</html>