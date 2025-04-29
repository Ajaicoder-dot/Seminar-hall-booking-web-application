<?php
session_start();
include('config.php'); // Include the database connection

// Ensure the user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Get user details
$user_email = $_SESSION['email'];
$user_role = $_SESSION['role'];

// Function to export attendance data to Excel/CSV
function exportAttendance($conn, $booking_id, $program_name) {
    // Table name for this booking's attendance
    $table_name = "attendance_" . $booking_id;
    
    // Check if the table exists
    $table_check = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($table_check->num_rows == 0) {
        return false; // Table doesn't exist yet
    }
    
    // Query to get attendance data
    $query = "SELECT name, email, phone, department, check_in_time FROM $table_name";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        // Clean filename - remove special characters
        $filename = preg_replace('/[^A-Za-z0-9_-]/', '', $program_name) . '_attendance.csv';
        
        // Set headers for file download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Create file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        
        // Write header row
        fputcsv($output, array('Name', 'Email', 'Phone', 'Department', 'Check-in Time'));
        
        // Write data rows
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        
        // Close the file pointer
        fclose($output);
        exit();
    }
    
    return false;
}

// Check if an export request was made
if (isset($_GET['export']) && isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];
    
    // Get program name for the file name
    $program_query = "SELECT program_name FROM hall_bookings WHERE id = ?";
    $stmt = $conn->prepare($program_query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if ($booking) {
        // Export the attendance data
        exportAttendance($conn, $booking_id, $booking['program_name']);
    } else {
        echo "<div class='alert alert-danger'>Booking not found.</div>";
    }
    
    $stmt->close();
}

// Fetch user's bookings (where they are the organizer)
$bookings_query = "SELECT b.id, b.program_name, b.from_date, b.end_date, h.hall_name, 
                  (SELECT COUNT(*) FROM attendance_" . $conn->real_escape_string($_GET['booking_id'] ?? 0) . " WHERE 1=1) as attendance_count
                  FROM hall_bookings b
                  JOIN halls h ON b.hall_id = h.hall_id
                  WHERE b.organizer_email = ? OR (b.user_id = ? AND ? = 'admin')
                  ORDER BY b.from_date DESC";

$bookings_stmt = $conn->prepare($bookings_query);
$bookings_stmt->bind_param("sis", $user_email, $_SESSION['user_id'] ?? 0, $user_role);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <title>Attendance Reports - Pondicherry University</title>
    <style>
        .dashboard-container {
            padding: 30px 0;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .no-bookings {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        .attendance-count {
            font-weight: bold;
            color: #28a745;
        }
        .attendance-count.zero {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include('navbar1.php'); // Include the navbar ?>
    
    <div class="container dashboard-container">
        <h1 class="mb-4">My Attendance Reports</h1>
        
        <?php if ($bookings_result->num_rows > 0): ?>
            <div class="row">
                <?php while($booking = $bookings_result->fetch_assoc()): 
                    // For each booking, check if attendance table exists
                    $table_name = "attendance_" . $booking['id'];
                    $table_check = $conn->query("SHOW TABLES LIKE '$table_name'");
                    $table_exists = ($table_check->num_rows > 0);
                    
                    // If table exists, get attendance count
                    $attendance_count = 0;
                    if ($table_exists) {
                        $count_query = "SELECT COUNT(*) as count FROM $table_name";
                        $count_result = $conn->query($count_query);
                        $count_data = $count_result->fetch_assoc();
                        $attendance_count = $count_data['count'];
                    }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <?php echo htmlspecialchars($booking['program_name']); ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($booking['hall_name']); ?></h5>
                            <p class="card-text">
                                <strong>Date:</strong> <?php echo htmlspecialchars($booking['from_date']); ?>
                                <?php if($booking['from_date'] != $booking['end_date']) echo " to " . htmlspecialchars($booking['end_date']); ?>
                            </p>
                            <p class="card-text">
                                <strong>Attendance:</strong> 
                                <span class="attendance-count <?php echo ($attendance_count == 0) ? 'zero' : ''; ?>">
                                    <?php echo $attendance_count; ?> people
                                </span>
                            </p>
                            <?php if ($table_exists && $attendance_count > 0): ?>
                                <a href="export_attendance.php?export=true&booking_id=<?php echo $booking['id']; ?>" class="btn btn-success">
                                    <i class="bi bi-file-excel"></i> Export to Excel
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="bi bi-file-excel"></i> No Attendance Data
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-bookings">
                <i class="bi bi-calendar-x" style="font-size: 3rem;"></i>
                <h4 class="mt-3">No bookings found</h4>
                <p>You haven't made any hall bookings yet.</p>
                <a href="halls.php" class="btn btn-primary">Book a Hall Now</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php include('footer user.php'); ?>
</html>