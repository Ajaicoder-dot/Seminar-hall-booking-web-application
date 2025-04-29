<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once('config.php'); // Database connection
require_once('vendor/autoload.php'); // Load TCPDF

// Debug information - comment out after testing
echo "<pre>SESSION: "; print_r($_SESSION); echo "</pre>";
echo "<pre>POST: "; print_r($_POST); echo "</pre>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to download bookings.");
}

$user_id = $_SESSION['user_id'];

// Check if bookings are selected
if (!isset($_POST['selected_bookings']) || !is_array($_POST['selected_bookings']) || empty($_POST['selected_bookings'])) {
    die("Error: No bookings selected. Please go back and select at least one booking.");
}

// Sanitize booking IDs
$booking_ids = array_map('intval', $_POST['selected_bookings']);
echo "<pre>Booking IDs: "; print_r($booking_ids); echo "</pre>";

// Create temp directory
$temp_dir = __DIR__ . '/temp_downloads';
if (!file_exists($temp_dir)) {
    if (!mkdir($temp_dir, 0777, true)) {
        die("Error: Could not create temporary directory.");
    }
}

// Simplified query - fetch one booking at a time
$bookings_data = [];
foreach ($booking_ids as $booking_id) {
    $query = "SELECT hb.*, h.hall_name, h.capacity
              FROM hall_bookings hb
              JOIN halls h ON hb.hall_id = h.hall_id
              WHERE hb.booking_id = ? AND hb.user_id = ?";
    
    // Simple prepared statement
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        echo "Error preparing statement: " . $conn->error;
        continue; // Skip this booking but try others
    }
    
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $bookings_data[] = $row;
        echo "Successfully fetched booking ID: {$booking_id}<br>";
    } else {
        echo "Failed to fetch booking ID: {$booking_id}<br>";
    }
    
    $stmt->close();
}

if (empty($bookings_data)) {
    die("Error: No valid bookings found. Please check your selection.");
}

// Create ZIP archive
$zip_filename = 'hall_bookings_' . date('Ymd_His') . '.zip';
$zip_path = $temp_dir . '/' . $zip_filename;

$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
    die("Error: Could not create ZIP file.");
}

// Generate PDFs and add to ZIP
foreach ($bookings_data as $booking) {
    $pdf_filename = 'booking_' . $booking['booking_id'] . '.pdf';
    $pdf_path = $temp_dir . '/' . $pdf_filename;
    
    // Generate PDF
    try {
        // Create PDF
        $pdf = new TCPDF();
        $pdf->SetCreator('Hall Booking System');
        $pdf->SetTitle('Booking Confirmation');
        $pdf->SetAuthor('System');
        $pdf->AddPage();
        
        // Basic content - simplified for testing
        $html = '<h1>Booking Confirmation</h1>';
        $html .= '<p><strong>Booking ID:</strong> ' . $booking['booking_id'] . '</p>';
        $html .= '<p><strong>Hall:</strong> ' . htmlspecialchars($booking['hall_name']) . '</p>';
        $html .= '<p><strong>Program:</strong> ' . htmlspecialchars($booking['program_name']) . '</p>';
        $html .= '<p><strong>Date:</strong> ' . htmlspecialchars($booking['from_date']) . '</p>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($pdf_path, 'F');
        
        // Add to ZIP
        $zip_internal_name = 'Booking_' . $booking['booking_id'] . '_' . 
                            preg_replace('/[^A-Za-z0-9_\-]/', '_', $booking['program_name']) . '.pdf';
        $zip->addFile($pdf_path, $zip_internal_name);
        
        echo "Created PDF for booking ID: {$booking['booking_id']}<br>";
    } catch (Exception $e) {
        echo "Error creating PDF for booking ID {$booking['booking_id']}: " . $e->getMessage() . "<br>";
    }
}

$zip->close();
echo "ZIP file created at: $zip_path<br>";

// Output ZIP if it exists
if (file_exists($zip_path)) {
    echo "<p>Preparing to download ZIP file...</p>";
    echo "<script>setTimeout(function() { window.location = 'download_zip.php?file=" . urlencode($zip_filename) . "'; }, 3000);</script>";
    echo "<p>If download doesn't start automatically, <a href='download_zip.php?file=" . urlencode($zip_filename) . "'>click here</a>.</p>";
} else {
    echo "Error: ZIP file not found.";
}
?>

<form method="POST" action="batch_download.php">
    <table class="table">
        <thead>
            <tr>
                <th><input type="checkbox" id="select-all" onclick="toggleAllCheckboxes()"></th>
                <th>Hall</th>
                <th>Program</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Your existing loop to display bookings
            while ($row = $result->fetch_assoc()): 
            ?>
            <tr>
                <td><input type="checkbox" name="selected_bookings[]" value="<?php echo $row['booking_id']; ?>" class="booking-checkbox"></td>
                <td><?php echo htmlspecialchars($row['hall_name']); ?></td>
                <td><?php echo htmlspecialchars($row['program_name']); ?></td>
                <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                <td>
                    <!-- Your existing action buttons -->
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <button type="submit" class="btn btn-primary">Download Selected Bookings</button>
</form>

<script>
function toggleAllCheckboxes() {
    var checkboxes = document.getElementsByClassName('booking-checkbox');
    var selectAllCheckbox = document.getElementById('select-all');
    
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = selectAllCheckbox.checked;
    }
}
</script>