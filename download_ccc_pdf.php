<?php
session_start();
ob_start(); // Start output buffering

require('config.php');
require_once('vendor/autoload.php'); // Load TCPDF

if (!isset($_SESSION['email']) || !isset($_GET['id'])) {
    header('Location: login.php');
    exit();
}

$booking_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch booking details
$sql = "SELECT b.*, p.amount_paid
        FROM ccc_hall_bookings b
        LEFT JOIN (
            SELECT booking_id, SUM(amount) as amount_paid 
            FROM ccc_hall_payments 
            WHERE payment_type IN ('Advance', 'Final')
            GROUP BY booking_id
        ) p ON b.booking_id = p.booking_id
        WHERE b.booking_id = ? AND b.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    die("Booking not found or unauthorized access");
}

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('University Hall Booking System');
$pdf->SetAuthor('University');
$pdf->SetTitle('CCC Hall Booking Details');
$pdf->SetHeaderData('images/logo.png', 30, 'CCC Hall Booking Details', "Generated on: " . date("Y-m-d"));
$pdf->setHeaderFont(Array('helvetica', '', 12));
$pdf->setFooterFont(Array('helvetica', '', 8));

// Set margins
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(20);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Create HTML content
$html = '<h2 style="text-align:center;">CCC Hall Booking Details</h2>';
$html .= '<table border="1" cellpadding="5">
            <tr><td width="30%"><strong>Booking ID</strong></td><td>' . $booking['booking_id'] . '</td></tr>
            <tr><td><strong>Organizer Name</strong></td><td>' . htmlspecialchars($booking['organizer_name']) . '</td></tr>
            <tr><td><strong>Department</strong></td><td>' . htmlspecialchars($booking['organizer_department']) . '</td></tr>
            <tr><td><strong>Contact</strong></td><td>' . htmlspecialchars($booking['organizer_contact']) . '</td></tr>
            <tr><td><strong>Program Name</strong></td><td>' . htmlspecialchars($booking['program_name']) . '</td></tr>
            <tr><td><strong>Date</strong></td><td>' . date('d M Y', strtotime($booking['from_date'])) . '</td></tr>
            <tr><td><strong>Time</strong></td><td>' . date('h:i A', strtotime($booking['start_time'])) . ' - ' . date('h:i A', strtotime($booking['end_time'])) . '</td></tr>
            <tr><td><strong>Status</strong></td><td>' . $booking['status'] . '</td></tr>
            <tr><td><strong>Payment Status</strong></td><td>' . $booking['payment_status'] . '</td></tr>
            <tr><td><strong>Total Amount</strong></td><td>₹' . number_format($booking['total_amount'], 2) . '</td></tr>
            <tr><td><strong>Amount Paid</strong></td><td>₹' . number_format($booking['amount_paid'], 2) . '</td></tr>
          </table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Clear output buffer
ob_end_clean();

// Output PDF
$pdf->Output('CCC_Hall_Booking_' . $booking_id . '.pdf', 'D');
exit();