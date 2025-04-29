<?php
session_start();
include('config.php'); // Include the database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // This will autoload all classes including PHPMailer


// Ensure the user is logged in
if (!isset($_SESSION['email'])) {
    echo "No user is logged in.";
    exit();
}

if (!isset($_GET['hall_id'])) {
    echo $_SERVER['REQUEST_URI'];
    exit();
} else {
    $hall_id = $_GET['hall_id'];
}

// Fetch hall details from the database, including the in-charge email
$query = "SELECT h.hall_id, h.hall_name, h.capacity, h.image, d.department_name, ht.type_name AS hall_type, h.incharge_email 
          FROM halls h
          LEFT JOIN departments d ON h.department_id = d.department_id
          LEFT JOIN hall_type ht ON h.hall_type = ht.hall_type_id
          WHERE h.hall_name = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $hall_id);
$stmt->execute();
$result = $stmt->get_result();
$hall = $result->fetch_assoc();

if (!$hall) {
    echo "Hall not found.";
    exit();
}


$hall_id = $hall['hall_id']; // Get hall_id
$incharge_email = $hall['incharge_email']; // Get hall in-charge email

// Fetch user_id based on the logged-in user's email
$user_email = $_SESSION['email'];
$user_role = $_SESSION['role']; // Assuming role is stored in session during login

$user_query = "SELECT id FROM users WHERE email = ? AND role = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("ss", $user_email, $user_role);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    echo "User  not found.";
    exit();
}

$user_id = $user['id']; // Get user_id

// Fetch the user's department_id from the database instead of session
$dept_query = "SELECT department_id FROM users WHERE id = ?";
$dept_stmt = $conn->prepare($dept_query);
$dept_stmt->bind_param("i", $user_id);
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();
$dept_data = $dept_result->fetch_assoc();
$user_department_id = $dept_data['department_id']; // Get department_id
$dept_stmt->close();

// Fetch all departments for the organizer's department dropdown
$departments_query = "SELECT department_id, department_name FROM departments";
$departments_result = $conn->query($departments_query);

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organizer_name = $_POST['organizer_name'];
    $organizer_email = $_POST['organizer_email'];
    $organizer_department = $_POST['organizer_department'];
    $selected_department_id = $_POST['selected_department_id']; // Get the selected department ID
    $organizer_contact = $_POST['organizer_contact'];
    $program_name = $_POST['program_name'];
    $program_type = $_POST['program_type'];
    $program_purpose = $_POST['program_purpose'];
    $from_date = $_POST['from_date'];
    $end_date = $_POST['end_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $queries = $_POST['queries'];

    // Check if selected department matches user's department
    if ($selected_department_id != $user_department_id) {
        echo "<div class='alert alert-danger'>Error: You can only book halls for your own department.</div>";
    } else {
        // Check for availability
        $availability_query = "
            SELECT * FROM hall_bookings 
            WHERE hall_id = ? 
              AND (
                (from_date <= ? AND end_date >= ?) -- Overlapping dates
                AND (start_time <= ? AND end_time >= ?) -- Overlapping times
              )
        ";
        $availability_stmt = $conn->prepare($availability_query);
        $availability_stmt->bind_param("issss", $hall_id, $end_date, $from_date, $end_time, $start_time);
        $availability_stmt->execute();
        $availability_result = $availability_stmt->get_result();

        if ($availability_result->num_rows > 0) {
            echo "<p>The hall is not available for the selected dates and times. Please choose a different slot.</p>";
        } else {
            // Update the insert query to include department_id
            $insert_query = "INSERT INTO hall_bookings 
                (user_id, hall_id, organizer_name, organizer_email, organizer_department, department_id, organizer_contact, program_name, program_type, program_purpose, from_date, end_date, start_time, end_time, queries) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iisssssssssssss", $user_id, $hall_id, $organizer_name, $organizer_email, $organizer_department, $user_department_id, $organizer_contact, $program_name, $program_type, $program_purpose, $from_date, $end_date, $start_time, $end_time, $queries);
            
            if ($insert_stmt->execute()) {
                echo "<p>Booking successful!</p>";

                // Send email notifications
              
              
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
                    $mail->addAddress($incharge_email);   
                   // $mail->addAddress('ajai55620@gmail.com', 'Hall In-Charge'); // In-charge email                     // Add the hall in-charge's email

                    // Content
                    $mail->isHTML(true);                                      // Set email format to HTML
                    $mail->Subject = 'Hall Booking Confirmation';
                    $mail->Body    = "Dear $organizer_name,<br><br>Your booking for the hall <strong>{$hall['hall_name']}</strong> has been confirmed.<br>
                                      Program Name: $program_name<br>
                                      From: $from_date to $end_date<br>
                                      Time: $start_time to $end_time<br><br>
                                      Thank you for using our service!";

                    $mail->send();
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                echo "<p>Error: Could not complete the booking.</p>";
            }

            $insert_stmt->close();
        }

        $availability_stmt->close();
    }
} // This closing brace was missing

$stmt->close();
$user_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <title>Book Hall</title>
    </head>
    <style>
        
    </style>

    <body>
        <?php include('navbar1.php'); // Include the navbar ?>
        <div class="container mt-5">
            <h1 class="text-center">Book Hall: <?php echo htmlspecialchars($hall['hall_name']); ?></h1>
           
            <div class="card-body">
                <div class="row text-center"> <!-- Add the row and text-center classes -->
                    <div class="col">
                        <h5 class="card-title"><?php echo htmlspecialchars($hall['hall_name']); ?></h5>
                        <p class="card-text">Capacity: <?php echo htmlspecialchars($hall['capacity']); ?></p>
                        <p class="card-text">Department: <?php echo htmlspecialchars($hall['department_name']); ?></p>
                        <p class="card-text">Room Type: <?php echo htmlspecialchars($hall['hall_type']); ?></p>
                    </div>
                </div>
                    <form method="POST" action="">
                        <h4>Organizer Details</h4>
                        <div class="mb-3">
                            <label for="organizer_name" class="form-label">Organizer Name</label>
                            <input type="text" class="form-control" name="organizer_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="organizer_email" class="form-label">Organizer Email</label>
                            <input type="email" class="form-control" name="organizer_email" required>
                        </div>
                        <!-- Add hidden field for department_id from session -->
                        <input type="hidden" name="organizer_department_id" value="<?php echo htmlspecialchars($user_department_id); ?>">
                        <div class="mb-3">
                            <label for="organizer_department" class="form-label">Organizer Department</label>
                            <select class="form-control" name="organizer_department" id="organizer_department" required>
                            <option value="">Select Department</option>
                                <?php 
                                // Reset the result pointer to the beginning
                                $departments_result->data_seek(0);
                                while ($row = $departments_result->fetch_assoc()) : 
                                    $is_user_dept = ($row['department_id'] == $user_department_id);
                                ?>
                                    <option value="<?php echo htmlspecialchars($row['department_name']); ?>" 
                                           data-dept-id="<?php echo htmlspecialchars($row['department_id']); ?>"
                                           <?php if($is_user_dept) echo 'selected'; ?>
                                           <?php if(!$is_user_dept) echo 'disabled'; ?>>
                                        <?php echo htmlspecialchars($row['department_name']); ?>
                                        <?php if(!$is_user_dept) echo ' (Not your department)'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="hidden" name="selected_department_id" id="selected_department_id" value="<?php echo $user_department_id; ?>">
                        </div>
                        
                        <script>
                        // Update the hidden field when department selection changes
                        document.getElementById('organizer_department').addEventListener('change', function() {
                            var selectedOption = this.options[this.selectedIndex];
                            var deptId = selectedOption.getAttribute('data-dept-id');
                            document.getElementById('selected_department_id').value = deptId;
                        });
                        </script>
                        <div class="mb-3">
                            <label for="organizer_contact" class="form-label">Organizer Contact</label>
                            <input type="text" class="form-control" name="organizer_contact" required>
                        </div>
                        
                        <h4>Program Details</h4>
                        <div class="mb-3">
                            <label for="program_name" class="form-label">Name of the Program</label>
                            <input type="text" class="form-control" name="program_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="program_type" class="form-label">Program Type</label><br>
                            <input type="radio" name="program_type" value="Event" required> Event
                            <input type="radio" name="program_type" value="Class" required> Class
                            <input type="radio" name="program_type" value="Other" required> Other
                        </div>
                        <div class="mb-3">
                            <label for="program_purpose" class="form-label">Purpose of the Program</label>
                            <textarea class="form-control" name="program_purpose" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="from_date" class="form-label">From Date</label>
                            <input type="date" class="form-control" name="from_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" name="end_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="queries" class="form-label">Any Queries</label>
                            <textarea class="form-control" name="queries" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Book Hall</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php include('footer user.php'); ?>
</html>