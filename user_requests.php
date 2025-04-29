<?php
session_start();
include('config.php');

// Ensure the user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_type = trim($_POST['request_type']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate inputs
    if (empty($request_type) || empty($subject) || empty($message)) {
        $error_message = "All fields are required.";
    } else {
        // Insert the request into the database
        $insert_query = "INSERT INTO user_requests (user_id, request_type, subject, message, status, created_at) 
                         VALUES (?, ?, ?, ?, 'Pending', NOW())";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isss", $user_id, $request_type, $subject, $message);
        
        if ($stmt->execute()) {
            $success_message = "Your request has been submitted successfully!";
        } else {
            $error_message = "Error submitting your request: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch user's previous requests
$requests_query = "SELECT r.*, 
                   CASE WHEN r.admin_response IS NULL THEN 'No' ELSE 'Yes' END as has_response 
                   FROM user_requests r 
                   WHERE r.user_id = ? 
                   ORDER BY r.created_at DESC";
$stmt = $conn->prepare($requests_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_requests = [];
while ($row = $result->fetch_assoc()) {
    $user_requests[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Request - Pondicherry University</title>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- AOS (Animate On Scroll) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    
    <style>
        /* Base styles */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --secondary-color: #6366f1;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --gray-color: #64748b;
            --border-radius: 12px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body, html {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--dark-color);
            font-size: 2.5rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            width: 100px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 40px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            pointer-events: none;
        }
        
        .card-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header h1 i {
            font-size: 1.2em;
            background: rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--dark-color);
            transition: var(--transition);
            position: relative;
            padding-left: 12px;
        }
        
        .form-group label::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 18px;
            background: var(--primary-color);
            border-radius: 2px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: var(--transition);
            background-color: #f8fafc;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background-color: #fff;
        }
        
        .form-control::placeholder {
            color: #94a3b8;
        }
        
        .form-select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 15px;
            appearance: none;
            background-color: #f8fafc;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 18px center;
            background-size: 12px;
            transition: var(--transition);
        }
        
        .form-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background-color: #fff;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn::after {
            content: '';
            position: absolute;
            width: 0;
            height: 100%;
            top: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.1);
            transition: width 0.3s ease;
            z-index: -1;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(37, 99, 235, 0.25);
        }
        
        .btn:hover::after {
            width: 100%;
            left: 0;
            right: auto;
        }
        
        .btn-secondary {
            background: var(--gray-color);
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            position: relative;
            animation: slideInDown 0.5s forwards;
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border-left: 4px solid var(--danger-color);
        }
        
        .alert::before {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            background-size: cover;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        /* Table styles */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
        }
        
        th, td {
            padding: 16px 20px;
            text-align: left;
        }
        
        th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: var(--dark-color);
            position: relative;
        }
        
        th:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, var(--primary-color), transparent);
        }
        
        tr {
            border-bottom: 1px solid #e2e8f0;
            transition: var(--transition);
        }
        
        tr:last-child {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: #f8fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-processing {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        
        .view-response {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            position: relative;
            padding: 2px 0;
        }
        
        .view-response:after {
            content: '';
            position: absolute;
            width: 100%;
            transform: scaleX(0);
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--primary-color);
            transform-origin: bottom right;
            transition: transform 0.3s ease-out;
        }
        
        .view-response:hover:after {
            transform: scaleX(1);
            transform-origin: bottom left;
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
            animation: fadeIn 0.3s ease;
            backdrop-filter: blur(5px);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 0;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 600px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            transform: scale(0.9);
            opacity: 0;
            animation: zoomIn 0.3s 0.1s forwards;
            overflow: hidden;
        }
        
        @keyframes zoomIn {
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 30px;
            position: relative;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 22px;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            color: white;
            font-size: 24px;
            background: rgba(255, 255, 255, 0.2);
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        #modalSubject {
            color: var(--dark-color);
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .request-content {
            margin-bottom: 25px;
        }
        
        .request-content p:first-child {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .response-box {
            background-color: #f1f5f9;
            padding: 20px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
        }
        
        .response-box p:first-child {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        /* Animations */
        .animated-icon {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .form-control, .form-select {
            transition: transform 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            transform: translateY(-3px);
        }
        
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            animation: fadeIn 1s;
        }
        
        .empty-state i {
            font-size: 60px;
            color: #cbd5e1;
            margin-bottom: 20px;
            display: block;
        }
        
        .empty-state p {
            color: #64748b;
            font-size: 18px;
        }
        
        /* Status indicators */
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        
        .status-pending {
            background-color: #f59e0b;
            box-shadow: 0 0 0 rgba(245, 158, 11, 0.4);
            animation: pulse-warning 2s infinite;
        }
        
        .status-approved {
            background-color: #10b981;
        }
        
        .status-rejected {
            background-color: #ef4444;
        }
        
        .status-processing {
            background-color: #3b82f6;
            box-shadow: 0 0 0 rgba(59, 130, 246, 0.4);
            animation: pulse-info 2s infinite;
        }
        
        @keyframes pulse-warning {
            0% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
            }
        }
        
        @keyframes pulse-info {
            0% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-header h1 {
                font-size: 20px;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 14px;
            }
            
            th, td {
                padding: 12px 15px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include('navbar1.php'); ?>
    
    <div class="container">
        <h1 class="page-title" data-aos="fade-up">Request Management Center</h1>
        
        <div class="card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-header">
                <h1><i class="fas fa-paper-plane animated-icon"></i> Submit a Request</h1>
            </div>
            <div class="card-body">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle" style="margin-right: 10px;"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" data-aos="fade-up" data-aos-delay="150">
                    <div class="form-group">
                        <label for="request_type">Request Type</label>
                        <select id="request_type" name="request_type" class="form-select" required>
                            <option value="">-- Select Request Type --</option>
                            <option value="Question">General Question</option>
                            <option value="Feedback">Feedback</option>
                            <option value="Booking Issue">Booking Issue</option>
                            <option value="Technical Problem">Technical Problem</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" placeholder="Brief description of your request" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" class="form-control" placeholder="Please provide details about your request..." required></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn">
                            <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card" data-aos="fade-up" data-aos-delay="200">
            <div class="card-header">
                <h1><i class="fas fa-history"></i> Your Previous Requests</h1>
            </div>
            <div class="card-body">
                <?php if (empty($user_requests)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>You haven't submitted any requests yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" data-aos="fade-up" data-aos-delay="250">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Response</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_requests as $request): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                        <td><i class="fas fa-tag" style="color: var(--primary-color); margin-right: 5px;"></i><?php echo htmlspecialchars($request['request_type']); ?></td>
                                        <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                        <td>
                                            <span class="status-indicator status-<?php echo strtolower($request['status']); ?>"></span>
                                            <span class="badge badge-<?php echo strtolower($request['status']); ?>">
                                                <?php echo htmlspecialchars($request['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['has_response'] === 'Yes'): ?>
                                                <a href="#" class="view-response" data-id="<?php echo $request['id']; ?>">
                                                    <i class="fas fa-comment-dots" style="margin-right: 5px;"></i>View Response
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #94a3b8;"><i class="fas fa-clock" style="margin-right: 5px;"></i>Awaiting Response</span>
                                            <?php endif; ?>
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
            <div class="modal-header">
                <h2><i class="fas fa-reply"></i> Admin Response</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <h3 id="modalSubject"></h3>
                <div class="request-content">
                    <p><i class="fas fa-question-circle" style="margin-right: 8px;"></i>Your request:</p>
                    <p id="modalRequest"></p>
                </div>
                <div class="response-box">
                    <p><i class="fas fa-comment-dots" style="margin-right: 8px;"></i>Admin response:</p>
                    <p id="modalResponse"></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- AOS JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    
    <script>
        // Initialize AOS
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
        });
        
        // Form field animation
        const formFields = document.querySelectorAll('.form-control, .form-select');
        formFields.forEach(field => {
            field.addEventListener('focus', function() {
                this.parentElement.querySelector('label').style.color = 'var(--primary-color)';
            });
            
            field.addEventListener('blur', function() {
                this.parentElement.querySelector('label').style.color = 'var(--dark-color)';
            });
        });
        
        // Modal functionality with animations
        const modal = document.getElementById('responseModal');
        const closeBtn = document.getElementsByClassName('close')[0];
        const responseLinks = document.querySelectorAll('.view-response');
        
        // Close modal when clicking the X
        closeBtn.onclick = function() {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = "none";
                modal.style.opacity = '1';
            }, 300);
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = "none";
                    modal.style.opacity = '1';
                }, 300);
            }
        }
        
        // Open modal with response data
        responseLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const requestId = this.getAttribute('data-id');
                
                // Add loading effect 
                this.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 5px;"></i>Loading...';
                
                // Fetch request details via AJAX
                fetch('get_request_details.php?id=' + requestId)
                    .then(response => response.json())
                    .then(data => {
                        // Restore original link text
                        this.innerHTML = '<i class="fas fa-comment-dots" style="margin-right: 5px;"></i>View Response';
                        
                        document.getElementById('modalSubject').textContent = data.subject;
                        document.getElementById('modalRequest').textContent = data.message;
                        document.getElementById('modalResponse').textContent = data.admin_response;
                        modal.style.display = "block";
                    })
                    .catch(error => {
                        console.error('Error fetching request details:', error);
                        // Restore original link text
                        this.innerHTML = '<i class="fas fa-comment-dots" style="margin-right: 5px;"></i>View Response';
                        
                        // Add fade-in animation to modal content
                        document.getElementById('modalSubject').textContent = data.subject;
                        document.getElementById('modalRequest').textContent = data.message;
                        document.getElementById('modalResponse').textContent = data.admin_response;
                        
                        // Show the modal with animation
                        modal.style.display = "block";
                    })
                    .catch(error => {
                        console.error('Error fetching request details:', error);
                        this.innerHTML = '<i class="fas fa-comment-dots" style="margin-right: 5px;"></i>View Response';
                        alert('Unable to load response. Please try again.');
                    });
            });
        });
        
        // Form validation with visual feedback
        document.querySelector('form').addEventListener('submit', function(e) {
            const requestType = document.getElementById('request_type');
            const subject = document.getElementById('subject');
            const message = document.getElementById('message');
            let isValid = true;
            
            // Reset previous validation styles
            [requestType, subject, message].forEach(field => {
                field.style.borderColor = '#e2e8f0';
                field.style.boxShadow = 'none';
            });
            
            if (requestType.value === '') {
                e.preventDefault();
                requestType.style.borderColor = 'var(--danger-color)';
                requestType.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                
                // Shake animation for invalid field
                requestType.classList.add('shake-animation');
                setTimeout(() => requestType.classList.remove('shake-animation'), 600);
                
                isValid = false;
            }
            
            if (subject.value.trim() === '') {
                e.preventDefault();
                subject.style.borderColor = 'var(--danger-color)';
                subject.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                
                // Shake animation for invalid field
                subject.classList.add('shake-animation');
                setTimeout(() => subject.classList.remove('shake-animation'), 600);
                
                isValid = false;
            }
            
            if (message.value.trim() === '') {
                e.preventDefault();
                message.style.borderColor = 'var(--danger-color)';
                message.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                
                // Shake animation for invalid field
                message.classList.add('shake-animation');
                setTimeout(() => message.classList.remove('shake-animation'), 600);
                
                isValid = false;
            }
            
            if (!isValid) {
                // Add shake animation style if not already in document
                if (!document.getElementById('shake-animation-style')) {
                    const styleSheet = document.createElement('style');
                    styleSheet.id = 'shake-animation-style';
                    styleSheet.innerHTML = `
                        @keyframes shake {
                            0%, 100% { transform: translateX(0); }
                            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                            20%, 40%, 60%, 80% { transform: translateX(5px); }
                        }
                        .shake-animation {
                            animation: shake 0.6s cubic-bezier(.36,.07,.19,.97) both;
                        }
                    `;
                    document.head.appendChild(styleSheet);
                }
                
                return false;
            }
            
            // Add loading animation to submit button when form is valid
            if (isValid) {
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i> Submitting...';
                submitButton.disabled = true;
            }
        });
        
        // Add floating labels animation
        document.addEventListener('DOMContentLoaded', function() {
            // Create floating effect for input fields when they have content
            const formControls = document.querySelectorAll('.form-control, .form-select');
            
            formControls.forEach(input => {
                // Check initial state
                if (input.value !== '') {
                    input.classList.add('has-content');
                }
                
                // Add event listeners
                input.addEventListener('focus', function() {
                    this.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.classList.remove('focused');
                    if (this.value !== '') {
                        this.classList.add('has-content');
                    } else {
                        this.classList.remove('has-content');
                    }
                });
                
                // For select elements
                if (input.tagName === 'SELECT') {
                    input.addEventListener('change', function() {
                        if (this.value !== '') {
                            this.classList.add('has-content');
                        } else {
                            this.classList.remove('has-content');
                        }
                    });
                }
            });
            
            // Add ripple effect to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple-effect');
                    
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = `${size}px`;
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Add ripple effect styles if not already present
            if (!document.getElementById('ripple-effect-style')) {
                const rippleStyle = document.createElement('style');
                rippleStyle.id = 'ripple-effect-style';
                rippleStyle.innerHTML = `
                    .btn {
                        position: relative;
                        overflow: hidden;
                    }
                    .ripple-effect {
                        position: absolute;
                        border-radius: 50%;
                        background-color: rgba(255, 255, 255, 0.4);
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        pointer-events: none;
                    }
                    @keyframes ripple {
                        to {
                            transform: scale(2);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(rippleStyle);
            }
            
            // Table row hover effect
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                    this.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.1)';
                    this.style.zIndex = '1';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                    this.style.boxShadow = 'none';
                    this.style.zIndex = 'auto';
                });
            });
        });
        
        // Add scroll-to-top button
        window.addEventListener('DOMContentLoaded', function() {
            const scrollTopBtn = document.createElement('button');
            scrollTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            scrollTopBtn.className = 'scroll-top-btn';
            document.body.appendChild(scrollTopBtn);
            
            // Add scroll button styles
            const scrollBtnStyle = document.createElement('style');
            scrollBtnStyle.innerHTML = `
                .scroll-top-btn {
                    position: fixed;
                    bottom: 30px;
                    right: 30px;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    background: var(--primary-color);
                    color: white;
                    border: none;
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s ease;
                    z-index: 999;
                }
                .scroll-top-btn.visible {
                    opacity: 1;
                    visibility: visible;
                }
                .scroll-top-btn:hover {
                    background: var(--primary-dark);
                    transform: translateY(-5px);
                }
                .scroll-top-btn i {
                    font-size: 20px;
                }
            `;
            document.head.appendChild(scrollBtnStyle);
            
            // Show/hide scroll button based on scroll position
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollTopBtn.classList.add('visible');
                } else {
                    scrollTopBtn.classList.remove('visible');
                }
            });
            
            // Scroll to top with smooth animation
            scrollTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
        
        // Add loading animation for the whole page
        window.addEventListener('load', function() {
            const pageLoader = document.getElementById('page-loader');
            if (pageLoader) {
                pageLoader.style.opacity = '0';
                setTimeout(() => {
                    pageLoader.style.display = 'none';
                }, 500);
            }
        });
        
        // Add page loader to DOM
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.getElementById('page-loader')) {
                const loader = document.createElement('div');
                loader.id = 'page-loader';
                loader.innerHTML = `
                    <div class="spinner"></div>
                    <p>Loading...</p>
                `;
                document.body.prepend(loader);
                
                const loaderStyle = document.createElement('style');
                loaderStyle.innerHTML = `
                    #page-loader {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-color: #fff;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        z-index: 9999;
                        transition: opacity 0.5s ease;
                    }
                    #page-loader .spinner {
                        width: 50px;
                        height: 50px;
                        border: 3px solid rgba(37, 99, 235, 0.2);
                        border-radius: 50%;
                        border-top-color: var(--primary-color);
                        animation: spin 1s linear infinite;
                        margin-bottom: 20px;
                    }
                    #page-loader p {
                        color: var(--dark-color);
                        font-weight: 500;
                    }
                    @keyframes spin {
                        to {
                            transform: rotate(360deg);
                        }
                    }
                `;
                document.head.appendChild(loaderStyle);
            }
        });
        
        // Add card hover interactions
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    const cardHeader = this.querySelector('.card-header');
                    if (cardHeader) {
                        cardHeader.style.transform = 'translateY(-5px)';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    const cardHeader = this.querySelector('.card-header');
                    if (cardHeader) {
                        cardHeader.style.transform = 'translateY(0)';
                    }
                });
            });
        });
    </script>
</body>
<?php include('footer user.php'); ?>
</html>