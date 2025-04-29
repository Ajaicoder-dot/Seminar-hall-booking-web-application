<?php
session_start();
include 'config.php'; // Database connection

// Ensure user is logged in
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="hall_bookings_export_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Handle filters if passed from the view page
$where_clause = "";
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

if (!empty($search_term)) {
    $search_term = $conn->real_escape_string($search_term);
    $where_clause .= " AND (h.hall_name LIKE '%$search_term%' OR 
                           hb.organizer_name LIKE '%$search_term%' OR 
                           hb.program_name LIKE '%$search_term%' OR
                           hb.organizer_department LIKE '%$search_term%')";
}

if (!empty($status_filter)) {
    $status_filter = $conn->real_escape_string($status_filter);
    $where_clause .= " AND hb.status = '$status_filter'";
}

if (!empty($date_filter)) {
    $date_filter = $conn->real_escape_string($date_filter);
    $where_clause .= " AND hb.from_date >= '$date_filter'";
}

// Fetch bookings with filters
$sql = "SELECT 
            hb.booking_id, 
            h.hall_name, 
            hb.organizer_name, 
            hb.organizer_email, 
            hb.organizer_department,
            hb.organizer_contact,
            hb.program_name, 
            hb.program_type,
            hb.program_purpose,
            hb.from_date, 
            hb.start_time, 
            hb.end_date, 
            hb.end_time, 
            hb.status,
            hb.reject_reason,
            hb.created_at
        FROM hall_bookings hb
        JOIN halls h ON hb.hall_id = h.hall_id
        WHERE 1=1 $where_clause
        ORDER BY hb.created_at DESC";

$result = $conn->query($sql);

// Start output buffering
ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hall Bookings Export</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Hall Bookings Export - <?php echo date('Y-m-d'); ?></h2>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Hall Name</th>
                <th>Organizer Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Contact</th>
                <th>Program Name</th>
                <th>Program Type</th>
                <th>Purpose</th>
                <th>From Date</th>
                <th>Start Time</th>
                <th>End Date</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Rejection Reason</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['booking_id']; ?></td>
                <td><?php echo $row['hall_name']; ?></td>
                <td><?php echo $row['organizer_name']; ?></td>
                <td><?php echo $row['organizer_email']; ?></td>
                <td><?php echo $row['organizer_department']; ?></td>
                <td><?php echo $row['organizer_contact']; ?></td>
                <td><?php echo $row['program_name']; ?></td>
                <td><?php echo $row['program_type']; ?></td>
                <td><?php echo $row['program_purpose']; ?></td>
                <td><?php echo $row['from_date']; ?></td>
                <td><?php echo $row['start_time']; ?></td>
                <td><?php echo $row['end_date']; ?></td>
                <td><?php echo $row['end_time']; ?></td>
                <td><?php echo $row['status']; ?></td>
                <td><?php echo $row['reject_reason']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>

<?php
// Output the buffer
echo ob_get_clean();
$conn->close();
?>