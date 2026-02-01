<?php
// เชื่อมต่อกับฐานข้อมูล
include 'db.php';

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// เริ่มต้น session
session_start();

// ฟังก์ชันสำหรับบันทึกข้อมูลการกระทำ
function logAction($user_name, $details, $conn) {
    // ตรวจสอบว่า user_name เป็นค่าที่ไม่ใช่ null
    if ($user_name == null) {
        echo "Error: User not logged in.";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO logs (user_name, action, details) VALUES (?, 'Deleted Set', ?)");
    $stmt->bind_param("ss", $user_name, $details);
    $stmt->execute();
    $stmt->close();
}

// ตรวจสอบว่า set_id ถูกส่งมาหรือไม่
if (isset($_GET['set_id']) && !empty($_GET['set_id'])) {
    $set_id = $_GET['set_id'];

    // ลบข้อมูลจากฐานข้อมูล
    $sql = "DELETE FROM sets WHERE set_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameter
        $stmt->bind_param("i", $set_id);

        // Execute the query
        if ($stmt->execute()) {
            // บันทึกการกระทำลงใน logs
            $user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Unknown'; // ดึงชื่อผู้ใช้จาก session (หากมี)

            $details = "Deleted set with set_id: $set_id";
            logAction($user_name, $details, $conn);  // บันทึกข้อมูลการลบใน logs

            // ถ้าลบสำเร็จ ให้ redirect ไปยังหน้าอื่น (เช่น หน้าแสดงรายการ sets)
            header("Location: index.php");  // เปลี่ยนเป็นหน้าที่ต้องการแสดงหลังการลบ
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }
} else {
    echo "Set ID not provided.";
    exit();
}

$conn->close();
?>
