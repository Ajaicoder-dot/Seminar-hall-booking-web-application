<?php
session_start();
include('config.php');
include('navbar1.php'); 

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'Professor') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Seminar Hall Booking System</title>
    <!-- Add Font Awesome for better icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add AOS library for scroll animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <!-- Add particles.js for background effects -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <style>
        :root {
            --primary-color: #3a7bd5;
            --secondary-color: #00d2ff;
            --text-color: #333;
            --bg-color: #f5f7fa;
            --card-bg: white;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            --border-color: rgba(0,0,0,0.03);
            --footer-bg: #2d3748;
            --footer-text: white;
        }
        
        .dark-mode {
            --primary-color: #4a8eff;
            --secondary-color: #00e1ff;
            --text-color: #e0e0e0;
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            --border-color: rgba(255,255,255,0.05);
            --footer-bg: #0a0a0a;
            --footer-text: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            color: white;
            padding: 100px 0 120px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') center/cover;
            opacity: 0.15;
            z-index: 0;
        }
        
        header::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background-color: #f5f7fa;
            border-radius: 50% 50% 0 0;
            z-index: 1;
        }
        
        header .container {
            position: relative;
            z-index: 2;
        }
        
        h1 {
            font-size: 48px;
            margin-bottom: 20px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            animation: fadeInDown 1s ease-out;
        }
        
        .subtitle {
            font-size: 22px;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto 30px;
            animation: fadeInUp 1s ease-out 0.3s;
            animation-fill-mode: both;
        }
        
        .mission-section {
            background-color: white;
            padding: 100px 0;
            text-align: center;
            position: relative;
        }
        
        .section-title {
            font-size: 36px;
            margin-bottom: 40px;
            position: relative;
            display: inline-block;
            color: #2d3748;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #3a7bd5, #00d2ff);
            border-radius: 2px;
        }
        
        .mission-text {
            max-width: 800px;
            margin: 0 auto;
            font-size: 18px;
            color: #555;
            line-height: 1.8;
        }
        
        .features-section {
            padding: 100px 0;
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .features-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(58, 123, 213, 0.1), rgba(0, 210, 255, 0.1));
            border-radius: 50%;
            transform: translate(100px, -150px);
        }
        
        .features-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(58, 123, 213, 0.1), rgba(0, 210, 255, 0.1));
            border-radius: 50%;
            transform: translate(-100px, 100px);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 60px;
            position: relative;
            z-index: 2;
        }
        
        .feature-card {
            background-color: white;
            border-radius: 15px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.03);
        }
        
        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #3a7bd5, #00d2ff);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(58, 123, 213, 0.3);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover .icon-wrapper {
            transform: rotateY(180deg);
        }
        
        .icon {
            width: 35px;
            height: 35px;
            color: white;
        }
        
        .feature-title {
            font-size: 24px;
            margin-bottom: 15px;
            color: #2d3748;
            position: relative;
            padding-bottom: 15px;
        }
        
        .feature-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: #e0e0e0;
        }
        
        .feature-description {
            color: #666;
            font-size: 16px;
            line-height: 1.7;
        }
        
        .how-it-works {
            padding: 100px 0;
            background-color: white;
            text-align: center;
            position: relative;
        }
        
        .steps-container {
            display: flex;
            justify-content: space-between;
            margin-top: 70px;
            position: relative;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .steps-container::before {
            content: '';
            position: absolute;
            top: 50px;
            left: 50px;
            right: 50px;
            height: 4px;
            background: #e0e0e0;
            z-index: 1;
            border-radius: 2px;
        }
        
        .step {
            width: 220px;
            z-index: 2;
            position: relative;
            transition: transform 0.3s ease;
        }
        
        .step:hover {
            transform: translateY(-10px);
        }
        
        .step-number {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px rgba(58, 123, 213, 0.3);
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .step:hover .step-number {
            transform: scale(1.1);
            box-shadow: 0 15px 25px rgba(58, 123, 213, 0.4);
        }
        
        .step-title {
            font-size: 22px;
            margin-bottom: 15px;
            color: #2d3748;
            font-weight: 600;
        }
        
        .step-description {
            color: #666;
            font-size: 16px;
            line-height: 1.7;
        }
        
        .testimonials {
            background-color: #f8fafc;
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .testimonials::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(58, 123, 213, 0.1), rgba(0, 210, 255, 0.1));
            border-radius: 50%;
        }
        
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            margin-top: 60px;
        }
        
        .testimonial-card {
            background-color: white;
            border-radius: 15px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            text-align: left;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
        }
        
        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .testimonial-card::after {
            content: '\201D';
            position: absolute;
            bottom: -20px;
            right: 20px;
            font-size: 150px;
            color: rgba(58, 123, 213, 0.1);
            font-family: Georgia, serif;
        }
        
        .quote {
            font-size: 18px;
            color: #555;
            font-style: italic;
            margin-bottom: 30px;
            position: relative;
            padding: 0 20px;
            line-height: 1.8;
        }
        
        .quote::before {
            content: '\201C';
            font-size: 60px;
            color: #3a7bd5;
            opacity: 0.3;
            position: absolute;
            top: -30px;
            left: -10px;
            font-family: Georgia, serif;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            border-top: 1px solid #f0f0f0;
            padding-top: 20px;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e0e0e0, #f5f5f5);
            margin-right: 15px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #3a7bd5;
        }
        
        .user-name {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
            color: #2d3748;
        }
        
        .user-role {
            color: #777;
            font-size: 14px;
        }
        
        .contact-section {
            padding: 100px 0;
            background-color: white;
            text-align: center;
            position: relative;
        }
        
        .contact-section::before {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(58, 123, 213, 0.1), rgba(0, 210, 255, 0.1));
            border-radius: 50%;
            transform: translate(50px, 50px);
        }
        
        .contact-methods {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin-top: 60px;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }
        
        .contact-method {
            width: 280px;
            background-color: white;
            border-radius: 15px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.03);
        }
        
        .contact-method:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .contact-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            border-radius: 50%;
            color: white;
            font-size: 28px;
            box-shadow: 0 10px 20px rgba(58, 123, 213, 0.3);
            transition: all 0.3s ease;
        }
        
        .contact-method:hover .contact-icon {
            transform: rotateY(180deg);
        }
        
        .contact-title {
            font-size: 22px;
            margin-bottom: 15px;
            color: #2d3748;
        }
        
        .contact-detail {
            color: #666;
            font-size: 16px;
        }
        
        footer {
            background-color: #2d3748;
            color: white;
            padding: 40px 0;
            text-align: center;
            position: relative;
        }
        
        .footer-text {
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.8;
            font-size: 14px;
        }
        
        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .btn-explore {
            display: inline-block;
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            box-shadow: 0 10px 20px rgba(58, 123, 213, 0.3);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            animation: fadeInUp 1s ease-out 0.6s;
            animation-fill-mode: both;
        }
        
        .btn-explore:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px rgba(58, 123, 213, 0.4);
        }
        
        .btn-explore i {
            margin-left: 8px;
        }
        
        /* Responsive styles */
        @media (max-width: 900px) {
            .steps-container {
                flex-direction: column;
                align-items: center;
                gap: 60px;
            }
            
            .steps-container::before {
                display: none;
            }
            
            .testimonial-grid {
                grid-template-columns: 1fr;
                max-width: 500px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .contact-methods {
                flex-direction: column;
                align-items: center;
            }
            
            h1 {
                font-size: 36px;
            }
            
            .subtitle {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1 data-aos="fade-down">About Our Seminar Hall Booking System</h1>
            <p class="subtitle" data-aos="fade-up" data-aos-delay="300">Simplifying seminar hall reservations with technology to save time and streamline the booking process for everyone.</p>
            <a href="#features" class="btn-explore" data-aos="fade-up" data-aos-delay="600">Explore Features <i class="fas fa-arrow-right"></i></a>
        </div>
    </header>

    <section class="mission-section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Our Mission</h2>
            <p class="mission-text" data-aos="fade-up" data-aos-delay="200">Our seminar hall booking system was developed to eliminate the challenges of manual booking processes. We aim to provide a streamlined, user-friendly platform that saves time, reduces administrative burden, and helps institutions manage their resources more effectively. With real-time availability updates and automated confirmations, we're committed to making venue management simple and efficient.</p>
        </div>
    </section>

    <section id="features" class="features-section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Key Features</h2>
            <div class="features-grid">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="icon-wrapper">
                        <i class="fas fa-calendar-alt icon"></i>
                    </div>
                    <h3 class="feature-title">Real-time Availability</h3>
                    <p class="feature-description">View up-to-date hall availability with our interactive calendar. Check vacant time slots instantly and make informed booking decisions.</p>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="icon-wrapper">
                        <i class="fas fa-bolt icon"></i>
                    </div>
                    <h3 class="feature-title">Quick Booking Process</h3>
                    <p class="feature-description">Complete your booking in minutes with our streamlined process. No more lengthy paperwork or manual approvals required.</p>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="icon-wrapper">
                        <i class="fas fa-bell icon"></i>
                    </div>
                    <h3 class="feature-title">Automatic Notifications</h3>
                    <p class="feature-description">Receive instant booking confirmations and timely reminders via email to ensure you never miss your scheduled event.</p>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="icon-wrapper">
                        <i class="fas fa-sliders-h icon"></i>
                    </div>
                    <h3 class="feature-title">Customizable Options</h3>
                    <p class="feature-description">Specify additional requirements such as seating arrangements, equipment needs, and catering services during the booking process.</p>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="500">
                    <div class="icon-wrapper">
                        <i class="fas fa-chart-line icon"></i>
                    </div>
                    <h3 class="feature-title">Detailed Analytics</h3>
                    <p class="feature-description">Administrators can access comprehensive usage reports to optimize resource allocation and make data-driven decisions.</p>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="600">
                    <div class="icon-wrapper">
                        <i class="fas fa-mobile-alt icon"></i>
                    </div>
                    <h3 class="feature-title">Mobile Responsive</h3>
                    <p class="feature-description">Book seminar halls anytime, anywhere using our mobile-friendly interface that works seamlessly across all devices.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">How It Works</h2>
            <div class="steps-container">
                <div class="step" data-aos="fade-right" data-aos-delay="100">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Register</h3>
                    <p class="step-description">Create an account with your institutional email address to access the booking platform.</p>
                </div>
                
                <div class="step" data-aos="fade-up" data-aos-delay="300">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Select Hall</h3>
                    <p class="step-description">Browse available halls and view their capacities, facilities, and availability.</p>
                </div>
                
                <div class="step" data-aos="fade-left" data-aos-delay="500">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Book & Confirm</h3>
                    <p class="step-description">Choose your date, time slot, and receive instant confirmation for your booking.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">What Users Say</h2>
            <div class="testimonial-grid">
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
                    <p class="quote">This system has transformed how we manage our department seminars. The hours we used to spend coordinating room bookings are now reduced to minutes.</p>
                    <div class="user-info">
                        <div class="user-avatar"><i class="fas fa-user"></i></div>
                        <div>
                            <p class="user-name">Dr. Jayakumar</p>
                            <p class="user-role">Department Head, Computer Science</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="300">
                    <p class="quote">As a student organizer, I appreciate how easy it is to check availability and book halls for our club meetings. The email notifications are especially helpful!</p>
                    <div class="user-info">
                        <div class="user-avatar"><i class="fas fa-user"></i></div>
                        <div>
                            <p class="user-name">UDHYA</p>
                            <p class="user-role">Student Council President</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="500">
                    <p class="quote">The analytics feature has helped us identify usage patterns and make better decisions about our facility management and expansion needs.</p>
                    <div class="user-info">
                        <div class="user-avatar"><i class="fas fa-user"></i></div>
                        <div>
                            <p class="user-name">ARUN</p>
                            <p class="user-role">Facilities Manager</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Get In Touch</h2>
            <div class="contact-methods">
                <div class="contact-method" data-aos="fade-up" data-aos-delay="100">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="contact-title">Email</h3>
                    <p class="contact-detail">ajaiofficial@gmail.com</p>
                </div>
                
                <div class="contact-method" data-aos="fade-up" data-aos-delay="300">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3 class="contact-title">Phone</h3>
                    <p class="contact-detail">0413 123-4567</p>
                </div>
                
                <div class="contact-method" data-aos="fade-up" data-aos-delay="500">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="contact-title">Location</h3>
                    <p class="contact-detail">123 University Avenue, Building 4, Room 201</p>
                </div>
            </div>
        </div>
    </section>

    <?php include('footer user.php'); ?>
    
    <!-- AOS Animation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialize AOS animations
        AOS.init({
            duration: 800,
            easing: 'ease-out',
            once: true
        });
        
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Initialize particles.js
        particlesJS('particles-js', {
            "particles": {
                "number": {
                    "value": 80,
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": "#3a7bd5"
                },
                "shape": {
                    "type": "circle",
                    "stroke": {
                        "width": 0,
                        "color": "#000000"
                    },
                    "polygon": {
                        "nb_sides": 5
                    }
                },
                "opacity": {
                    "value": 0.5,
                    "random": false,
                    "anim": {
                        "enable": false,
                        "speed": 1,
                        "opacity_min": 0.1,
                        "sync": false
                    }
                },
                "size": {
                    "value": 3,
                    "random": true,
                    "anim": {
                        "enable": false,
                        "speed": 40,
                        "size_min": 0.1,
                        "sync": false
                    }
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#3a7bd5",
                    "opacity": 0.4,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 2,
                    "direction": "none",
                    "random": false,
                    "straight": false,
                    "out_mode": "out",
                    "bounce": false,
                    "attract": {
                        "enable": false,
                        "rotateX": 600,
                        "rotateY": 1200
                    }
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
                },
                "modes": {
                    "grab": {
                        "distance": 140,
                        "line_linked": {
                            "opacity": 1
                        }
                    },
                    "bubble": {
                        "distance": 400,
                        "size": 40,
                        "duration": 2,
                        "opacity": 8,
                        "speed": 3
                    },
                    "repulse": {
                        "distance": 200,
                        "duration": 0.4
                    },
                    "push": {
                        "particles_nb": 4
                    },
                    "remove": {
                        "particles_nb": 2
                    }
                }
            },
            "retina_detect": true
        });
        
        // Scroll to top functionality
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });
        
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;
        
        // Check for saved theme preference
        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
        
        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                localStorage.setItem('darkMode', null);
                darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        });
        
        // Animated counter for statistics
        function animateCounter(elementId, targetValue) {
            const element = document.getElementById(elementId);
            let currentValue = 0;
            const increment = targetValue / 100;
            const duration = 2000; // 2 seconds
            const interval = duration / 100;
            
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= targetValue) {
                    element.textContent = targetValue.toLocaleString();
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(currentValue).toLocaleString();
                }
            }, interval);
        }
        
        // Intersection Observer to trigger counter animation when in view
        const statsSection = document.querySelector('.stats-section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter('bookingsCount', 5840);
                    animateCounter('usersCount', 1250);
                    animateCounter('hallsCount', 24);
                    animateCounter('hoursCount', 9600);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(statsSection);
    </script>
</body>
</html>





