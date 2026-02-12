<?php
session_start();
include 'db.php';

// 1. ตรวจสอบ Login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Unknown';

// 2. ฟังก์ชัน logAction (แก้ไขให้รับ user_id และบันทึกลงฐานข้อมูล)
function logAction($conn, $user_id, $created_by, $action, $details) {
    // เตรียม SQL ให้บันทึก user_id ด้วย
    $stmt = $conn->prepare("INSERT INTO logs (user_id, created_by, action, details) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $created_by, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

// 3. ตรวจสอบการส่งค่ามาลบ
if (isset($_GET['set_id']) && !empty($_GET['set_id'])) {
    $set_id = $_GET['set_id'];

    // --- ขั้นตอนที่ A: ดึงชื่อรูปภาพมาก่อน (เพื่อลบไฟล์) ---
    $image_to_delete = "";
    $stmt_img = $conn->prepare("SELECT set_image FROM sets WHERE set_id = ?");
    $stmt_img->bind_param("i", $set_id);
    $stmt_img->execute();
    $stmt_img->bind_result($image_to_delete);
    $stmt_img->fetch();
    $stmt_img->close();

    // --- ขั้นตอนที่ B: ลบข้อมูลรายการย่อยใน set_items ก่อน (ป้องกัน Foreign Key Error) ---
    $stmt_items = $conn->prepare("DELETE FROM set_items WHERE set_id = ?");
    $stmt_items->bind_param("i", $set_id);
    $stmt_items->execute();
    $stmt_items->close();

    // --- ขั้นตอนที่ C: ลบข้อมูลในตาราง sets ---
    $sql = "DELETE FROM sets WHERE set_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $set_id);

        if ($stmt->execute()) {
            
            // --- ขั้นตอนที่ D: ลบไฟล์รูปภาพออกจาก Server (ถ้ามี) ---
            if (!empty($image_to_delete)) {
                $file_path = __DIR__ . "/sets/" . $image_to_delete;
                if (file_exists($file_path)) {
                    unlink($file_path); // ลบไฟล์
                }
            }

            // บันทึก Log (ส่ง user_id ไปด้วย แก้ error)
            $details = "Deleted set ID: $set_id ($image_to_delete)";
            logAction($conn, $user_id, $user_name, "Delete Set", $details);

            // กลับไปหน้า index หรือ allset
            header("Location: index.php?msg=deleted");
            exit();
        } else {
            echo "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    }
} else {
    echo "Set ID not provided.";
    exit();
}

$conn->close();
?>
