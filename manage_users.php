<?php
session_start();
include 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location:login.php');
    exit();
}

// Handle user deletion
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting user: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header('Location: manage_users.php');
    exit();
}

// Handle role change
if (isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    $update_query = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User role updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating user role: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header('Location: manage_users.php');
    exit();
}

// Fetch all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($query);
$users = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Count users by role for statistics
$admin_count = 0;
$professor_count = 0;
$hod_count = 0;
$dean_count = 0;

foreach ($users as $user) {
    switch ($user['role']) {
        case 'Admin':
            $admin_count++;
            break;
        case 'Professor':
            $professor_count++;
            break;
        case 'HOD':
            $hod_count++;
            break;
        case 'Dean':
            $dean_count++;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | University Admin</title>
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
        
        .main-content {
            padding: 20px;
            margin-top: 60px;
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
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
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
        
        .role-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .role-admin {
            background-color: #3498db;
            color: white;
        }
        
        .role-professor {
            background-color: #2ecc71;
            color: white;
        }
        
        .role-hod {
            background-color: #f39c12;
            color: white;
        }
        
        .role-dean {
            background-color: #9b59b6;
            color: white;
        }
        
        .action-buttons a {
            margin: 0 5px;
            font-size: 1.2rem;
        }
        
        .action-buttons .edit {
            color: #f39c12;
        }
        
        .action-buttons .delete {
            color: #e74c3c;
        }
        
        /* Export buttons styles */
        .export-buttons {
            display: flex;
            gap: 8px;
        }
        
        .export-buttons .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .export-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Include navbar -->
    <?php include('navbar.php'); ?>

    <div class="main-content">
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h1 class="mb-3"><i class="fas fa-users me-2"></i>User Management</h1>
                    <p class="text-muted">Manage all university system users and their roles</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="add_user.php" class="btn btn-success btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Add New User
                    </a>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Total Users</div>
                                    <div class="text-lg fw-bold"><?php echo count($users); ?></div>
                                </div>
                                <i class="fas fa-users fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Professors</div>
                                    <div class="text-lg fw-bold"><?php echo $professor_count; ?></div>
                                </div>
                                <i class="fas fa-chalkboard-teacher fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">HODs</div>
                                    <div class="text-lg fw-bold"><?php echo $hod_count; ?></div>
                                </div>
                                <i class="fas fa-user-tie fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Admins</div>
                                    <div class="text-lg fw-bold"><?php echo $admin_count; ?></div>
                                </div>
                                <i class="fas fa-user-shield fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Management Card -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-cog me-2"></i>Manage Users</span>
                    <div class="export-buttons">
                        <a href="export_users.php?format=pdf" class="btn btn-sm btn-danger">
                            <i class="fas fa-file-pdf me-1"></i> PDF
                        </a>
                        <a href="export_users.php?format=excel" class="btn btn-sm btn-success">
                            <i class="fas fa-file-excel me-1"></i> Excel
                        </a>
                        <a href="export_users.php?format=csv" class="btn btn-sm btn-primary">
                            <i class="fas fa-file-csv me-1"></i> CSV
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    endif; 
                    ?>
                    
                    <div class="search-box mb-4">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearch" class="form-control" placeholder="Search users...">
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-2 bg-light rounded-circle text-center" style="width: 40px; height: 40px; line-height: 40px;">
                                                <span class="fw-bold"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                                            </div>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="new_role" class="form-select form-select-sm" onchange="this.form.submit()" <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                                <option value="HOD" <?= $user['role'] == 'HOD' ? 'selected' : '' ?>>HOD</option>
                                                <option value="Dean" <?= $user['role'] == 'Dean' ? 'selected' : '' ?>>Dean</option>
                                                <option value="Professor" <?= $user['role'] == 'Professor' ? 'selected' : '' ?>>Professor</option>
                                                <option value="Admin" <?= $user['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                            <input type="hidden" name="change_role" value="1">
                                        </form>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                    <td class="action-buttons">
                                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="edit" title="Edit" data-bs-toggle="tooltip">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="#" class="delete" title="Delete" data-bs-toggle="tooltip" onclick="confirmDelete(<?= $user['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No users found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = 'manage_users.php?delete_id=' + id;
            }
        }
        
        // Search functionality
        document.getElementById('userSearch').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length - 1; j++) {
                    const cellText = cells[j].textContent.toLowerCase();
                    if (cellText.indexOf(searchValue) > -1) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
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