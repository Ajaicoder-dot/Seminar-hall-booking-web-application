<?php
session_start();
// Include database connection
include 'config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Initialize an empty array for departments
$departments = [];
$schools = [];
$filter_school = isset($_GET['school']) ? $_GET['school'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Get all schools for filter
    $schoolsQuery = "SELECT school_id, school_name FROM schools ORDER BY school_name";
    $schoolsResult = $conn->query($schoolsQuery);
    if ($schoolsResult && $schoolsResult->num_rows > 0) {
        while ($row = $schoolsResult->fetch_assoc()) {
            $schools[] = $row;
        }
    }

    // Base query to fetch department details
    $sql = "SELECT d.department_id, d.department_name, d.hod_name, d.hod_contact_mobile, d.designation, 
                   d.hod_contact_email, d.hod_intercom, s.school_name, s.school_id 
            FROM departments d
            LEFT JOIN schools s ON d.school_id = s.school_id";
    
    // Add filters if provided
    $where_clauses = [];
    $params = [];
    $types = "";
    
    if (!empty($filter_school)) {
        $where_clauses[] = "s.school_id = ?";
        $params[] = $filter_school;
        $types .= "i";
    }
    
    if (!empty($search_term)) {
        $where_clauses[] = "(d.department_name LIKE ? OR d.hod_name LIKE ? OR d.hod_contact_email LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $sql .= " ORDER BY s.school_name, d.department_name";
    
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
            $departments[] = $row;
        }
    }
    
    // Get department statistics
    $stats = [
        'total_departments' => 0,
        'total_schools' => 0,
        'total_hods' => 0
    ];
    
    $statsQuery = "SELECT 
                    (SELECT COUNT(*) FROM departments) as total_departments,
                    (SELECT COUNT(DISTINCT school_id) FROM departments) as total_schools,
                    (SELECT COUNT(DISTINCT hod_name) FROM departments) as total_hods";
    $statsResult = $conn->query($statsQuery);
    if ($statsResult && $statsResult->num_rows > 0) {
        $stats = $statsResult->fetch_assoc();
    }
    
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}

include('navbar.php');
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
    <title>Manage Departments</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 0;
            margin-top: -120px; 
            position: relative;
            z-index: 1;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            background-color: var(--secondary-color);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 500;
            border-color: #4a6785;
        }
        
        .table td {
            vertical-align: middle;
            padding: 12px 15px;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(236, 240, 241, 0.5);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .department-name {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .school-name {
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .hod-details {
            line-height: 1.6;
        }
        
        .hod-name {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .designation {
            color: #7f8c8d;
            font-style: italic;
        }
        
        .intercom {
            background-color: #f1f1f1;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .email-link {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .email-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            color: white;
        }
        
        .btn-delete {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            color: white;
        }
        
        .actions-container {
            display: flex;
            gap: 8px;
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 30px;
        }
        
        .badge-contact {
            background-color: var(--light-color);
            color: var(--dark-color);
        }
        
        .search-container {
            margin-bottom: 20px;
        }
        
        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stats-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .no-departments {
            text-align: center;
            padding: 30px;
            color: var(--dark-color);
            font-style: italic;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .pagination .page-link {
            color: var(--primary-color);
        }
        
        .mobile-contact {
            display: flex;
            align-items: center;
            margin-top: 5px;
        }
        
        .contact-icon {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .export-btn {
            margin-left: 10px;
        }
        
        .filter-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 10px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
        }
        
        .filter-badge .close {
            margin-left: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .filter-badge .close:hover {
            color: #f8f9fa;
        }
        
        .department-count {
            font-size: 0.9rem;
            color: var(--dark-color);
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-building me-2"></i>Manage Departments</h1>
                </div>
                <div class="col-md-6 text-end">
                    <a href="add_department.php" class="btn btn-light">
                        <i class="fas fa-plus me-1"></i> Add New Department
                    </a>
                    <button class="btn btn-outline-light export-btn" id="exportBtn">
                        <i class="fas fa-file-export me-1"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_departments'] ?></div>
                <div class="stat-label">Total Departments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_schools'] ?></div>
                <div class="stat-label">Schools</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_hods'] ?></div>
                <div class="stat-label">HODs</div>
            </div>
        </div>
        
        <!-- Search and Filter -->
        <div class="row">
            <div class="col-md-6">
                <form action="" method="GET" id="searchForm">
                    <div class="search-container">
                        <div class="input-group">
                            <input type="text" name="search" id="searchInput" class="form-control" placeholder="Search departments, HODs, or emails..." value="<?= htmlspecialchars($search_term) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search_term) || !empty($filter_school)): ?>
                            <a href="modify_dept.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($filter_school)): ?>
                        <input type="hidden" name="school" value="<?= htmlspecialchars($filter_school) ?>">
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6">
                <div class="filter-container">
                    <select id="schoolFilter" class="form-select" onchange="filterBySchool(this.value)">
                        <option value="">Filter by School</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= htmlspecialchars($school['school_id']) ?>" <?= ($filter_school == $school['school_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['school_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Active Filters -->
        <?php if (!empty($search_term) || !empty($filter_school)): ?>
        <div class="mb-3">
            <div class="d-flex align-items-center">
                <span class="me-2">Active Filters:</span>
                <?php if (!empty($search_term)): ?>
                <span class="filter-badge">
                    Search: <?= htmlspecialchars($search_term) ?>
                    <span class="close" onclick="removeFilter('search')">&times;</span>
                </span>
                <?php endif; ?>
                
                <?php if (!empty($filter_school)): 
                    $school_name = '';
                    foreach ($schools as $school) {
                        if ($school['school_id'] == $filter_school) {
                            $school_name = $school['school_name'];
                            break;
                        }
                    }
                ?>
                <span class="filter-badge">
                    School: <?= htmlspecialchars($school_name) ?>
                    <span class="close" onclick="removeFilter('school')">&times;</span>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Department Count -->
        <div class="department-count">
            Showing <?= count($departments) ?> department<?= count($departments) != 1 ? 's' : '' ?>
        </div>
        
        <!-- Departments Table Card -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <i class="fas fa-list me-2"></i> Department List
                    </div>
                    <div class="col text-end">
                        <button class="btn btn-sm btn-outline-light" id="printBtn">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="25%">Department & School</th>
                            <th width="30%">HOD Details</th>
                            <th width="20%">Contact</th>
                            <th width="20%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($departments)) : ?>
                            <?php $sno = 1; ?>
                            <?php foreach ($departments as $dept) : ?>
                                <tr>
                                    <!-- S.No -->
                                    <td><?= $sno++; ?></td>

                                    <!-- Department Name and School -->
                                    <td>
                                        <div class="department-name"><?= htmlspecialchars($dept['department_name']); ?></div>
                                        <div class="school-name">
                                            <i class="fas fa-university me-1"></i>
                                            <?= htmlspecialchars($dept['school_name'] ?? 'N/A'); ?>
                                        </div>
                                    </td>

                                    <!-- HOD Details -->
                                    <td class="hod-details">
                                        <div class="hod-name">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($dept['hod_name']); ?>
                                        </div>
                                        <div class="designation"><?= htmlspecialchars($dept['designation'] ?: 'No designation'); ?></div>
                                        <span class="intercom">
                                            <i class="fas fa-phone-alt me-1"></i>
                                            Intercom: <?= htmlspecialchars($dept['hod_intercom'] ?: 'N/A'); ?>
                                        </span>
                                    </td>

                                    <!-- Contact Information -->
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($dept['hod_contact_email']); ?>" class="email-link">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?= htmlspecialchars($dept['hod_contact_email']); ?>
                                        </a>
                                        <div class="mobile-contact">
                                            <i class="fas fa-mobile-alt contact-icon"></i>
                                            <span><?= htmlspecialchars($dept['hod_contact_mobile']); ?></span>
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="actions-container">
                                            <a href="edit_dept.php?id=<?= htmlspecialchars($dept['department_id']); ?>" class="btn btn-sm btn-edit">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-delete" 
                                                    onclick="confirmDelete(<?= htmlspecialchars($dept['department_id']); ?>, '<?= htmlspecialchars(addslashes($dept['department_name'])); ?>')">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5" class="no-departments">
                                    <i class="fas fa-info-circle me-2"></i>No departments found.
                                    <?php if (!empty($search_term) || !empty($filter_school)): ?>
                                        <div class="mt-2">
                                            <a href="modify_dept.php" class="btn btn-sm btn-primary">Clear Filters</a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the department: <span id="departmentName"></span>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="post" action="delete_dept.php">
                        <input type="hidden" name="department_id" id="departmentId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export Options Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="exportModalLabel">Export Departments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Select the export format:</p>
                    <div class="list-group">
                        <a href="export_departments.php?format=pdf<?= !empty($filter_school) ? '&school='.$filter_school : '' ?><?= !empty($search_term) ? '&search='.$search_term : '' ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-pdf me-2 text-danger"></i> Export as PDF
                        </a>
                        <a href="export_departments.php?format=excel<?= !empty($filter_school) ? '&school='.$filter_school : '' ?><?= !empty($search_term) ? '&search='.$search_term : '' ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-excel me-2 text-success"></i> Export as Excel
                        </a>
                        <a href="export_departments.php?format=csv<?= !empty($filter_school) ? '&school='.$filter_school : '' ?><?= !empty($search_term) ? '&search='.$search_term : '' ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-csv me-2 text-primary"></i> Export as CSV
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete confirmation
        function confirmDelete(id, name) {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('departmentId').value = id;
            document.getElementById('departmentName').textContent = name;
            modal.show();
        }
        
        // Filter by school
        function filterBySchool(schoolId) {
            if (schoolId) {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('school', schoolId);
                
                // Preserve search term if it exists
                const searchTerm = document.getElementById('searchInput').value;
                if (searchTerm) {
                    currentUrl.searchParams.set('search', searchTerm);
                }
                
                window.location.href = currentUrl.toString();
            } else {
                // If no school selected, remove the school parameter but keep search
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('school');
                
                // Preserve search term if it exists
                const searchTerm = document.getElementById('searchInput').value;
                if (searchTerm) {
                    currentUrl.searchParams.set('search', searchTerm);
                }
                
                window.location.href = currentUrl.toString();
            }
        }
        
        // Remove filter
        function removeFilter(filterType) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete(filterType);
            window.location.href = currentUrl.toString();
        }
        
        // Print functionality
        document.getElementById('printBtn').addEventListener('click', function() {
            window.print();
        });
        
        // Export functionality
        document.getElementById('exportBtn').addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('exportModal'));
            modal.show();
        });
    </script>
</body>
<?php include('footer user.php'); ?>
</html>
