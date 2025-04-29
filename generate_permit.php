<?php
// Start output buffering at the very beginning
ob_start();

session_start();
include 'config.php'; // Database connection

// Include the appropriate navbar based on user role
if ($_SESSION['role'] == 'Professor') {
    include 'navbar1.php'; // Professor navbar
} else {
    include 'navbar.php'; // Admin/HOD navbar
}

// Ensure user is logged in
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD' && $_SESSION['role'] != 'Professor')) {
    header("Location: login.php");
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Error: No booking ID provided.";
    exit();
}

$booking_id = intval($_GET['id']);

// Check if we need to output PDF directly or show HTML with options
$output_mode = isset($_GET['output']) ? $_GET['output'] : 'html';

// If we're generating a PDF, make sure no output has been sent
if ($output_mode == 'pdf') {
    // Clean any existing output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
}

// Fetch booking details - Fix the SQL query by removing non-existent columns
$sql = "SELECT 
            hb.booking_id, 
            h.hall_name, 
            hb.organizer_name, 
            hb.organizer_email, 
            hb.organizer_department,
            hb.organizer_contact,
            hb.program_name, 
            hb.program_type,
            hb.program_purpose,
            hb.from_date, 
            hb.start_time, 
            hb.end_date, 
            hb.end_time, 
            hb.status,
            hb.created_at
        FROM hall_bookings hb
        JOIN halls h ON hb.hall_id = h.hall_id
        WHERE hb.booking_id = $booking_id AND hb.status = 'Approved'";

// Execute the query and check for errors
$result = $conn->query($sql);

// Debug the SQL query if it fails
if ($result === false) {
    echo "SQL Error: " . $conn->error;
    exit();
}

// Check if booking exists and is approved
if ($result->num_rows == 0) {
    echo "Error: Booking not found or not approved.";
    exit();
}

$booking = $result->fetch_assoc();

// Add default values for missing hall details
$booking['hall_capacity'] = 'Not specified';
$booking['hall_location'] = 'Not specified';

// Get university details - Add error handling
$university_sql = "SELECT * FROM university_details LIMIT 1";
$university_result = $conn->query($university_sql);

// Check if university_details table exists and has data
if ($university_result === false) {
    // Table might not exist, use default values
    $university = [
        'name' => 'University Name',
        'address' => 'University Address',
        'phone' => 'University Phone',
        'email' => 'university@example.com',
        'website' => 'www.university.edu'
    ];
} else {
    $university = $university_result->num_rows > 0 ? $university_result->fetch_assoc() : [
        'name' => 'University Name',
        'address' => 'University Address',
        'phone' => 'University Phone',
        'email' => 'university@example.com',
        'website' => 'www.university.edu'
    ];
}

// Format dates
$from_date = date('d F Y', strtotime($booking['from_date']));
$to_date = date('d F Y', strtotime($booking['end_date']));
$start_time = date('h:i A', strtotime($booking['start_time']));
$end_time = date('h:i A', strtotime($booking['end_time']));
$permit_date = date('d F Y');
$permit_number = 'HALL-' . str_pad($booking['booking_id'], 5, '0', STR_PAD_LEFT);

// Check if we need to output PDF directly or show HTML with options
$output_mode = isset($_GET['output']) ? $_GET['output'] : 'html';

// Generate HTML content
$html_content = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hall Booking Permit</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4a6fdc;
            padding-bottom: 10px;
        }
        .university-name {
            font-size: 24px;
            font-weight: bold;
            color: #4a6fdc;
            margin-bottom: 5px;
        }
        .university-details {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .permit-title {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            color: #4a6fdc;
            text-decoration: underline;
        }
        .permit-number {
            text-align: right;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #4a6fdc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .signatures {
            margin-top: 80px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            width: 45%;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 10px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(200, 200, 200, 0.2);
            z-index: -1;
        }
        .university-watermark {
            position: absolute;
            top: 70%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            color: rgba(200, 200, 200, 0.2);
            z-index: -1;
            font-family: "Times New Roman", serif;
            font-weight: bold;
        }
        .action-buttons {
            position: fixed;
            top: 100px; /* Changed from 20px to 100px to move buttons down */
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }
        .btn {
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .btn-primary {
            background-color: #4a6fdc;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        @media print {
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    ' . ($output_mode == 'html' ? '
    <div class="action-buttons">
        <a href="javascript:window.print();" class="btn btn-primary"><i class="fas fa-print"></i> Print</a>
        <a href="generate_permit.php?id=' . $booking_id . '&output=pdf" class="btn btn-success"><i class="fas fa-download"></i> Download PDF</a>
        <a href="' . ($_SESSION['role'] == 'Professor' ? 'view_my_booking.php' : 'view_bookings.php') . '" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    ' : '') . '
    <div class="container">
        <div class="header">
            <div class="university-name">Pondicherry University</div>
            <div class="university-details">Main Road, Kalapet, Puducherry</div>
            <div class="university-details">Phone: 9361685137 | Email: ' . htmlspecialchars($university['email']) . '</div>
            <div class="university-details">Website: ' . htmlspecialchars($university['website']) . '</div>
        </div>
        
        <div class="permit-title">HALL BOOKING PERMIT</div>
        
        <div class="permit-number">Permit Number: ' . $permit_number . '</div>
        <div class="permit-number">Date: ' . $permit_date . '</div>
        
        <div class="section">
            <div class="section-title">HALL DETAILS</div>
            <table>
                <tr>
                    <th>Hall Name</th>
                    <td>' . htmlspecialchars($booking['hall_name']) . '</td>
                </tr>
                <tr>
                    <th>Location</th>
                    <td>' . htmlspecialchars($booking['hall_location']) . '</td>
                </tr>
                <tr>
                    <th>Capacity</th>
                    <td>' . htmlspecialchars($booking['hall_capacity']) . ' persons</td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">ORGANIZER DETAILS</div>
            <table>
                <tr>
                    <th>Name</th>
                    <td>' . htmlspecialchars($booking['organizer_name']) . '</td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td>' . htmlspecialchars($booking['organizer_department']) . '</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>' . htmlspecialchars($booking['organizer_email']) . '</td>
                </tr>
                <tr>
                    <th>Contact</th>
                    <td>' . htmlspecialchars($booking['organizer_contact']) . '</td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">PROGRAM DETAILS</div>
            <table>
                <tr>
                    <th>Program Name</th>
                    <td>' . htmlspecialchars($booking['program_name']) . '</td>
                </tr>
                <tr>
                    <th>Program Type</th>
                    <td>' . htmlspecialchars($booking['program_type']) . '</td>
                </tr>
                <tr>
                    <th>Purpose</th>
                    <td>' . htmlspecialchars($booking['program_purpose']) . '</td>
                </tr>
                <tr>
                    <th>Date & Time</th>
                    <td>From: ' . $from_date . ' at ' . $start_time . '<br>To: ' . $to_date . ' at ' . $end_time . '</td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <p>This permit authorizes the use of the above-mentioned hall for the specified program and time period. The organizer is responsible for ensuring that all university rules and regulations are followed during the event.</p>
            
            <p><strong>Terms and Conditions:</strong></p>
            <ol>
                <li>The hall must be used only for the purpose mentioned in this permit.</li>
                <li>Any damage to the hall or its facilities will be the responsibility of the organizer.</li>
                <li>The hall must be vacated promptly at the end of the allocated time.</li>
                <li>Noise levels must be kept at a reasonable level.</li>
                <li>No smoking, alcohol, or prohibited substances are allowed in the hall.</li>
                <li>This permit must be presented upon request by university authorities.</li>
            </ol>
        </div>
        
        <div class="signatures">
            <div class="signature">
                <p>Authorized By</p>
                <p>' . htmlspecialchars($_SESSION['name'] ?? 'Administrator') . '</p>
            </div>
            <div class="signature">
                <p>Received By</p>
                <p>' . htmlspecialchars($booking['organizer_name']) . '</p>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an official document of ' . htmlspecialchars($university['name']) . '. Any alteration or misuse may result in disciplinary action.</p>
        </div>
        
        <div class="watermark">APPROVED</div>
        <div class="university-watermark">PONDICHERRY UNIVERSITY</div>
    </div>
    ' . ($output_mode == 'html' ? '
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    ' : '') . '
</body>
</html>
';

// Handle different output modes
if ($output_mode == 'pdf') {
    // Check if TCPDF is available in multiple possible locations
    $tcpdf_found = false;
    $tcpdf_paths = [
        'tcpdf/tcpdf.php',
        'vendor/tecnickcom/tcpdf/tcpdf.php',
        '../tcpdf/tcpdf.php',
        '../vendor/tecnickcom/tcpdf/tcpdf.php',
        'tecnickcom/tcpdf/tcpdf.php',
        'TCPDF/tcpdf.php'
    ];
    
    foreach ($tcpdf_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            $tcpdf_found = true;
            break;
        }
    }
    
    if ($tcpdf_found) {
        // Make sure no output has been sent before generating PDF
        if (headers_sent()) {
            echo '<div style="color: red; padding: 20px; text-align: center;">
                    <h3>Error: Headers already sent</h3>
                    <p>Cannot generate PDF because some output has already been sent to the browser.</p>
                    <p><a href="generate_permit.php?id=' . $booking_id . '" class="btn btn-primary" style="display: inline-block; margin-top: 15px; padding: 10px 15px; background-color: #4a6fdc; color: white; text-decoration: none; border-radius: 5px;">Go back to permit</a></p>
                  </div>';
            exit;
        }
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($university['name']);
        $pdf->SetTitle('Hall Booking Permit');
        $pdf->SetSubject('Hall Booking Permit for ' . $booking['program_name']);
        
        // Remove header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Write HTML content
        $pdf->writeHTML($html_content, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output('hall_booking_permit_' . $booking_id . '.pdf', 'D'); // 'D' forces download
        exit;
    } else {
        // If TCPDF is not available, show an error with more detailed instructions
        echo '<div style="color: red; padding: 20px; text-align: center;">
                <h3>Error: TCPDF library not found</h3>
                <p>The system searched in the following locations:</p>
                <ul style="text-align: left; display: inline-block;">';
        
        foreach ($tcpdf_paths as $path) {
            echo '<li>' . htmlspecialchars($path) . '</li>';
        }
        
        echo '</ul>
                <p>Please install TCPDF library using one of these methods:</p>
                <ol style="text-align: left; display: inline-block;">
                    <li>Download TCPDF from <a href="https://github.com/tecnickcom/TCPDF/releases" target="_blank">GitHub</a> and extract to a "tcpdf" folder in this directory</li>
                    <li>Install via Composer: <code>composer require tecnickcom/tcpdf</code></li>
                </ol>
                <p><a href="generate_permit.php?id=' . $booking_id . '" class="btn btn-primary" style="display: inline-block; margin-top: 15px; padding: 10px 15px; background-color: #4a6fdc; color: white; text-decoration: none; border-radius: 5px;">Go back to permit</a></p>
              </div>';
        exit;
    }
} else {
    // Output as HTML
    echo $html_content;
    
    // Include footer after the HTML content
    if ($output_mode == 'html') {
        include 'footer user.php';
    }
}

$conn->close();

// End output buffering if we're still in HTML mode
if ($output_mode == 'html') {
    ob_end_flush();
}
?>