<?php
session_start();
include('config.php');
include('navbar.php');

// Ensure user is logged in and is either Admin or HOD
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Handle archive/unarchive actions
if (isset($_POST['action']) && isset($_POST['hall_ids'])) {
    $hall_ids = $_POST['hall_ids'];
    $action = $_POST['action'];
    
    if (!empty($hall_ids)) {
        $ids = implode(',', array_map('intval', $hall_ids));
        
        if ($action === 'archive') {
            $update_query = "UPDATE halls SET archived = 1 WHERE hall_id IN ($ids)";
            $conn->query($update_query);
            $_SESSION['success_message'] = "Selected halls have been archived successfully.";
        } elseif ($action === 'unarchive') {
            $update_query = "UPDATE halls SET archived = 0 WHERE hall_id IN ($ids)";
            $conn->query($update_query);
            $_SESSION['success_message'] = "Selected halls have been unarchived successfully.";
        }
    }
    
    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['show_archived']) ? '?show_archived=1' : ''));
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

// Filter by archive status - Check if archived column exists first
$check_column_query = "SHOW COLUMNS FROM halls LIKE 'archived'";
$column_result = $conn->query($check_column_query);

if ($column_result && $column_result->num_rows > 0) {
    // Column exists, apply filter
    if ($show_archived) {
        $where_conditions[] = "h.archived = 1";
    } else {
        $where_conditions[] = "h.archived = 0";
    }
} else {
    // Column doesn't exist, add it
    $add_column_query = "ALTER TABLE halls ADD COLUMN archived TINYINT(1) NOT NULL DEFAULT 0";
    $conn->query($add_column_query);
    // Since we just added the column, all halls are not archived by default
    if ($show_archived) {
        $where_conditions[] = "0"; // No results when viewing archived
    }
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
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
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
                    
                    <?php if ($show_archived): ?>
                        <input type="hidden" name="show_archived" value="1">
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    
                    <?php if (!empty($search) || !empty($filter_type) || !empty($filter_school)): ?>
                        <a href="modify_hall.php<?php echo $show_archived ? '?show_archived=1' : ''; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="btn-container">
                <?php if ($show_archived): ?>
                    <a href="modify_hall.php" class="btn btn-outline-primary">
                        <i class="fas fa-eye"></i> View Active Halls
                    </a>
                <?php else: ?>
                    <a href="modify_hall.php?show_archived=1" class="btn btn-outline-secondary">
                        <i class="fas fa-archive"></i> View Archived Halls
                    </a>
                <?php endif; ?>
                
                <a href="add_hall.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Hall
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($result && $result->num_rows > 0) { ?>
            <form id="hallActionForm" method="POST">
                <div class="mb-3 d-flex align-items-center">
                    <div class="select-all-container">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">Select All</label>
                        </div>
                    </div>
                    
                    <?php if ($show_archived): ?>
                        <button type="submit" name="action" value="unarchive" class="btn btn-unarchive me-2" onclick="return confirmAction('unarchive')">
                            <i class="fas fa-box-open"></i> Unarchive Selected
                        </button>
                    <?php else: ?>
                        <button type="submit" name="action" value="archive" class="btn btn-archive me-2" onclick="return confirmAction('archive')">
                            <i class="fas fa-archive"></i> Archive Selected
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="checkbox-column"></th>
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
                                    <td class="checkbox-column">
                                        <input class="form-check-input hall-checkbox" type="checkbox" name="hall_ids[]" value="<?php echo $row['hall_id']; ?>">
                                    </td>
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
                                            <a href="delete_hall.php?id=<?php echo $row['hall_id']; ?>" class="btn btn-danger btn-action" 
                                               onclick="return confirm('Are you sure you want to delete this hall?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <?php if ($show_archived): ?>
                                                <a href="javascript:void(0);" onclick="unarchiveSingle(<?php echo $row['hall_id']; ?>)" class="btn btn-warning btn-action">
                                                    <i class="fas fa-box-open"></i> Unarchive
                                                </a>
                                            <?php else: ?>
                                                <a href="javascript:void(0);" onclick="archiveSingle(<?php echo $row['hall_id']; ?>)" class="btn btn-secondary btn-action">
                                                    <i class="fas fa-archive"></i> Archive
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </form>
        <?php } else { ?>
            <div class="no-data">
                <i class="fas fa-info-circle me-2"></i>
                <?php if ($show_archived): ?>
                    No archived halls found. 
                    <a href="modify_hall.php">View active halls</a>.
                <?php elseif (!empty($search) || !empty($filter_type) || !empty($filter_school)): ?>
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

        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.hall-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Confirm action before submitting
        function confirmAction(action) {
            const checkboxes = document.querySelectorAll('.hall-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one hall.');
                return false;
            }
            
            const actionText = action === 'archive' ? 'archive' : 'unarchive';
            return confirm(`Are you sure you want to ${actionText} the selected halls?`);
        }
        
        // Single hall archive/unarchive functions
        function archiveSingle(hallId) {
            if (confirm('Are you sure you want to archive this hall?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'archive';
                
                const hallInput = document.createElement('input');
                hallInput.type = 'hidden';
                hallInput.name = 'hall_ids[]';
                hallInput.value = hallId;
                
                form.appendChild(actionInput);
                form.appendChild(hallInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function unarchiveSingle(hallId) {
            if (confirm('Are you sure you want to unarchive this hall?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'unarchive';
                
                const hallInput = document.createElement('input');
                hallInput.type = 'hidden';
                hallInput.name = 'hall_ids[]';
                hallInput.value = hallId;
                
                form.appendChild(actionInput);
                form.appendChild(hallInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

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
