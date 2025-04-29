<?php
session_start();
include('config.php');
include('navbar.php');

// Ensure user is logged in and is either Admin or HOD
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Initialize search and filter variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_school = isset($_GET['filter_school']) ? $_GET['filter_school'] : '';
$show_archived = isset($_GET['show_archived']) ? true : false;

// Base query
$query = "SELECT 
    h.*, 
    s.school_name, 
    d.department_name, 
    ht.type_name 
FROM halls h
LEFT JOIN schools s ON h.school_id = s.school_id
LEFT JOIN departments d ON h.department_id = d.department_id
LEFT JOIN hall_type ht ON h.hall_type = ht.hall_type_id";

// Add search and filter conditions
$where_conditions = [];
if (!$show_archived) {
    $where_conditions[] = "h.is_archived = 0";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_conditions[] = "(h.hall_name LIKE '%$search%' OR h.incharge_name LIKE '%$search%' OR h.incharge_email LIKE '%$search%')";
}
if (!empty($filter_type)) {
    $filter_type = $conn->real_escape_string($filter_type);
    $where_conditions[] = "h.hall_type = '$filter_type'";
}
if (!empty($filter_school)) {
    $filter_school = $conn->real_escape_string($filter_school);
    $where_conditions[] = "h.school_id = '$filter_school'";
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$result = $conn->query($query);

// Get hall types for filter dropdown
$hall_types_query = "SELECT * FROM hall_type";
$hall_types_result = $conn->query($hall_types_query);

// Get schools for filter dropdown
$schools_query = "SELECT * FROM schools";
$schools_result = $conn->query($schools_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Manage Halls</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding-top: 0;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: -140px; 
            position: relative;
            z-index: 1;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin: 0;
            text-align: center;
        }

        .container {
            max-width: 1500px;
            margin: 20px auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-filter-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            flex: 1;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .filter-dropdown {
            min-width: 150px;
        }

        .btn-container {
            display: flex;
            gap: 10px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #3da8d9;
            border-color: #3da8d9;
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #d61a6c;
            border-color: #d61a6c;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }

        th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }

        tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .hall-type {
            font-weight: 500;
            color: var(--primary-color);
        }

        .school-name {
            font-weight: 500;
            color: var(--accent-color);
        }

        .feature-tag {
            display: inline-block;
            background-color: #e9ecef;
            color: #495057;
            padding: 3px 8px;
            border-radius: 4px;
            margin: 2px;
            font-size: 0.85rem;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 1.2rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        @media (max-width: 992px) {
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-filter-container {
                flex-direction: column;
            }
            
            .btn-container {
                justify-content: space-between;
            }
        }

        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
        }

        .back-to-top.visible {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container-fluid">
            <h1 class="page-title">Hall Management</h1>
        </div>
    </div>

    <div class="container">
        <div class="action-bar">
            <div class="search-filter-container">
                <form action="" method="GET" class="d-flex flex-grow-1 gap-3 flex-wrap">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search halls or incharge..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <select name="filter_type" class="form-select filter-dropdown">
                        <option value="">All Hall Types</option>
                        <?php if ($hall_types_result && $hall_types_result->num_rows > 0): ?>
                            <?php while ($type = $hall_types_result->fetch_assoc()): ?>
                                <option value="<?php echo $type['hall_type_id']; ?>" <?php echo ($filter_type == $type['hall_type_id']) ? 'selected' : ''; ?>>
                                    <?php echo $type['type_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    
                    <select name="filter_school" class="form-select filter-dropdown">
                        <option value="">All Schools</option>
                        <?php if ($schools_result && $schools_result->num_rows > 0): ?>
                            <?php while ($school = $schools_result->fetch_assoc()): ?>
                                <option value="<?php echo $school['school_id']; ?>" <?php echo ($filter_school == $school['school_id']) ? 'selected' : ''; ?>>
                                    <?php echo $school['school_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    
                    <div class="form-check form-switch ms-2">
                        <input class="form-check-input" type="checkbox" id="showArchived" name="show_archived" value="1" <?php echo isset($_GET['show_archived']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="showArchived">Show Archived Halls</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    
                    <?php if (!empty($search) || !empty($filter_type) || !empty($filter_school)): ?>
                        <a href="modify_hall.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="btn-container">
                <a href="add_hall.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Hall
                </a>
                <a href="archived_halls.php" class="btn btn-warning">
                    <i class="fas fa-archive"></i> View Archived Halls
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($result && $result->num_rows > 0) { ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Capacity</th>
                            <th>Floor</th>
                            <th>Zone</th>
                            <th>Incharge</th>
                            <th>Contact</th>
                            <th>Features</th>
                            <th>Belongs To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sno = 1; while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $sno++; ?></td>
                                <td class="hall-type"><?php echo $row['type_name']; ?></td>
                                <td><strong><?php echo $row['hall_name']; ?></strong></td>
                                <td><span class="badge bg-info"><?php echo $row['capacity']; ?> seats</span></td>
                                <td><?php echo $row['floor_name']; ?></td>
                                <td><?php echo $row['zone']; ?></td>
                                <td><?php echo $row['incharge_name']; ?></td>
                                <td>
                                    <div><a href="mailto:<?php echo $row['incharge_email']; ?>"><i class="fas fa-envelope me-1"></i><?php echo $row['incharge_email']; ?></a></div>
                                    <div><i class="fas fa-phone me-1"></i><?php echo $row['incharge_phone']; ?></div>
                                </td>
                                <td>
                                    <?php
                                    $features = json_decode($row['features'], true);
                                    if (!empty($features)) {
                                        foreach ($features as $feature) {
                                            echo "<span class='feature-tag'>{$feature}</span> ";
                                        }
                                    } else {
                                        echo "<span class='text-muted'>No Features</span>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="school-name"><?php echo $row['school_name']; ?></div>
                                    <div><?php echo $row['department_name']; ?></div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_hall.php?id=<?php echo $row['hall_id']; ?>" class="btn btn-primary btn-action">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($row['is_archived'] == 0): ?>
                                            <a href="archive_hall.php?id=<?php echo $row['hall_id']; ?>&action=archive" class="btn btn-warning btn-action" 
                                               onclick="return confirm('Are you sure you want to archive this hall?');">
                                                <i class="fas fa-archive"></i> Archive
                                            </a>
                                        <?php else: ?>
                                            <a href="archive_hall.php?id=<?php echo $row['hall_id']; ?>&action=restore" class="btn btn-success btn-action" 
                                               onclick="return confirm('Are you sure you want to restore this hall?');">
                                                <i class="fas fa-trash-restore"></i> Restore
                                            </a>
                                        <?php endif; ?>
                                        <a href="delete_hall.php?id=<?php echo $row['hall_id']; ?>" class="btn btn-danger btn-action" 
                                           onclick="return confirm('Are you sure you want to permanently delete this hall? This action cannot be undone.');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="no-data">
                <i class="fas fa-info-circle me-2"></i>
                <?php if (!empty($search) || !empty($filter_type) || !empty($filter_school)): ?>
                    No halls match your search criteria. Try different filters or <a href="modify_hall.php">view all halls</a>.
                <?php else: ?>
                    No halls found in the database. <a href="add_hall.php">Add a new hall</a> to get started.
                <?php endif; ?>
            </div>
        <?php } ?>
    </div>

    <div class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </div>

    <script>
        // Back to top button functionality
        const backToTopButton = document.querySelector('.back-to-top');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });
        
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Initialize any Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</body>
<?php include('footer user.php'); ?>
</html>

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

// Check if booking_id is set in the URL
if (!isset($_GET['booking_id'])) {
    echo "No booking ID provided.";
    exit();
} else {
    $booking_id = intval($_GET['booking_id']); // Sanitize input
}

// Fetch booking details from the database
$query = "SELECT hb.*, h.hall_name, h.incharge_email, d.department_name 
          FROM hall_bookings hb
          JOIN halls h ON hb.hall_id = h.hall_id
          JOIN departments d ON h.department_id = d.department_id
          WHERE hb.booking_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo "Booking not found.";
    exit();
}

// Get hall and user details
$hall_id = $booking['hall_id'];
$incharge_email = $booking['incharge_email'];
$user_id = $booking['user_id'];

// Fetch the user's department_id from the database
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

// Handle form submission for updating booking
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
    $end_date = $_POST['end_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $queries = $_POST['queries'];

    // Check if selected department matches user's department
    if ($selected_department_id != $user_department_id) {
        echo "<div class='alert alert-danger'>Error: You can only book halls for your own department.</div>";
    } else {
        // Check for availability (excluding the current booking)
        $availability_query = "
            SELECT * FROM hall_bookings 
            WHERE hall_id = ? 
              AND booking_id != ?
              AND (
                (from_date <= ? AND end_date >= ?) -- Overlapping dates
                AND (start_time <= ? AND end_time >= ?) -- Overlapping times
              )
        ";
        $availability_stmt = $conn->prepare($availability_query);
        $availability_stmt->bind_param("iissss", $hall_id, $booking_id, $end_date, $from_date, $end_time, $start_time);
        $availability_stmt->execute();
        $availability_result = $availability_stmt->get_result();

        if ($availability_result->num_rows > 0) {
            echo "<p>The hall is not available for the selected dates and times. Please choose a different slot.</p>";
        } else {
            // Update booking in the database
            $update_query = "UPDATE hall_bookings SET 
                organizer_name = ?, 
                organizer_email = ?, 
                organizer_department = ?, 
                department_id = ?, 
                organizer_contact = ?, 
                program_name = ?, 
                program_type = ?, 
                program_purpose = ?, 
                from_date = ?, 
                end_date = ?, 
                start_time = ?, 
                end_time = ?, 
                queries = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE booking_id = ?";
            
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssssssssssssi", 
                $organizer_name, 
                $organizer_email, 
                $organizer_department, 
                $user_department_id, 
                $organizer_contact, 
                $program_name, 
                $program_type, 
                $program_purpose, 
                $from_date, 
                $end_date, 
                $start_time, 
                $end_time, 
                $queries,
                $booking_id
            );
            
            if ($update_stmt->execute()) {
                echo "<p>Booking updated successfully!</p>";

                // Send email notification about the update
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
                    $mail->setFrom('ajaiofficial06@gmail.com', 'Hall Booking System');
                    $mail->addAddress($organizer_email);
                    $mail->addAddress($incharge_email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Hall Booking Update Confirmation';
                    $mail->Body    = "Dear $organizer_name,<br><br>Your booking for the hall <strong>{$booking['hall_name']}</strong> has been updated.<br>
                                      Program Name: $program_name<br>
                                      From: $from_date to $end_date<br>
                                      Time: $start_time to $end_time<br><br>
                                      Thank you for using our service!";

                    $mail->send();
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
                
                // Redirect to the booking list page
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'delete_modify_hall.php';
                        }, 2000);
                      </script>";
            } else {
                echo "<p>Error: Could not update the booking.</p>";
            }

            $update_stmt->close();
        }

        $availability_stmt->close();
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Modify Hall Booking</title>
</head>
<body>
    <?php include('navbar1.php'); // Include the navbar ?>
    <div class="container mt-5">
        <h1 class="text-center">Modify Hall Booking: <?php echo htmlspecialchars($booking['hall_name']); ?></h1>
       
        <div class="card-body">
            <div class="row text-center">
                <div class="col">
                    <h5 class="card-title"><?php echo htmlspecialchars($booking['hall_name']); ?></h5>
                    <p class="card-text">Department: <?php echo htmlspecialchars($booking['department_name']); ?></p>
                </div>
            </div>
            <form method="POST" action="">
                <h4>Organizer Details</h4>
                <div class="mb-3">
                    <label for="organizer_name" class="form-label">Organizer Name</label>
                    <input type="text" class="form-control" name="organizer_name" value="<?php echo htmlspecialchars($booking['organizer_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="organizer_email" class="form-label">Organizer Email</label>
                    <input type="email" class="form-control" name="organizer_email" value="<?php echo htmlspecialchars($booking['organizer_email']); ?>" required>
                </div>
                <input type="hidden" name="selected_department_id" id="selected_department_id" value="<?php echo $user_department_id; ?>">
                <div class="mb-3">
                    <label for="organizer_department" class="form-label">Organizer Department</label>
                    <select class="form-control" name="organizer_department" id="organizer_department" required>
                    <option value="">Select Department</option>
                        <?php 
                        // Reset the result pointer to the beginning
                        $departments_result->data_seek(0);
                        while ($row = $departments_result->fetch_assoc()) : 
                            $is_user_dept = ($row['department_id'] == $user_department_id);
                            $is_selected = ($row['department_name'] == $booking['organizer_department']);
                        ?>
                            <option value="<?php echo htmlspecialchars($row['department_name']); ?>" 
                                   data-dept-id="<?php echo htmlspecialchars($row['department_id']); ?>"
                                   <?php if($is_selected) echo 'selected'; ?>
                                   <?php if(!$is_user_dept) echo 'disabled'; ?>>
                                <?php echo htmlspecialchars($row['department_name']); ?>
                                <?php if(!$is_user_dept) echo ' (Not your department)'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
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
                    <input type="text" class="form-control" name="organizer_contact" value="<?php echo htmlspecialchars($booking['organizer_contact']); ?>" required>
                </div>
                
                <h4>Program Details</h4>
                <div class="mb-3">
                    <label for="program_name" class="form-label">Name of the Program</label>
                    <input type="text" class="form-control" name="program_name" value="<?php echo htmlspecialchars($booking['program_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="program_type" class="form-label">Program Type</label><br>
                    <input type="radio" name="program_type" value="Event" <?php if($booking['program_type'] == 'Event') echo 'checked'; ?> required> Event
                    <input type="radio" name="program_type" value="Class" <?php if($booking['program_type'] == 'Class') echo 'checked'; ?> required> Class
                    <input type="radio" name="program_type" value="Other" <?php if($booking['program_type'] == 'Other') echo 'checked'; ?> required> Other
                </div>
                <div class="mb-3">
                    <label for="program_purpose" class="form-label">Purpose of the Program</label>
                    <textarea class="form-control" name="program_purpose" rows="3" required><?php echo htmlspecialchars($booking['program_purpose']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($booking['from_date']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($booking['end_date']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" class="form-control" name="start_time" value="<?php echo htmlspecialchars($booking['start_time']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" class="form-control" name="end_time" value="<?php echo htmlspecialchars($booking['end_time']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="queries" class="form-label">Any Queries</label>
                    <textarea class="form-control" name="queries" rows="3"><?php echo htmlspecialchars($booking['queries']); ?></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Update Booking</button>
                    <a href="delete_modify_hall.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php include('footer user.php'); ?>
</html>
