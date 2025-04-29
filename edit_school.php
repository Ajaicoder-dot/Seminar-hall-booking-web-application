<?php
session_start();
// Include database connection
include 'config.php';
include 'navbar.php';

// Check if the user is logged in and authorized
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$school_id = "";
$school_name = "";
$dean_name = "";
$dean_contact = "";
$dean_email = "";
$dean_intercom = "";
$dean_status = "";

// Fetch school details if an ID is provided
if (isset($_POST['school_id'])) {
    $school_id = $_POST['school_id'];

    try {
        // Fetch school details from the database
        $stmt = $conn->prepare("SELECT * FROM schools WHERE school_id = ?");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $school = $result->fetch_assoc();
            $school_name = $school['school_name'];
            $dean_name = $school['dean_name'];
            $dean_contact = $school['dean_contact_number'];
            $dean_email = $school['dean_email'];
            $dean_intercom = $school['dean_intercome'];
            $dean_status = $school['dean_status'];
        } else {
            echo "School not found.";
            exit();
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    echo "Invalid request.";
    exit();
}

// Handle form submission to update school details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_school'])) {
    $school_name = $_POST['school_name'];
    $dean_name = $_POST['dean_name'];
    $dean_contact = $_POST['dean_contact'];
    $dean_email = $_POST['dean_email'];
    $dean_intercom = $_POST['dean_intercom'];
    $dean_status = $_POST['dean_status'];

    try {
        // Update school details in the database
        $stmt = $conn->prepare("UPDATE schools SET school_name = ?, dean_name = ?, dean_contact_number = ?, dean_email = ?, dean_intercome = ?, dean_status = ? WHERE school_id = ?");
        $stmt->bind_param("ssssssi", $school_name, $dean_name, $dean_contact, $dean_email, $dean_intercom, $dean_status, $school_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "<script>alert('School details updated successfully!'); window.location.href='modify_school.php';</script>";
        } else {
            echo "<script>alert('No changes were made or update failed.');</script>";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Edit School</title>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 0;
        }
        
        .edit-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin: -30px -30px 30px -30px;
            text-align: center;
            margin-top: -120px; 
            position: relative;
            z-index: 1;
           
        }
        
        .page-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .form-label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 15px;
            height: auto;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .input-group-text {
            background-color: #3498db;
            color: white;
            border: none;
        }
        
        .form-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
        }
        
        .section-title {
            color: #3498db;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .btn-action {
            padding: 10px 25px;
            font-weight: 500;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-update {
            background-color: #2ecc71;
            border: none;
            color: white;
        }
        
        .btn-cancel {
            background-color: #95a5a6;
            border: none;
            color: white;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-update:hover {
            background-color: #27ae60;
        }
        
        .btn-cancel:hover {
            background-color: #7f8c8d;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #3498db;
        }
        
        .input-with-icon .form-control {
            padding-left: 40px;
        }
        
        @media (max-width: 768px) {
            .edit-container {
                margin: 20px;
                padding: 20px;
            }
            
            .page-header {
                margin: -20px -20px 20px -20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="edit-container">
            <div class="page-header">
                <h2><i class="fas fa-university"></i> Edit School Details</h2>
            </div>
            
            <form method="post" action="">
                <input type="hidden" name="school_id" value="<?= htmlspecialchars($school_id); ?>">
                
                <!-- School Information Section -->
                <div class="form-section">
                    <div class="section-title"><i class="fas fa-info-circle"></i> School Information</div>
                    
                    <!-- School Name -->
                    <div class="mb-3">
                        <label for="school_name" class="form-label">School Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-university input-icon"></i>
                            <input type="text" class="form-control" id="school_name" name="school_name" value="<?= htmlspecialchars($school_name); ?>" required>
                        </div>
                    </div>
                </div>
                
                <!-- Dean Information Section -->
                <div class="form-section">
                    <div class="section-title"><i class="fas fa-user-tie"></i> Dean Information</div>
                    
                    <!-- Dean Name -->
                    <div class="mb-3">
                        <label for="dean_name" class="form-label">Dean Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-control" id="dean_name" name="dean_name" value="<?= htmlspecialchars($dean_name); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Dean Status -->
                    <div class="mb-3">
                        <label for="dean_status" class="form-label">Dean Status</label>
                        <div class="input-with-icon">
                            <i class="fas fa-info-circle input-icon"></i>
                            <select class="form-control" id="dean_status" name="dean_status" required>
                                <option value="Active" <?= ($dean_status == 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="On Leave" <?= ($dean_status == 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
                                <option value="Retired" <?= ($dean_status == 'Retired') ? 'selected' : ''; ?>>Retired</option>
                                <option value="Transferred" <?= ($dean_status == 'Transferred') ? 'selected' : ''; ?>>Transferred</option>
                                <?php if ($dean_status && !in_array($dean_status, ['Active', 'On Leave', 'Retired', 'Transferred'])): ?>
                                    <option value="<?= htmlspecialchars($dean_status); ?>" selected><?= htmlspecialchars($dean_status); ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information Section -->
                <div class="form-section">
                    <div class="section-title"><i class="fas fa-address-card"></i> Contact Information</div>
                    
                    <!-- Dean Contact -->
                    <div class="mb-3">
                        <label for="dean_contact" class="form-label">Phone Number</label>
                        <div class="input-with-icon">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="text" class="form-control" id="dean_contact" name="dean_contact" value="<?= htmlspecialchars($dean_contact); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Dean Email -->
                    <div class="mb-3">
                        <label for="dean_email" class="form-label">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" class="form-control" id="dean_email" name="dean_email" value="<?= htmlspecialchars($dean_email); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Dean Intercom -->
                    <div class="mb-3">
                        <label for="dean_intercom" class="form-label">Intercom Number</label>
                        <div class="input-with-icon">
                            <i class="fas fa-phone-office input-icon"></i>
                            <input type="text" class="form-control" id="dean_intercom" name="dean_intercom" value="<?= htmlspecialchars($dean_intercom); ?>" required>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="submit" name="update_school" class="btn btn-action btn-update">
                        <i class="fas fa-save"></i> Update School
                    </button>
                    <a href="modify_school.php" class="btn btn-action btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
<?php include('footer user.php'); ?>
</html>
