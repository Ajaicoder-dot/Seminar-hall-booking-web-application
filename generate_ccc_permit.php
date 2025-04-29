<?php
// Turn off all error reporting and output
@ini_set('display_errors', 0);
@ini_set('log_errors', 0);
@ini_set('error_log', null);
error_reporting(0);

// Force clean any existing output
ob_clean();
ob_start();

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Include files
@include('config.php');
@include_once('vendor/autoload.php');

try {
    // Authentication check
    if (!isset($_SESSION['email']) || !isset($_GET['id'])) {
        throw new Exception('Unauthorized access');
    }

    $booking_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Fetch booking details with hall information
    $sql = "SELECT b.*, h.hall_name 
            FROM ccc_hall_bookings b
            JOIN halls h ON b.hall_id = h.hall_id
            WHERE b.booking_id = ? AND b.user_id = ? AND b.status = 'Approved'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();

    if (!$booking) {
        die("Booking not found, unauthorized access, or booking not approved");
    }

    // Format dates
    $from_date = date('d F Y', strtotime($booking['from_date']));
    $start_time = date('h:i A', strtotime($booking['start_time']));
    $end_time = date('h:i A', strtotime($booking['end_time']));
    $permit_date = date('d F Y');
    $permit_number = 'CCC-' . str_pad($booking['booking_id'], 5, '0', STR_PAD_LEFT);

    // Create PDF
    class PermitPDF extends TCPDF {
        public function Header() {
            // Add university logo
            $this->Image('images/pondicherry_university_logo.png', 15, 10, 30, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            
            $this->SetFont('helvetica', 'B', 20);
            $this->Cell(0, 15, 'PONDICHERRY UNIVERSITY', 0, 1, 'C');
            $this->SetFont('helvetica', '', 12);
            $this->Cell(0, 10, 'R.V. Nagar, Kalapet, Puducherry - 605014', 0, 1, 'C');
            $this->Cell(0, 10, 'Phone: 0413-2654300 | Email: info@pondiuni.edu.in', 0, 1, 'C');
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 15, 'CCC Hall Usage Permit', 0, 1, 'C');
            $this->Line(15, $this->GetY(), 195, $this->GetY());
        }
    }

    $pdf = new PermitPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Pondicherry University Hall Booking System');
    $pdf->SetAuthor('Pondicherry University');
    $pdf->SetTitle('CCC Hall Usage Permit');
    $pdf->SetMargins(15, 60, 15); // Increased top margin to accommodate header
    $pdf->SetHeaderMargin(20);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();

    // Create HTML content
    $html = '
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        td { padding: 10px; border: 1px solid #ddd; }
        .label { font-weight: bold; width: 30%; background-color: #f2f2f2; }
        h3 { color: #4a6fdc; }
    </style>
    <h3 style="text-align: right; color: #4a6fdc;">Permit Number: ' . $permit_number . '</h3>
    <p style="text-align: right;">Date: ' . $permit_date . '</p>
    <table cellpadding="8">
        <tr>
            <td class="label">Organizer Name</td>
            <td>' . htmlspecialchars($booking['organizer_name']) . '</td>
        </tr>
        <tr>
            <td class="label">Department</td>
            <td>' . htmlspecialchars($booking['organizer_department']) . '</td>
        </tr>
        <tr>
            <td class="label">Contact</td>
            <td>' . htmlspecialchars($booking['organizer_contact']) . '</td>
        </tr>
        <tr>
            <td class="label">Hall Name</td>
            <td>' . htmlspecialchars($booking['hall_name']) . '</td>
        </tr>
        <tr>
            <td class="label">Program</td>
            <td>' . htmlspecialchars($booking['program_name']) . '</td>
        </tr>
        <tr>
            <td class="label">Date & Time</td>
            <td>' . $from_date . '<br>' . $start_time . ' - ' . $end_time . '</td>
        </tr>
    </table>
    <p style="text-align: center; font-style: italic; margin-top: 20px;">This permit must be presented to the hall authorities on the day of the event.</p>
    <br><br>
    <table style="border: none;">
        <tr>
            <td style="border: none; width: 50%; text-align: center; padding-top: 30px;">____________________<br>Authorized Signature</td>
            <td style="border: none; width: 50%; text-align: center; padding-top: 30px;">____________________<br>Hall Administrator</td>
        </tr>
    </table>
    <p style="text-align: center; font-size: 10px; margin-top: 40px; color: #666;">This is an official document of Pondicherry University. Any alteration renders it invalid.</p>';
    
    // Before PDF output
    if (ob_get_length()) {
        ob_clean();
    }
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('CCC_Hall_Permit_' . $booking_id . '.pdf', 'D');
    exit;
} catch (Exception $e) {
    exit($e->getMessage());
}