<?php
session_start();
include 'config.php'; // Database connection
include 'navbar.php';

// Ensure user is logged in
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Get the department ID of the logged-in HOD
$hod_department_id = null;
if ($_SESSION['role'] == 'HOD' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $dept_query = "SELECT department_id FROM users WHERE id = '$user_id'";
    $dept_result = $conn->query($dept_query);
    if ($dept_result && $dept_result->num_rows > 0) {
        $dept_row = $dept_result->fetch_assoc();
        $hod_department_id = $dept_row['department_id'];
    }
}

// Handle search and filter
$where_clause = "";

// If user is HOD, only show bookings from their department or forwarded to their department
if ($_SESSION['role'] == 'HOD' && $hod_department_id) {
    $where_clause .= " AND (hb.user_id IN (SELECT id FROM users WHERE department_id = '$hod_department_id') 
                         OR (hb.forwarded = 1 AND hb.forwarded_to = '$hod_department_id'))";
}

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

// Fetch all bookings with filters
$sql = "SELECT 
            hb.booking_id, 
            h.hall_name, 
            h.department_id as hall_department_id,
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
            hb.created_at,
            hb.user_id,
            hb.forwarded,
            hb.forwarded_to,
            hb.forwarded_by_id
        FROM hall_bookings hb
        JOIN halls h ON hb.hall_id = h.hall_id
        WHERE 1=1 $where_clause
        ORDER BY hb.created_at DESC";

$result = $conn->query($sql);

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
              FROM hall_bookings";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1400px;
            margin: auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #4a6fdc;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        .stats-card {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .table th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        .btn-action {
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.85rem;
            margin: 2px;
        }
        .badge {
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .modal-content {
            border-radius: 10px;
        }
        .search-box {
            border-radius: 20px;
            padding: 8px 15px;
        }
        .nav-tabs .nav-link {
            border-radius: 5px 5px 0 0;
            padding: 10px 20px;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .booking-details {
            font-size: 0.9rem;
        }
        .booking-details strong {
            color: #4a6fdc;
        }
        .export-btn {
            border-radius: 20px;
            padding: 8px 20px;
        }
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            text-align: center;
            line-height: 40px;
            background-color: #4a6fdc;
            color: white;
            cursor: pointer;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Hall Booking Management</h2>
                <div>
                    <a href="dashboard.php" class="btn btn-light btn-sm me-2"><i class="fas fa-home me-1"></i>Dashboard</a>
                    <a href="export_bookings.php" class="btn btn-success btn-sm export-btn"><i class="fas fa-file-excel me-1"></i>Export to Excel</a>
                </div>
            </div>
            <div class="card-body">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card bg-primary text-white">
                            <div class="card-body text-center">
                                <h5><i class="fas fa-calendar-alt me-2"></i>Total Bookings</h5>
                                <h2><?php echo $stats['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-warning text-white">
                            <div class="card-body text-center">
                                <h5><i class="fas fa-clock me-2"></i>Pending</h5>
                                <h2><?php echo $stats['pending']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-success text-white">
                            <div class="card-body text-center">
                                <h5><i class="fas fa-check-circle me-2"></i>Approved</h5>
                                <h2><?php echo $stats['approved']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-danger text-white">
                            <div class="card-body text-center">
                                <h5><i class="fas fa-times-circle me-2"></i>Rejected</h5>
                                <h2><?php echo $stats['rejected']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control search-box" name="search" placeholder="Search by hall, organizer, program..." value="<?php echo htmlspecialchars($search_term); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="status_filter" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" name="date_filter" value="<?php echo $date_filter; ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Hall</th>
                                <th>Organizer Details</th>
                                <th>Program Details</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sno = 1; // Serial number
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()): 
                                    $status_class = '';
                                    switch($row['status']) {
                                        case 'Pending': $status_class = 'bg-warning'; break;
                                        case 'Approved': $status_class = 'bg-success'; break;
                                        case 'Rejected': $status_class = 'bg-danger'; break;
                                    }
                            ?>
                            <tr>
                                <td><?php echo $sno++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['hall_name']); ?></strong></td>
                                <td class="booking-details">
                                    <strong><i class="fas fa-user me-1"></i>Name:</strong> <?php echo htmlspecialchars($row['organizer_name']); ?><br>
                                    <strong><i class="fas fa-envelope me-1"></i>Email:</strong> <?php echo htmlspecialchars($row['organizer_email']); ?><br>
                                    <strong><i class="fas fa-building me-1"></i>Department:</strong> <?php echo htmlspecialchars($row['organizer_department']); ?><br>
                                    <strong><i class="fas fa-phone me-1"></i>Contact:</strong> <?php echo htmlspecialchars($row['organizer_contact']); ?>
                                </td>
                                <td class="booking-details">
                                    <strong><i class="fas fa-calendar-day me-1"></i>Program:</strong> <?php echo htmlspecialchars($row['program_name']); ?><br>
                                    <strong><i class="fas fa-tag me-1"></i>Type:</strong> <?php echo htmlspecialchars($row['program_type']); ?><br>
                                    <strong><i class="fas fa-info-circle me-1"></i>Purpose:</strong> <?php echo htmlspecialchars($row['program_purpose']); ?>
                                </td>
                                <td class="booking-details">
                                    <strong><i class="fas fa-calendar me-1"></i>From:</strong> <?php echo date('d M Y', strtotime($row['from_date'])); ?><br>
                                    <strong><i class="fas fa-clock me-1"></i>Time:</strong> <?php echo date('h:i A', strtotime($row['start_time'])); ?><br>
                                    <strong><i class="fas fa-calendar-check me-1"></i>To:</strong> <?php echo date('d M Y', strtotime($row['end_date'])); ?><br>
                                    <strong><i class="fas fa-clock me-1"></i>Time:</strong> <?php echo date('h:i A', strtotime($row['end_time'])); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo $row['status']; ?></span>
                                    <?php if ($row['status'] == 'Rejected' && !empty($row['reject_reason'])): ?>
                                        <br><small class="text-danger mt-1"><i class="fas fa-info-circle me-1"></i><?php echo htmlspecialchars($row['reject_reason']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($row['forwarded'] == 1): ?>
                                        <?php 
                                        // Get department name and HOD info for the forwarded_to department
                                        $dept_id = $row['forwarded_to'];
                                        $dept_query = "SELECT d.department_name, u.name as hod_name 
                                                      FROM departments d 
                                                      LEFT JOIN users u ON d.department_id = u.department_id AND u.role = 'HOD' 
                                                      WHERE d.department_id = '$dept_id'";
                                        $dept_result = $conn->query($dept_query);
                                        $dept_name = "Unknown Department";
                                        $hod_name = "Unknown HOD";
                                        if ($dept_result && $dept_result->num_rows > 0) {
                                            $dept_row = $dept_result->fetch_assoc();
                                            $dept_name = $dept_row['department_name'];
                                            if (!empty($dept_row['hod_name'])) {
                                                $hod_name = $dept_row['hod_name'];
                                            }
                                        }
                                        
                                        // Get the name of the HOD who forwarded the booking
                                        if (!empty($row['forwarded_by_id'])) {
                                            $forwarded_by_query = "SELECT u.name as forwarded_by_name, d.department_name as forwarded_by_dept
                                                                  FROM users u
                                                                  JOIN departments d ON u.department_id = d.department_id
                                                                  WHERE u.id = '{$row['forwarded_by_id']}'";
                                            $forwarded_by_result = $conn->query($forwarded_by_query);
                                            $forwarded_by_name = "Unknown";
                                            $forwarded_by_dept = "Unknown Department";
                                            if ($forwarded_by_result && $forwarded_by_result->num_rows > 0) {
                                                $forwarded_by_row = $forwarded_by_result->fetch_assoc();
                                                $forwarded_by_name = $forwarded_by_row['forwarded_by_name'];
                                                $forwarded_by_dept = $forwarded_by_row['forwarded_by_dept'];
                                            }
                                        ?>
                                            <br><small class="text-primary mt-1"><i class="fas fa-forward me-1"></i>Forwarded by: <?php echo htmlspecialchars($forwarded_by_name); ?></small>
                                            <br><small class="text-secondary"><i class="fas fa-building me-1"></i>From: <?php echo htmlspecialchars($forwarded_by_dept); ?></small>
                                        <?php } ?>
                                        <br><small class="text-primary mt-1"><i class="fas fa-arrow-right me-1"></i>To: <?php echo htmlspecialchars($dept_name); ?></small>
                                        <br><small class="text-secondary"><i class="fas fa-user-tie me-1"></i>HOD: <?php echo htmlspecialchars($hod_name); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <?php if ($row['status'] == 'Pending'): ?>
                                            <?php if ($_SESSION['role'] == 'HOD' && $hod_department_id != $row['hall_department_id'] && $row['forwarded'] == 0): ?>
                                                <form method="POST" action="forward_booking.php" class="mb-1">
                                                    <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                                    <button type="submit" name="forward" class="btn btn-primary btn-sm btn-action w-100">
                                                        <i class="fas fa-forward me-1"></i>Forward to Hall Dept
                                                    </button>
                                                </form>
                                            <?php elseif ($_SESSION['role'] == 'HOD' && $row['forwarded'] == 1 && $row['forwarded_to'] == $hod_department_id): ?>
                                                <!-- This booking was forwarded to this HOD's department -->
                                                <form method="POST" action="update_status.php" class="mb-1">
                                                    <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                                    <button type="submit" name="status" value="Approved" class="btn btn-success btn-sm btn-action w-100">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-danger btn-sm btn-action" onclick="showRejectModal(<?php echo $row['booking_id']; ?>)">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            <?php else: ?>
                                                <form method="POST" action="update_status.php" class="mb-1">
                                                    <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                                    <button type="submit" name="status" value="Approved" class="btn btn-success btn-sm btn-action w-100">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-danger btn-sm btn-action" onclick="showRejectModal(<?php echo $row['booking_id']; ?>)">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-info btn-sm btn-action" onclick="viewDetails(<?php echo $row['booking_id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </button>
                                            <?php if ($row['status'] == 'Approved'): ?>
                                                <a href="generate_permit.php?id=<?php echo $row['booking_id']; ?>" class="btn btn-primary btn-sm btn-action mt-1">
                                                    <i class="fas fa-file-pdf me-1"></i>Generate Permit
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            } else {
                            ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>No bookings found matching your criteria
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Reject Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="update_status.php" id="rejectForm">
                        <input type="hidden" id="reject_booking_id" name="booking_id">
                        <div class="mb-3">
                            <label for="reject_reason" class="form-label">Reason for Rejection:</label>
                            <textarea class="form-control" id="reject_reason" name="reject_reason" rows="4" required placeholder="Please provide a detailed reason for rejection"></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="status" value="Rejected" class="btn btn-danger">
                                <i class="fas fa-times-circle me-1"></i>Reject Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bookingDetailsContent">
                    <!-- Content will be loaded dynamically -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to top button -->
    <a id="back-to-top" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap components
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        const viewDetailsModal = new bootstrap.Modal(document.getElementById('viewDetailsModal'));

        function showRejectModal(bookingId) {
            document.getElementById("reject_booking_id").value = bookingId;
            rejectModal.show();
        }

        function viewDetails(bookingId) {
            // In a real implementation, you would fetch details via AJAX
            // For now, we'll just show a placeholder
            document.getElementById('bookingDetailsContent').innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Loading details for booking #${bookingId}...
                </div>
                <p>In a complete implementation, this would show detailed information about the booking 
                including all fields from the database, history of status changes, and any additional notes.</p>
            `;
            viewDetailsModal.show();
        }

        // Back to top button functionality
        const backToTopButton = document.getElementById("back-to-top");
        
        window.onscroll = function() {
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                backToTopButton.style.display = "block";
            } else {
                backToTopButton.style.display = "none";
            }
        };
        
        backToTopButton.addEventListener("click", function() {
            document.body.scrollTop = 0; // For Safari
            document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
        });
    </script>
</body>
<?php include 'footer user.php';?> 
</html>

<?php $conn->close(); ?>
