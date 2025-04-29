<?php
session_start();
include('navbar1.php'); 
include('config.php');

// Add PHPMailer includes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer autoloader
require 'vendor/autoload.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'Professor') {
    header("Location: login.php");
    exit();
}
$user_email = $_SESSION['email'];
// Fetch only approved bookings (exclude canceled and rejected)
// Fetch only approved bookings (exclude canceled and rejected)
$query = "SELECT hb.booking_id, hb.organizer_name, hb.organizer_email, hb.program_name, 
                 hb.from_date, hb.end_date, hb.start_time, hb.end_time, h.hall_name, hb.status, hb.created_at
          FROM hall_bookings hb 
          JOIN halls h ON hb.hall_id = h.hall_id
          WHERE hb.status IN ('Approved', 'Pending') 
          AND hb.organizer_email = ?";  // Add condition for current user's email

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Manage Hall Bookings</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .page-header {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            padding: 40px 0;
            text-align: center;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .page-title {
            color: white;
            font-weight: 600;
            margin: 0;
            font-size: 2.2rem;
        }
        
        .page-subtitle {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 300;
            margin-top: 10px;
        }
        
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .booking-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .booking-header {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .program-name {
            font-size: 1.25rem;
            font-weight: 500;
            margin: 0;
        }
        
        .hall-badge {
            display: inline-block;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
        
        .booking-body {
            padding: 20px;
        }
        
        .booking-detail {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: 500;
            color: #6c757d;
            display: block;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-weight: 400;
            color: #343a40;
        }
        
        .booking-actions {
            background: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn-modify {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-modify:hover {
            background: linear-gradient(135deg, #00f2fe, #4facfe);
            transform: translateY(-2px);
        }
        
        .btn-modify.disabled {
            background: #b0b0b0;
            cursor: not-allowed;
            opacity: 0.7;
            pointer-events: none;
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #ff758c, #ff7eb3);
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: linear-gradient(135deg, #ff7eb3, #ff758c);
            transform: translateY(-2px);
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 500px;
            max-width: 90%;
            transform: translateY(20px);
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-weight: 600;
            font-size: 1.5rem;
            color: #333;
            margin: 0;
        }
        
        .radio-option {
            display: block;
            margin: 15px 0;
            position: relative;
            padding-left: 35px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .radio-option input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .radio-checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 24px;
            width: 24px;
            background-color: #eee;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        .radio-option:hover input ~ .radio-checkmark {
            background-color: #ccc;
        }
        
        .radio-option input:checked ~ .radio-checkmark {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
        }
        
        .radio-checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .radio-option input:checked ~ .radio-checkmark:after {
            display: block;
        }
        
        .radio-option .radio-checkmark:after {
            top: 7px;
            left: 7px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: white;
        }
        
        .custom-textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 10px;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s ease;
        }
        
        .custom-textarea:focus {
            border-color: #6a11cb;
            outline: none;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, #2575fc, #6a11cb);
            transform: translateY(-2px);
        }
        
        .btn-close {
            background: #e9ecef;
            color: #495057;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-close:hover {
            background: #dee2e6;
        }
        
        .search-filter-section {
            margin-bottom: 30px;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 20px 12px 50px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: #6a11cb;
            box-shadow: 0 0 0 3px rgba(106, 17, 203, 0.1);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .filter-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background: white;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            border-color: transparent;
        }
        
        .no-bookings {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .no-bookings-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .no-bookings-text {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 30px 0;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .booking-header {
                flex-direction: column;
            }
            
            .hall-badge {
                margin-left: 0;
                margin-top: 5px;
            }
            
            .booking-actions {
                flex-direction: column;
            }
            
            .btn-modify, .btn-cancel {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        
        /* Loading spinner */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            visibility: hidden;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .spinner-overlay.active {
            visibility: visible;
            opacity: 1;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #6a11cb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .tooltip-text {
            position: absolute;
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 5px 12px;
            border-radius: 6px;
            z-index: 1;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
            pointer-events: none;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            width: max-content;
            max-width: 200px;
        }
        
        .tooltip-container {
            position: relative;
            display: inline-block;
        }
        
        .tooltip-container:hover .tooltip-text {
            opacity: 1;
            visibility: visible;
        }
        .status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    margin-left: 10px;
}

.status-Approved {
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    color: white;
}

.status-Pending {
    background: linear-gradient(135deg, #FF9800, #FFC107);
    color: white;
}
.search-filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .navigation-icons {
            display: flex;
            gap: 15px;
        }

        .nav-icon {
            color: #6c757d;
            font-size: 1.3rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .nav-icon:hover {
            color: #6a11cb;
            background-color: #e9ecef;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .search-filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .navigation-icons {
                justify-content: center;
                margin-top: 15px;
            }
        }

        search-input {
    width: 300px; /* Change this value to your desired width */
}

/* Option 2: Percentage width */
.search-input {
    width: 80%; /* Adjusts width relative to its parent container */
}

/* Option 3: Minimum and maximum width */
.search-input {
    width: 250px;
    max-width: 500px; /* Prevents it from becoming too wide */
    min-width: 200px; /* Ensures it doesn't get too narrow */
}

/* Option 4: Responsive width with media queries */
.search-input {
    width: 100%; /* Full width on small screens */
}

@media (min-width: 768px) {
    .search-input {
        width: 400px; /* Specific width on larger screens */
    }
}
    </style>
</head>
<body>
    <div class="page-header">
        <h1 class="page-title">Manage Hall Bookings</h1>
        <p class="page-subtitle">Modify or cancel your upcoming hall reservations</p>
    </div>
    
    <div class="content-container">
        <div class="search-filter-section">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search by program name, organizer, or hall...">
            </div>
            <div class="filter-options">
                <button class="filter-btn active" data-filter="all">All Bookings</button>
                <button class="filter-btn" data-filter="upcoming">Upcoming</button>
                <button class="filter-btn" data-filter="past">Past</button>
            </div>
            
            <!-- Navigation Icons -->
            <div class="navigation-icons">
                <a href="index.php" class="nav-icon" title="Home">
                    <i class="fas fa-home"></i>
                </a>
                <a href="view_hall.php" class="nav-icon" title="View Halls">
                    <i class="fas fa-building"></i>
                </a>
            </div>
        </div>
        
        <div id="bookingsContainer">
            <?php
            $sno = 1;
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // Format dates for display
                    $from_date = date("M d, Y", strtotime($row['from_date']));
                    $end_date = date("M d, Y", strtotime($row['end_date']));
                    
                    // Format times for display
                    $start_time = date("h:i A", strtotime($row['start_time']));
                    $end_time = date("h:i A", strtotime($row['end_time']));
                    
                    // Default hall name if not available
                    $hall_name = !empty($row['hall_name']) ? $row['hall_name'] : "Main Hall";
                    
                    // Check if modification is allowed (2 days after booking creation)
                    $booking_creation_date = new DateTime($row['created_at']); // Assuming 'created_at' is the column for booking creation date
                    $today = new DateTime();
                    $days_since_creation = $today->diff($booking_creation_date)->days;
                    $can_modify = $days_since_creation <= 2 && $days_since_creation >= 0; // Allow modification if within 2 days after creation
                    
                    // Calculate days left for modification
                    $days_left = 2 - $days_since_creation; // Days left to modify
                    
                    // Determine if event has already ended
                    $has_ended = strtotime($row['end_date']) < time();
                    
                    // Only show bookings that are not completed
                    if (!$has_ended) {
                        echo "<div class='booking-card' data-booking-id='{$row['booking_id']}'>
                                <div class='booking-header'>
                                    <h3 class='program-name'>{$row['program_name']} <span class='hall-badge'>{$hall_name}</span></h3>
                                </div>
                                <div class='booking-body'>
                                    <div class='row'>
                                        <div class='col-md-6'>
                                            <div class='booking-detail'>
                                                <span class='detail-label'><i class='fas fa-user me-2'></i>Organizer</span>
                                                <span class='detail-value'>{$row['organizer_name']}</span>
                                            </div>
                                        </div>
                                        <div class='col-md-6'>
                                            <div class='booking-detail'>
                                                <span class='detail-label'><i class='fas fa-envelope me-2'></i>Email</span>
                                                <span class='detail-value'>{$row['organizer_email']}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class='row'>
                                        <div class='col-md-6'>
                                            <div class='booking-detail'>
                                                <span class='detail-label'><i class='fas fa-calendar-alt me-2'></i>Date</span>
                                                <span class='detail-value'>{$from_date} - {$end_date}</span>
                                            </div>
                                        </div>
                                        <div class='col-md-6'>
                                            <div class='booking-detail'>
                                                <span class='detail-label'><i class='fas fa-clock me-2'></i>Time</span>
                                                <span class='detail-value'>{$start_time} - {$end_time}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>";
                        
                        // Show action buttons if the event hasn't ended
                        echo "<div class='booking-actions'>";
                        if ($can_modify) {
                            echo "<button class='btn-modify' onclick='redirectToModify({$row['booking_id']})'><i class='fas fa-edit me-2'></i>Modify</button>";
                            echo "<span class='days-left'>Days left to modify: {$days_left}</span>"; // Display days left
                        } else {
                            echo "<div class='tooltip-container'>
                                    <button class='btn-modify disabled'><i class='fas fa-edit me-2'></i>Modify</button>
                                    <span class='tooltip-text'>Modification not allowed after two days from booking creation</span>
                                  </div>";
                        }
                        echo "<button class='btn-cancel' onclick='showCancelModal({$row['booking_id']})'><i class='fas fa-times-circle me-2'></i>Cancel</button>
                              </div>";
                        echo "</div>"; // Close booking-card div
                    }
                }
            } else {
                echo "<div class='no-bookings'>
                        <div class='no-bookings-icon'><i class='fas fa-calendar-times'></i></div>
                        <h3 class='no-bookings-text'>No active bookings found</h3>
                        <a href='book_hall.php' class='btn-modify'><i class='fas fa-plus me-2'></i>Book a Hall</a>
                      </div>";
            } 
            ?>
        </div>
    </div>

    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Reason for Cancellation</h4>
            </div>
            <form id="cancelForm">
                <input type="hidden" id="cancelBookingId" name="booking_id">
                
                <label class="radio-option">
                    Schedule Conflict
                    <input type="radio" name="cancel_reason" value="Schedule Conflict">
                    <span class="radio-checkmark"></span>
                </label>
                
                <label class="radio-option">
                    Event Postponed
                    <input type="radio" name="cancel_reason" value="Event Postponed">
                    <span class="radio-checkmark"></span>
                </label>
                
                <label class="radio-option">
                    Venue Changed
                    <input type="radio" name="cancel_reason" value="Venue Changed">
                    <span class="radio-checkmark"></span>
                </label>
                
                <label class="radio-option">
                    Event Cancelled
                    <input type="radio" name="cancel_reason" value="Event Cancelled">
                    <span class="radio-checkmark"></span>
                </label>
                
                <label class="radio-option">
                    Other
                    <input type="radio" name="cancel_reason" value="Other">
                    <span class="radio-checkmark"></span>
                </label>
                
                <textarea id="otherReason" class="custom-textarea" name="other_reason" style="display:none;" placeholder="Please specify the reason for cancellation..."></textarea>
                
                <div class="modal-footer">
                    <button type="submit" class="btn-submit">Confirm Cancellation</button>
                    <button type="button" class="btn-close" onclick="closeCancelModal()">Close</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="spinner-overlay" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <script>
        // Show loading spinner
        function showLoading() {
            document.getElementById('loadingSpinner').classList.add('active');
        }
        
        // Hide loading spinner
        function hideLoading() {
            document.getElementById('loadingSpinner').classList.remove('active');
        }
        
        // Redirect to modify page
        function redirectToModify(bookingId) {
            showLoading();
            window.location.href = 'modify_halldata.php?booking_id=' + bookingId;
        }
        
        // Show cancel modal
        function showCancelModal(bookingId) {
            document.getElementById('cancelBookingId').value = bookingId;
            document.getElementById('cancelModal').classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }
        
        // Close cancel modal
        function closeCancelModal() {
            document.getElementById('cancelModal').classList.remove('active');
            document.body.style.overflow = ''; // Allow scrolling
            
            // Reset form
            document.getElementById('cancelForm').reset();
            document.getElementById('otherReason').style.display = 'none';
        }
        
        // Filter bookings by search term
        function filterBookings() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const bookings = document.querySelectorAll('.booking-card');
            
            bookings.forEach(booking => {
                const text = booking.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    booking.style.display = 'block';
                } else {
                    booking.style.display = 'none';
                }
            });
        }
        
        // Apply date filters
        function applyFilter(filter) {
            const bookings = document.querySelectorAll('.booking-card');
            const today = new Date();
            
            bookings.forEach(booking => {
                const dateText = booking.querySelector('.detail-value').textContent;
                const endDateStr = dateText.split(' - ')[1];
                const endDate = new Date(endDateStr);
                
                if (filter === 'all') {
                    booking.style.display = 'block';
                } else if (filter === 'upcoming' && endDate >= today) {
                    booking.style.display = 'block';
                } else if (filter === 'past' && endDate < today) {
                    booking.style.display = 'block';
                } else {
                    booking.style.display = 'none';
                }
            });
        }
        
        // Document ready
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', filterBookings);
            
            // Filter buttons
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    applyFilter(this.dataset.filter);
                });
            });
            
            // Handle radio buttons for cancel reason
            const radioButtons = document.querySelectorAll('input[name="cancel_reason"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'Other') {
                        document.getElementById('otherReason').style.display = 'block';
                    } else {
                        document.getElementById('otherReason').style.display = 'none';
                    }
                });
            });
            
            // Cancel form submission
            document.getElementById('cancelForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const bookingId = document.getElementById('cancelBookingId').value;
                const reason = document.querySelector('input[name="cancel_reason"]:checked')?.value;
                const otherReason = document.getElementById('otherReason').value;
                
                if (!reason) {
                    alert('Please select a reason for cancellation');
                    return;
                }
                
                if (reason === 'Other' && !otherReason.trim()) {
                    alert('Please provide details for the cancellation reason');
                    return;
                }
                
                // Show loading spinner
                showLoading();
                
                // Send cancellation request
                $.post('cancel_booking.php', {
                    booking_id: bookingId,
                    reason: reason,
                    other_reason: otherReason,
                    send_email: true // Add parameter to indicate email should be sent
                }, function(response) {
                    hideLoading();
                    
                    // Show toast notification instead of alert
                    showNotification(response);
                    
                    // Close modal
                    closeCancelModal();
                    
                    // Reload page after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }).fail(function() {
                    hideLoading();
                    showNotification('Error processing your request. Please try again.', 'error');
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('cancelModal');
                if (event.target === modal) {
                    closeCancelModal();
                }
            });
        });
        
        // Toast notification function
        function showNotification(message, type = 'success') {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast-notification ' + type;
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                </div>
                <div class="toast-message">${message}</div>
            `;
            
            // Add styles
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.backgroundColor = type === 'success' ? '#4CAF50' : '#F44336';
            toast.style.color = 'white';
            toast.style.padding = '15px 20px';
            toast.style.borderRadius = '8px';
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            toast.style.zIndex = '3000';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            toast.style.transition = 'all 0.3s ease';
            
            // Add to body
            document.body.appendChild(toast);
            
            // Show with animation
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            }, 10);
            
            // Remove after timeout
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
    </script>

    <?php include('footer user.php'); ?>
</body>
</html>