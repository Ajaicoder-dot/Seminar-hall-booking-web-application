<?php
session_start();
include('config.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location:login.php');
    exit();
}

// Handle booking status updates
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['new_status'];
    $reason = $_POST['reason'] ?? null;
    
    $update_query = "UPDATE ccc_hall_bookings SET 
        status = ?,
        reject_reason = CASE WHEN ? = 'Rejected' THEN ? ELSE reject_reason END,
        cancellation_reason = CASE WHEN ? = 'Cancelled' THEN ? ELSE cancellation_reason END
        WHERE booking_id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssssi", $new_status, $new_status, $reason, $new_status, $reason, $booking_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Handle payment status updates
if (isset($_POST['update_payment'])) {
    $booking_id = $_POST['booking_id'];
    $payment_status = $_POST['payment_status'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $receipt_number = $_POST['receipt_number'];
    
    // Update booking payment status
    $update_payment_query = "UPDATE ccc_hall_bookings SET payment_status = ? WHERE booking_id = ?";
    $update_payment_stmt = $conn->prepare($update_payment_query);
    $update_payment_stmt->bind_param("si", $payment_status, $booking_id);
    $update_payment_stmt->execute();
    
    // Insert payment record
    $payment_query = "INSERT INTO ccc_hall_payments (booking_id, amount, payment_type, payment_method, payment_date, receipt_number) 
                     VALUES (?, ?, ?, ?, NOW(), ?)";
    $payment_stmt = $conn->prepare($payment_query);
    $payment_type = ($payment_status == 'Fully Paid') ? 'Final' : 'Advance';
    $payment_stmt->bind_param("idsss", $booking_id, $amount, $payment_type, $payment_method, $receipt_number);
    $payment_stmt->execute();
}

// Fetch all bookings with user and department details
$bookings_query = "
    SELECT b.*, u.name as user_name, d.department_name,
           (SELECT SUM(amount) FROM ccc_hall_payments WHERE booking_id = b.booking_id) as paid_amount
    FROM ccc_hall_bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN departments d ON b.department_id = d.department_id
    ORDER BY b.created_at DESC";
$bookings_result = $conn->query($bookings_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCC Hall Bookings Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
        }
        h2 {
            color: #2c3e50;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }
        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: #3498db;
        }
        .table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .table thead th {
            background: #3498db;
            color: white;
            border: none;
            padding: 15px;
        }
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        .btn-sm {
            margin: 0 2px;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .badge {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        .modal-content {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .modal-header {
            background: #3498db;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .form-control, .form-select {
            border-radius: 6px;
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 6px;
            padding: 6px 10px;
            border: 1px solid #dee2e6;
        }
        .dataTables_wrapper .dataTables_length select {
            border-radius: 6px;
            padding: 6px;
            border: 1px solid #dee2e6;
        }
        .btn-primary {
            background: #3498db;
            border: none;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>
    
    <div class="container-fluid py-4">
        <h2 class="mb-4">CCC Hall Bookings Management</h2>
        
        <!-- Add this dashboard section -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Bookings</h5>
                        <h2 class="mb-0"><?php echo $bookings_result->num_rows; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pending Approvals</h5>
                        <h2 class="mb-0"><?php 
                            $pending_count = $conn->query("SELECT COUNT(*) as count FROM ccc_hall_bookings WHERE status='Pending'")->fetch_assoc()['count'];
                            echo $pending_count;
                        ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Approved</h5>
                        <h2 class="mb-0"><?php 
                            $approved_count = $conn->query("SELECT COUNT(*) as count FROM ccc_hall_bookings WHERE status='Approved'")->fetch_assoc()['count'];
                            echo $approved_count;
                        ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Today's Bookings</h5>
                        <h2 class="mb-0"><?php 
                            $today_count = $conn->query("SELECT COUNT(*) as count FROM ccc_hall_bookings WHERE DATE(from_date) = CURDATE()")->fetch_assoc()['count'];
                            echo $today_count;
                        ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="bookingsTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event</th>
                        <th>Organizer</th>
                        <th>Department</th>
                        <th>Date & Time</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $booking['booking_id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($booking['program_name']); ?></strong><br>
                            <small><?php echo htmlspecialchars($booking['program_type']); ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($booking['organizer_name']); ?><br>
                            <small><?php echo htmlspecialchars($booking['organizer_email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($booking['department_name']); ?></td>
                        <td>
                            <?php echo date('d M Y', strtotime($booking['from_date'])); ?><br>
                            <small><?php echo date('h:i A', strtotime($booking['start_time'])) . ' - ' . 
                                           date('h:i A', strtotime($booking['end_time'])); ?></small>
                        </td>
                        <td>
                            Total: ₹<?php echo number_format($booking['total_amount'], 2); ?><br>
                            <small>Paid: ₹<?php echo number_format($booking['paid_amount'] ?? 0, 2); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo match($booking['status']) {
                                    'Pending' => 'warning',
                                    'Approved' => 'success',
                                    'Rejected' => 'danger',
                                    'Cancelled' => 'secondary',
                                    'Completed' => 'info',
                                    default => 'primary'
                                };
                            ?>">
                                <?php echo $booking['status']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo match($booking['payment_status']) {
                                    'Not Paid' => 'danger',
                                    'Partially Paid' => 'warning',
                                    'Fully Paid' => 'success',
                                    'Refunded' => 'info',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo $booking['payment_status']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                    data-bs-target="#viewModal<?php echo $booking['booking_id']; ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                    data-bs-target="#statusModal<?php echo $booking['booking_id']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                    data-bs-target="#paymentModal<?php echo $booking['booking_id']; ?>">
                                <i class="fas fa-rupee-sign"></i>
                            </button>
                        </td>
                    </tr>
                    
                    <!-- View Modal -->
                    <div class="modal fade" id="viewModal<?php echo $booking['booking_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Booking Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Event Details</h6>
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['program_name']); ?></p>
                                            <p><strong>Type:</strong> <?php echo htmlspecialchars($booking['program_type']); ?></p>
                                            <p><strong>Purpose:</strong> <?php echo htmlspecialchars($booking['program_purpose']); ?></p>
                                            <p><strong>Duration:</strong> <?php echo $booking['duration_hours']; ?> hours</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Organizer Details</h6>
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['organizer_name']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['organizer_email']); ?></p>
                                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($booking['organizer_contact']); ?></p>
                                            <p><strong>Department:</strong> <?php echo htmlspecialchars($booking['department_name']); ?></p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h6>Additional Information</h6>
                                            <p><strong>Queries/Requirements:</strong> <?php echo htmlspecialchars($booking['queries']); ?></p>
                                            <?php if($booking['status'] == 'Rejected'): ?>
                                                <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($booking['reject_reason']); ?></p>
                                            <?php endif; ?>
                                            <?php if($booking['status'] == 'Cancelled'): ?>
                                                <p><strong>Cancellation Reason:</strong> <?php echo htmlspecialchars($booking['cancellation_reason']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Update Modal -->
                    <div class="modal fade" id="statusModal<?php echo $booking['booking_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Booking Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="new_status" class="form-select" required>
                                                <option value="Pending" <?php echo $booking['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Approved" <?php echo $booking['status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="Rejected" <?php echo $booking['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                <option value="Cancelled" <?php echo $booking['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="Completed" <?php echo $booking['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Reason (if rejecting/cancelling)</label>
                                            <textarea name="reason" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Modal -->
                    <div class="modal fade" id="paymentModal<?php echo $booking['booking_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Payment Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Status</label>
                                            <select name="payment_status" class="form-select" required>
                                                <option value="Not Paid">Not Paid</option>
                                                <option value="Partially Paid">Partially Paid</option>
                                                <option value="Fully Paid">Fully Paid</option>
                                                <option value="Refunded">Refunded</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Amount</label>
                                            <input type="number" name="amount" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Payment Method</label>
                                            <select name="payment_method" class="form-select" required>
                                                <option value="Cash">Cash</option>
                                                <option value="Card">Card</option>
                                                <option value="UPI">UPI</option>
                                                <option value="Bank Transfer">Bank Transfer</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Receipt Number</label>
                                            <input type="text" name="receipt_number" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="update_payment" class="btn btn-primary">Update Payment</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#bookingsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    search: "Search bookings:"
                },
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rtip',
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                responsive: true,
                stateSave: true,
                initComplete: function() {
                    $('.dataTables_filter input').addClass('form-control');
                    $('.dataTables_length select').addClass('form-select');
                }
            });
        });
    </script>
</body>
<?php include('footer user.php'); ?>
</html>