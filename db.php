<?php
// db.php

// ตั้งค่า Timezone ให้เป็นไทย
date_default_timezone_set('Asia/Bangkok');

// ข้อมูลเชื่อมต่อฐานข้อมูล (แนะนำให้ใช้ Environment Variable ใน Production)
$db_host = getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$db_user = getenv('DB_USER') ?: '3WUQLTeLKsCs6W4.root';
$db_pass = getenv('DB_PASSWORD') ?: 'wknpq6pvH9P0rVdH';
$db_name = getenv('DB_NAME') ?: 'project';
$db_port = getenv('DB_PORT') ?: 4000;

$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL); // SSL สำหรับ TiDB Cloud

// เชื่อมต่อ
$connected = @$conn->real_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port, NULL, MYSQLI_CLIENT_SSL);

if (!$connected) {
    error_log("Connection failed: " . $conn->connect_error);
    die("ขออภัย ระบบฐานข้อมูลขัดข้องชั่วคราว");
}

$conn->set_charset("utf8mb4");

// --- ฟังก์ชันกลางสำหรับบันทึก Log (เรียกใช้ได้ทุกที่) ---
if (!function_exists('logAction')) {
    function logAction($conn, $action, $details) {
        // ตรวจสอบว่ามี session หรือยัง
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $user_id = $_SESSION['user_id'] ?? 0;
        $created_by = $_SESSION['user_name'] ?? 'System'; // ใช้ System ถ้าไม่มีคนล็อกอิน

        $stmt = $conn->prepare("INSERT INTO logs (user_id, created_by, action, details) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $created_by, $action, $details);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>
