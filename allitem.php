<?php
// Include the database connection
include('db.php');

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all items from the database
$query = "SELECT * FROM items";
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
    <title>All Items</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <!-- Header with Back Button positioned to the right -->
        <div class="d-flex justify-content-end mb-3">
            <a href="setting.php" class="btn btn-secondary">Back</a> <!-- Adjust the link as needed -->
        </div>

        <h2>All Items</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>รหัสวัสดุ</th>
                    <th>ชื่อ</th>
                    <th>ราคา</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['item_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['item_price']); ?></td>
                        <td>
                            <a href="edit_item.php?item_id=<?php echo $row['item_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_item.php?item_id=<?php echo $row['item_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
