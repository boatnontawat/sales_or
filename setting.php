<?php
// setting.php

// 1. กำหนดค่าการเชื่อมต่อ (TiDB Serverless)
$host = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$port = 4000;
$user = '3WUQLTeLKsCs6W4.root'; 
$password = 'wknpq6pvH9P0rVdH';
$dbname = 'project';

// 2. เริ่มต้นการเชื่อมต่อแบบ SSL
$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL); // ตั้งค่าให้ใช้ SSL
$conn->real_connect($host, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

// 3. ตรวจสอบการเชื่อมต่อ
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
    
    // เตรียม SQL เพื่อดึงข้อมูล
    $sql = "SELECT user_name, hospital_name, department_name FROM users WHERE user_id = ?";
    // หมายเหตุ: เช็คชื่อ column ID ใน DB ด้วยนะครับ (ปกติคือ id หรือ user_id)
    // ถ้าในฐานข้อมูลชื่อ user_id ให้แก้ SQL เป็น WHERE user_id = ?
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $stmt->bind_result($user_name, $hospital_name, $department_name);
        $stmt->fetch();
    }
    $stmt->close();
} else {
    // ถ้าไม่มี session ให้เด้งไปหน้า login
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="setting.css">
    <title>การตั้งค่า</title>
</head>
<body>
    
    <div class="container mt-4">
    <div class="header compact d-flex justify-content-between align-items-center p-3">
        <div class="logo-container d-flex justify-content-center flex-grow-1">
            <a href="index.php" class="logo">
                <img src="logo.png" alt="Logo" class="img-fluid" style="max-height: 50px;">
            </a>
        </div>

        <a href="index.php" class="back">
            <img src="back.png" alt="Back" class="img-fluid" style="max-height: 30px;">
        </a>
    </div>
        <h2 class="text-center">การตั้งค่า</h2>
        <div class="row justify-content-center">
            <div class="col-md-3 col-sm-4 mb-3">
                <a href="addset.php" class="btn btn-primary w-100">สร้าง Set</a>
            </div>
            <div class="col-md-3 col-sm-4 mb-3">
                <a href="additem.php" class="btn btn-primary w-100">สร้าง Item</a>
            </div>
            <div class="col-md-3 col-sm-4 mb-3">
                <a href="reports.php" class="btn btn-primary w-100">รายงาน</a>
            </div>
            <div class="col-md-3 col-sm-4 mb-3">
                <a href="allitem.php" class="btn btn-secondary w-100">จัดการ Item</a>
            </div>
            <div class="col-md-3 col-sm-4 mb-3">
                <a href="allset.php" class="btn btn-secondary w-100">จัดการ Set</a>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
