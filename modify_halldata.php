<?php
session_start();
include('config.php'); // Include the database connection

// Make sure PHPMailer is properly included
// Check if the autoloader exists
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    // If no autoloader, try direct inclusion
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
}

// Add PHPMailer imports at the top level
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Ensure the user is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'Professor') {
    header("Location: login.php");
    exit();
}

// Ensure the booking_id is passed
if (!isset($_GET['booking_id'])) {
    echo "Invalid request.";
    exit();
}

$booking_id = $_GET['booking_id'];

// Fetch the existing booking details
$query = "SELECT * FROM hall_bookings WHERE booking_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo "Booking not found.";
    exit();
}

// Fetch all departments for dropdown
$departments_query = "SELECT department_id, department_name FROM departments";
$departments_result = $conn->query($departments_query);

// Handle form submission for updating booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organizer_name = $_POST['organizer_name'];
    $organizer_email = $_POST['organizer_email'];
    $organizer_department = $_POST['organizer_department'];
    $organizer_contact = $_POST['organizer_contact'];
    $program_name = $_POST['program_name'];
    $program_type = $_POST['program_type'];
    $program_purpose = $_POST['program_purpose'];
    $from_date = $_POST['from_date'];
    $end_date = $_POST['end_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $queries = $_POST['queries'];

    // Update the booking
    $update_query = "UPDATE hall_bookings 
                     SET organizer_name = ?, organizer_email = ?, organizer_department = ?, organizer_contact = ?, 
                         program_name = ?, program_type = ?, program_purpose = ?, from_date = ?, end_date = ?, 
                         start_time = ?, end_time = ?, queries = ?
                     WHERE booking_id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssssssssssssi", $organizer_name, $organizer_email, $organizer_department, $organizer_contact, 
                                              $program_name, $program_type, $program_purpose, $from_date, $end_date, 
                                              $start_time, $end_time, $queries, $booking_id);
    
    if ($update_stmt->execute()) {
        // Set a flash message for successful update
        $_SESSION['update_success'] = "Booking for '{$program_name}' updated successfully!";
        
        // Remove the use statements from here
        // Get hall and organizer details
        $query = "SELECT h.hall_name, h.incharge_email, hb.organizer_name, hb.organizer_email 
                FROM halls h 
                JOIN hall_bookings hb ON h.hall_id = hb.hall_id 
                WHERE hb.booking_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $hall_name = $row['hall_name'];
            $incharge_email = $row['incharge_email'];
            $organizer_email = $row['organizer_email'];
            $organizer_name = $row['organizer_name'];
            
            // Format dates for display
            $formatted_from_date = date("M d, Y", strtotime($from_date));
            $formatted_end_date = date("M d, Y", strtotime($end_date));
            $formatted_start_time = date("h:i A", strtotime($start_time));
            $formatted_end_time = date("h:i A", strtotime($end_time));
            
            // Email content
            $subject = "Hall Booking Modified - " . $program_name;
            $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4a6fdc; color: white; padding: 15px; text-align: center; }
                    .content { padding: 20px; border: 1px solid #ddd; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                    .details { margin: 15px 0; }
                    .label { font-weight: bold; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Hall Booking Modified</h2>
                    </div>
                    <div class='content'>
                        <p>Dear Concerned,</p>
                        <p>This is to inform you that the following hall booking has been <strong>modified</strong>:</p>
                        
                        <div class='details'>
                            <p><span class='label'>Program:</span> {$program_name}</p>
                            <p><span class='label'>Hall:</span> {$hall_name}</p>
                            <p><span class='label'>Organizer:</span> {$organizer_name}</p>
                            <p><span class='label'>Date:</span> {$formatted_from_date} to {$formatted_end_date}</p>
                            <p><span class='label'>Time:</span> {$formatted_start_time} to {$formatted_end_time}</p>
                        </div>
                        
                        <p>Please review the updated booking details.</p>
                        <p>Thank you for using our Hall Booking System.</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Send email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();                                            // Send using SMTP
                $mail->Host       = 'smtp.gmail.com';                   // Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                $mail->Username   = 'ajaiofficial06@gmail.com';             // SMTP username
                $mail->Password   = 'pxqzpxdkdbfgbfah';                      // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // Enable TLS encryption
                $mail->Port       = 587;                                   // TCP port to connect to

                //Recipients
                $mail->setFrom('ajaiofficial06@gmail.com', 'Hall Booking System');
                $mail->addAddress($organizer_email);                       // Add the organizer's email
                $mail->addAddress($incharge_email);                        // Add the hall in-charge's email
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                
                $mail->send();
                // Success message
                $_SESSION['update_success'] = "Booking updated successfully and notification email sent.";
            } catch (Exception $e) {
                // Log email sending error but don't stop the process
                error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                $_SESSION['update_success'] = "Booking updated successfully but email notification failed.";
            }
        }
        
        header("Location: delete_modify_hall.php");
        exit();
    } else {
        // Set an error message if update fails
        $_SESSION['update_error'] = "Error: Could not update the booking.";
    }

    $update_stmt->close();
};

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Modify Hall Booking</title>
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .booking-form-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 30px;
            animation: fadeIn 0.5s ease-out;
            position: relative;
        }
        .form-section-header {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .form-control, .form-select {
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }
        .program-type-group {
            display: flex;
            gap: 15px;
        }
        .program-type-input {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* New unique features styles */
        .character-counter {
            position: absolute;
            bottom: -20px;
            right: 10px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .conflict-warning {
            display: none;
            color: #dc3545;
            margin-top: 10px;
        }
        .event-size-indicator {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .event-size-icon {
            margin-right: 10px;
            font-size: 1.5rem;
        }
        .event-size-text {
            flex-grow: 1;
        }
        .complexity-meter {
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }
        .complexity-fill {
            height: 100%;
            background-color: #28a745;
            transition: width 0.5s ease;
        }
        .navigation-icons {
            position: absolute;
            top: 20px;
            right: 30px;
            display: flex;
            gap: 15px;
        }
        .nav-icon {
            color: #3498db;
            font-size: 24px;
            transition: color 0.3s ease, transform 0.2s ease;
        }
        .nav-icon:hover {
            color: #2980b9;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
<?php include('navbar1.php'); ?>
    <div class="container">
        <div class="booking-form-container">
            <!-- New navigation icons -->
            <div class="navigation-icons">
                <a href="index.php" class="nav-icon" title="Go to Home">
                    <i class="fas fa-home"></i>
                </a>
                <a href="view_hall.php" class="nav-icon" title="View Hall Bookings">
                    <i class="fas fa-book"></i>
                </a>
            </div>

            <h1 class="text-center mb-4">Modify Booking: <?php echo htmlspecialchars($booking['program_name']); ?></h1>

            <form method="POST" action="" class="needs-validation" novalidate id="bookingForm">
                <div class="row">
                    <div class="col-12">
                        <h4 class="form-section-header">Organizer Details</h4>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="organizer_name" class="form-label">Organizer Name</label>
                        <input type="text" class="form-control" name="organizer_name" 
                               id="organizer_name"
                               value="<?php echo htmlspecialchars($booking['organizer_name']); ?>" 
                               required>
                        <div class="invalid-feedback">Please enter the organizer's name</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="organizer_email" class="form-label">Organizer Email</label>
                        <input type="email" class="form-control" name="organizer_email" 
                               id="organizer_email"
                               value="<?php echo htmlspecialchars($booking['organizer_email']); ?>" 
                               required>
                        <div class="invalid-feedback">Please enter a valid email address</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="organizer_department" class="form-label">Organizer Department</label>
                        <select class="form-select" name="organizer_department" id="organizer_department" required>
                            <?php while ($row = $departments_result->fetch_assoc()) : ?>
                                <option value="<?php echo htmlspecialchars($row['department_name']); ?>" 
                                    <?php echo ($row['department_name'] == $booking['organizer_department']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['department_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="organizer_contact" class="form-label">Organizer Contact</label>
                        <input type="text" class="form-control" name="organizer_contact" 
                               id="organizer_contact"
                               value="<?php echo htmlspecialchars($booking['organizer_contact']); ?>" 
                               required>
                        <div class="invalid-feedback">Please enter contact information</div>
                    </div>

                    <div class="col-12">
                        <h4 class="form-section-header">Program Details</h4>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="program_name" class="form-label">Name of the Program</label>
                        <input type="text" class="form-control" name="program_name" 
                               id="program_name"
                               value="<?php echo htmlspecialchars($booking['program_name']); ?>" 
                               required maxlength="100">
                        <div class="character-counter" id="program_name_counter">0/100</div>
                        <div class="invalid-feedback">Please enter the program name</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label d-block">Program Type</label>
                        <div class="program-type-group">
                            <div class="program-type-input">
                                <input type="radio" class="form-check-input" id="event-type" name="program_type" value="Event" 
                                    <?php echo ($booking['program_type'] == 'Event') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="event-type">Event</label>
                            </div>
                            <div class="program-type-input">
                                <input type="radio" class="form-check-input" id="class-type" name="program_type" value="Class" 
                                    <?php echo ($booking['program_type'] == 'Class') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="class-type">Class</label>
                            </div>
                            <div class="program-type-input">
                                <input type="radio" class="form-check-input" id="other-type" name="program_type" value="Other" 
                                    <?php echo ($booking['program_type'] == 'Other') ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="other-type">Other</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="program_purpose" class="form-label">Purpose of the Program</label>
                        <textarea class="form-control" name="program_purpose" id="program_purpose" rows="3" required maxlength="500"><?php echo htmlspecialchars($booking['program_purpose']); ?></textarea>
                        <div class="character-counter" id="program_purpose_counter">0/500</div>
                        <div class="invalid-feedback">Please describe the program purpose</div>
                        <div class="complexity-meter">
                            <div class="complexity-fill" id="complexity-fill"></div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" name="from_date" 
                               id="from_date"
                               value="<?php echo htmlspecialchars($booking['from_date']); ?>" 
                               required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" 
                               id="end_date"
                               value="<?php echo htmlspecialchars($booking['end_date']); ?>" 
                               required>
                        <div class="conflict-warning" id="date-conflict">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Potential booking conflict detected!
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" name="start_time" 
                               id="start_time"
                               value="<?php echo htmlspecialchars($booking['start_time']); ?>" 
                               required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" name="end_time" 
                               id="end_time"
                               value="<?php echo htmlspecialchars($booking['end_time']); ?>" 
                               required>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="queries" class="form-label">Any Additional Queries</label>
                        <textarea class="form-control" name="queries" id="queries" rows="3"><?php echo htmlspecialchars($booking['queries']); ?></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="event-size-indicator">
                            <i class="fas fa-users event-size-icon" id="event-size-icon"></i>
                            <div class="event-size-text" id="event-size-text">Estimated Event Size: Not Specified</div>
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">Update Booking</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Character counter for program name
            const programNameInput = document.getElementById('program_name');
            const programNameCounter = document.getElementById('program_name_counter');
            programNameInput.addEventListener('input', function() {
                programNameCounter.textContent = `${this.value.length}/100`;
            });

            // Character counter and complexity meter for program purpose
            const programPurposeInput = document.getElementById('program_purpose');
            const programPurposeCounter = document.getElementById('program_purpose_counter');
            const complexityFill = document.getElementById('complexity-fill');
            programPurposeInput.addEventListener('input', function() {
                // Update character counter
                programPurposeCounter.textContent = `${this.value.length}/500`;
                
                // Update complexity meter based on input length and content complexity
                const complexity = calculateComplexity(this.value);
                complexityFill.style.width = `${complexity}%`;
            });

            // Date conflict detection
            const fromDateInput = document.getElementById('from_date');
            const endDateInput = document.getElementById('end_date');
            const dateConflictWarning = document.getElementById('date-conflict');
            
            [fromDateInput, endDateInput].forEach(input => {
                input.addEventListener('change', checkDateConflict);
            });

            function checkDateConflict() {
                const fromDate = new Date(fromDateInput.value);
                const endDate = new Date(endDateInput.value);
                
                if (fromDate > endDate) {
                    dateConflictWarning.style.display = 'block';
                } else {
                    dateConflictWarning.style.display = 'none';
                }
            }

            // Event size estimation
            const programNameField = document.getElementById('program_name');
            const eventSizeIcon = document.getElementById('event-size-icon');
            const eventSizeText = document.getElementById('event-size-text');
            
            programNameField.addEventListener('input', estimateEventSize);

            function estimateEventSize() {
                const name = programNameField.value.toLowerCase();
                let size = 'Small';
                let iconClass = 'fa-user';
                
                if (name.includes('conference') || name.includes('summit')) {
                    size = 'Large';
                    iconClass = 'fa-users';
                } else if (name.includes('workshop') || name.includes('seminar')) {
                    size = 'Medium';
                    iconClass = 'fa-user-friends';
                }

                // Update icon and text
                eventSizeIcon.className = `fas ${iconClass} event-size-icon`;
                eventSizeText.textContent = `Estimated Event Size: ${size}`;
            }

            // Complexity calculation function
            function calculateComplexity(text) {
                // Basic complexity calculation based on length and some keywords
                const length = text.length;
                const complexityKeywords = ['comprehensive', 'detailed', 'extensive', 'complex', 'strategy', 'analysis'];
                
                let complexityScore = Math.min((length / 5), 80); // Base complexity from length
                
                // Bonus for complexity keywords
                complexityKeywords.forEach(keyword => {
                    if (text.toLowerCase().includes(keyword)) {
                        complexityScore += 10;
                    }
                });

                return Math.min(complexityScore, 100);
            }

            // Bootstrap form validation
            (function() {
                'use strict';
                window.addEventListener('load', function() {
                    var forms = document.getElementsByClassName('needs-validation');
                    var validation = Array.prototype.filter.call(forms, function(form) {
                        form.addEventListener('submit', function(event) {
                            if (form.checkValidity() === false) {
                                event.preventDefault();
                                event.stopPropagation();
                            }
                            form.classList.add('was-validated');
                        }, false);
                    });
                }, false);
            })();
        });
    </script>
</body>
<?php include('footer user.php'); ?>
</html>