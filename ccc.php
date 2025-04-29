<?php
session_start();
include('config.php'); // Include the database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // This will autoload all classes including PHPMailer

// Ensure the user is logged in
if (!isset($_SESSION['email'])) {
    echo "<div class='alert alert-danger'>Please log in to book the hall.</div>";
    header("Location: login.php");
    exit();
}

// Fetch user details
$user_email = $_SESSION['email'];
$user_role = $_SESSION['role']; 

$user_query = "SELECT id, department_id FROM users WHERE email = ? AND role = ?";
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
$user_department_id = $user['department_id'];

// Fetch all departments for the dropdown
$departments_query = "SELECT department_id, department_name FROM departments";
$departments_result = $conn->query($departments_query);

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organizer_name = $_POST['organizer_name'];
    $organizer_email = $_POST['organizer_email'];
    $organizer_department = $_POST['organizer_department'];
    $selected_department_id = $_POST['selected_department_id'];
    $organizer_contact = $_POST['organizer_contact'];
    $program_name = $_POST['program_name'];
    $program_type = $_POST['program_type'];
    $program_purpose = $_POST['program_purpose'];
    $from_date = $_POST['from_date'];
    $end_date = $_POST['from_date']; // Same day for CCC Hall
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $duration_hours = $_POST['duration_hours'];
    $advance_payment = 5000; // Fixed advance payment
    $total_amount = $duration_hours * 500; // ₹1000 for 2 hours = ₹500 per hour
    $queries = $_POST['queries'];

    // Get the hall_id for CCC Hall from the database
    $hall_query = "SELECT hall_id FROM halls WHERE hall_name = 'CCC Auditorium'";
    $hall_result = $conn->query($hall_query);
    $hall_id = ($hall_result && $hall_result->num_rows > 0) ? $hall_result->fetch_assoc()['hall_id'] : 1;

    // Check for availability
    $availability_query = "
        SELECT * FROM hall_bookings 
        WHERE hall_id = ? 
          AND (
            (from_date = ? AND end_date = ?) -- Same day
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
          )
    ";
    $availability_stmt = $conn->prepare($availability_query);
    
    // Add error checking for the prepare statement
    if ($availability_stmt === false) {
        echo "<div class='alert alert-danger'>Error preparing query: " . $conn->error . "</div>";
        exit();
    }
    
    $availability_stmt->bind_param("issssssss", $hall_id, $from_date, $from_date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
    $availability_stmt->execute();
    $availability_result = $availability_stmt->get_result();

    if ($availability_result->num_rows > 0) {
        echo "<div class='alert alert-danger'>The CCC Hall is not available for the selected date and time. Please choose a different slot.</div>";
    } else {
        // Insert booking into ccc_hall_bookings
        $insert_query = "INSERT INTO ccc_hall_bookings 
            (user_id, hall_id, organizer_name, organizer_email, organizer_department, department_id,
            organizer_contact, program_name, program_type, program_purpose, from_date, end_date, 
            start_time, end_time, duration_hours, advance_payment, total_amount, queries) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insert_stmt = $conn->prepare($insert_query);
        if ($insert_stmt === false) {
            echo "<div class='alert alert-danger'>Error preparing insert query: " . $conn->error . "</div>";
            exit();
        }
        
        $insert_stmt->bind_param("iisssissssssssiids", 
            $user_id, 
            $hall_id, 
            $organizer_name, 
            $organizer_email, 
            $organizer_department,
            $selected_department_id,
            $organizer_contact, 
            $program_name, 
            $program_type, 
            $program_purpose, 
            $from_date, 
            $end_date, 
            $start_time, 
            $end_time,
            $duration_hours,
            $advance_payment,
            $total_amount,
            $queries
        );

        if ($insert_stmt->execute()) {
            $booking_id = $insert_stmt->insert_id;
            
            // Insert advance payment record
            $payment_query = "INSERT INTO ccc_hall_payments 
                (booking_id, amount, payment_type, payment_date) 
                VALUES (?, ?, 'Advance', NOW())";
            $payment_stmt = $conn->prepare($payment_query);
            $payment_stmt->bind_param("id", $booking_id, $advance_payment);
            $payment_stmt->execute();
            $payment_stmt->close();

            echo "<div class='alert alert-success animate__animated animate__fadeIn'>Booking request submitted successfully! Please pay the advance amount of ₹5,000 at the university finance office.</div>";

            // Send email notification
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
                $mail->addAddress($organizer_email);
                $mail->addAddress('ajai55620@gmail.com'); // CCC Hall admin email

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'CCC Hall Booking Request - ' . $program_name;
                $mail->Body    = "
                    <h2>CCC Hall Booking Request</h2>
                    <p>Dear $organizer_name,</p>
                    <p>Your booking request for the CCC Hall has been received with the following details:</p>
                    <table border='0' cellpadding='5' style='border-collapse: collapse;'>
                        <tr><td><strong>Program Name:</strong></td><td>$program_name</td></tr>
                        <tr><td><strong>Program Type:</strong></td><td>$program_type</td></tr>
                        <tr><td><strong>Date:</strong></td><td>$from_date</td></tr>
                        <tr><td><strong>Time:</strong></td><td>$start_time to $end_time</td></tr>
                        <tr><td><strong>Duration:</strong></td><td>$duration_hours hours</td></tr>
                        <tr><td><strong>Total Amount:</strong></td><td>₹$total_amount</td></tr>
                        <tr><td><strong>Advance Payment:</strong></td><td>₹$advance_payment</td></tr>
                    </table>
                    <p>Please note that your booking is pending approval. You are required to pay the advance amount of ₹5,000 at the university finance office within 48 hours to confirm your booking.</p>
                    <p>The advance payment will be refunded after the event if no damages occur.</p>
                    <p>Thank you for choosing Pondicherry University's CCC Hall for your event.</p>
                    <p>Regards,<br>CCC Hall Administration</p>";

                $mail->send();
            } catch (Exception $e) {
                echo "<div class='alert alert-warning'>Email notification could not be sent. Please contact the administrator.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Error: Could not complete the booking. Please try again.</div>";
        }

        $insert_stmt->close();
    }
    $availability_stmt->close();
}

$user_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCC Hall Booking - Pondicherry University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .hero-section {
            background: url('https://www.pondiuni.edu.in/wp-content/uploads/2021/12/pondicherry-university-building.jpg') center/cover;
            height: 300px;
            position: relative;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            border-radius: 0 0 20px 20px;
        }
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            animation: fadeInDown 1s;
        }
        .hero-subtitle {
            font-size: 1.2rem;
            max-width: 600px;
            animation: fadeInUp 1s;
        }
        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            animation: fadeIn 1s;
        }
        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            animation: fadeIn 1.2s;
        }
        .info-card h3 {
            border-bottom: 2px solid rgba(255,255,255,0.3);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .info-item {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .info-icon {
            margin-right: 10px;
            width: 30px;
            text-align: center;
        }
        .section-title {
            color: #4a4a4a;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        .form-label {
            font-weight: 500;
            color: #555;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .event-type-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .event-type-item {
            flex: 1;
            min-width: 120px;
            text-align: center;
            padding: 15px 10px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .event-type-item:hover {
            background: #e9ecef;
        }
        .event-type-item.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        .event-icon {
            font-size: 24px;
            margin-bottom: 8px;
            color: #667eea;
        }
        .calculator {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .calculator-result {
            font-size: 1.2rem;
            font-weight: 600;
            text-align: right;
            color: #4a4a4a;
        }
        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 25px 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include('navbar1.php'); ?>
    
    <div class="hero-section">
        <div class="hero-overlay">
            <h1 class="hero-title">CCC Auditorium</h1>
            <p class="hero-subtitle">Pondicherry University's premier venue for academic and cultural events</p>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="booking-card">
                    <h2 class="section-title">Book CCC Auditorium</h2>
                    
                    <form method="POST" action="" id="bookingForm">
                        <h4 class="mb-3">Organizer Details</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="organizer_name" class="form-label">Organizer Name</label>
                                <input type="text" class="form-control" name="organizer_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="organizer_email" class="form-label">Organizer Email</label>
                                <input type="email" class="form-control" name="organizer_email" value="<?php echo $user_email; ?>" required>
                            </div>
                        </div>
                        
                        <input type="hidden" name="organizer_department_id" value="<?php echo htmlspecialchars($user_department_id); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="organizer_department" class="form-label">Organizer Department</label>
                                <select class="form-select" name="organizer_department" id="organizer_department" required>
                                    <option value="">Select Department</option>
                                    <?php 
                                    $departments_result->data_seek(0);
                                    while ($row = $departments_result->fetch_assoc()) : 
                                        $is_user_dept = ($row['department_id'] == $user_department_id);
                                    ?>
                                        <option value="<?php echo htmlspecialchars($row['department_name']); ?>" 
                                               data-dept-id="<?php echo htmlspecialchars($row['department_id']); ?>"
                                               <?php if($is_user_dept) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($row['department_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <input type="hidden" name="selected_department_id" id="selected_department_id" value="<?php echo $user_department_id; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="organizer_contact" class="form-label">Organizer Contact</label>
                                <input type="text" class="form-control" name="organizer_contact" required>
                            </div>
                        </div>
                        
                        <div class="divider"></div>
                        
                        <h4 class="mb-3">Event Details</h4>
                        <div class="mb-3">
                            <label for="program_name" class="form-label">Event Name</label>
                            <input type="text" class="form-control" name="program_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Event Type</label>
                            <div class="event-type-container">
                                <div class="event-type-item" data-value="Conference">
                                    <div class="event-icon"><i class="fas fa-users"></i></div>
                                    <div>Conference</div>
                                </div>
                                <div class="event-type-item" data-value="Seminar">
                                    <div class="event-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                    <div>Seminar</div>
                                </div>
                                <div class="event-type-item" data-value="Freshers">
                                    <div class="event-icon"><i class="fas fa-graduation-cap"></i></div>
                                    <div>Freshers</div>
                                </div>
                                <div class="event-type-item" data-value="Farewell">
                                    <div class="event-icon"><i class="fas fa-award"></i></div>
                                    <div>Farewell</div>
                                </div>
                                <div class="event-type-item" data-value="Cultural">
                                    <div class="event-icon"><i class="fas fa-music"></i></div>
                                    <div>Cultural</div>
                                </div>
                            </div>
                            <input type="hidden" name="program_type" id="program_type" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="program_purpose" class="form-label">Purpose of the Event</label>
                            <textarea class="form-control" name="program_purpose" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="from_date" class="form-label">Event Date</label>
                                <input type="date" class="form-control" name="from_date" id="from_date" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" name="start_time" id="start_time" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" name="end_time" id="end_time" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duration_hours" class="form-label">Duration (hours)</label>
                            <input type="number" class="form-control" name="duration_hours" id="duration_hours" min="1" max="8" value="2" required>
                        </div>
                        
                        <div class="calculator mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Rate:</strong> ₹500 per hour (₹1000 for 2 hours)</p>
                                    <p><strong>Advance Payment:</strong> ₹5,000 (refundable)</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Hours:</strong> <span id="total_hours">2</span></p>
                                    <p class="calculator-result">Total Amount: ₹<span id="total_amount">1000</span></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="queries" class="form-label">Additional Requirements or Queries</label>
                            <textarea class="form-control" name="queries" rows="3"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg animate__animated animate__pulse animate__infinite animate__slower">Submit Booking Request</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="info-card">
                    <h3><i class="fas fa-info-circle me-2"></i> CCC Hall Information</h3>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>Pondicherry University Main Campus</div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-users"></i></div>
                        <div>Capacity: 500 seats</div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-rupee-sign"></i></div>
                        <div>Rate: ₹1000 for 2 hours</div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div>Advance: ₹5000 (refundable)</div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-clock"></i></div>
                        <div>Available: 8:00 AM - 8:00 PM</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-clipboard-list me-2"></i> Booking Policy</h3>
                    <ul class="ps-3">
                        <li class="mb-2">Advance payment of ₹5,000 is mandatory</li>
                        <li class="mb-2">Bookings must be made at least 7 days in advance</li>
                        <li class="mb-2">Cancellations must be made 48 hours before the event</li>
                        <li class="mb-2">Any damage to the hall will be charged from the advance</li>
                        <li class="mb-2">The hall must be vacated on time</li>
                        <li class="mb-2">Food and beverages are allowed only in designated areas</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('footer user.php'); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Event type selection
        document.querySelectorAll('.event-type-item').forEach(item => {
            item.addEventListener('click', function() {
                // Remove selected class from all items
                document.querySelectorAll('.event-type-item').forEach(el => {
                    el.classList.remove('selected');
                });
                
                // Add selected class to clicked item
                this.classList.add('selected');
                
                // Update hidden input
                document.getElementById('program_type').value = this.getAttribute('data-value');
            });
        });
        
        // Update department ID when selection changes
        document.getElementById('organizer_department').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var deptId = selectedOption.getAttribute('data-dept-id');
            document.getElementById('selected_department_id').value = deptId;
        });
        
        // Calculate duration and total amount
        function calculateAmount() {
            const durationHours = parseInt(document.getElementById('duration_hours').value) || 2;
            const totalAmount = durationHours * 500; // ₹500 per hour
            
            document.getElementById('total_hours').textContent = durationHours;
            document.getElementById('total_amount').textContent = totalAmount;
        }
        
        document.getElementById('duration_hours').addEventListener('input', calculateAmount);
        document.getElementById('start_time').addEventListener('change', function() {
            // Set minimum end time to be at least 1 hour after start time
            const startTime = this.value;
            if (startTime) {
                const [hours, minutes] = startTime.split(':');
                let endHours = parseInt(hours) + 1;
                if (endHours > 23) endHours = 23;
                const endTime = `${endHours.toString().padStart(2, '0')}:${minutes}`;
                document.getElementById('end_time').min = startTime;
                if (!document.getElementById('end_time').value) {
                    document.getElementById('end_time').value = endTime;
                }
            }
        });
        
        // Set minimum date to today
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const todayStr = `${yyyy}-${mm}-${dd}`;
        document.getElementById('from_date').min = todayStr;
        
        // Initialize calculations
        calculateAmount();
    </script>
</body>
</html>