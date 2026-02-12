<?php
// Include the database connection
include('db.php');
include 'header.php';

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all sets from the database
$query = "SELECT * FROM sets";
$result = $conn->query($query);

if (!$result) {
    die("Query Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Sets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
        <!-- Header with Back Button positioned to the right -->
        <div class="d-flex justify-content-end mb-3">
            <a href="setting.php" class="btn btn-secondary">Back</a> <!-- Adjust the link as needed -->
        </div>
    <div class="container mt-5">
        <h2>All Sets</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>รหัส Set</th>
                    <th>ชื่อ</th>
                    <th>ราคา</th>
                    <th>ลดเหลือ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['set_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['set_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['set_price']); ?></td>
                        <td><?php echo htmlspecialchars($row['sale_price']); ?></td>
                        <td>
                            <a href="edit_set.php?set_id=<?php echo $row['set_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_set.php?set_id=<?php echo $row['set_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this set?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
