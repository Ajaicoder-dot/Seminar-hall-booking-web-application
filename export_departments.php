<?php
// Prevent any output before headers
ob_start();
session_start();
include 'config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Get export format and filters
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';
$filter_school = isset($_GET['school']) ? $_GET['school'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Base query to fetch department details
$sql = "SELECT d.department_id, d.department_name, d.hod_name, d.hod_contact_mobile, d.designation, 
               d.hod_contact_email, d.hod_intercom, s.school_name
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

// Fetch all departments
$departments = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Get current date for filename
$date = date('Y-m-d');
$filename = "departments_export_" . $date;

// Handle different export formats
switch ($format) {
    case 'csv':
        exportCSV($departments, $filename);
        break;
    case 'excel':
        // Try Excel export but catch any errors
        try {
            exportExcel($departments, $filename);
        } catch (Exception $e) {
            // If Excel export fails, fallback to CSV
            exportCSV($departments, $filename);
        }
        break;
    case 'pdf':
    default:
        // Try PDF export but catch any errors
        try {
            exportPDF($departments, $filename);
        } catch (Exception $e) {
            // If PDF export fails, fallback to CSV
            exportCSV($departments, $filename);
        }
        break;
}

/**
 * Export data as CSV
 */
function exportCSV($departments, $filename) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add header row
    fputcsv($output, [
        'Department ID', 
        'Department Name', 
        'School', 
        'HOD Name', 
        'Designation', 
        'Email', 
        'Mobile', 
        'Intercom'
    ]);
    
    // Add data rows
    foreach ($departments as $dept) {
        fputcsv($output, [
            $dept['department_id'],
            $dept['department_name'],
            $dept['school_name'],
            $dept['hod_name'],
            $dept['designation'],
            $dept['hod_contact_email'],
            $dept['hod_contact_mobile'],
            $dept['hod_intercom']
        ]);
    }
    
    // Close the output stream
    fclose($output);
    exit;
}

/**
 * Export data as Excel using XML format
 */
function exportExcel($departments, $filename) {
    // Use simple Excel XML format (compatible with most Excel versions)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
    echo '<Worksheet ss:Name="Departments">' . "\n";
    echo '<Table>' . "\n";
    
    // Header row
    echo '<Row>' . "\n";
    echo '<Cell><Data ss:Type="String">Department ID</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">Department Name</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">School</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">HOD Name</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">Designation</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">Email</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">Mobile</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">Intercom</Data></Cell>' . "\n";
    echo '</Row>' . "\n";
    
    // Data rows
    foreach ($departments as $dept) {
        echo '<Row>' . "\n";
        echo '<Cell><Data ss:Type="Number">' . htmlspecialchars($dept['department_id']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($dept['department_name']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($dept['school_name']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($dept['hod_name']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($dept['designation']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($dept['hod_contact_email']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($dept['hod_contact_mobile']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($dept['hod_intercom']) . '</Data></Cell>' . "\n";
        echo '</Row>' . "\n";
    }
    
    echo '</Table>' . "\n";
    echo '</Worksheet>' . "\n";
    echo '</Workbook>' . "\n";
    exit;
}

/**
 * Export data as PDF using TCPDF
 */
function exportPDF($departments, $filename) {
    try {
        // Make sure no output has been sent before
        if (ob_get_contents()) ob_clean();
        
        // Make sure TCPDF is properly included
        require_once('vendor/autoload.php');
        
        // Create new PDF document
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Disable default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set document information
        $pdf->SetCreator('University Management System');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Departments List');
        $pdf->SetSubject('Departments Export');
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Add title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Departments List', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Table header
        $header = [
            'ID', 
            'Department Name', 
            'School', 
            'HOD Name', 
            'Designation', 
            'Email', 
            'Mobile', 
            'Intercom'
        ];
        
        // Colors, line width and bold font
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetLineWidth(0.3);
        $pdf->SetFont('', 'B');
        
        // Header width
        $w = [10, 50, 40, 30, 30, 45, 30, 20];
        
        // Header
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        
        // Color and font restoration
        $pdf->SetFillColor(236, 240, 241);
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        
        // Data
        $fill = 0;
        foreach($departments as $dept) {
            $pdf->Cell($w[0], 6, $dept['department_id'], 'LR', 0, 'C', $fill);
            $pdf->Cell($w[1], 6, $dept['department_name'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[2], 6, $dept['school_name'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[3], 6, $dept['hod_name'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[4], 6, $dept['designation'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[5], 6, $dept['hod_contact_email'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[6], 6, $dept['hod_contact_mobile'], 'LR', 0, 'C', $fill);
            $pdf->Cell($w[7], 6, $dept['hod_intercom'], 'LR', 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }
        
        // Closing line
        $pdf->Cell(array_sum($w), 0, '', 'T');
        
        // Output the PDF
        $pdf->Output($filename . '.pdf', 'D');
        exit;
    } catch (Exception $e) {
        // Log the error for debugging
        error_log('PDF Export Error: ' . $e->getMessage());
        
        // If PDF export fails, fallback to CSV
        exportCSV($departments, $filename);
        exit;
    }
}
?>