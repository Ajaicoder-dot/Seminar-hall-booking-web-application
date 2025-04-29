<?php
session_start(); // Keep session_start at the very beginning
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location:login.php');
    exit();
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Determine which table to use
$table = "hall_bookings"; // Default table
if (isset($_GET['table']) && $_GET['table'] == 'ccc') {
    $table = "ccc_hall_bookings";
}

// Pagination settings
$results_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $results_per_page;

// Filtering
$where_clause = "WHERE 1=1";
$params = [];

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_clause .= " AND status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
    $dates = explode(" - ", $_GET['date_range']);
    if (count($dates) == 2) {
        $where_clause .= " AND from_date BETWEEN ? AND ?";
        $params[] = date('Y-m-d', strtotime($dates[0]));
        $params[] = date('Y-m-d', strtotime($dates[1]));
    }
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = "%" . $_GET['search'] . "%";
    $where_clause .= " AND (organizer_name LIKE ? OR program_name LIKE ? OR organizer_email LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Count total results for pagination
$count_sql = "SELECT COUNT(*) as total FROM $table $where_clause";
$stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_results = $row['total'];
$total_pages = ceil($total_results / $results_per_page);

// Get booking data
$sql = "SELECT b.*, h.hall_name 
        FROM $table b 
        LEFT JOIN halls h ON b.hall_id = h.hall_id 
        $where_clause 
        ORDER BY b.created_at DESC 
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);

// Add pagination parameters
$params[] = $offset;
$params[] = $results_per_page;

if (!empty($params)) {
    $types = str_repeat('s', count($params) - 2) . 'ii'; // All strings plus two integers for pagination
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);

// Get all statuses for filter dropdown
$status_query = "SELECT DISTINCT status FROM $table WHERE status IS NOT NULL";
$status_result = $conn->query($status_query);
$statuses = [];
while ($status_row = $status_result->fetch_assoc()) {
    $statuses[] = $status_row['status'];
}

// Process action requests (approve, reject, cancel)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    
    if ($action == 'approve') {
        $update_sql = "UPDATE $table SET status = 'Approved' WHERE booking_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('i', $booking_id);
        $stmt->execute();
    } elseif ($action == 'reject') {
        $update_sql = "UPDATE $table SET status = 'Rejected', reject_reason = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('si', $reason, $booking_id);
        $stmt->execute();
    } elseif ($action == 'cancel') {
        $update_sql = "UPDATE $table SET status = 'Cancelled', cancellation_reason = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('si', $reason, $booking_id);
        $stmt->execute();
    }
    
    // Redirect to refresh the page after action
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit();
}

// Close connection
$conn->close();

// Include navbar AFTER all potential header redirects
include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hall Bookings</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DateRangePicker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <!-- Custom styles -->
    <style>
        .container-fluid {
            background-color: #f0f5ff;
            min-height: 100vh;
            padding: 2rem;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #e4eff9 100%);
        }

        .booking-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            background: white;
            transform: translateZ(0);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .booking-card:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 5px;
        }

        .booking-pending::before { background: linear-gradient(to bottom, #ffc107, #ffdb4d); }
        .booking-approved::before { background: linear-gradient(to bottom, #28a745, #34ce57); }
        .booking-cancelled::before { background: linear-gradient(to bottom, #6c757d, #868e96); }
        .booking-rejected::before { background: linear-gradient(to bottom, #dc3545, #e4606d); }
        .booking-completed::before { background: linear-gradient(to bottom, #0d6efd, #3d8bfd); }

        .card-body {
            padding: 1.8rem;
        }

        .status-badge {
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .filter-section {
            background: white;
            padding: 1.8rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 2.5rem;
            border-top: 4px solid #0d6efd;
            animation: fadeIn 0.8s ease;
        }

        .btn-group .btn {
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            margin-right: 8px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-group .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .input-group {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .input-group:focus-within {
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.15);
            border-color: rgba(13, 110, 253, 0.3);
        }

        .input-group-text {
            background-color: white;
            border: none;
            padding-left: 1.2rem;
            color: #6c757d;
        }

        .form-control, .form-select {
            border: none;
            padding: 0.8rem 1.2rem;
            font-size: 0.95rem;
            background-color: white;
        }

        .form-control:focus, .form-select:focus {
            box-shadow: none;
            border-color: transparent;
        }

        .date-badge {
            background: #f8f9fa;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            font-size: 0.9rem;
            color: #495057;
            display: inline-block;
            margin-bottom: 0.8rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            font-weight: 500;
        }

        .booking-actions .btn {
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
            margin: 0.3rem;
            font-weight: 600;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .booking-actions .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .pagination {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-radius: 50px;
            padding: 0.5rem;
            background: white;
        }

        .page-link {
            border: none;
            padding: 0.6rem 1.2rem;
            margin: 0 0.3rem;
            border-radius: 30px !important;
            color: #495057;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .page-link:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .page-item.active .page-link {
            background-color: #0d6efd;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }

        .no-bookings {
            background: white;
            border-radius: 20px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.8s ease;
        }

        .no-bookings i {
            color: #c9d6e9;
            margin-bottom: 2rem;
            font-size: 5rem;
            animation: pulse 2s infinite;
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .modal-header {
            border-bottom: 1px solid #f1f1f1;
            padding: 1.8rem;
            background-color: #f8f9fa;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: 1px solid #f1f1f1;
            padding: 1.8rem;
        }

        h2 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 1.8rem;
            position: relative;
            padding-bottom: 0.8rem;
            letter-spacing: 0.5px;
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #0d6efd, #4dabf7);
            border-radius: 2px;
        }

        .booking-details p {
            margin-bottom: 0.9rem;
            padding-left: 0.8rem;
            border-left: 3px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .booking-details p:hover {
            border-left-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.03);
            padding-left: 1rem;
            border-radius: 0 5px 5px 0;
        }

        .booking-details p strong {
            color: #2d3748;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #4dabf7 100%);
            border: none;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0b5ed7 0%, #3d8bfd 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.4);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }

            .filter-section {
                padding: 1.5rem;
            }

            .booking-actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                margin-top: 1.2rem;
            }
            
            h2:after {
                width: 40px;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h2 class="mb-3">Manage Hall Bookings</h2>
                <div class="d-flex justify-content-between mb-3">
                    <div class="btn-group" role="group">
                        <a href="?table=hall" class="btn btn-outline-primary <?php echo $table == 'hall_bookings' ? 'active' : ''; ?>">
                            Regular Bookings
                        </a>
                        <a href="?table=ccc" class="btn btn-outline-primary <?php echo $table == 'ccc_hall_bookings' ? 'active' : ''; ?>">
                            CCC Bookings
                        </a>
                    </div>
                    <a href="add_booking.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Booking
                    </a>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" action="" class="row g-3">
                        <input type="hidden" name="table" value="<?php echo $table == 'ccc_hall_bookings' ? 'ccc' : 'hall'; ?>">
                        
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Search bookings..." 
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-filter"></i></span>
                                <select class="form-select" name="status">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo (isset($_GET['status']) && $_GET['status'] == $status) ? 'selected' : ''; ?>>
                                            <?php echo $status; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="text" class="form-control" id="date-range" name="date_range" 
                                       placeholder="Select date range" 
                                       value="<?php echo isset($_GET['date_range']) ? htmlspecialchars($_GET['date_range']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($bookings)): ?>
                    <div class="no-bookings">
                        <i class="fas fa-calendar-xmark fa-4x mb-3"></i>
                        <h4>No bookings found</h4>
                        <p class="text-muted">Try adjusting your filters or create a new booking</p>
                    </div>
                <?php else: ?>
                    <!-- Bookings Display -->
                    <div class="row">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="col-12 mb-3">
                                <div class="card booking-card booking-<?php echo strtolower($booking['status']); ?>">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h5 class="card-title mb-3"><?php echo htmlspecialchars($booking['program_name']); ?></h5>
                                                    <span class="badge <?php 
                                                        switch($booking['status']) {
                                                            case 'Pending': echo 'bg-warning'; break;
                                                            case 'Approved': echo 'bg-success'; break;
                                                            case 'Cancelled': echo 'bg-secondary'; break;
                                                            case 'Rejected': echo 'bg-danger'; break;
                                                            case 'Completed': echo 'bg-primary'; break;
                                                            default: echo 'bg-light text-dark';
                                                        }
                                                    ?> status-badge">
                                                        <?php echo $booking['status']; ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="booking-details">
                                                    <p class="mb-2"><strong>Hall:</strong> <?php echo htmlspecialchars($booking['hall_name'] ?? 'Unknown'); ?></p>
                                                    <p class="mb-2"><strong>Organizer:</strong> <?php echo htmlspecialchars($booking['organizer_name']); ?> 
                                                        <span class="text-muted">(<?php echo htmlspecialchars($booking['organizer_email']); ?>)</span>
                                                    </p>
                                                    <p class="mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($booking['organizer_department']); ?></p>
                                                    
                                                    <?php if ($table == 'ccc_hall_bookings' && isset($booking['payment_status'])): ?>
                                                        <p class="mb-2">
                                                            <strong>Payment:</strong> 
                                                            <span class="badge <?php 
                                                                switch($booking['payment_status']) {
                                                                    case 'Not Paid': echo 'bg-danger'; break;
                                                                    case 'Partially Paid': echo 'bg-warning'; break;
                                                                    case 'Fully Paid': echo 'bg-success'; break;
                                                                    case 'Refunded': echo 'bg-info'; break;
                                                                    default: echo 'bg-secondary';
                                                                }
                                                            ?>">
                                                                <?php echo $booking['payment_status']; ?>
                                                            </span>
                                                            <?php if (isset($booking['total_amount'])): ?>
                                                                <span class="ms-2">₹<?php echo number_format($booking['total_amount'], 2); ?></span>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="text-md-end mb-3">
                                                    <div class="date-badge mb-2">
                                                        <i class="fas fa-calendar-alt me-1"></i>
                                                        <?php 
                                                            $from_date = date('M d, Y', strtotime($booking['from_date']));
                                                            $end_date = date('M d, Y', strtotime($booking['end_date']));
                                                            echo $from_date;
                                                            if ($booking['from_date'] != $booking['end_date']) {
                                                                echo " - " . $end_date;
                                                            }
                                                        ?>
                                                    </div>
                                                    <div class="date-badge">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php 
                                                            echo date('h:i A', strtotime($booking['start_time']));
                                                            echo " - ";
                                                            echo date('h:i A', strtotime($booking['end_time']));
                                                        ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="booking-actions text-md-end">
                                                    <a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>&table=<?php echo $table == 'ccc_hall_bookings' ? 'ccc' : 'hall'; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    
                                                    <?php if ($booking['status'] == 'Pending'): ?>
                                                        <button type="button" class="btn btn-sm btn-success" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#approveModal<?php echo $booking['booking_id']; ?>">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                        
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#rejectModal<?php echo $booking['booking_id']; ?>">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (in_array($booking['status'], ['Pending', 'Approved'])): ?>
                                                        <button type="button" class="btn btn-sm btn-secondary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#cancelModal<?php echo $booking['booking_id']; ?>">
                                                            <i class="fas fa-ban"></i> Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Approve Modal -->
                            <div class="modal fade" id="approveModal<?php echo $booking['booking_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Approve Booking</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <p>Are you sure you want to approve this booking?</p>
                                                <p><strong>Program:</strong> <?php echo htmlspecialchars($booking['program_name']); ?></p>
                                                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($booking['from_date'])); ?></p>
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Approve</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal<?php echo $booking['booking_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reject Booking</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <p>Please provide a reason for rejecting this booking:</p>
                                                <textarea class="form-control" name="reason" rows="3" required></textarea>
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Reject</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Cancel Modal -->
                            <div class="modal fade" id="cancelModal<?php echo $booking['booking_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Cancel Booking</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <p>Please provide a reason for cancelling this booking:</p>
                                                <textarea class="form-control" name="reason" rows="3" required></textarea>
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-warning">Cancel Booking</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container mt-4">
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php
                                    $query_params = $_GET;
                                    
                                    // Previous page
                                    if ($page > 1) {
                                        $query_params['page'] = $page - 1;
                                        echo '<li class="page-item">
                                                <a class="page-link" href="?' . http_build_query($query_params) . '" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                              </li>';
                                    } else {
                                        echo '<li class="page-item disabled">
                                                <a class="page-link" href="#" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                              </li>';
                                    }
                                    
                                    // Page numbers
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        $query_params['page'] = 1;
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($query_params) . '">1</a></li>';
                                        if ($start_page > 2) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        $query_params['page'] = $i;
                                        echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                                <a class="page-link" href="?' . http_build_query($query_params) . '">' . $i . '</a>
                                              </li>';
                                    }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                        $query_params['page'] = $total_pages;
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($query_params) . '">' . $total_pages . '</a></li>';
                                    }
                                    
                                    // Next page
                                    if ($page < $total_pages) {
                                        $query_params['page'] = $page + 1;
                                        echo '<li class="page-item">
                                                <a class="page-link" href="?' . http_build_query($query_params) . '" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                              </li>';
                                    } else {
                                        echo '<li class="page-item disabled">
                                                <a class="page-link" href="#" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                              </li>';
                                    }
                                    ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap and jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Moment.js -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <!-- DateRangePicker JS -->
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize date range picker
            $('#date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });
            
            $('#date-range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
            });
            
            $('#date-range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</body>
<?php 
include 'footer user.php';
?>  
</html>