<?php
session_start();
include 'config.php'; // Ensure you have a DB connection file
include 'navbar1.php';

// Check if the user is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'Professor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Assuming user ID is stored in session

// Default to regular hall bookings
$booking_type = isset($_GET['type']) ? $_GET['type'] : 'regular';

// SQL query to fetch booking details based on booking type
if ($booking_type == 'ccc') {
    // CCC Hall bookings query
    $sql = "SELECT 
                b.booking_id, 
                'CCC Hall' as hall_name, 
                b.organizer_name, 
                b.organizer_email, 
                b.organizer_department, 
                b.organizer_contact, 
                b.program_name, 
                b.program_type, 
                b.program_purpose, 
                b.from_date, 
                b.end_date, 
                b.start_time, 
                b.end_time, 
                b.reject_reason,
                b.status,
                b.payment_status,
                b.advance_payment,
                b.total_amount,
                COALESCE(p.amount_paid, 0) as amount_paid
            FROM ccc_hall_bookings b
            LEFT JOIN (
                SELECT booking_id, SUM(amount) as amount_paid 
                FROM ccc_hall_payments 
                WHERE payment_type IN ('Advance', 'Final')
                GROUP BY booking_id
            ) p ON b.booking_id = p.booking_id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC";
} else {
    // Regular hall bookings query
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
                hb.end_date, 
                hb.start_time, 
                hb.end_time, 
                hb.reject_reason,
                hb.status
            FROM hall_bookings hb
            JOIN halls h ON hb.hall_id = h.hall_id
            WHERE hb.user_id = ?
            ORDER BY hb.created_at DESC";
}

$stmt = $conn->prepare($sql);

// Check if the statement was prepared successfully
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Count various status types for dashboard
$total_bookings = 0;
$approved_bookings = 0;
$pending_bookings = 0;
$rejected_bookings = 0;

// Clone the result set by fetching all results into an array
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
    $total_bookings++;
    
    if ($row['status'] == 'Approved') {
        $approved_bookings++;
    } elseif ($row['status'] == 'Pending') {
        $pending_bookings++;
    } elseif ($row['status'] == 'Rejected') {
        $rejected_bookings++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        /* Add booking type switch styles */
        .booking-type-switch {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 15px;
        }
        
        .type-btn {
            padding: 12px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            background: #fff;
            color: #333;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .type-btn.active {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            box-shadow: 0 4px 15px rgba(106, 17, 203, 0.3);
        }
        
        .type-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .container {
            width: 95%;
            margin: 0 auto;
            padding: 20px 0;
        }
        
        h2 {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 30px;
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            padding: 10px 0;
        }
        
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            margin: 10px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.total {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            color: white;
        }
        
        .stat-card.approved {
            background: linear-gradient(45deg, #0ba360, #3cba92);
            color: white;
        }
        
        .stat-card.pending {
            background: linear-gradient(45deg, #f6d365, #fda085);
            color: white;
        }
        
        .stat-card.rejected {
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            color: white;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .stat-title {
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .filter-controls {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-box {
            padding: 10px 15px;
            border-radius: 25px;
            border: 1px solid #ddd;
            width: 300px;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .filter-buttons button {
            background: #fff;
            border: 1px solid #ddd;
            padding: 8px 15px;
            margin-left: 5px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-buttons button:hover, .filter-buttons button.active {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            border-color: transparent;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: linear-gradient(45deg, #f2f2f2, #e6e6e6);
            color: #333;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .download-btn {
            display: inline-block;
            padding: 8px 15px;
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .download-all-btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(106, 17, 203, 0.3);
        }
        
        .download-all-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(106, 17, 203, 0.4);
        }
        
        .checkbox-column {
            width: 50px;
            text-align: center;
        }
        
        .select-all-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-container {
            display: inline-block;
            position: relative;
            padding-left: 30px;
            cursor: pointer;
            font-size: 16px;
            user-select: none;
            margin-right: 20px;
        }
        
        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: #eee;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .checkbox-container:hover input ~ .checkmark {
            background-color: #ccc;
        }
        
        .checkbox-container input:checked ~ .checkmark {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }
        
        .checkbox-container .checkmark:after {
            left: 7px;
            top: 3px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .cancel-btn {
            display: inline-block;
            padding: 8px 15px;
            background: #f8d7da;
            color: #721c24;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .cancel-btn:hover {
            background: #e74c3c;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        /* Toast notification style */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .stat-card {
                min-width: 150px;
            }
            
            .filter-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .filter-buttons {
                display: flex;
                overflow-x: auto;
                width: 100%;
                padding-bottom: 10px;
            }
        }
        
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            
            th, td {
                min-width: 200px;
            }
        }
        
        /* Animation for new bookings */
        @keyframes highlight {
            0% { background-color: rgba(106, 17, 203, 0.1); }
            100% { background-color: transparent; }
        }
        
        .new-booking {
            animation: highlight 2s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-calendar-check"></i> My Hall Bookings</h2>
        
        <!-- Booking Type Switch -->
        <div class="booking-type-switch">
            <a href="?type=regular" class="type-btn <?php echo $booking_type == 'regular' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i> Regular Halls
            </a>
            <a href="?type=ccc" class="type-btn <?php echo $booking_type == 'ccc' ? 'active' : ''; ?>">
                <i class="fas fa-landmark"></i> CCC Hall
            </a>
        </div>
        
        <!-- Statistics Dashboard -->
        <div class="stats-container">
            <div class="stat-card total">
                <i class="fas fa-clipboard-list fa-2x"></i>
                <div class="stat-number"><?php echo $total_bookings; ?></div>
                <div class="stat-title">Total Bookings</div>
            </div>
            <div class="stat-card approved">
                <i class="fas fa-check-circle fa-2x"></i>
                <div class="stat-number"><?php echo $approved_bookings; ?></div>
                <div class="stat-title">Approved</div>
            </div>
            <div class="stat-card pending">
                <i class="fas fa-clock fa-2x"></i>
                <div class="stat-number"><?php echo $pending_bookings; ?></div>
                <div class="stat-title">Pending</div>
            </div>
            <div class="stat-card rejected">
                <i class="fas fa-times-circle fa-2x"></i>
                <div class="stat-number"><?php echo $rejected_bookings; ?></div>
                <div class="stat-title">Rejected</div>
            </div>
        </div>
        
        <!-- Filter Controls -->
        <div class="filter-controls">
            <input type="text" class="search-box" id="searchBox" placeholder="Search bookings...">
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="Approved">Approved</button>
                <button class="filter-btn" data-filter="Pending">Pending</button>
                <button class="filter-btn" data-filter="Rejected">Rejected</button>
            </div>
        </div>
        
        <?php if (count($bookings) > 0): ?>
        
        <!-- Batch download section -->
        <div class="select-all-container">
            <label class="checkbox-container">Select All
                <input type="checkbox" id="selectAll">
                <span class="checkmark"></span>
            </label>
            
            <a href="#" id="downloadSelected" class="download-all-btn">
                <i class="fas fa-download"></i> Download Selected
            </a>
        </div>
            
        <form id="batchDownloadForm" method="post" action="batch_download.php">
            <input type="hidden" name="booking_type" value="<?php echo $booking_type; ?>">
            <table id="bookingsTable">
                <tr>
                    <th class="checkbox-column">
                        <i class="fas fa-check-square"></i>
                    </th>
                    <th>S. No.</th>
                    <th>Hall Name</th>
                    <th>Organizer Details</th>
                    <th>Program Details</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php 
                $sno = 1; // Initialize Serial Number
                foreach ($bookings as $row): 
                    // Determine if this is a new booking (less than 24 hours old)
                    $is_new = false; // You would need to have a created_at timestamp and compare
                ?>
                <tr class="booking-row <?php echo $is_new ? 'new-booking' : ''; ?>" data-status="<?php echo $row['status']; ?>">
                    <td class="checkbox-column">
                        <label class="checkbox-container">
                            <input type="checkbox" name="selected_bookings[]" value="<?php echo $row['booking_id']; ?>" class="booking-checkbox">
                            <span class="checkmark"></span>
                        </label>
                    </td>
                    <td><?php echo $sno++; ?></td>
                    <td><?php echo htmlspecialchars($row['hall_name']); ?></td>
                    <td>
                        <strong>Name:</strong> <?php echo htmlspecialchars($row['organizer_name']); ?><br>
                        <strong>Email:</strong> <?php echo htmlspecialchars($row['organizer_email']); ?><br>
                        <strong>Department:</strong> <?php echo htmlspecialchars($row['organizer_department']); ?><br>
                        <strong>Contact:</strong> <?php echo htmlspecialchars($row['organizer_contact']); ?>
                    </td>
                    <td>
                        <strong>Program Name:</strong> <?php echo htmlspecialchars($row['program_name']); ?><br>
                        <strong>Type:</strong> <?php echo htmlspecialchars($row['program_type']); ?><br>
                        <strong>Purpose:</strong> <?php echo htmlspecialchars($row['program_purpose']); ?>
                    </td>
                    <td>
                        <strong>From:</strong> <?php echo date('d M Y', strtotime($row['from_date'])); ?> <br><?php echo date('h:i A', strtotime($row['start_time'])); ?><br>
                        <strong>To:</strong> <?php echo date('d M Y', strtotime($row['end_date'])); ?> <br><?php echo date('h:i A', strtotime($row['end_time'])); ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                            <?php echo $row['status']; ?>
                        </span>
                        <?php if ($row['status'] == 'Rejected' && !empty($row['reject_reason'])): ?>
                            <br><br><strong>Reason:</strong> <?php echo htmlspecialchars($row['reject_reason']); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo $booking_type == 'ccc' ? 'download_ccc_pdf.php' : 'download_booking_pdf.php'; ?>?id=<?php echo $row['booking_id']; ?>" class="download-btn">
                            <i class="fas fa-download"></i> Download
                        </a>
                        
                        <?php if ($row['status'] == 'Approved'): ?>
                        <a href="<?php echo $booking_type == 'ccc' ? 'generate_ccc_permit.php' : 'generate_permit.php'; ?>?id=<?php echo $row['booking_id']; ?>" class="download-btn" style="background: linear-gradient(45deg, #0ba360, #3cba92); margin-top: 5px;">
                            <i class="fas fa-file-certificate"></i> Generate Permit
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($row['status'] == 'Pending'): ?>
                            <a href="<?php echo $booking_type == 'ccc' ? 'cancel_ccc_booking.php' : 'cancel_booking.php'; ?>?id=<?php echo $row['booking_id']; ?>" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            
                            <?php if ($booking_type == 'ccc'): ?>
                                <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                    <strong>Payment Status:</strong> 
                                    <span class="status-badge" style="background: <?php 
                                        echo $row['payment_status'] == 'Fully Paid' ? '#d4edda' : 
                                            ($row['payment_status'] == 'Partially Paid' ? '#fff3cd' : '#f8d7da'); 
                                    ?>">
                                        <?php echo $row['payment_status']; ?>
                                    </span><br>
                                    <strong>Advance Payment:</strong> ₹<?php echo number_format($row['advance_payment'], 2); ?><br>
                                    <strong>Total Amount:</strong> ₹<?php echo number_format($row['total_amount'], 2); ?><br>
                                    <strong>Amount Paid:</strong> ₹<?php echo number_format($row['amount_paid'], 2); ?><br>
                                    
                                    <?php if ($row['payment_status'] != 'Fully Paid'): ?>
                                        <a href="make_payment.php?id=<?php echo $row['booking_id']; ?>" class="download-btn" style="background: linear-gradient(45deg, #2ecc71, #27ae60); margin-top: 10px;">
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </form>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
            <h3>No Bookings Found</h3>
            <p>You haven't made any hall bookings yet. Start by booking a hall for your event.</p>
            <a href="book_hall.php" class="download-btn">
                <i class="fas fa-plus"></i> Book a Hall
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Toast notification -->
    <div id="toast" class="toast"></div>
    
    <script>
        // Search functionality
        document.getElementById('searchBox').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.booking-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if(text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const rows = document.querySelectorAll('.booking-row');
                
                rows.forEach(row => {
                    if(filter === 'all') {
                        row.style.display = '';
                    } else {
                        const status = row.getAttribute('data-status');
                        if(status === filter) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
            });
        });
        
        // Select All functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.booking-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Download Selected functionality
        document.getElementById('downloadSelected').addEventListener('click', function(e) {
            e.preventDefault();
            
            const checkboxes = document.querySelectorAll('.booking-checkbox:checked');
            
            if (checkboxes.length === 0) {
                showToast('Please select at least one booking to download');
                return;
            }
            
            // Submit the form to process the batch download
            document.getElementById('batchDownloadForm').submit();
        });
        
        // Function to show toast notification
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
<?php include('footer user.php'); ?>
</html>

<?php
$stmt->close();
$conn->close();
?>