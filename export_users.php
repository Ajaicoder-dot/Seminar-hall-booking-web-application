<?php
// Make sure there are no whitespace or output before this opening PHP tag
session_start();
include 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location:login.php');
    exit();
}

// Check if format is specified
if (!isset($_GET['format'])) {
    $_SESSION['message'] = "Export format not specified";
    $_SESSION['message_type'] = "danger";
    header('Location: manage_users.php');
    exit();
}

// Get all users
$query = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($query);
$users = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Make sure there are no whitespace, echo statements, or print statements before this point
$format = $_GET['format'];

switch ($format) {
    case 'pdf':
        // Turn off output buffering
        ob_clean();
        // Start output buffering
        ob_start();
        exportPDF($users);
        break;
    case 'excel':
        // Turn off output buffering
        ob_clean();
        // Start output buffering
        ob_start();
        exportExcel($users);
        break;
    case 'csv':
        // Turn off output buffering
        ob_clean();
        // Start output buffering
        ob_start();
        exportCSV($users);
        break;
    default:
        $_SESSION['message'] = "Invalid export format";
        $_SESSION['message_type'] = "danger";
        header('Location: manage_users.php');
        exit();
}

/**
 * Export users data as PDF using TCPDF
 */
function exportPDF($users) {
    // Include TCPDF library
    require_once('vendor/tecnickcom/tcpdf/tcpdf.php');

    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('University Management System');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Users List');
    $pdf->SetSubject('University Management System Users');
    $pdf->SetKeywords('Users, University, Management');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set margins
    $pdf->SetMargins(15, 15, 15);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', 'B', 16);

    // Title
    $pdf->Cell(0, 15, 'University Management System - Users List', 0, 1, 'C');
    $pdf->Ln(5);

    // Table header
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(52, 152, 219);
    $pdf->SetTextColor(255);
    $pdf->Cell(15, 10, 'ID', 1, 0, 'C', 1);
    $pdf->Cell(50, 10, 'Name', 1, 0, 'C', 1);
    $pdf->Cell(70, 10, 'Email', 1, 0, 'C', 1);
    $pdf->Cell(30, 10, 'Role', 1, 0, 'C', 1);
    $pdf->Cell(30, 10, 'Created At', 1, 1, 'C', 1);

    // Table data
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0);
    $pdf->SetFillColor(245, 245, 245);
    $fill = false;

    foreach ($users as $user) {
        $pdf->Cell(15, 10, $user['id'], 1, 0, 'C', $fill);
        $pdf->Cell(50, 10, $user['name'], 1, 0, 'L', $fill);
        $pdf->Cell(70, 10, $user['email'], 1, 0, 'L', $fill);
        $pdf->Cell(30, 10, $user['role'], 1, 0, 'C', $fill);
        $pdf->Cell(30, 10, date('M d, Y', strtotime($user['created_at'])), 1, 1, 'C', $fill);
        $fill = !$fill;
    }

    // Close and output PDF document
    $pdf->Output('university_users.pdf', 'D');
    exit;
}

/**
 * Export users data as Excel
 */
function exportExcel($users) {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="university_users.xls"');
    header('Cache-Control: max-age=0');
    
    // Create Excel content
    echo '<table border="1">';
    
    // Header row
    echo '<tr style="background-color: #3498db; color: white; font-weight: bold;">';
    echo '<th>ID</th>';
    echo '<th>Name</th>';
    echo '<th>Email</th>';
    echo '<th>Role</th>';
    echo '<th>Created At</th>';
    echo '</tr>';
    
    // Data rows
    foreach ($users as $user) {
        echo '<tr>';
        echo '<td>' . $user['id'] . '</td>';
        echo '<td>' . $user['name'] . '</td>';
        echo '<td>' . $user['email'] . '</td>';
        echo '<td>' . $user['role'] . '</td>';
        echo '<td>' . date('M d, Y', strtotime($user['created_at'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}

/**
 * Export users data as CSV
 */
function exportCSV($users) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="university_users.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add header row
    fputcsv($output, ['ID', 'Name', 'Email', 'Role', 'Created At']);
    
    // Add data rows
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['name'],
            $user['email'],
            $user['role'],
            date('M d, Y', strtotime($user['created_at']))
        ]);
    }
    
    fclose($output);
    exit;
}
?>