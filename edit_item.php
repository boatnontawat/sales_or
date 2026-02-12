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

// Get the item_id from the URL
if (isset($_GET['item_id'])) {
    $item_id = $_GET['item_id'];

    // Fetch the item details from the database
    $query = "SELECT * FROM items WHERE item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
    } else {
        die("Item not found");
    }
} else {
    die("Item ID not specified");
}

// -----------------------------------------------------------
// ฟังก์ชันบันทึก Log (ใช้แบบเดียวกับไฟล์อื่นๆ เพื่อป้องกัน Error)
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

// Process form submission for updating item
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = $_POST['item_name'];
    $item_price = $_POST['item_price'];
    $item_image = $item['item_image'];  // Default to existing image

    // Handle image upload (if a new image is provided)
    if (!empty($_FILES['image']['name'])) {
        
        // --- แก้ไข Path ให้รองรับ Render/Linux ---
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
        $new_image_name = uniqid() . '.' . $extension; // ตั้งชื่อสั้นๆ ป้องกันปัญหาภาษาไทย
        $target_file = $target_dir . $new_image_name;

        // Move the uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $item_image = $new_image_name;
        } else {
            die("Error uploading image.");
        }
    }

    // Update the item details in the database
    $update_query = "UPDATE items SET item_name = ?, item_price = ?, item_image = ? WHERE item_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sdsi", $item_name, $item_price, $item_image, $item_id);
    
    if ($update_stmt->execute()) {
        // บันทึก Log (เรียกใช้ฟังก์ชันที่แก้แล้ว)
        logAction($conn, $user_id, $user_name, "Update Item", "Updated item ID: $item_id ($item_name)");
        
        header("Location: allitem.php");  // Redirect after successful update
        exit();
    } else {
        echo "Error updating item: " . $update_stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="form.css" rel="stylesheet"> 
    <style>
        /* CSS เพิ่มเติมเล็กน้อยเพื่อให้ดูดีขึ้น */
        .container { max-width: 600px; }
        .current-img { max-width: 150px; border-radius: 5px; margin-top: 10px; border: 1px solid #ddd; padding: 5px; }
    </style>
</head>
<body>
   
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>แก้ไขข้อมูลสินค้า</h2>
            <a href="allitem.php" class="btn btn-secondary">ย้อนกลับ</a>
        </div>

        <div class="card p-4 shadow-sm">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="item_name" class="form-label">ชื่อสินค้า</label>
                    <input type="text" class="form-control" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="item_price" class="form-label">ราคา (บาท)</label>
                    <input type="number" step="0.01" class="form-control" id="item_price" name="item_price" value="<?php echo htmlspecialchars($item['item_price']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="item_image" class="form-label">รูปภาพสินค้า</label>
                    <input type="file" class="form-control" id="item_image" name="image">
                    
                    <?php if ($item['item_image']) : ?>
                        <div class="mt-2">
                            <p class="text-muted small mb-1">รูปภาพปัจจุบัน:</p>
                            <img src="items/<?php echo $item['item_image']; ?>" alt="Item Image" class="current-img">
                        </div>
                    <?php endif; ?>
                    <small class="form-text text-muted d-block mt-1">ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรูปภาพ</small>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3">บันทึกการแก้ไข</button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
