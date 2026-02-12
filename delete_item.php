<?php
// Include the database connection
include('db.php');
include 'header.php';

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Unknown';

// -----------------------------------------------------------
// ฟังก์ชันบันทึก Log (มาตรฐานเดียวกับไฟล์อื่น)
// -----------------------------------------------------------
function logAction($conn, $user_id, $created_by, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO logs (user_id, created_by, action, details) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $created_by, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}
// -----------------------------------------------------------

// Check if item_id is provided
if (isset($_GET['item_id'])) {
    $item_id = $_GET['item_id'];

    // 1. ดึงข้อมูล Item มาก่อน (เพื่อเอารูปภาพไปลบ และเอาชื่อไปเก็บ Log)
    $query = "SELECT item_name, item_price, item_image FROM items WHERE item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        $image_to_delete = $item['item_image'];

        // 2. ลบข้อมูลที่เกี่ยวข้องใน set_items ก่อน (ถ้ามีสินค้านี้อยู่ใน Set ไหน ให้ลบออก)
        // เพื่อป้องกัน Error foreign key
        $del_set_items = $conn->prepare("DELETE FROM set_items WHERE item_id = ?");
        $del_set_items->bind_param("i", $item_id);
        $del_set_items->execute();
        $del_set_items->close();

        // 3. ลบข้อมูลจากตาราง items
        $sql = "DELETE FROM items WHERE item_id = ?";
        $delete_stmt = $conn->prepare($sql);
        $delete_stmt->bind_param("i", $item_id);

        if ($delete_stmt->execute()) {
            
            // 4. ลบไฟล์รูปภาพออกจาก Server (ถ้ามีรูป)
            if (!empty($image_to_delete)) {
                $file_path = __DIR__ . "/items/" . $image_to_delete;
                if (file_exists($file_path)) {
                    unlink($file_path); // คำสั่งลบไฟล์
                }
            }

            // 5. บันทึก Log (ส่ง user_id ไปด้วย เพื่อไม่ให้ error)
            $log_details = "Deleted item ID: $item_id, Name: {$item['item_name']}, Price: {$item['item_price']}";
            logAction($conn, $user_id, $user_name, "Delete Item", $log_details);

            header("Location: allitem.php?msg=deleted");  // Redirect after successful deletion
            exit();
        } else {
            echo "Error deleting item: " . $delete_stmt->error;
        }
    } else {
        echo "Item not found";
    }
} else {
    echo "Item ID not specified";
}
?>
