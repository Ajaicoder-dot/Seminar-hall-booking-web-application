<?php
session_start();
ob_start(); // Start output buffering to prevent premature output

require_once('config.php'); // Database connection
require_once('vendor/autoload.php'); // Load TCPDF

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access!");
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT organizer_name, organizer_email, organizer_department, organizer_contact,
           program_name, program_type, program_purpose, from_date, end_date, start_time, end_time
    FROM hall_bookings
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Create new PDF document
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Seminar Hall Booking System');
$pdf->SetTitle('Booking Details');
$pdf->SetHeaderData('', 0, 'Booking Details', "Generated on: " . date("Y-m-d"));
$pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
$pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
$pdf->SetMargins(10, 10, 20);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// PDF Content
$html = '<h2>Booking Details</h2>';
$html .= '<table border="1" cellpadding="5">
            <tr>
                <th>Organizer Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Contact</th>
                <th>Program Name</th>
                <th>Type</th>
                <th>Purpose</th>
                <th>From Date</th>
                <th>End Date</th>
                <th>Start Time</th>
                <th>End Time</th>
            </tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . htmlspecialchars($row['organizer_name']) . '</td>
                <td>' . htmlspecialchars($row['organizer_email']) . '</td>
                <td>' . htmlspecialchars($row['organizer_department']) . '</td>
                <td>' . htmlspecialchars($row['organizer_contact']) . '</td>
                <td>' . htmlspecialchars($row['program_name']) . '</td>
                <td>' . htmlspecialchars($row['program_type']) . '</td>
                <td>' . htmlspecialchars($row['program_purpose']) . '</td>
                <td>' . htmlspecialchars($row['from_date']) . '</td>
                <td>' . htmlspecialchars($row['end_date']) . '</td>
                <td>' . htmlspecialchars($row['start_time']) . '</td>
                <td>' . htmlspecialchars($row['end_time']) . '</td>
              </tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// Clear output buffer before sending PDF
ob_end_clean();

// Output PDF
$pdf->Output('Booking_Details.pdf', 'D'); // 'D' forces download
exit();
?>
