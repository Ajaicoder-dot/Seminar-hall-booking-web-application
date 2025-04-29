<?php
session_start();
include('config.php');

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer autoloader
require 'vendor/autoload.php';

if (!isset($_SESSION['email'])) {
    echo "Unauthorized access";
    exit();
}

if (isset($_POST['booking_id']) && isset($_POST['reason'])) {
    $booking_id = $_POST['booking_id'];
    $reason = $_POST['reason'];
    $other_reason = isset($_POST['other_reason']) ? $_POST['other_reason'] : '';
    
    // Get the full reason text
    $full_reason = $reason;
    if ($reason === 'Other' && !empty($other_reason)) {
        $full_reason = "Other: " . $other_reason;
    }
    
    // First, get booking details to use in email
    $query = "SELECT hb.organizer_name, hb.organizer_email, hb.program_name, 
                     hb.from_date, hb.end_date, hb.start_time, hb.end_time, 
                     h.hall_name, h.incharge_email
              FROM hall_bookings hb 
              JOIN halls h ON hb.hall_id = h.hall_id
              WHERE hb.booking_id = ?";
    
    $stmt = $conn->prepare($query);
    
    // Check if prepare statement was successful
    if ($stmt === false) {
        echo "Error preparing statement: " . $conn->error;
        exit();
    }
    
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Update booking status to Cancelled with correct column name
        $update_query = "UPDATE hall_bookings SET status = 'Cancelled', cancellation_reason = ? WHERE booking_id = ?";
        $update_stmt = $conn->prepare($update_query);
        
        // Check if prepare statement was successful
        if ($update_stmt === false) {
            echo "Error preparing update statement: " . $conn->error;
            exit();
        }
        
        $update_stmt->bind_param("si", $full_reason, $booking_id);
        
        if ($update_stmt->execute()) {
            // Send email notification
            if (isset($_POST['send_email']) && $_POST['send_email'] == true) {
                $organizer_email = $row['organizer_email'];
                $incharge_email = $row['incharge_email'];
                
                // Format dates for display
                $from_date = date("M d, Y", strtotime($row['from_date']));
                $end_date = date("M d, Y", strtotime($row['end_date']));
                $start_time = date("h:i A", strtotime($row['start_time']));
                $end_time = date("h:i A", strtotime($row['end_time']));
                
                // Email content
                $subject = "Hall Booking Cancellation - " . $row['program_name'];
                $body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #4a6fdc; color: white; padding: 15px; text-align: center; }
                        .content { padding: 20px; border: 1px solid #ddd; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                        .details { margin: 15px 0; }
                        .label { font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Hall Booking Cancellation</h2>
                        </div>
                        <div class='content'>
                            <p>Dear Concerned,</p>
                            <p>This is to inform you that the following hall booking has been <strong>cancelled</strong>:</p>
                            
                            <div class='details'>
                                <p><span class='label'>Program:</span> {$row['program_name']}</p>
                                <p><span class='label'>Hall:</span> {$row['hall_name']}</p>
                                <p><span class='label'>Organizer:</span> {$row['organizer_name']}</p>
                                <p><span class='label'>Date:</span> {$from_date} to {$end_date}</p>
                                <p><span class='label'>Time:</span> {$start_time} to {$end_time}</p>
                                <p><span class='label'>Reason for Cancellation:</span> {$full_reason}</p>
                            </div>
                            
                            <p>The hall is now available for new bookings during this time slot.</p>
                            <p>Thank you for using our Hall Booking System.</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message. Please do not reply to this email.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Send email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    //Server settings
                    $mail->isSMTP();                                            // Send using SMTP
                    $mail->Host       = 'smtp.gmail.com';                   // Set the SMTP server to send through
                    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                    $mail->Username   = 'ajaiofficial06@gmail.com';             // SMTP username
                    $mail->Password   = 'pxqzpxdkdbfgbfah';                      // SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // Enable TLS encryption
                    $mail->Port       = 587;                                   // TCP port to connect to

                    //Recipients
                    $mail->setFrom('ajaiofficial06@gmail.com', 'Hall Booking System');
                    $mail->addAddress($organizer_email);                       // Add the organizer's email
                    $mail->addAddress($incharge_email);                        // Add the hall in-charge's email
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $body;
                    
                    $mail->send();
                } catch (Exception $e) {
                    // Log email sending error but don't stop the process
                    error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                }
            }
            
            echo "Booking cancelled successfully";
        } else {
            echo "Error cancelling booking";
        }
    } else {
        echo "Booking not found";
    }
} else {
    echo "Invalid request";
}
?>
