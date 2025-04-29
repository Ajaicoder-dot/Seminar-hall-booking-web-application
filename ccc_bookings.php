<?php
session_start();
include('config.php'); // Include the database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // This will autoload all classes including PHPMailer

// Ensure the user is logged in
if (!isset($_SESSION['email'])) {
    echo "<div class='alert alert-danger'>Please log in to view your bookings.</div>";
    header("Location: login.php");
    exit();
}

// Fetch user details
$user_email = $_SESSION['email'];
$user_role = $_SESSION['role']; 

$user_query = "SELECT id FROM users WHERE email = ? AND role = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("ss", $user_email, $user_role);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    echo "<div class='alert alert-danger'>User not found.</div>";
    exit();
}

$user_id = $user['id'];

// Handle booking cancellation
if (isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $cancellation_reason = isset($_POST['cancellation_reason']) ? $_POST['cancellation_reason'] : '';
    
    // Verify the booking belongs to the user
    $verify_query = "SELECT * FROM ccc_hall_bookings WHERE booking_id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $booking_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $booking = $verify_result->fetch_assoc();
        $from_date = new DateTime($booking['from_date']);
        $current_date = new DateTime();
        $interval = $current_date->diff($from_date);
        
        // Check if the booking is at least 48 hours away
        if ($interval->days >= 2) {
            // Update booking status to cancelled and save the reason
            $cancel_query = "UPDATE ccc_hall_bookings SET status = 'Cancelled', cancellation_reason = ? WHERE booking_id = ?";
            $cancel_stmt = $conn->prepare($cancel_query);
            $cancel_stmt->bind_param("si", $cancellation_reason, $booking_id);
            
            if ($cancel_stmt->execute()) {
                echo "<div class='alert alert-success'>Booking cancelled successfully. Your advance payment will be refunded.</div>";
                
                // Send cancellation email notification
                $mail = new PHPMailer(true);
                try {
                    //Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'ajaiofficial06@gmail.com';
                    $mail->Password   = 'pxqzpxdkdbfgbfah';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    //Recipients
                    $mail->setFrom('ajaiofficial06@gmail.com', 'Pondicherry University Hall Booking');
                    $mail->addAddress($user_email);
                    $mail->addAddress('ccc.admin@pondiuni.edu.in'); // CCC Hall admin email

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'CCC Hall Booking Cancellation - ' . $booking['program_name'];
                    $mail->Body    = "
                        <h2>CCC Hall Booking Cancellation</h2>
                        <p>Dear {$booking['organizer_name']},</p>
                        <p>Your booking for the CCC Hall has been cancelled as requested:</p>
                        <table border='0' cellpadding='5' style='border-collapse: collapse;'>
                            <tr><td><strong>Program Name:</strong></td><td>{$booking['program_name']}</td></tr>
                            <tr><td><strong>Date:</strong></td><td>{$booking['from_date']}</td></tr>
                            <tr><td><strong>Time:</strong></td><td>{$booking['start_time']} to {$booking['end_time']}</td></tr>
                        </table>
                        <p>Your advance payment will be refunded. Please visit the university finance office with your receipt.</p>
                        <p>Regards,<br>CCC Hall Administration</p>";

                    $mail->send();
                } catch (Exception $e) {
                    echo "<div class='alert alert-warning'>Email notification could not be sent. Please contact the administrator.</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Error: Could not cancel the booking. Please try again.</div>";
            }
            $cancel_stmt->close();
        } else {
            echo "<div class='alert alert-danger'>Bookings can only be cancelled at least 48 hours before the event date.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Invalid booking or you don't have permission to cancel this booking.</div>";
    }
    $verify_stmt->close();
}

// Fetch user's bookings
$bookings_query = "SELECT b.*, h.hall_name 
                  FROM ccc_hall_bookings b 
                  JOIN halls h ON b.hall_id = h.hall_id 
                  WHERE b.user_id = ? 
                  ORDER BY b.from_date DESC, b.start_time DESC";
$bookings_stmt = $conn->prepare($bookings_query);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My CCC Hall Bookings - Pondicherry University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
       /* Reset default browser styles */
*,
*::before,
*::after {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* Set up body defaults */
body {
  font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f9f9f9;
  color: #333;
  line-height: 1.6;
  font-size: 16px;
}

/* Container for layout */
.container {
  width: 90%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

/* Hero section styling */
.hero-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  background-size: cover;
  background-position: center;
  color: white;
  padding: 60px 0;
  text-align: center;
  margin-bottom: 30px;
}

.hero-overlay {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 20px;
}

.hero-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 15px;
  animation: fadeInDown 1s ease;
}

/* Section title styling */
.section-title {
  border-bottom: 2px solid #4f46e5;
  padding-bottom: 10px;
  margin-bottom: 25px;
  color: #333;
  font-weight: 600;
}

/* Booking card styling */
.booking-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
  padding: 25px;
  margin-bottom: 30px;
}

/* Booking item styling */
.booking-item {
  background: white;
  border-radius: 8px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.08);
  margin-bottom: 20px;
  padding: 20px;
  transition: box-shadow 0.3s ease;
  border-left: 5px solid #ccc;
  /* Remove transform from transition to prevent shaking */
}

.booking-item:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  /* Remove transform on hover to prevent layout shifts */
}

/* Card Component - modify to prevent shaking */
.card {
  background: #fff;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  /* Remove transform transition */
}

.card:hover {
  /* Remove transform on hover */
  box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

/* Status-based styling */
.booking-item.approved {
  border-left-color: #28a745;
}

.booking-item.cancelled {
  border-left-color: #dc3545;
  background-color: #fff8f8;
}

.booking-item.pending {
  border-left-color: #ffc107;
}

/* Booking header styling */
.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.booking-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #333;
}

/* Status badge styling */
.booking-status {
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-approved {
  background-color: #d4edda;
  color: #155724;
}

.status-cancelled {
  background-color: #f8d7da;
  color: #721c24;
}

.status-pending {
  background-color: #fff3cd;
  color: #856404;
}

/* Booking details styling */
.booking-details {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.detail-item {
  margin-bottom: 10px;
}

.detail-label {
  font-size: 0.85rem;
  color: #6c757d;
  margin-bottom: 3px;
  font-weight: 500;
}

.detail-value {
  font-weight: 500;
  color: #333;
}

/* Booking actions styling */
.booking-actions {
  display: flex;
  gap: 10px;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #eee;
}

.btn-modify {
  background-color: #4f46e5;
  color: white;
  border: none;
  padding: 8px 15px;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.btn-modify:hover {
  background-color: #4338ca;
  color: white;
  text-decoration: none;
}

.btn-cancel {
  background-color: #dc3545;
  color: white;
  border: none;
  padding: 8px 15px;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.btn-cancel:hover {
  background-color: #c82333;
}

/* Empty state styling */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  background-color: #f8f9fa;
  border-radius: 8px;
  margin: 20px 0;
}

.empty-icon {
  font-size: 3rem;
  color: #adb5bd;
  margin-bottom: 15px;
}

/* Modal customization */
.modal-content {
  border-radius: 10px;
  border: none;
}

.modal-header {
  background-color: #f8f9fa;
  border-radius: 10px 10px 0 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .booking-details {
    grid-template-columns: 1fr;
  }
  
  .booking-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .booking-status {
    margin-top: 10px;
  }
  
  .booking-actions {
    flex-direction: column;
  }
  
  .btn-modify, .btn-cancel {
    width: 100%;
    text-align: center;
  }
  
  .hero-title {
    font-size: 2rem;
  }
}

/* Animation keyframes */
@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Centered Flex Utility */
.flex-center {
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Button Styles */
.button {
  display: inline-block;
  padding: 12px 24px;
  background-color: #4f46e5; /* Indigo */
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-weight: bold;
  transition: background-color 0.3s ease, transform 0.2s ease;
  border: none;
  cursor: pointer;
}

.button:hover {
  background-color: #4338ca;
  transform: translateY(-2px);
}

/* Card Component */
.card {
  background: #fff;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
}

/* Navbar */
.navbar {
  background-color: #fff;
  padding: 15px 30px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.navbar a {
  text-decoration: none;
  color: #333;
  margin-left: 20px;
  font-weight: 500;
}

.navbar a:hover {
  color: #4f46e5;
}

/* Footer */
.footer {
  background-color: #111827;
  color: #d1d5db;
  text-align: center;
  padding: 20px;
  font-size: 14px;
}

/* Images */
img {
  max-width: 100%;
  height: auto;
  display: block;
}

/* Forms */
input, textarea, select {
  width: 100%;
  padding: 10px;
  margin-top: 8px;
  margin-bottom: 20px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

input:focus, textarea:focus, select:focus {
  border-color: #4f46e5;
  outline: none;
}

/* Headings */
h1, h2, h3, h4, h5, h6 {
  font-weight: 700;
  margin-bottom: 15px;
  color: #111;
}

/* Responsive Media Queries */
@media (max-width: 768px) {
  .container {
    width: 95%;
  }

  .navbar {
    flex-direction: column;
  }

  .navbar a {
    margin: 10px 0;
  }
}

    </style>
</head>
<body>
    <?php include('navbar1.php'); ?>
    
    <div class="hero-section">
        <div class="hero-overlay">
            <h1 class="hero-title">My CCC Hall Bookings</h1>
        </div>
    </div>
    
    <div class="container">
        <div class="booking-card">
            <h2 class="section-title">
                <i class="fas fa-calendar-check me-2"></i> My Booking History
                <a href="ccc.php" class="btn btn-primary float-end"><i class="fas fa-plus me-2"></i> New Booking</a>
            </h2>
            
            <?php if ($bookings_result->num_rows > 0) : ?>
                <?php while ($booking = $bookings_result->fetch_assoc()) : 
                    $status = isset($booking['status']) ? $booking['status'] : 'Pending';
                    $statusClass = '';
                    $statusBadgeClass = '';
                    
                    if ($status == 'Approved') {
                        $statusClass = 'approved';
                        $statusBadgeClass = 'status-approved';
                    } elseif ($status == 'Cancelled') {
                        $statusClass = 'cancelled';
                        $statusBadgeClass = 'status-cancelled';
                    } else {
                        $statusClass = 'pending';
                        $statusBadgeClass = 'status-pending';
                    }
                    
                    // Check if booking date is in the future and not cancelled
                    $canModify = false;
                    $canCancel = false;
                    
                    if ($status != 'Cancelled' && $status != 'Rejected') {
                        $bookingDate = new DateTime($booking['from_date'] . ' ' . $booking['start_time']);
                        $currentDate = new DateTime();
                        
                        // Modify this section to properly calculate time difference
                        $interval = $currentDate->diff($bookingDate);
                        $hoursDifference = ($interval->days * 24) + $interval->h;
                        
                        if ($bookingDate > $currentDate) {
                            $canModify = true;
                            
                            // Can cancel only if at least 48 hours before the event
                            if ($hoursDifference >= 48) {
                                $canCancel = true;
                            }
                        }
                    }
                ?>
                <div class="booking-item <?php echo $statusClass; ?>">
                    <div class="booking-header">
                        <div class="booking-title"><?php echo htmlspecialchars($booking['program_name']); ?></div>
                        <div class="booking-status <?php echo $statusBadgeClass; ?>">
                            <?php echo $status; ?>
                        </div>
                    </div>
                    
                    <div class="booking-details">
                        <div class="detail-item">
                            <div class="detail-label">Booking ID</div>
                            <div class="detail-value">#<?php echo $booking['booking_id']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Event Date</div>
                            <div class="detail-value"><?php echo date('d M Y', strtotime($booking['from_date'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Time</div>
                            <div class="detail-value"><?php echo date('h:i A', strtotime($booking['start_time'])) . ' - ' . date('h:i A', strtotime($booking['end_time'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Hall</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['hall_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Department</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['organizer_department']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Event Type</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['program_type']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value"><?php echo $booking['duration_hours']; ?> hours</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value">
                                <span class="badge <?php echo ($booking['payment_status'] == 'Fully Paid') ? 'bg-success' : (($booking['payment_status'] == 'Partially Paid') ? 'bg-warning' : (($booking['payment_status'] == 'Refunded') ? 'bg-info' : 'bg-danger')); ?>">
                                    <?php echo $booking['payment_status']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Advance Payment</div>
                            <div class="detail-value">₹<?php echo number_format($booking['advance_payment']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value">₹<?php echo number_format($booking['total_amount']); ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($booking['queries'])): ?>
                    <div class="mt-3">
                        <div class="detail-label">Special Requests/Queries:</div>
                        <div class="detail-value fst-italic"><?php echo htmlspecialchars($booking['queries']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($booking['status'] == 'Cancelled' && !empty($booking['cancellation_reason'])): ?>
                    <div class="mt-3">
                        <div class="detail-label text-danger">Cancellation Reason:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['cancellation_reason']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($booking['status'] == 'Rejected' && !empty($booking['reject_reason'])): ?>
                    <div class="mt-3">
                        <div class="detail-label text-danger">Rejection Reason:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['reject_reason']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($canModify || $canCancel) : ?>
                    <div class="booking-actions">
                        <?php if ($canModify) : ?>
                        <a href="ccc_modify.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-modify">
                            <i class="fas fa-edit me-1"></i> Modify
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($canCancel) : ?>
                        <button type="button" class="btn btn-cancel" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $booking['booking_id']; ?>">
                            <i class="fas fa-times-circle me-1"></i> Cancel
                        </button>
                        
                        <!-- Cancel Confirmation Modal -->
                        <div class="modal fade" id="cancelModal<?php echo $booking['booking_id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirm Cancellation</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to cancel your booking for <strong><?php echo htmlspecialchars($booking['program_name']); ?></strong> on <strong><?php echo date('d M Y', strtotime($booking['from_date'])); ?></strong>?</p>
                                        
                                        <form method="POST" action="ccc_bookings.php">
                                            <div class="mb-3">
                                                <label for="cancellation_reason<?php echo $booking['booking_id']; ?>" class="form-label">Cancellation Reason:</label>
                                                <textarea class="form-control" id="cancellation_reason<?php echo $booking['booking_id']; ?>" name="cancellation_reason" rows="3" required placeholder="Please provide a reason for cancellation..."></textarea>
                                            </div>
                                            
                                            <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> This action cannot be undone.</p>
                                            
                                            <div class="mt-3 d-flex justify-content-end">
                                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <button type="submit" name="cancel_booking" class="btn btn-danger">Confirm Cancellation</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="far fa-calendar-times"></i></div>
                    <h3>No Bookings Found</h3>
                    <p>You haven't made any bookings for the CCC Hall yet.</p>
                    <a href="ccc.php" class="btn btn-primary mt-3">Book CCC Hall Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include('footer user.php'); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Remove all existing event listeners to prevent conflicts
        document.addEventListener('DOMContentLoaded', function() {
            // Disable any hover effects that might cause shaking
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('mouseenter', function(e) {
                    // Prevent any default behaviors that might cause shaking
                    e.stopPropagation();
                });
            });
            
            // Remove the click event listener from cancel buttons to prevent multiple triggers
            const cancelButtons = document.querySelectorAll('button[name="cancel_booking"]');
            cancelButtons.forEach(button => {
                // Clone and replace the button to remove all event listeners
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
            });
        });
    </script>
</body>
</html>