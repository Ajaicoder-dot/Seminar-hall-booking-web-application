<?php
session_start();
include('config.php');
include('navbar.php');


// Ensure user is logged in and is either Admin or HOD
if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'HOD')) {
    header("Location: login.php");
    exit();
}

// Fetch all hall data
$query = "SELECT 
    h.*, 
    s.school_name, 
    d.department_name, 
    ht.type_name 
FROM halls h
LEFT JOIN schools s ON h.school_id = s.school_id
LEFT JOIN departments d ON h.department_id = d.department_id
LEFT JOIN hall_type ht ON h.hall_type = ht.hall_type_id";
$result = $conn->query($query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Halls</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:w ght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
            border-radius: 8px;
            overflow: hidden;
        }
        table thead {
            background-color: #007BFF;
            color: white;
        }
        table th, table td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        table tbody tr:hover {
            background-color: #f1f1f1;
        }
        table th {
            text-transform: uppercase;
            font-size: 13px;
        }
        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 4px;
             font-size: 14px;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-block; /* Ensure buttons are inline-block */
            white-space: nowrap; /* Prevent text from wrapping */
        }
        .btn-modify {
            background-color: #28a745;
        }
        .btn-modify:hover {
            background-color: #218838;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-size: 16px;
            margin: 20px 0;
        }
        @media (max-width: 768px) {
            table th, table td {
                font-size: 12px;
            }
            .btn {
                padding: 6px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Modify Halls</h1>
        <?php if ($result && $result->num_rows > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>Hall Details</th>
                        <th>Belong To</th>
                        <th>Incharge Details</th>
                        <th>Features</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td>
                                <strong>Type:</strong> <?php echo $row['type_name']; ?><br>
                                <strong>Name:</strong> <?php echo $row['hall_name']; ?><br>
                                <strong>Capacity:</strong> <?php echo $row['capacity']; ?><br>
                                <strong>Floor:</strong> <?php echo $row['floor_name']; ?><br>
                                <strong>Zone:</strong> <?php echo $row['zone']; ?>
                            </td>
                            <td>
                                <?php if ($row['belong_to'] == 'School') { ?>
                                    <strong>School:</strong> <?php echo $row['school_name']; ?><br>
                                <?php } elseif ($row['belong_to'] == 'Department') { ?>
                                    <strong>Department:</strong> <?php echo $row['department_name']; ?><br>
                                <?php } ?>
                            </td>
                            <td>
                                <strong>Name:</strong> <?php echo $row['incharge_name']; ?><br>
                                <strong>Designation:</strong> <?php echo $row['designation']; ?><br>
                                <strong>Email:</strong> <?php echo $row['incharge_email']; ?><br>
                                <strong>Phone:</strong> <?php echo $row['incharge_phone']; ?>
                            </td>
                            <td>
                                <?php
                                $features = json_decode($row['features'], true);
                                if (!empty($features)) {
                                    echo implode(', ', $features);
                                } else {
                                    echo "No Features";
                                }
                                ?>
                            </td>
                            <td>
                                <a href="edit_hall.php?id=<?php echo $row['hall_id']; ?>" class="btn btn-modify">Modify</a>
                                <a href="delete_hall.php?id=<?php echo $row['hall_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this hall?');">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="no-data">No halls found.</p>
        <?php } ?>
    </div>
</body>
</html>