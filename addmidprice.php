<?php
session_start();
include 'db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// เมื่อมีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mid_name = $_POST['mid_name'];
    $mid_price = $_POST['mid_price'];

    // ตรวจสอบความถูกต้องของข้อมูล
    if (!empty($mid_name) && !empty($mid_price)) {
        $query = "INSERT INTO mid_prices (mid_name, mid_price) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sd', $mid_name, $mid_price);

        if ($stmt->execute()) {
            echo "<script>alert('เพิ่มข้อมูล set กลางเรียบร้อยแล้ว'); window.location.href='addmidprice.php';</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการเพิ่มข้อมูล');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('กรุณากรอกข้อมูลให้ครบถ้วน');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข้อมูล Set กลาง</title>
    <link rel="stylesheet" href="mid.css"> <!-- ใช้สไตล์ที่เหมาะสม -->
</head>
<body>
    <div class="container">
        <h2>เพิ่มข้อมูล Set กลาง</h2>
        <form action="addmidprice.php" method="POST">
            <div class="form-group">
                <label for="mid_name">ชื่อ Set กลาง:</label>
                <input type="text" name="mid_name" id="mid_name" required>
            </div>
            <div class="form-group">
                <label for="mid_price">ราคา Set กลาง:</label>
                <input type="number" name="mid_price" id="mid_price" required step="0.01">
            </div>
            <button type="submit">บันทึกข้อมูล</button>
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
?>
