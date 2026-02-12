<?php
session_start();
include 'db.php';
include 'header.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// รับค่า set_id จาก URL และตรวจสอบว่าเป็นตัวเลข
$set_id = isset($_GET['set_id']) && is_numeric($_GET['set_id']) ? $_GET['set_id'] : 0;

// ตรวจสอบว่า set_id ถูกต้องหรือไม่
if ($set_id <= 0) {
    echo "Invalid set ID.";
    exit;
}

// คิวรีเพื่อดึงข้อมูลชุด (set) จาก set_id
$query_set = "SELECT * FROM sets WHERE set_id = $set_id";
$result_set = $conn->query($query_set);
if ($result_set->num_rows > 0) {
    $set = $result_set->fetch_assoc();
    $set_name = $set['set_name'];
    $set_price = $set['set_price'];
    $user_price = $set['user_price']; // หากมีการกรอกข้อมูลราคา
} else {
    echo "Set not found.";
    exit;
}

// คิวรีเพื่อดึงข้อมูลจาก mid_prices และเปรียบเทียบราคา
$query_mid_prices = "SELECT * FROM mid_prices WHERE mid_name = '$set_name'";
$result_mid_prices = $conn->query($query_mid_prices);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        // รับข้อมูลจากฟอร์ม
        $user_price_input = isset($_POST['user_price']) ? $_POST['user_price'] : 0;

        // ปรับปรุงฐานข้อมูลด้วยราคา user
        $update_query = "UPDATE sets SET user_price = $user_price_input WHERE set_id = $set_id";
        if ($conn->query($update_query)) {
            echo "User price updated successfully!";
            // Reload page to show updated data
            header("Location: midprice.php?set_id=$set_id");
            exit;
        } else {
            echo "Error updating user price: " . $conn->error;
        }
    } elseif (isset($_POST['save'])) {
        // เมื่อกดปุ่ม Save ให้บันทึกข้อมูลและไปที่หน้า index.php
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตารางเปรียบเทียบราคา</title>
    <!-- เชื่อมต่อ Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #priceComparisonChart {
            width: 100%; 
            height: 300px;
            margin: 0 auto;
        }

        .chart-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .form-container {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center my-4">ตารางเปรียบเทียบราคา: <?php echo $set_name; ?></h2>

        <!-- ฟอร์มกรอกข้อมูล -->
        <div class="form-container">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="user_price" class="form-label">ราคาที่ลูกค้าซื้ออยู่ปัจจุบัน:</label>
                    <input type="number" id="user_price" name="user_price" value="<?php echo $user_price; ?>" step="0.01" class="form-control" required>
                </div>
                <button type="submit" name="submit" class="btn btn-primary">กรอก</button>
                <button type="submit" name="save" class="btn btn-success">บันทึก</button>
            </form>
        </div>

        <?php
        if ($result_mid_prices->num_rows > 0) {
            $mid_price = 0;
            while ($row_mid = $result_mid_prices->fetch_assoc()) {
                $mid_price = $row_mid['mid_price'];
            }

            $price_difference_set_mid = $set_price - $mid_price;
            $price_difference_set_user = $set_price - $user_price;

            $labels = ["Set Price", "Mid Price", "User Price"];
            $data = [$set_price, $mid_price, $user_price];
        ?>
        <div class="chart-title">ตารางเปรียบเทียบราคา</div>
        <canvas id="priceComparisonChart"></canvas>
        <script>
            var ctx = document.getElementById('priceComparisonChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Prices',
                        data: <?php echo json_encode($data); ?>,
                        backgroundColor: ['#4CAF50', '#FFEB3B', '#2196F3'],
                        borderColor: ['#388E3C', '#FBC02D', '#1976D2'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                max: Math.max(<?php echo $set_price; ?>, <?php echo $mid_price; ?>, <?php echo $user_price; ?>) * 1.2,
                                stepSize: 10
                            }
                        }
                    }
                }
            });
        </script>
        <?php } else { ?>
            <p>No mid price data found for this set.</p>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
