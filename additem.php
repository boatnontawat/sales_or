<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user_name from the database using user_id from the session
$user_id = $_SESSION['user_id'];
$user_name = "";

// Fetch user_name from users table
$stmt = $conn->prepare("SELECT user_name FROM users WHERE user_id = ?"); // แก้ไข column ให้ตรงกับ DB (id หรือ user_id)
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $stmt->bind_result($user_name);
    $stmt->fetch();
}
$stmt->close();

if (empty($user_name)) {
    // Fallback if name not found (optional)
    $user_name = "Unknown User"; 
}

// Function to log actions in logs table
function log_action($conn, $created_by, $action, $details) {
    // ต้อง prepare ใหม่เพราะเราอยู่ใน function Scope
    $stmt = $conn->prepare("INSERT INTO logs (created_by, action, details) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $created_by, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect item data from the form
    $item_name = trim($_POST['item_name']);
    
    // Check if price is set
    $item_price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    
    $item_image = null; // Default value

    // Validate required fields
    if (empty($item_name) || $item_price <= 0) {
        $error_msg = "Invalid item name or price.";
    } else {
        // Handle image upload if file is provided
        if (!empty($_FILES['image']['name'])) {
            
            // --- แก้ไข Path สำหรับ Render/Linux ---
            $target_dir = __DIR__ . "/items/";
            
            // สร้างโฟลเดอร์ items ถ้ายังไม่มี
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $original_filename = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
            $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif');

            // Validate file type
            if (!in_array($extension, $allowed_types)) {
                die("Only JPG, JPEG, PNG, and GIF files are allowed.");
            }

            // Validate file size (max 2MB)
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                die("File size exceeds the 2MB limit.");
            }

            // Generate a unique filename
            $new_image_name = uniqid() . '.' . $extension; // ตั้งชื่อไฟล์ใหม่สั้นๆ ป้องกันปัญหาภาษาไทย
            $target_file = $target_dir . $new_image_name;

            // Move the uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $item_image = $new_image_name;
            } else {
                die("Error uploading image.");
            }
        }

        // Insert item into the database
        // หมายเหตุ: ตรวจสอบว่าตาราง items มี column ชื่อ item_image หรือ image_path ให้แก้ SQL ตามจริง
        $stmt = $conn->prepare("INSERT INTO items (item_name, item_price, item_image) VALUES (?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("sds", $item_name, $item_price, $item_image);
            
            if ($stmt->execute()) {
                // Log the creation of the item
                log_action($conn, $user_name, "Create Item", "Item '$item_name' created with price $item_price");

                // Redirect to setting page (หรือหน้ารายการสินค้า)
                header('Location: setting.php?success=Item added successfully');
                exit;
            } else {
                echo "Error executing query: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้าง Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* CSS ภายในไฟล์เพื่อให้แสดงผลสวยงามทันที */
        body { background-color: #f8f9fa; }
        .container { max-width: 600px; margin-top: 50px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { text-align: center; margin-bottom: 30px; color: #333; }
        .form-label { font-weight: bold; }
        .btn-primary { width: 100%; padding: 10px; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>สร้าง Item ใหม่</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="item_name" class="form-label">ชื่อ Item</label>
                <input type="text" id="item_name" name="item_name" class="form-control" placeholder="กรอกชื่อสินค้า" required>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="price" class="form-label">ราคา (บาท)</label>
                    <input type="number" step="0.01" id="price" name="price" class="form-control" placeholder="0.00" required>
                </div>
                <div class="col-md-6">
                    <label for="image" class="form-label">รูปภาพ (ถ้ามี)</label>
                    <input type="file" id="image" name="image" class="form-control">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
            <a href="setting.php" class="btn btn-secondary w-100 mt-2">ย้อนกลับ</a>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
