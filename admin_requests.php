<?php
session_start();
include('config.php');

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['email']) || !isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'respond') {
    $request_id = $_POST['request_id'];
    $admin_response = trim($_POST['admin_response']);
    $status = $_POST['status'];
    
    if (empty($admin_response)) {
        $error_message = "Response message cannot be empty.";
    } else {
        // Update the request with admin response and status
        $update_query = "UPDATE user_requests SET admin_response = ?, status = ?, responded_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $admin_response, $status, $request_id);
        
        if ($stmt->execute()) {
            $success_message = "Response submitted successfully!";
        } else {
            $error_message = "Error submitting response: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle filter submission
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// Prepare the base query
$requests_query = "SELECT r.*, u.name as user_name, u.email as user_email 
                   FROM user_requests r 
                   JOIN users u ON r.user_id = u.id 
                   WHERE 1=1";

// Add filters if selected
$params = [];
$types = "";

if (!empty($status_filter)) {
    $requests_query .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($type_filter)) {
    $requests_query .= " AND r.request_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

// Add order by
$requests_query .= " ORDER BY r.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($requests_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();

// Get unique request types for filter dropdown
$types_query = "SELECT DISTINCT request_type FROM user_requests ORDER BY request_type";
$types_result = $conn->query($types_query);
$request_types = [];
while ($row = $types_result->fetch_assoc()) {
    $request_types[] = $row['request_type'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Requests - Pondicherry University</title>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Base styles */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, html {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #0062cc, #0a84ff);
            color: white;
            padding: 20px 30px;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23444' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 12px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #0a84ff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #0062cc;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Table styles */
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #444;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .badge-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-processing {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .action-btn {
            margin-right: 5px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        .modal-header {
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .request-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #0a84ff;
        }
        
        .user-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #0a84ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.1);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .status-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .status-option {
            flex: 1;
            text-align: center;
        }
        
        .status-radio {
            display: none;
        }
        
        .status-label {
            display: block;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid #ddd;
        }
        
        .status-radio:checked + .status-label {
            border-color: #0a84ff;
            background-color: rgba(10, 132, 255, 0.1);
        }
        
        .status-label-approved {
            border-color: #28a745;
            color: #155724;
        }
        
        .status-radio:checked + .status-label-approved {
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .status-label-rejected {
            border-color: #dc3545;
            color: #721c24;
        }
        
        .status-radio:checked + .status-label-rejected {
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .status-label-processing {
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .status-radio:checked + .status-label-processing {
            background-color: rgba(23, 162, 184, 0.1);
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-ticket-alt"></i> Manage User Requests</h1>
                <div>
                    <span class="badge badge-pending">Pending: <?php echo count(array_filter($requests, function($r) { return $r['status'] === 'Pending'; })); ?></span>
                    <span class="badge badge-processing">Processing: <?php echo count(array_filter($requests, function($r) { return $r['status'] === 'Processing'; })); ?></span>
                    <span class="badge badge-approved">Approved: <?php echo count(array_filter($requests, function($r) { return $r['status'] === 'Approved'; })); ?></span>
                    <span class="badge badge-rejected">Rejected: <?php echo count(array_filter($requests, function($r) { return $r['status'] === 'Rejected'; })); ?></span>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filter Form -->
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <label for="status">Filter by Status</label>
                        <select id="status" name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo $status_filter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="type">Filter by Type</label>
                        <select id="type" name="type" class="form-select" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <?php foreach ($request_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $type_filter === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (!empty($status_filter) || !empty($type_filter)): ?>
                        <div class="filter-group" style="display: flex; align-items: flex-end;">
                            <a href="admin_requests.php" class="btn btn-secondary btn-sm">Clear Filters</a>
                        </div>
                    <?php endif; ?>
                </form>
                
                <!-- Requests Table -->
                <?php if (empty($requests)): ?>
                    <div class="alert alert-info">
                        No requests found. <?php echo !empty($status_filter) || !empty($type_filter) ? 'Try changing your filters.' : ''; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Response</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($request['user_name']); ?><br>
                                            <small><?php echo htmlspecialchars($request['user_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($request['request_type']); ?></td>
                                        <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($request['status']); ?>">
                                                <?php echo htmlspecialchars($request['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($request['admin_response'])): ?>
                                                <span class="badge badge-approved">Responded</span>
                                            <?php else: ?>
                                                <span class="badge badge-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm action-btn view-request" 
                                                    data-id="<?php echo $request['id']; ?>"
                                                    data-type="<?php echo htmlspecialchars($request['request_type']); ?>"
                                                    data-subject="<?php echo htmlspecialchars($request['subject']); ?>"
                                                    data-message="<?php echo htmlspecialchars($request['message']); ?>"
                                                    data-status="<?php echo htmlspecialchars($request['status']); ?>"
                                                    data-response="<?php echo htmlspecialchars($request['admin_response'] ?? ''); ?>"
                                                    data-username="<?php echo htmlspecialchars($request['user_name']); ?>"
                                                    data-useremail="<?php echo htmlspecialchars($request['user_email']); ?>"
                                                    data-date="<?php echo date('M d, Y', strtotime($request['created_at'])); ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Response Modal -->
    <div id="responseModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h2>Request Details</h2>
            </div>
            <div class="modal-body">
                <div class="user-info">
                    <h3 id="modalSubject"></h3>
                    <p><strong>From:</strong> <span id="modalUsername"></span> (<span id="modalUserEmail"></span>)</p>
                    <p><strong>Type:</strong> <span id="modalType"></span></p>
                    <p><strong>Date:</strong> <span id="modalDate"></span></p>
                    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                </div>
                
                <div class="request-details">
                    <p><strong>Request Message:</strong></p>
                    <p id="modalMessage"></p>
                </div>
                
                <form id="responseForm" method="POST" action="">
                    <input type="hidden" name="action" value="respond">
                    <input type="hidden" id="request_id" name="request_id" value="">
                    
                    <div class="status-options">
                        <div class="status-option">
                            <input type="radio" id="status_processing" name="status" value="Processing" class="status-radio">
                            <label for="status_processing" class="status-label status-label-processing">Processing</label>
                        </div>
                        <div class="status-option">
                            <input type="radio" id="status_approved" name="status" value="Approved" class="status-radio">
                            <label for="status_approved" class="status-label status-label-approved">Approved</label>
                        </div>
                        <div class="status-option">
                            <input type="radio" id="status_rejected" name="status" value="Rejected" class="status-radio">
                            <label for="status_rejected" class="status-label status-label-rejected">Rejected</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_response">Your Response</label>
                        <textarea id="admin_response" name="admin_response" class="form-control" required></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Submit Response</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functionality
        const modal = document.getElementById('responseModal');
        const closeBtn = document.getElementsByClassName('close')[0];
        const closeBtnAlt = document.getElementsByClassName('close-modal')[0];
        const viewButtons = document.querySelectorAll('.view-request');
        
        // Close modal when clicking the X
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
        
        // Close modal when clicking the Cancel button
        closeBtnAlt.onclick = function() {
            modal.style.display = "none";
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        // Open modal with request data
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const type = this.getAttribute('data-type');
                const subject = this.getAttribute('data-subject');
                const message = this.getAttribute('data-message');
                const status = this.getAttribute('data-status');
                const response = this.getAttribute('data-response');
                const username = this.getAttribute('data-username');
                const useremail = this.getAttribute('data-useremail');
                const date = this.getAttribute('data-date');
                
                document.getElementById('modalSubject').textContent = subject;
                document.getElementById('modalType').textContent = type;
                document.getElementById('modalMessage').textContent = message;
                document.getElementById('modalStatus').textContent = status;
                document.getElementById('modalUsername').textContent = username;
                document.getElementById('modalUserEmail').textContent = useremail;
                document.getElementById('modalDate').textContent = date;
                
                document.getElementById('request_id').value = id;
                document.getElementById('admin_response').value = response;
                
                // Set the correct status radio button
                const statusRadio = document.getElementById('status_' + status.toLowerCase());
                if (statusRadio) {
                    statusRadio.checked = true;
                }
                
                modal.style.display = "block";
            });
        });
        
        // Form validation
        document.getElementById('responseForm').addEventListener('submit', function(e) {
            const responseField = document.getElementById('admin_response');
            
            if (responseField.value.trim() === '') {
                e.preventDefault();
                alert('Please enter your response');
                responseField.focus();
                return false;
            }
            
            // Check if a status is selected
            const statusOptions = document.querySelectorAll('input[name="status"]');
            let statusSelected = false;
            
            statusOptions.forEach(option => {
                if (option.checked) {
                    statusSelected = true;
                }
            });
            
            if (!statusSelected) {
                e.preventDefault();
                alert('Please select a status for this request');
                return false;
            }
        });
    </script>
</body>
<?php include('footer user.php'); ?>
</html>