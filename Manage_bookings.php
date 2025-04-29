
<?php
session_start();
include('config.php');

// Ensure only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch($action) {
        case 'add_event':
            $stmt = $conn->prepare("INSERT INTO university_events (title, description, date, location, organizer) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", 
                $_POST['title'], 
                $_POST['description'], 
                $_POST['date'], 
                $_POST['location'], 
                $_POST['organizer']
            );
            $stmt->execute();
            $stmt->close();
            break;

        case 'add_circular':
            $stmt = $conn->prepare("INSERT INTO university_circulars (title, description, issue_date, department, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", 
                $_POST['title'], 
                $_POST['description'], 
                $_POST['issue_date'], 
                $_POST['department'], 
                $_POST['created_by']
            );
            $stmt->execute();
            $stmt->close();
            break;

        case 'add_phd_notification':
            $stmt = $conn->prepare("INSERT INTO phd_notifications (title, description, department, research_area, additional_details) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", 
                $_POST['title'], 
                $_POST['description'], 
                $_POST['department'], 
                $_POST['research_area'], 
                $_POST['additional_details']
            );
            $stmt->execute();
            $stmt->close();
            break;

        case 'delete_event':
            $stmt = $conn->prepare("DELETE FROM university_events WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            $stmt->close();
            break;

        case 'delete_circular':
            $stmt = $conn->prepare("DELETE FROM university_circulars WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            $stmt->close();
            break;

        case 'delete_phd_notification':
            $stmt = $conn->prepare("DELETE FROM phd_notifications WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            $stmt->close();
            break;
            
        case 'update_event':
            $stmt = $conn->prepare("UPDATE university_events SET title=?, description=?, date=?, location=?, organizer=? WHERE id=?");
            $stmt->bind_param("sssssi", 
                $_POST['title'], 
                $_POST['description'], 
                $_POST['date'], 
                $_POST['location'], 
                $_POST['organizer'],
                $_POST['id']
            );
            $stmt->execute();
            $stmt->close();
            break;
            
        case 'update_circular':
            $stmt = $conn->prepare("UPDATE university_circulars SET title=?, description=?, issue_date=?, department=?, created_by=? WHERE id=?");
            $stmt->bind_param("sssssi", 
                $_POST['title'], 
                $_POST['description'], 
                $_POST['issue_date'], 
                $_POST['department'], 
                $_POST['created_by'],
                $_POST['id']
            );
            $stmt->execute();
            $stmt->close();
            break;
            
        case 'update_phd_notification':
            $stmt = $conn->prepare("UPDATE phd_notifications SET title=?, description=?, department=?, research_area=?, additional_details=? WHERE id=?");
            $stmt->bind_param("sssssi", 
                $_POST['title'], 
                $_POST['description'], 
                $_POST['department'], 
                $_POST['research_area'], 
                $_POST['additional_details'],
                $_POST['id']
            );
            $stmt->execute();
            $stmt->close();
            break;
    }
}

// Fetch existing data
$events_query = "SELECT * FROM university_events ORDER BY date DESC";
$circulars_query = "SELECT * FROM university_circulars ORDER BY issue_date DESC";
$phd_notifications_query = "SELECT * FROM phd_notifications ORDER BY date DESC";

$events_result = $conn->query($events_query);
$circulars_result = $conn->query($circulars_query);
$phd_notifications_result = $conn->query($phd_notifications_query);

// Count statistics
$event_count = $conn->query("SELECT COUNT(*) as count FROM university_events")->fetch_assoc()['count'];
$circular_count = $conn->query("SELECT COUNT(*) as count FROM university_circulars")->fetch_assoc()['count'];
$phd_count = $conn->query("SELECT COUNT(*) as count FROM phd_notifications")->fetch_assoc()['count'];
$total_items = $event_count + $circular_count + $phd_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background-color: var(--secondary-color);
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 20px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-warning {
            color: white;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .search-box {
            position: relative;
            margin-bottom: 15px;
        }
        
        .search-box i {
            position: absolute;
            top: 10px;
            left: 10px;
            color: #999;
        }
        
        .search-box input {
            padding-left: 35px;
            border-radius: 20px;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Include navbar -->
    <?php include('navbar.php'); ?>

    <!-- Main Content -->
    <div class="main-content" style="margin-left: 0; margin-top: 60px;">
        <div class="container-fluid">
            <h1 class="mb-4">University Administration Dashboard</h1>
            
            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#dashboard">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#events">
                        <i class="fas fa-calendar-alt me-2"></i>Events
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#circulars">
                        <i class="fas fa-clipboard me-2"></i>Circulars
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#phd">
                        <i class="fas fa-graduation-cap me-2"></i>PhD Notifications
                    </a>
                </li>
            </ul>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Total Items</div>
                                    <div class="text-lg fw-bold"><?php echo $total_items; ?></div>
                                </div>
                                <i class="fas fa-database fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Events</div>
                                    <div class="text-lg fw-bold"><?php echo $event_count; ?></div>
                                </div>
                                <i class="fas fa-calendar-alt fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Circulars</div>
                                    <div class="text-lg fw-bold"><?php echo $circular_count; ?></div>
                                </div>
                                <i class="fas fa-clipboard fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">PhD Notifications</div>
                                    <div class="text-lg fw-bold"><?php echo $phd_count; ?></div>
                                </div>
                                <i class="fas fa-graduation-cap fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Dashboard Tab (default) -->
                <div class="tab-pane fade show active" id="dashboard">
                    <div class="row">
                        <!-- Events Section -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-calendar-alt me-2"></i>Add New Event</span>
                                    <a href="#events" class="btn btn-sm btn-light" data-bs-toggle="tab">View All</a>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="needs-validation" novalidate>
                                        <input type="hidden" name="action" value="add_event">
                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" name="title" class="form-control" required>
                                            <div class="invalid-feedback">Please provide a title.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" required></textarea>
                                            <div class="invalid-feedback">Please provide a description.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date</label>
                                            <input type="date" name="date" class="form-control" required>
                                            <div class="invalid-feedback">Please select a date.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Location</label>
                                            <input type="text" name="location" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Organizer</label>
                                            <input type="text" name="organizer" class="form-control">
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus-circle me-2"></i>Add Event
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Circulars Section -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-clipboard me-2"></i>Add New Circular</span>
                                    <a href="#circulars" class="btn btn-sm btn-light" data-bs-toggle="tab">View All</a>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="needs-validation" novalidate>
                                        <input type="hidden" name="action" value="add_circular">
                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" name="title" class="form-control" required>
                                            <div class="invalid-feedback">Please provide a title.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" required></textarea>
                                            <div class="invalid-feedback">Please provide a description.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Issue Date</label>
                                            <input type="date" name="issue_date" class="form-control" required>
                                            <div class="invalid-feedback">Please select a date.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Department</label>
                                            <input type="text" name="department" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Created By</label>
                                            <input type="text" name="created_by" class="form-control">
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus-circle me-2"></i>Add Circular
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- PhD Notifications Section -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-graduation-cap me-2"></i>Add PhD Notification</span>
                                    <a href="#phd" class="btn btn-sm btn-light" data-bs-toggle="tab">View All</a>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="needs-validation" novalidate>
                                        <input type="hidden" name="action" value="add_phd_notification">
                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" name="title" class="form-control" required>
                                            <div class="invalid-feedback">Please provide a title.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" required></textarea>
                                            <div class="invalid-feedback">Please provide a description.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Department</label>
                                            <input type="text" name="department" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Research Area</label>
                                            <input type="text" name="research_area" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Additional Details</label>
                                            <textarea name="additional_details" class="form-control"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus-circle me-2"></i>Add PhD Notification
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Events Tab -->
                <div class="tab-pane fade" id="events">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-calendar-alt me-2"></i>Manage Events
                        </div>
                        <div class="card-body">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="eventSearch" class="form-control" placeholder="Search events...">
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="eventsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Title</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Organizer</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Reset result pointer
                                        $events_result->data_seek(0);
                                        while($event = $events_result->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                                            <td><?php echo $event['date']; ?></td>
                                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                                            <td><?php echo htmlspecialchars($event['organizer']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm edit-event" 
                                                        data-bs-toggle="modal" data-bs-target="#editEventModal"
                                                        data-id="<?php echo $event['id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($event['title']); ?>"
                                                        data-description="<?php echo htmlspecialchars($event['description']); ?>"
                                                        data-date="<?php echo $event['date']; ?>"
                                                        data-location="<?php echo htmlspecialchars($event['location']); ?>"
                                                        data-organizer="<?php echo htmlspecialchars($event['organizer']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                                    <input type="hidden" name="action" value="delete_event">
                                                    <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Circulars Tab -->
                <div class="tab-pane fade" id="circulars">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clipboard me-2"></i>Manage Circulars
                        </div>
                        <div class="card-body">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="circularSearch" class="form-control" placeholder="Search circulars...">
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="circularsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Title</th>
                                            <th>Issue Date</th>
                                            <th>Department</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Reset result pointer
                                        $circulars_result->data_seek(0);
                                        while($circular = $circulars_result->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($circular['title']); ?></td>
                                            <td><?php echo $circular['issue_date']; ?></td>
                                            <td><?php echo htmlspecialchars($circular['department']); ?></td>
                                            <td><?php echo htmlspecialchars($circular['created_by']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm edit-circular" 
                                                        data-bs-toggle="modal" data-bs-target="#editCircularModal"
                                                        data-id="<?php echo $circular['id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($circular['title']); ?>"
                                                        data-description="<?php echo htmlspecialchars($circular['description']); ?>"
                                                        data-issue-date="<?php echo $circular['issue_date']; ?>"
                                                        data-department="<?php echo htmlspecialchars($circular['department']); ?>"
                                                        data-created-by="<?php echo htmlspecialchars($circular['created_by']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this circular?');">
                                                    <input type="hidden" name="action" value="delete_circular">
                                                    <input type="hidden" name="id" value="<?php echo $circular['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- PhD Notifications Tab -->
                <div class="tab-pane fade" id="phd">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-graduation-cap me-2"></i>Manage PhD Notifications
                        </div>
                        <div class="card-body">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="phdSearch" class="form-control" placeholder="Search PhD notifications...">
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="phdTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Title</th>
                                            <th>Department</th>
                                            <th>Research Area</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Reset result pointer
                                        $phd_notifications_result->data_seek(0);
                                        while($notification = $phd_notifications_result->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                            <td><?php echo htmlspecialchars($notification['department']); ?></td>
                                            <td><?php echo htmlspecialchars($notification['research_area']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm edit-phd" 
                                                        data-bs-toggle="modal" data-bs-target="#editPhdModal"
                                                        data-id="<?php echo $notification['id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($notification['title']); ?>"
                                                        data-description="<?php echo htmlspecialchars($notification['description']); ?>"
                                                        data-department="<?php echo htmlspecialchars($notification['department']); ?>"
                                                        data-research-area="<?php echo htmlspecialchars($notification['research_area']); ?>"
                                                        data-additional-details="<?php echo htmlspecialchars($notification['additional_details']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this PhD notification?');">
                                                    <input type="hidden" name="action" value="delete_phd_notification">
                                                    <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editEventForm" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update_event">
                        <input type="hidden" name="id" id="edit_event_id">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" id="edit_event_title" class="form-control" required>
                            <div class="invalid-feedback">Please provide a title.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_event_description" class="form-control" required></textarea>
                            <div class="invalid-feedback">Please provide a description.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" id="edit_event_date" class="form-control" required>
                            <div class="invalid-feedback">Please select a date.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" id="edit_event_location" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Organizer</label>
                            <input type="text" name="organizer" id="edit_event_organizer" class="form-control">
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Circular Modal -->
    <div class="modal fade" id="editCircularModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Circular</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editCircularForm" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update_circular">
                        <input type="hidden" name="id" id="edit_circular_id">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" id="edit_circular_title" class="form-control" required>
                            <div class="invalid-feedback">Please provide a title.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_circular_description" class="form-control" required></textarea>
                            <div class="invalid-feedback">Please provide a description.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Issue Date</label>
                            <input type="date" name="issue_date" id="edit_circular_issue_date" class="form-control" required>
                            <div class="invalid-feedback">Please select a date.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" id="edit_circular_department" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Created By</label>
                            <input type="text" name="created_by" id="edit_circular_created_by" class="form-control">
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit PhD Notification Modal -->
    <div class="modal fade" id="editPhdModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit PhD Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editPhdForm" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update_phd_notification">
                        <input type="hidden" name="id" id="edit_phd_id">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" id="edit_phd_title" class="form-control" required>
                            <div class="invalid-feedback">Please provide a title.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_phd_description" class="form-control" required></textarea>
                            <div class="invalid-feedback">Please provide a description.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" id="edit_phd_department" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Research Area</label>
                            <input type="text" name="research_area" id="edit_phd_research_area" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Additional Details</label>
                            <textarea name="additional_details" id="edit_phd_additional_details" class="form-control"></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        // Search functionality
        document.getElementById('eventSearch').addEventListener('keyup', function() {
            filterTable('eventsTable', this.value);
        });
        
        document.getElementById('circularSearch').addEventListener('keyup', function() {
            filterTable('circularsTable', this.value);
        });
        
        document.getElementById('phdSearch').addEventListener('keyup', function() {
            filterTable('phdTable', this.value);
        });
        
        function filterTable(tableId, query) {
            query = query.toLowerCase();
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length - 1; j++) {
                    const cellText = cells[j].textContent.toLowerCase();
                    if (cellText.indexOf(query) > -1) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }

        // Edit Event Modal
        const editEventButtons = document.querySelectorAll('.edit-event');
        editEventButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_event_id').value = this.getAttribute('data-id');
                document.getElementById('edit_event_title').value = this.getAttribute('data-title');
                document.getElementById('edit_event_description').value = this.getAttribute('data-description');
                document.getElementById('edit_event_date').value = this.getAttribute('data-date');
                document.getElementById('edit_event_location').value = this.getAttribute('data-location');
                document.getElementById('edit_event_organizer').value = this.getAttribute('data-organizer');
            });
        });

        // Edit Circular Modal
        const editCircularButtons = document.querySelectorAll('.edit-circular');
        editCircularButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_circular_id').value = this.getAttribute('data-id');
                document.getElementById('edit_circular_title').value = this.getAttribute('data-title');
                document.getElementById('edit_circular_description').value = this.getAttribute('data-description');
                document.getElementById('edit_circular_issue_date').value = this.getAttribute('data-issue-date');
                document.getElementById('edit_circular_department').value = this.getAttribute('data-department');
                document.getElementById('edit_circular_created_by').value = this.getAttribute('data-created-by');
            });
        });

        // Edit PhD Notification Modal
        const editPhdButtons = document.querySelectorAll('.edit-phd');
        editPhdButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_phd_id').value = this.getAttribute('data-id');
                document.getElementById('edit_phd_title').value = this.getAttribute('data-title');
                document.getElementById('edit_phd_description').value = this.getAttribute('data-description');
                document.getElementById('edit_phd_department').value = this.getAttribute('data-department');
                document.getElementById('edit_phd_research_area').value = this.getAttribute('data-research-area');
                document.getElementById('edit_phd_additional_details').value = this.getAttribute('data-additional-details');
            });
        });

        // Tab navigation with URL hash
        document.addEventListener('DOMContentLoaded', function() {
            // Check for hash in URL and activate corresponding tab
            let hash = window.location.hash;
            if (hash) {
                const tab = document.querySelector(`a[href="${hash}"]`);
                if (tab) {
                    const bsTab = new bootstrap.Tab(tab);
                    bsTab.show();
                }
            }

            // Update URL hash when tab changes
            const tabs = document.querySelectorAll('a[data-bs-toggle="tab"]');
            tabs.forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(e) {
                    window.location.hash = e.target.getAttribute('href');
                });
            });
        });

        // Animation for stat cards
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseover', function() {
                this.style.transform = 'translateY(-10px)';
            });
            card.addEventListener('mouseout', function() {
                this.style.transform = 'translateY(-5px)';
            });
        });
    </script>
</body>
<?php include('footer user.php'); ?>
</html>
