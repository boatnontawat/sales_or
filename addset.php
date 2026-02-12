<?php
include 'db.php'; // รวมไฟล์เชื่อมต่อฐานข้อมูล
session_start(); // เริ่มต้น session

// ตรวจสอบว่า user_id ถูกตั้งค่าหรือยัง (ผู้ใช้ล็อกอินหรือไม่)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// -----------------------------------------------------------
// จุดที่แก้ไข: ปรับปรุงฟังก์ชัน logAction ให้บันทึก user_id ด้วย
// -----------------------------------------------------------
function logAction($action, $details, $conn) {
    // ดึง user_id จาก Session (ถ้าไม่มีให้เป็น 0)
    $user_id = $_SESSION['user_id'] ?? 0;
    $created_by = $_SESSION['user_name'] ?? 'Guest';

    // เพิ่ม user_id เข้าไปในคำสั่ง SQL
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, created_by) VALUES (?, ?, ?, ?)");
    
    // bind_param: i = integer (user_id), s = string (action, details, created_by)
    $stmt->bind_param("isss", $user_id, $action, $details, $created_by);
    
    $stmt->execute();
    $stmt->close();
}
// -----------------------------------------------------------

// Initialize variables
$set_name = $set_price = $sale_price = $set_image = "";
$set_name_err = $set_price_err = $sale_price_err = $set_image_err = "";

// เมื่อมีการ submit ฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าจากฟอร์มและตรวจสอบความถูกต้อง (Validation)
    if (empty(trim($_POST["set_name"]))) {
        $set_name_err = "Please enter the set name.";
    } else {
        $set_name = trim($_POST["set_name"]);
    }

    if (empty(trim($_POST["set_price"]))) {
        $set_price_err = "Please enter the set price.";
    } else {
        $set_price = trim($_POST["set_price"]);
    }

    if (empty(trim($_POST["sale_price"]))) {
        $sale_price_err = "Please enter the sale price.";
    } else {
        $sale_price = trim($_POST["sale_price"]);
    }

    $discount = 0;
    if (isset($_POST['discount']) && is_numeric($_POST['discount'])) {
        $discount = trim($_POST['discount']);
    }

    // 2. จัดการการอัปโหลดรูปภาพ (Image Upload) - สำหรับ Render/Linux
    if (isset($_FILES['set_image']) && $_FILES['set_image']['error'] == 0) {
        $image_tmp = $_FILES['set_image']['tmp_name'];
        $image_name = basename($_FILES['set_image']['name']);
        
        // ใช้ __DIR__ เพื่อหา Path ปัจจุบันของไฟล์นี้บน Server
        $target_dir = __DIR__ . "/sets/"; 
        
        // ตรวจสอบว่ามีโฟลเดอร์ sets หรือไม่ ถ้าไม่มีให้สร้าง
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . $image_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "gif"];

        if (!in_array($image_file_type, $allowed_types)) {
            $set_image_err = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        } else {
            // ย้ายไฟล์จาก Temp ไปยังโฟลเดอร์ sets
            if (move_uploaded_file($image_tmp, $target_file)) {
                $set_image = $image_name; // เก็บชื่อไฟล์ไว้ลง Database
            } else {
                $set_image_err = "Sorry, there was an error uploading your image.";
            }
        }
    }

    // 3. บันทึกลงฐานข้อมูล (ถ้าไม่มี Error)
    if (empty($set_name_err) && empty($set_price_err) && empty($sale_price_err) && empty($set_image_err)) {
        
        $created_by = $_SESSION['user_name'] ?? 'Guest';

        // เตรียม SQL Insert
        $sql = "INSERT INTO sets (set_name, set_price, sale_price, discount_percentage, set_image, created_by) VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters (s=string, d=double)
            $stmt->bind_param("ssddss", $set_name, $set_price, $sale_price, $discount, $set_image, $created_by);

            if ($stmt->execute()) {
                // บันทึก Log (ตอนนี้ฟังก์ชันนี้รองรับ user_id แล้ว จะไม่ error)
                logAction('AddSet', "Added set: $set_name with discount $discount%", $conn);

                // ดึง ID ล่าสุดที่เพิ่งเพิ่ม
                $set_id = $stmt->insert_id;

                // Redirect ไปหน้าเพิ่มรายการสินค้าใน Set
                header("Location: additemtoset.php?set_id=" . $set_id);
                exit();
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
    <title>สร้าง Set</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <script>
        function calculateSalePrice() {
            const setPrice = parseFloat(document.getElementById('set_price').value) || 0;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const salePrice = setPrice - (setPrice * (discount / 100));
            document.getElementById('sale_price').value = salePrice.toFixed(2);
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center text-success">สร้าง Set</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="form-group">
            
            <div class="mb-3">
                <label for="set_name" class="form-label">ชื่อ Set:</label>
                <input type="text" name="set_name" class="form-control" value="<?php echo htmlspecialchars($set_name); ?>">
                <span class="text-danger"><?php echo $set_name_err; ?></span>
            </div>

            <div class="mb-3">
                <label for="set_price" class="form-label">ราคาเต็ม:</label>
                <input type="text" id="set_price" name="set_price" class="form-control" value="<?php echo htmlspecialchars($set_price); ?>" oninput="calculateSalePrice()">
                <span class="text-danger"><?php echo $set_price_err; ?></span>
            </div>

            <div class="mb-3">
                <label for="discount" class="form-label">ส่วนลด (%):</label>
                <input type="text" id="discount" name="discount" class="form-control" value="0" oninput="calculateSalePrice()">
            </div>

            <div class="mb-3">
                <label for="sale_price" class="form-label">ราคาขายจริง:</label>
                <input type="text" id="sale_price" name="sale_price" class="form-control" value="<?php echo htmlspecialchars($sale_price); ?>" readonly>
                <span class="text-danger"><?php echo $sale_price_err; ?></span>
            </div>

            <div class="mb-3">
                <label for="set_image" class="form-label">รูปภาพ Set (ถ้ามี):</label>
                <input type="file" name="set_image" class="form-control">
                <span class="text-danger"><?php echo $set_image_err; ?></span>
            </div>

            <button type="submit" class="btn btn-success w-100">สร้าง Set และไปต่อ</button>
        </form>
    </div>
</body>
</html>
