<?php
// Include database connection
session_start(); // Start the session
include('config.php'); // Include the database connection

// Ensure the user is logged in and is a Professor
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'Professor') {
    header("Location: login.php");
    exit();
}

// Get user email from the session
$user_email = $_SESSION['email'];

// Fetch the user's name from the database
$query = "SELECT name FROM users WHERE email = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $name = $user_data['name'] ?? 'Professor';
    $stmt->close();
} else {
    $name = 'Professor';
}

// Initialize arrays
$halls = [];
$departments = [];
$schools = [];
$room_types = [];
$amenities = [
    'ac' => 'Air Conditioning',
    'projector' => 'Projector',
    'smart_board' => 'Smart Board',
    'white_board' => 'White Board',
    'audio_system' => 'Audio System',
    'wifi' => 'WiFi',
    'computer_lab' => 'Computer Lab',
    'charging_points' => 'Charging Points'
];

// Fetch dropdown data
$dropdown_queries = [
    ['query' => "SELECT department_id, department_name FROM departments", 'array' => &$departments],
    ['query' => "SELECT school_id, school_name FROM schools", 'array' => &$schools],
    ['query' => "SELECT hall_type_id, type_name FROM hall_type", 'array' => &$room_types]
];

foreach ($dropdown_queries as $dropdown) {
    $result = $conn->query($dropdown['query']);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dropdown['array'][] = $row;
        }
    }
}

// Handle filtering with enhanced options
$filters = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Existing filters
    $filter_mapping = [
        'search' => "h.hall_name LIKE '%{value}%'",
        'department' => "h.department_id = {value}",
        'school' => "h.school_id = {value}",
        'room_type' => "h.hall_type = {value}",
        'capacity_min' => "h.capacity >= {value}",
        'capacity_max' => "h.capacity <= {value}"
    ];

    foreach ($filter_mapping as $param => $condition) {
        if (!empty($_GET[$param])) {
            $value = $conn->real_escape_string($_GET[$param]);
            $filters[] = str_replace('{value}', $value, $condition);
        }
    }

    // Enhanced feature filtering
    if (!empty($_GET['features'])) {
        $selected_features = array_map('htmlspecialchars', $_GET['features']);
        $feature_conditions = [];
        foreach ($selected_features as $feature) {
            $feature_conditions[] = "h.features LIKE '%$feature%'";
        }
        if (!empty($feature_conditions)) {
            $filters[] = "(" . implode(" OR ", $feature_conditions) . ")";
        }
    }

    // Price range filter (assuming you might add a price column later)
    if (!empty($_GET['price_min']) && !empty($_GET['price_max'])) {
        $price_min = floatval($_GET['price_min']);
        $price_max = floatval($_GET['price_max']);
        $filters[] = "h.price BETWEEN $price_min AND $price_max";
    }
}

try {
    // Enhanced query to fetch more hall details
    $sql = "
        SELECT 
            h.hall_id,
            h.hall_name, 
            h.capacity, 
            h.image, 
            h.features,
            d.department_name, 
            ht.type_name AS hall_type,
            s.school_name
        FROM 
            halls h
        LEFT JOIN 
            departments d ON h.department_id = d.department_id
        LEFT JOIN 
            hall_type ht ON h.hall_type = ht.hall_type_id
        LEFT JOIN
            schools s ON h.school_id = s.school_id
        WHERE h.is_archived = 0
    ";

    if (!empty($filters)) {
        $sql .= " AND " . implode(" AND ", $filters);
    }

    // Add sorting functionality
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'hall_name';
    $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
    
    // Validate sort parameters to prevent SQL injection
    $allowed_sort_fields = ['hall_name', 'capacity', 'department_name', 'hall_type'];
    $allowed_sort_orders = ['ASC', 'DESC'];
    
    if (!in_array($sort_by, $allowed_sort_fields)) {
        $sort_by = 'hall_name';
    }
    
    if (!in_array($sort_order, $allowed_sort_orders)) {
        $sort_order = 'ASC';
    }
    
    $sql .= " ORDER BY $sort_by $sort_order";

    $result = $conn->query($sql);

    // Fetch results into an array
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $halls[] = $row;
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Finder</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --bg-gradient: linear-gradient(45deg, #6a11cb, #2575fc);
        }

        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .hall-finder-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            min-height: calc(100vh - 150px);
            position: relative;
        }

        .sidebar {
            background: linear-gradient(135deg,rgb(14, 106, 243) 0%,rgb(167, 208, 250) 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 0 0 15px;
            position: sticky;
            top: 0;
            height: calc(100vh - 170px);
            overflow-y: auto;
        }

        .filter-section .form-check-input:checked {
            background-color: #2b4162;
            border-color: #2b4162;
        }

        .form-control, .form-select {
            border: 2px solid rgba(43, 65, 98, 0.2);
            border-radius: 8px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2b4162;
            box-shadow: 0 0 0 0.2rem rgba(43, 65, 98, 0.25);
        }

        .input-group-text {
            background-color: #2b4162;
            color: white;
            border: none;
        }

        .filter-btn {
            background: linear-gradient(135deg, #2b4162 0%, #12100e 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: linear-gradient(135deg, #12100e 0%, #2b4162 100%);
            color: white;
            transform: translateY(-2px);
        }

        .form-check-label {
            color: #e0e0e0;
        }

        .amenities-title, .capacity-range-title {
            color: #e0e0e0;
            font-weight: 600;
            margin-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 8px;
        }

        .hall-listings {
            height: calc(100vh - 150px);
            overflow-y: auto;
            padding: 20px;
        }

        .hall-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .hall-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .hall-card img {
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .hall-card:hover img {
            transform: scale(1.1);
        }

        .card-body {
            background: white;
            padding: 20px;
        }

        .btn-view, .btn-book {
            border-radius: 25px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: var(--bg-gradient);
            border: none;
        }

        .btn-book {
            background: linear-gradient(45deg, #28a745, #218838);
            border: none;
        }

        .btn-view:hover, .btn-book:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .filter-section .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .page-title {
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }

        .amenities-badge {
            background-color: rgba(106, 17, 203, 0.1);
            color: var(--primary-color);
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include('navbar1.php'); ?>
    
    <div class="container-fluid mt-4 hall-finder-container">
        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-md-3 sidebar">
                <h4 class="text-center mb-4"><i class="fas fa-filter me-2"></i>Advanced Filters</h4>
                <form method="GET" action="">
                    <!-- Search Bar -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search Halls" name="search" 
                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Department Dropdown -->
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department['department_id']); ?>" 
                                    <?php echo (isset($_GET['department']) && $_GET['department'] == $department['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($department['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div class="mb-3">
                        <label class="form-label">Sort By</label>
                        <div class="d-flex">
                            <select class="form-select me-2" name="sort_by">
                                <option value="hall_name" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'hall_name') ? 'selected' : ''; ?>>Name</option>
                                <option value="capacity" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'capacity') ? 'selected' : ''; ?>>Capacity</option>
                                <option value="department_name" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'department_name') ? 'selected' : ''; ?>>Department</option>
                                <option value="hall_type" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'hall_type') ? 'selected' : ''; ?>>Type</option>
                            </select>
                            <select class="form-select" name="sort_order">
                                <option value="ASC" <?php echo (isset($_GET['sort_order']) && $_GET['sort_order'] == 'ASC') ? 'selected' : ''; ?>>Ascending</option>
                                <option value="DESC" <?php echo (isset($_GET['sort_order']) && $_GET['sort_order'] == 'DESC') ? 'selected' : ''; ?>>Descending</option>
                            </select>
                        </div>
                    </div>

                    <!-- Capacity Range -->
                    <div class="mb-4">
                        <div class="capacity-range-title">
                            <i class="fas fa-users me-2"></i>Capacity Range
                        </div>
                        <div class="row">
                            <div class="col">
                                <input type="number" class="form-control capacity-input" placeholder="Min" name="capacity_min">
                            </div>
                            <div class="col">
                                <input type="number" class="form-control capacity-input" placeholder="Max" name="capacity_max">
                            </div>
                        </div>
                    </div>

                    <!-- Amenities Checkboxes -->
                    <div class="mb-4 filter-section">
                        <div class="amenities-title">
                            <i class="fas fa-clipboard-list me-2"></i>Amenities
                        </div>
                        <?php foreach ($amenities as $key => $label): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="features[]" value="<?php echo $key; ?>" id="feature_<?php echo $key; ?>">
                                <label class="form-check-label" for="feature_<?php echo $key; ?>">
                                    <?php echo $label; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn filter-btn w-100">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                </form>
            </div>

            <!-- Hall Listings -->
            <div class="col-md-9 hall-listings">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title mb-0">Hall Explorer</h1>
                    <div class="d-flex">
                        <a href="check_hall_availability.php" class="btn btn-link text-decoration-none me-3" title="Check Hall Availability">
                            <i class="fas fa-calendar-check fs-4 text-primary"></i>
                            <span class="ms-2 text-primary">Check Availability</span>
                        </a>
                        
                        <!-- View Toggle Buttons -->
                        <div class="btn-group me-3" role="group" aria-label="View options">
                            <button type="button" class="btn btn-outline-primary active" id="grid-view-btn">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="list-view-btn">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                        
                        <!-- Reset Filters -->
                        <a href="view_hall.php" class="btn btn-outline-secondary">
                            <i class="fas fa-sync-alt me-2"></i>Clear Filters
                        </a>
                    </div>
                </div>
                <div class="row g-4">
                    <?php if (!empty($halls)): ?>
                        <?php foreach ($halls as $hall): ?>
                            <div class="col-md-4">
                                <div class="card hall-card">
                                    <img src="<?php echo htmlspecialchars($hall['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($hall['hall_name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-building hall-info-icon"></i>
                                            <?php echo htmlspecialchars($hall['hall_name']); ?>
                                        </h5>
                                        <p class="card-text">
                                            <i class="fas fa-users hall-info-icon"></i>
                                            <strong>Capacity:</strong> <?php echo htmlspecialchars($hall['capacity']); ?> 
                                            <br>
                                            <i class="fas fa-university hall-info-icon"></i>
                                            <strong>Department:</strong> <?php echo htmlspecialchars($hall['department_name']); ?>
                                        </p>
                                        
                                        <!-- Amenities Badges -->
                                        <div class="mb-3">
                                            <?php 
                                            $hall_features = explode(',', $hall['features']);
                                            foreach ($hall_features as $feature):
                                                if (isset($amenities[trim($feature)])): 
                                            ?>
                                                <span class="badge amenities-badge">
                                                    <?php echo htmlspecialchars($amenities[trim($feature)]); ?>
                                                </span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <a href="detail_view_hall.php?hall_id=<?php echo urlencode($hall['hall_name']); ?>" class="btn btn-view flex-grow-1 me-2">
                                                <i class="fas fa-eye me-2"></i>View
                                            </a>
                                            <a href="book_hall.php?hall_id=<?php echo urlencode($hall['hall_name']); ?>" class="btn btn-book flex-grow-1">
                                                <i class="fas fa-calendar-check me-2"></i>Book
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center">
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>No halls match your search criteria.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include('footer user.php'); ?>
    </body> 
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --bg-gradient: linear-gradient(45deg, #6a11cb, #2575fc);
        }

        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .hall-finder-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            min-height: calc(100vh - 150px);
            position: relative;
        }

        .sidebar {
            background: linear-gradient(135deg,rgb(14, 106, 243) 0%,rgb(167, 208, 250) 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 0 0 15px;
            position: sticky;
            top: 0;
            height: calc(100vh - 170px);
            overflow-y: auto;
        }

        .filter-section .form-check-input:checked {
            background-color: #2b4162;
            border-color: #2b4162;
        }

        .form-control, .form-select {
            border: 2px solid rgba(43, 65, 98, 0.2);
            border-radius: 8px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2b4162;
            box-shadow: 0 0 0 0.2rem rgba(43, 65, 98, 0.25);
        }

        .input-group-text {
            background-color: #2b4162;
            color: white;
            border: none;
        }

        .filter-btn {
            background: linear-gradient(135deg, #2b4162 0%, #12100e 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: linear-gradient(135deg, #12100e 0%, #2b4162 100%);
            color: white;
            transform: translateY(-2px);
        }

        .form-check-label {
            color: #e0e0e0;
        }

        .amenities-title, .capacity-range-title {
            color: #e0e0e0;
            font-weight: 600;
            margin-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 8px;
        }

        .hall-listings {
            height: calc(100vh - 150px);
            overflow-y: auto;
            padding: 20px;
        }

        .hall-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .hall-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .hall-card img {
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .hall-card:hover img {
            transform: scale(1.1);
        }

        .card-body {
            background: white;
            padding: 20px;
        }

        .btn-view, .btn-book {
            border-radius: 25px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: var(--bg-gradient);
            border: none;
        }

        .btn-book {
            background: linear-gradient(45deg, #28a745, #218838);
            border: none;
        }

        .btn-view:hover, .btn-book:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .filter-section .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .page-title {
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }

        .amenities-badge {
            background-color: rgba(106, 17, 203, 0.1);
            color: var(--primary-color);
            margin-right: 5px;
            margin-bottom: 5px;
        }
    

        /* List view styles */
        .hall-card.flex-row {
            display: flex;
            flex-direction: row;
            height: 200px;
        }
        
        .hall-card.flex-row .card-img-top {
            width: 300px;
            height: 100%;
            object-fit: cover;
        }
        
        .hall-card.flex-row .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        /* Sort and filter styles */
        .form-select, .form-control {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-select option {
            background-color: #2b4162;
            color: white;
        }
        
        .form-label {
            color: #e0e0e0;
            font-weight: 500;
        }
        
        /* Button styles */
        .btn-outline-primary {
            color: blue;
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .btn-outline-primary:hover, .btn-outline-primary.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: white;
            color: blue;
        }
        
        .btn-outline-secondary {
            color: blue;
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .btn-outline-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: blue;
        }
    </style>
</body>
</html>