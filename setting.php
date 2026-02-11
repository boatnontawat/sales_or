<?php
// เชื่อมต่อกับฐานข้อมูล
$conn = new mysqli('gateway01.ap-southeast-1.prod.aws.tidbcloud.com', '3WUQLTeLKsCs6W4.root', 'wknpq6pvH9P0rVdH', 'project');

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// เริ่มต้น session
session_start();

// ตรวจสอบการเข้าสู่ระบบและดึงข้อมูลของผู้ใช้
$user_name = $hospital_name = $department_name = "";

// ตรวจสอบว่า session มีค่า 'user_id' หรือไม่
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT user_name, hospital_name, department_name FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_name, $hospital_name, $department_name);
    $stmt->fetch();
    $stmt->close();
} else {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="setting.css">
    <title>การตั้งค่า</title>
</head>
<body>
    
    <div class="container mt-4">
    <div class="header compact d-flex justify-content-between align-items-center p-3">
        <!-- Logo placed in the center -->
        <div class="logo-container d-flex justify-content-center flex-grow-1">
            <a href="index.php" class="logo">
                <img src="logo.png" alt="Logo" class="img-fluid" style="max-height: 50px;">
            </a>
        </div>

        <!-- Back button placed on the right -->
        <a href="index.php" class="back">
            <img src="back.png" alt="Back" class="img-fluid" style="max-height: 30px;">
        </a>
    </div>
        <h2 class="text-center">การตั้งค่า</h2>
        <div class="row justify-content-center">
            <div class="col-md-3 col-sm-4 mb-3">
                <a href="addset.php" class="btn btn-primary btn-block">สร้าง Set</a>
            </div>
            <div class="col-md-3 col-sm-4 mb-3">
                <a href="additem.php" class="btn btn-primary btn-block">สร้าง Item</a>
            </div>
            <div class="col-md-3 col-sm-4 mb-3">
                <a href="reports.php" class="btn btn-primary btn-block">รายงาน</a>
            </div>
            <div class="col-md-3 col-sm-4 mb-3">
                <a href="allitem.php" class="btn btn-secondary btn-block">จัดการ Item</a>
            </div>
            <div class="col-md-3 col-sm-4 mb-3">
                <a href="allset.php" class="btn btn-secondary btn-block">จัดการ Set</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and jQuery (optional for interactive features) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>


