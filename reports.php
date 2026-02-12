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

// SQL Query to fetch data from both tables
$query = "
    SELECT 
        sets.set_name, 
        mid_prices.mid_name,
        sets.set_price,
        mid_prices.mid_price,
        sets.user_price
    FROM sets
    JOIN mid_prices 
    ON sets.set_name = mid_prices.mid_name  -- Join condition
";

// Prepare the query
$stmt = $conn->prepare($query);
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch logs data for display
$log_query = "SELECT user_name, details, created_by FROM logs ORDER BY created_at DESC LIMIT 10";
$log_stmt = $conn->prepare($log_query);
$log_stmt->execute();
$log_result = $log_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="report.css" rel="stylesheet"> <!-- Include your custom CSS file for report styling -->
    
    <script>
    function toggleView(view) {
        if (view === 'price') {
            // เปลี่ยนแปลงการแสดงผล
            document.getElementById('priceTable').style.display = 'block';
            document.getElementById('logTable').style.display = 'none';
            
            // เปลี่ยนสีปุ่ม
            document.getElementById('price-btn').classList.add('active');
            document.getElementById('price-btn').classList.remove('inactive');
            document.getElementById('logs-btn').classList.add('inactive');
            document.getElementById('logs-btn').classList.remove('active');
        } else if (view === 'logs') {
            // เปลี่ยนแปลงการแสดงผล
            document.getElementById('priceTable').style.display = 'none';
            document.getElementById('logTable').style.display = 'block';
            
            // เปลี่ยนสีปุ่ม
            document.getElementById('logs-btn').classList.add('active');
            document.getElementById('logs-btn').classList.remove('inactive');
            document.getElementById('price-btn').classList.add('inactive');
            document.getElementById('price-btn').classList.remove('active');
        }
    }
</script>
</head>
<body>
    <div class="container mt-5">
        <h2>Reports</h2>
        
        <!-- Switch buttons to toggle between the views -->
        <div class="mb-3">
    <button class="btn btn-primary active" id="price-btn" onclick="toggleView('price')">Price Comparison</button>
    <button class="btn btn-secondary inactive" id="logs-btn" onclick="toggleView('logs')">Logs</button>
    </div>

        <!-- Price Table -->
        <div id="priceTable" style="display: block;">
            <h3>Price Comparison Report</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ชื่อ Set</th>
                        <th>ชื่อ Set</th>
                        <th>ราคา Set</th>
                        <th>ราคา ตลาด</th>
                        <th>ราคาที่ลูกค้าซื้ออยู่ปัจจุบัน</th>
                    </tr>
                </thead>
                <tbody>
    <?php
    if ($log_result->num_rows > 0) {
        while ($log_row = $log_result->fetch_assoc()) {
            echo '<tr>';
            // เติม ?? '' ต่อท้ายทุกตัวเช่นกัน
            echo '<td>' . htmlspecialchars($log_row['user_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($log_row['details'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($log_row['created_by'] ?? '') . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="3">No logs found.</td></tr>';
    }
    ?>
</tbody>
            </table>
        </div>

        <!-- Logs Table -->
        <div id="logTable" style="display: none;">
            <h3>Action Logs</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ชื่อผู้ เพิ่ม/ลบ/แก้ไข</th>
                        <th>Action Details</th>
                        <th>ชื่อผู้สร้าง</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($log_result->num_rows > 0) {
                        while ($log_row = $log_result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($log_row['user_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($log_row['details']) . '</td>';
                            echo '<td>' . htmlspecialchars($log_row['created_by']) . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3">No logs found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
