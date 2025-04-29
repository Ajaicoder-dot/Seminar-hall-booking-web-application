<?php
session_start();
require_once 'config.php';

// Check if user is logged in and has appropriate role (Admin or HOD)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'HOD')) {
    header("Location: login.php");
    exit();
}

// Get booking statistics
$bookingStats = [];

// Total bookings
$totalBookingsQuery = "SELECT COUNT(*) as total FROM hall_bookings";
$totalBookingsResult = $conn->query($totalBookingsQuery);
$bookingStats['total'] = $totalBookingsResult->fetch_assoc()['total'];

// Pending bookings
$pendingBookingsQuery = "SELECT COUNT(*) as pending FROM hall_bookings WHERE status = 'Pending'";
$pendingBookingsResult = $conn->query($pendingBookingsQuery);
$bookingStats['pending'] = $pendingBookingsResult->fetch_assoc()['pending'];

// Approved bookings
$approvedBookingsQuery = "SELECT COUNT(*) as approved FROM hall_bookings WHERE status = 'Approved'";
$approvedBookingsResult = $conn->query($approvedBookingsQuery);
$bookingStats['approved'] = $approvedBookingsResult->fetch_assoc()['approved'];

// Rejected bookings
$rejectedBookingsQuery = "SELECT COUNT(*) as rejected FROM hall_bookings WHERE status = 'Rejected'";
$rejectedBookingsResult = $conn->query($rejectedBookingsQuery);
$bookingStats['rejected'] = $rejectedBookingsResult->fetch_assoc()['rejected'];

// Cancelled bookings
$cancelledBookingsQuery = "SELECT COUNT(*) as cancelled FROM hall_bookings WHERE status = 'Cancelled'";
$cancelledBookingsResult = $conn->query($cancelledBookingsQuery);
$bookingStats['cancelled'] = $cancelledBookingsResult->fetch_assoc()['cancelled'];

// Get total users
$totalUsersQuery = "SELECT COUNT(*) as total_users FROM users";
$totalUsersResult = $conn->query($totalUsersQuery);
$totalUsers = $totalUsersResult->fetch_assoc()['total_users'];

// Get user statistics by role
$userRoleQuery = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$userRoleResult = $conn->query($userRoleQuery);
$userRoleStats = [];
while ($row = $userRoleResult->fetch_assoc()) {
    $userRoleStats[$row['role']] = $row['count'];
}

// Get recent bookings
$recentBookingsQuery = "
    SELECT hb.booking_id, hb.organizer_name, h.hall_name, hb.from_date, hb.status, hb.created_at
    FROM hall_bookings hb
    JOIN halls h ON hb.hall_id = h.hall_id
    ORDER BY hb.created_at DESC
    LIMIT 5
";
$recentBookingsResult = $conn->query($recentBookingsQuery);
$recentBookings = [];
while ($row = $recentBookingsResult->fetch_assoc()) {
    $recentBookings[] = $row;
}

// Include the navbar
include('navbar.php');
?>

<!-- Modern Dashboard Styling -->
<style>
    :root {
        --primary-color: #7e3ff2;
        --primary-light: #a97fff;
        --primary-dark: #5c2cb5;
        --secondary-color: #2c3e50;
        --accent-color: #8057db;
        --success-color: #2ecc71;
        --warning-color: #f39c12;
        --danger-color: #e74c3c;
        --info-color: #3498db;
        --light-bg: #f9fafc;
        --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        --hover-shadow: 0 14px 28px rgba(0, 0, 0, 0.15);
        --gradient-primary: linear-gradient(135deg, #7f43f8 0%, #5925b5 100%);
        --gradient-success: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        --gradient-warning: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        --gradient-danger: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        --gradient-info: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }
    
    body {
        background-color: var(--light-bg);
        font-family: 'Poppins', 'Segoe UI', Roboto, sans-serif;
    }
    
    .admin-dashboard {
        padding: 30px 20px;
        min-height: calc(100vh - 200px);
    }
    
    /* Dashboard Header */
    .dashboard-header {
        margin-bottom: 2.5rem;
        position: relative;
        padding-bottom: 15px;
    }
    
    .dashboard-header h1 {
        font-weight: 700;
        color: var(--secondary-color);
        margin-bottom: 0.5rem;
        font-size: 2.2rem;
        position: relative;
        display: inline-block;
    }
    
    .dashboard-header h1:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -8px;
        height: 4px;
        width: 60px;
        background: var(--gradient-primary);
        border-radius: 2px;
    }
    
    .dashboard-header p {
        color: #6c757d;
        font-size: 1.1rem;
        margin-top: 5px;
    }
    
    /* Stats Cards */
    .stats-row {
        margin-bottom: 2rem;
    }
    
    .stats-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        padding: 25px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        border-left: 5px solid transparent;
        overflow: hidden;
        position: relative;
    }
    
    .stats-card:hover {
        transform: translateY(-7px);
        box-shadow: var(--hover-shadow);
    }
    
    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
        z-index: 1;
    }
    
    .stats-card.total-card {
        border-left-color: var(--accent-color);
    }
    
    .stats-card.pending-card {
        border-left-color: var(--warning-color);
    }
    
    .stats-card.approved-card {
        border-left-color: var(--success-color);
    }
    
    .stats-card.rejected-card {
        border-left-color: var(--danger-color);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        font-size: 24px;
        color: white;
        position: relative;
        z-index: 2;
    }
    
    .total-icon {
        background: var(--gradient-primary);
    }
    
    .pending-icon {
        background: var(--gradient-warning);
    }
    
    .approved-icon {
        background: var(--gradient-success);
    }
    
    .rejected-icon {
        background: var(--gradient-danger);
    }
    
    .stats-info {
        position: relative;
        z-index: 2;
    }
    
    .stats-info h3 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: #333;
    }
    
    .stats-info p {
        color: #6c757d;
        margin: 0;
        font-weight: 500;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Chart Containers */
    .chart-container {
        background: white;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        padding: 25px;
        margin-bottom: 25px;
        transition: all 0.3s ease;
    }
    
    .chart-container:hover {
        box-shadow: var(--hover-shadow);
    }
    
    .chart-title {
        color: var(--secondary-color);
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        font-size: 1.2rem;
        position: relative;
    }
    
    .chart-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -1px;
        height: 3px;
        width: 50px;
        background: var(--gradient-primary);
        border-radius: 1.5px;
    }
    
    .chart-area, .chart-pie {
        height: 300px;
        width: 100%;
        position: relative;
    }
    
    /* Table Styling */
    .bookings-table {
        background: white;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        padding: 25px;
        margin-bottom: 25px;
        transition: all 0.3s ease;
    }
    
    .bookings-table:hover {
        box-shadow: var(--hover-shadow);
    }
    
    .table-title {
        color: var(--secondary-color);
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        font-size: 1.2rem;
        position: relative;
    }
    
    .table-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -1px;
        height: 3px;
        width: 50px;
        background: var(--gradient-primary);
        border-radius: 1.5px;
    }
    
    .table {
        margin-bottom: 20px;
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    
    .table thead th {
        background-color: transparent;
        color: #6c757d;
        font-weight: 600;
        border: none;
        padding: 12px 15px;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table tbody tr {
        background-color: #f8f9fa;
        box-shadow: 0 3px 10px rgba(0,0,0,0.03);
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .table tbody tr:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .table td {
        vertical-align: middle;
        border: none;
        padding: 15px;
        font-size: 0.95rem;
    }
    
    .table td:first-child {
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }
    
    .table td:last-child {
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    
    .badge {
        padding: 7px 14px;
        border-radius: 30px;
        font-weight: 500;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    
    .bg-warning {
        background-color: #fff3cd !important;
        color: #856404;
    }
    
    .bg-success {
        background-color: #d4edda !important;
        color: #155724;
    }
    
    .bg-danger {
        background-color: #f8d7da !important;
        color: #721c24;
    }
    
    .bg-secondary {
        background-color: #e2e3e5 !important;
        color: #383d41;
    }
    
    .view-all-btn {
        background: var(--gradient-primary);
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 30px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(126, 63, 242, 0.3);
    }
    
    .view-all-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(126, 63, 242, 0.4);
        color: white;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .stats-card {
            margin-bottom: 20px;
        }
    }
    
    @media (max-width: 768px) {
        .admin-dashboard {
            padding: 20px 10px;
        }
        
        .dashboard-header h1 {
            font-size: 1.8rem;
        }
        
        .stats-info h3 {
            font-size: 1.8rem;
        }
    }
    
    /* User role legend */
    .role-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
        margin-top: 25px;
    }
    
    .role-item {
        display: flex;
        align-items: center;
        margin-right: 15px;
    }
    
    .role-color {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
    }
    
    .role-name {
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    /* Animation for counters */
    @keyframes countUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .stats-info h3 {
        animation: countUp 1s ease-out forwards;
    }
</style>

<!-- Dashboard Content -->
<div class="container-fluid admin-dashboard">
    <div class="row">
        <div class="col-12 dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome to the Pondicherry University Seminar Hall Booking System Administration</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row stats-row">
        <div class="col-xl-3 col-md-6">
            <div class="stats-card total-card">
                <div class="stats-icon total-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo $bookingStats['total']; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stats-card pending-card">
                <div class="stats-icon pending-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo $bookingStats['pending']; ?></h3>
                    <p>Pending Bookings</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stats-card approved-card">
                <div class="stats-icon approved-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo $bookingStats['approved']; ?></h3>
                    <p>Approved Bookings</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stats-card rejected-card">
                <div class="stats-icon rejected-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo $bookingStats['rejected']; ?></h3>
                    <p>Rejected Bookings</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="chart-container">
                <h5 class="chart-title">Booking Overview</h5>
                <div class="chart-area">
                    <canvas id="bookingChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="chart-container">
                <h5 class="chart-title">User Distribution</h5>
                <div class="chart-pie">
                    <canvas id="userRoleChart"></canvas>
                </div>
                <div class="role-legend">
                    <?php 
                    $colors = [
                        'Admin' => '#7e3ff2',
                        'HOD' => '#2ecc71',
                        'Dean' => '#3498db',
                        'Professor' => '#f39c12'
                    ];
                    
                    foreach ($userRoleStats as $role => $count): 
                        $color = isset($colors[$role]) ? $colors[$role] : '#6c757d';
                    ?>
                    <div class="role-item">
                        <span class="role-color" style="background-color: <?php echo $color; ?>"></span>
                        <span class="role-name"><?php echo $role; ?> (<?php echo $count; ?>)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="row">
        <div class="col-12">
            <div class="bookings-table">
                <h5 class="table-title">Recent Bookings</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Organizer</th>
                                <th>Hall</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['booking_id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['organizer_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['hall_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['from_date'])); ?></td>
                                <td>
                                    <span class="badge 
                                    <?php 
                                    switch($booking['status']) {
                                        case 'Pending': echo 'bg-warning'; break;
                                        case 'Approved': echo 'bg-success'; break;
                                        case 'Rejected': echo 'bg-danger'; break;
                                        case 'Cancelled': echo 'bg-secondary'; break;
                                        default: echo 'bg-info';
                                    }
                                    ?>">
                                        <?php echo $booking['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center">
                    <a href="view_bookings.php" class="btn view-all-btn">View All Bookings</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Chart.js with improved visuals -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Booking Chart with improved visuals
    var bookingCtx = document.getElementById('bookingChart').getContext('2d');
    var gradient1 = bookingCtx.createLinearGradient(0, 0, 0, 400);
    gradient1.addColorStop(0, 'rgba(126, 63, 242, 0.7)');
    gradient1.addColorStop(1, 'rgba(126, 63, 242, 0.1)');
    
    var gradient2 = bookingCtx.createLinearGradient(0, 0, 0, 400);
    gradient2.addColorStop(0, 'rgba(243, 156, 18, 0.7)');
    gradient2.addColorStop(1, 'rgba(243, 156, 18, 0.1)');
    
    var gradient3 = bookingCtx.createLinearGradient(0, 0, 0, 400);
    gradient3.addColorStop(0, 'rgba(46, 204, 113, 0.7)');
    gradient3.addColorStop(1, 'rgba(46, 204, 113, 0.1)');
    
    var gradient4 = bookingCtx.createLinearGradient(0, 0, 0, 400);
    gradient4.addColorStop(0, 'rgba(231, 76, 60, 0.7)');
    gradient4.addColorStop(1, 'rgba(231, 76, 60, 0.1)');
    
    var gradient5 = bookingCtx.createLinearGradient(0, 0, 0, 400);
    gradient5.addColorStop(0, 'rgba(108, 117, 125, 0.7)');
    gradient5.addColorStop(1, 'rgba(108, 117, 125, 0.1)');
    
    var bookingChart = new Chart(bookingCtx, {
        type: 'bar',
        data: {
            labels: ['Total', 'Pending', 'Approved', 'Rejected', 'Cancelled'],
            datasets: [{
                label: 'Booking Statistics',
                data: [
                    <?php echo $bookingStats['total']; ?>,
                    <?php echo $bookingStats['pending']; ?>,
                    <?php echo $bookingStats['approved']; ?>,
                    <?php echo $bookingStats['rejected']; ?>,
                    <?php echo $bookingStats['cancelled']; ?>
                ],
                backgroundColor: [
                    gradient1,
                    gradient2,
                    gradient3,
                    gradient4,
                    gradient5
                ],
                borderColor: [
                    'rgba(126, 63, 242, 1)',
                    'rgba(243, 156, 18, 1)',
                    'rgba(46, 204, 113, 1)',
                    'rgba(231, 76, 60, 1)',
                    'rgba(108, 117, 125, 1)'
                ],
                borderWidth: 2,
                borderRadius: 8,
                maxBarThickness: 60
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: '#6c757d',
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        color: '#6c757d',
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });

    // User Role Chart with improved visuals
    var userRoleCtx = document.getElementById('userRoleChart').getContext('2d');
    var userRoleChart = new Chart(userRoleCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php 
                foreach ($userRoleStats as $role => $count) {
                    echo "'" . $role . "', ";
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    foreach ($userRoleStats as $count) {
                        echo $count . ", ";
                    }
                    ?>
                ],
                backgroundColor: [
                    'rgba(126, 63, 242, 0.8)',
                    'rgba(46, 204, 113, 0.8)',
                    'rgba(52, 152, 219, 0.8)',
                    'rgba(243, 156, 18, 0.8)'
                ],
                borderColor: [
                    'rgba(126, 63, 242, 1)',
                    'rgba(46, 204, 113, 1)',
                    'rgba(52, 152, 219, 1)',
                    'rgba(243, 156, 18, 1)'
                ],
                borderWidth: 2,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    displayColors: false
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
    
    // Add smooth scroll to table rows on hover
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', () => {
            row.style.transition = 'all 0.3s ease';
        });
    });
    
    // Add number counter animation for stats
    const animateCounter = (el, start, end, duration) => {
        let startTime = null;
        const step = (timestamp) => {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            el.innerHTML = Math.floor(progress * (end - start) + start);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    };
    
    // Apply counter animation to stat numbers
    const statNumbers = document.querySelectorAll('.stats-info h3');
    statNumbers.forEach(statNumber => {
        const finalValue = parseInt(statNumber.innerText);
        statNumber.innerText = '0';
        animateCounter(statNumber, 0, finalValue, 1500);
    });
});
</script>

<?php include('footer user.php'); ?>




