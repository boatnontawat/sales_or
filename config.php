<?php
// config.php

// 1. ฟังก์ชันโหลด .env (ปรับปรุงให้รองรับค่าจาก Render Dashboard ด้วย)
function loadEnv($path) {
    // ถ้ามีไฟล์ .env ให้อ่านค่าจากไฟล์
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // ถ้ายังไม่มีค่าใน $_ENV ให้ใส่เข้าไป (เพื่อให้ความสำคัญกับ Server Env ก่อน)
            if (!isset($_ENV[$key]) && getenv($key) === false) {
                $_ENV[$key] = $value;
                putenv("$key=$value"); // ใส่ใน getenv() ด้วยเพื่อความชัวร์
            }
        }
    }
}

// โหลดไฟล์ .env (ถ้ามี)
loadEnv(__DIR__ . '/.env');

// ดึงค่าตัวแปร (รองรับทั้งจากไฟล์ .env และ Environment Variables ของ Render)
$db_host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$db_user = $_ENV['DB_USER'] ?? getenv('DB_USER');
$db_pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');
$db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
$db_port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 4000; // ถ้าไม่ระบุให้ใช้ 4000 (TiDB)

// 2. เชื่อมต่อฐานข้อมูล (แก้ไขใหม่ให้รองรับ SSL และ Port ของ TiDB)
try {
    // ใช้ mysqli_init เพื่อตั้งค่า SSL ได้
    $conn = mysqli_init();
    
    // ตั้งค่า Timeout (เผื่อเน็ตช้า)
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    
    // ✅ เปิดใช้ SSL (จำเป็นสำหรับ TiDB Cloud)
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

    // สั่งเชื่อมต่อจริง (ใส่ Port และ Flag SSL)
    $connected = @$conn->real_connect(
        $db_host, 
        $db_user, 
        $db_pass, 
        $db_name, 
        (int)$db_port, 
        NULL, 
        MYSQLI_CLIENT_SSL
    );

    if (!$connected) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // ตั้งค่าภาษา
    $conn->set_charset("utf8mb4");
    
    // (ปิดบรรทัดนี้เมื่อใช้งานจริง เพื่อไม่ให้รบกวนหน้าเว็บ)
    // echo "✅ Database connected successfully!"; 

} catch (Exception $e) {
    // บันทึก Error ลง System Log ของ Render (แทนการโชว์หน้าเว็บตรงๆ)
    error_log("Database Error: " . $e->getMessage());
    die("❌ Database connection error. Please check system logs.");
}
?>
