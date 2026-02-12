<?php
// edit_item.php

// 1. เชื่อมต่อฐานข้อมูล (ต้องทำเป็นอย่างแรก)
include 'db.php';

// ตรวจสอบสถานะ Session ก่อนเริ่มใหม่ (แก้ปัญหา Notice: Ignoring session_start)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. ตรวจสอบ Login (ถ้าไม่ได้ล็อกอิน ดีดออกทันที)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Unknown';

// 3. ตรวจสอบว่ามี ID สินค้าส่งมาไหม
$item_id = $_GET['item_id'] ?? '';
if (empty($item_id)) {
    die("ไม่พบรหัสสินค้า (Item ID not provided)");
}

// 4. ดึงข้อมูลสินค้าเดิมออกมาเตรียมไว้
$stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $item = $result->fetch_assoc();
} else {
    die("ไม่พบสินค้านี้ในระบบ");
}

$error = "";

// 5. ส่วนประมวลผลเมื่อกดปุ่ม "บันทึก" (Logic ต้องอยู่ตรงนี้ ก่อน HTML)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = trim($_POST['item_name']);
    $item_price = floatval($_POST['item_price']);
    $item_image = $item['item_image']; // ค่าเริ่มต้นใช้รูปเดิม

    // จัดการอัปโหลดรูปภาพใหม่ (ถ้ามี)
    if (!empty($_FILES['image']['name'])) {
        $target_dir = __DIR__ . "/items/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed_types)) {
            $new_image_name = uniqid() . '.' . $ext;
            $target_file = $target_dir . $new_image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // ลบรูปเก่าทิ้งเพื่อประหยัดพื้นที่ (Option)
                if (!empty($item['item_image']) && file_exists($target_dir . $item['item_image'])) {
                    @unlink($target_dir . $item['item_image']);
                }
                $item_image = $new_image_name;
            } else {
                $error = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
            }
        } else {
            $error = "อนุญาตเฉพาะไฟล์รูปภาพ (JPG, JPEG, PNG, GIF) เท่านั้น";
        }
    }

    // ถ้าไม่มี Error ให้บันทึกลงฐานข้อมูล
    if (empty($error)) {
        $update_sql = "UPDATE items SET item_name = ?, item_price = ?, item_image = ? WHERE item_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sdsi", $item_name, $item_price, $item_image, $item_id);
        
        if ($stmt->execute()) {
            // บันทึก Log (ถ้ามีฟังก์ชันนี้)
            if (function_exists('logAction')) {
                logAction($conn, $user_id, $user_name, "แก้ไขวัสดุ", "แก้ไขข้อมูล '$item_name' (ราคา: $item_price)");
            }

            // *** Redirect ตรงนี้จะทำงานได้สมบูรณ์ เพราะยังไม่มี HTML Output ***
            header("Location: allitem.php");
            exit();
        } else {
            $error = "เกิดข้อผิดพลาดฐานข้อมูล: " . $stmt->error;
        }
    }
}

// 6. เริ่มแสดงผล HTML (ค่อยเรียก header.php ตรงนี้)
include 'header.php';
?>

<div class="container pb-5 mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="text-warning m-0"><i class="bi bi-pencil-square"></i> แก้ไขข้อมูลสินค้า</h3>
                    <a href="allitem.php" class="btn btn-outline-secondary btn-sm">ย้อนกลับ</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="item_name" class="form-label">ชื่อสินค้า / วัสดุ</label>
                        <input type="text" class="form-control" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="item_price" class="form-label">ราคา (บาท)</label>
                        <input type="number" step="0.01" class="form-control" id="item_price" name="item_price" value="<?php echo htmlspecialchars($item['item_price']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="item_image" class="form-label">รูปภาพสินค้า</label>
                        <input type="file" class="form-control" id="item_image" name="image">
                        
                        <?php if (!empty($item['item_image'])) : ?>
                            <div class="mt-3 text-center p-2 border rounded bg-light">
                                <small class="d-block text-muted mb-2">รูปภาพปัจจุบัน</small>
                                <?php 
                                    $img_path = "items/" . $item['item_image'];
                                    // ตรวจสอบไฟล์จริงเพื่อป้องกันรูปแตก
                                    if (!file_exists(__DIR__ . "/" . $img_path)) {
                                        $img_path = "https://via.placeholder.com/150?text=No+Image";
                                    }
                                ?>
                                <img src="<?php echo $img_path; ?>" alt="Item Image" style="max-height: 150px; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 py-2 mt-2">บันทึกการแก้ไข</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
