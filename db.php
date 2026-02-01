<?php

// 1. รับค่าจาก Environment Variable (ถ้าไม่มีจะใช้ค่า Default ที่เราใส่ไว้)
$servername = getenv('DB_HOST') ?: "gateway01.ap-southeast-1.prod.aws.tidbcloud.com"; // Host เดิม
$username   = getenv('DB_USER') ?: "3WUQLTeLKsCs6W4.root";    // ⚠️ แก้เป็น User ของ TiDB (ที่มีเลขนำหน้า)
$password   = getenv('DB_PASSWORD') ?: "wknpq6pvH9P0rVdH";     // ⚠️ แก้เป็น Password ของ TiDB
$dbname     = getenv('DB_NAME') ?: "project"; // ⚠️ ชื่อ DB ใหม่ของโปรเจกต์ 2
$port       = getenv('DB_PORT') ?: 4000;           // Port มาตรฐาน TiDB

// 2. เริ่มต้น Object MySQLi
$conn = mysqli_init();

// ตั้งค่า Timeout (เผื่อเน็ตช้า)
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

// 3. ✅ เปิดใช้ SSL (จำเป็นสำหรับ TiDB Cloud)
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

// 4. เชื่อมต่อฐานข้อมูล
// ใช้ real_connect แทน new mysqli() เพื่อใส่ Flag SSL ได้
$isConnected = @$conn->real_connect(
    $servername, 
    $username, 
    $password, 
    $dbname, 
    (int)$port, 
    NULL, 
    MYSQLI_CLIENT_SSL
);

// 5. ตรวจสอบการเชื่อมต่อ
if (!$isConnected) {
    // บันทึก Error ลง Log ของระบบแทนการโชว์หน้าเว็บ (เพื่อความปลอดภัย)
    error_log("Database Connection Error: " . $conn->connect_error);
    die("Connection failed: ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
}

// ตั้งค่าภาษาไทย
$conn->set_charset("utf8mb4");

?>
