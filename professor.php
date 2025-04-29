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