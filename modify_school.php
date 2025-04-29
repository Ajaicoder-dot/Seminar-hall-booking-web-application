<?php
session_start();
// Include database connection
include 'config.php';
include('navbar.php');

// Check if the user is logged in and authorized
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Initialize search and filter variables
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Initialize an empty array for schools
$schools = [];

try {
    // Base query to fetch school details
    $sql = "SELECT * FROM schools";
    
    // Add filters if provided
    $where_clauses = [];
    $params = [];
    $types = "";
    
    if (!empty($search_term)) {
        $where_clauses[] = "(school_name LIKE ? OR dean_name LIKE ? OR dean_email LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    if (!empty($filter_status)) {
        $where_clauses[] = "dean_status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $sql .= " ORDER BY school_name";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if there are results and fetch them
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $schools[] = $row;
        }
    }
    
    // Get school statistics
    $stats = [
        'total_schools' => 0,
        'total_deans' => 0
    ];
    
    $statsQuery = "SELECT 
                    COUNT(*) as total_schools,
                    COUNT(DISTINCT dean_name) as total_deans
                   FROM schools";
    $statsResult = $conn->query($statsQuery);
    if ($statsResult && $statsResult->num_rows > 0) {
        $stats = $statsResult->fetch_assoc();
    }
    
    // Get all possible dean statuses for filter dropdown
    $statusQuery = "SELECT DISTINCT dean_status FROM schools WHERE dean_status IS NOT NULL AND dean_status != '' ORDER BY dean_status";
    $statusResult = $conn->query($statusQuery);
    $dean_statuses = [];
    if ($statusResult && $statusResult->num_rows > 0) {
        while ($row = $statusResult->fetch_assoc()) {
            $dean_statuses[] = $row['dean_status'];
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Manage Schools</title>
    <style>
        /* General Styling */
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styling */
        .page-header {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 20px 0;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
             margin-top: -120px; 
            position: relative;
            z-index: 1;
        }
        
        .page-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Stats Cards */
        .stats-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            width: 200px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #3498db;
            margin: 10px 0;
        }
        
        .stat-card .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        /* Search and Filter Styling */
        .search-filter-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .search-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .search-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .reset-btn {
            background-color: #95a5a6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .search-btn:hover, .reset-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Table Styling */
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background-color: #3498db;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        tr:hover {
            background-color: #f5f9ff;
        }
        
        /* Button Styling */
        .btn-action {
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            width: 100%;
            margin-bottom: 5px;
        }
        
        .btn-edit {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Dean Info Styling */
        .dean-details {
            display: flex;
            flex-direction: column;
        }
        
        .dean-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .dean-info {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        /* School Name Styling */
        .school-name {
            color: #3498db;
            font-weight: 600;
            font-size: 16px;
        }
        
        /* Export Options */
        .export-options {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .export-btn {
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
        }
        
        .export-btn-pdf {
            background-color: #e74c3c;
            color: white;
        }
        
        .export-btn-excel {
            background-color: #27ae60;
            color: white;
        }
        
        .export-btn-csv {
            background-color: #f39c12;
            color: white;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                align-items: center;
            }
            
            .stat-card {
                width: 100%;
                max-width: 300px;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .search-filter-form {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-university"></i> Manage Schools</h2>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-university fa-2x" style="color: #3498db;"></i>
                </div>
                <div class="stat-value"><?= $stats['total_schools'] ?></div>
                <div class="stat-label">Total Schools</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-tie fa-2x" style="color: #3498db;"></i>
                </div>
                <div class="stat-value"><?= $stats['total_deans'] ?></div>
                <div class="stat-label">Total Deans</div>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="search-filter-container">
            <form method="GET" action="" class="search-filter-form">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Search</label>
                    <input type="text" id="search" name="search" placeholder="Search by school name, dean name or email" value="<?= htmlspecialchars($search_term) ?>">
                </div>
                
                <div class="form-group">
                    <label for="status"><i class="fas fa-filter"></i> Filter by Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <?php foreach ($dean_statuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>" <?= $filter_status === $status ? 'selected' : '' ?>>
                                <?= htmlspecialchars($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="flex: 0 0 auto;">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="modify_school.php" class="reset-btn">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Export Options -->
        <div class="export-options">
            <a href="export_schools.php?format=pdf<?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?><?= !empty($filter_status) ? '&status=' . urlencode($filter_status) : '' ?>" class="export-btn export-btn-pdf">
                <i class="fas fa-file-pdf"></i> Export as PDF
            </a>
            <a href="export_schools.php?format=excel<?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?><?= !empty($filter_status) ? '&status=' . urlencode($filter_status) : '' ?>" class="export-btn export-btn-excel">
                <i class="fas fa-file-excel"></i> Export as Excel
            </a>
            <a href="export_schools.php?format=csv<?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?><?= !empty($filter_status) ? '&status=' . urlencode($filter_status) : '' ?>" class="export-btn export-btn-csv">
                <i class="fas fa-file-csv"></i> Export as CSV
            </a>
        </div>
        
        <!-- Schools Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> S.No</th>
                        <th><i class="fas fa-university"></i> School Name</th>
                        <th><i class="fas fa-user-tie"></i> Dean Details</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($schools)) : ?>
                        <?php $sno = 1; ?>
                        <?php foreach ($schools as $school) : ?>
                            <tr>
                                <!-- S.No -->
                                <td><?= $sno++; ?></td>

                                <!-- School Name -->
                                <td class="school-name"><?= htmlspecialchars($school['school_name']); ?></td>

                                <!-- Dean Details -->
                                <td class="dean-details">
                                    <div class="dean-name"><?= htmlspecialchars($school['dean_name']); ?></div>
                                    <div class="dean-info">
                                        <i class="fas fa-phone"></i> Intercom: <?= htmlspecialchars($school['dean_intercome']); ?>
                                    </div>
                                    <div class="dean-info">
                                        <i class="fas fa-info-circle"></i> Status: <?= htmlspecialchars($school['dean_status']); ?>
                                    </div>
                                </td>

                                <!-- Email Column -->
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($school['dean_email']); ?>" style="color: #3498db; text-decoration: none;">
                                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($school['dean_email']); ?>
                                    </a>
                                </td>

                                <!-- Actions -->
                                <td>
                                    <div class="actions-container">
                                        <form method="post" action="edit_school.php">
                                            <input type="hidden" name="school_id" value="<?= htmlspecialchars($school['school_id']); ?>">
                                            <button type="submit" class="btn-action btn-edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </form>
                                        <form method="post" action="delete_school.php">
                                            <input type="hidden" name="school_id" value="<?= htmlspecialchars($school['school_id']); ?>">
                                            <button type="submit" 
                                                    onclick="return confirm('Are you sure you want to delete this school?');" 
                                                    class="btn-action btn-delete">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">
                                <i class="fas fa-info-circle fa-2x" style="color: #3498db;"></i>
                                <p style="margin-top: 10px;">No schools found. Try adjusting your search criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
<?php include('footer user.php'); ?>
</html>
