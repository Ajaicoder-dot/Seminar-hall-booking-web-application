<?php
// Prevent any output before headers
ob_start();
session_start();
include 'config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Get export format
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

// Base query to fetch school details
$sql = "SELECT * FROM schools ORDER BY school_name";

// Execute the query
$result = $conn->query($sql);

// Fetch all schools
$schools = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schools[] = $row;
    }
}

// Get current date for filename
$date = date('Y-m-d');
$filename = "schools_export_" . $date;

// Handle different export formats
switch ($format) {
    case 'csv':
        exportCSV($schools, $filename);
        break;
    case 'excel':
        // Try Excel export but catch any errors
        try {
            exportExcel($schools, $filename);
        } catch (Exception $e) {
            // If Excel export fails, fallback to CSV
            exportCSV($schools, $filename);
        }
        break;
    case 'pdf':
    default:
        // Try PDF export but catch any errors
        try {
            exportPDF($schools, $filename);
        } catch (Exception $e) {
            // Log the error for debugging
            error_log('PDF Export Error: ' . $e->getMessage());
            // If PDF export fails, fallback to CSV
            exportCSV($schools, $filename);
        }
        break;
}

/**
 * Export data as CSV
 */
function exportCSV($schools, $filename) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add header row
    fputcsv($output, [
        'School ID', 
        'School Name', 
        'Dean Name', 
        'Dean Email',
        'Dean Intercom',
        'Dean Status'
    ]);
    
    // Add data rows
    foreach ($schools as $school) {
        fputcsv($output, [
            $school['school_id'],
            $school['school_name'],
            $school['dean_name'],
            $school['dean_email'],
            $school['dean_intercome'],
            $school['dean_status']
        ]);
    }
    
    // Close the output stream
    fclose($output);
    exit;
}

/**
 * Export data as Excel using XML format
 */
function exportExcel($schools, $filename) {
    // Use simple Excel XML format (compatible with most Excel versions)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
    echo '<Worksheet ss:Name="Schools">' . "\n";
    echo '<Table>' . "\n";
    
    // Header row
    echo '<Row>' . "\n";
    echo '<Cell><Data ss:Type="String">School ID</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">School Name</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">Dean Name</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">Dean Email</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">Dean Intercom</Data></Cell>' . "\n";
    echo '<Cell><Data ss:Type="String">Dean Status</Data></Cell>' . "\n";
    echo '</Row>' . "\n";
    
    // Data rows
    foreach ($schools as $school) {
        echo '<Row>' . "\n";
        echo '<Cell><Data ss:Type="Number">' . htmlspecialchars($school['school_id']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($school['school_name']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($school['dean_name']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($school['dean_email']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($school['dean_intercome']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($school['dean_status']) . '</Data></Cell>' . "\n";
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
function exportPDF($schools, $filename) {
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
        $pdf->SetTitle('Schools List');
        $pdf->SetSubject('Schools Export');
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Add title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Schools List', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Table header
        $header = [
            'ID', 
            'School Name', 
            'Dean Name', 
            'Email',
            'Intercom',
            'Status'
        ];
        
        // Colors, line width and bold font
        $pdf->SetFillColor(52, 152, 219);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetLineWidth(0.3);
        $pdf->SetFont('', 'B');
        
        // Header width
        $w = [10, 60, 40, 60, 30, 30];
        
        // Header
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        
        // Color and font restoration
        $pdf->SetFillColor(224, 235, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        
        // Data
        $fill = 0;
        foreach($schools as $school) {
            $pdf->Cell($w[0], 6, $school['school_id'], 'LR', 0, 'C', $fill);
            $pdf->Cell($w[1], 6, $school['school_name'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[2], 6, $school['dean_name'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[3], 6, $school['dean_email'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[4], 6, $school['dean_intercome'], 'LR', 0, 'C', $fill);
            $pdf->Cell($w[5], 6, $school['dean_status'], 'LR', 0, 'C', $fill);
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
        exportCSV($schools, $filename);
        exit;
    }
}
?>