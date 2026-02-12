<?php
// edit_set.php

// 1. เริ่มต้น Session และเชื่อมต่อฐานข้อมูล
// (ต้องทำก่อน Output ใดๆ)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php'; 

// 2. ตรวจสอบ Login (Logic ต้องมาก่อน HTML)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Unknown';

// ฟังก์ชัน Log (ใส่กันเหนียวไว้ กรณีใน db.php ยังไม่มี)
if (!function_exists('logAction')) {
    function logAction($conn, $user_id, $created_by, $action, $details) {
        $stmt = $conn->prepare("INSERT INTO logs (user_id, created_by, action, details) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $created_by, $action, $details);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$set_id = $_GET['set_id'] ?? '';
if (empty($set_id)) {
    die("ไม่พบรหัส Set (Set ID not provided)");
}

$error = "";
$set_name = "";
$set_price = "";
$sale_price = "";
$set_image = "";

// 3. ดึงข้อมูลเก่ามาแสดง
$stmt = $conn->prepare("SELECT * FROM sets WHERE set_id = ?");
$stmt->bind_param("i", $set_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $set_name = $row['set_name'];
    $set_price = $row['set_price'];
    $sale_price = $row['sale_price'];
    $set_image = $row['set_image'];
} else {
    die("ไม่พบข้อมูล Set นี้");
}

// 4. จัดการเมื่อกดปุ่ม "บันทึก" (POST Request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_set_name = trim($_POST["set_name"]);
    $new_set_price = floatval($_POST["set_price"]);
    $new_sale_price = floatval($_POST["sale_price"]);
    $new_discount = floatval($_POST['discount'] ?? 0);
    
    // อัปโหลดรูปภาพใหม่ (ถ้ามี)
    if (!empty($_FILES['set_image']['name'])) {
        $target_dir = __DIR__ . "/sets/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['set_image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . "." . $ext;
        
        if (move_uploaded_file($_FILES['set_image']['tmp_name'], $target_dir . $new_filename)) {
            $set_image = $new_filename; // อัปเดตชื่อไฟล์ใหม่
        }
    }

    if (!empty($new_set_name)) {
        // อัปเดตข้อมูลลงฐานข้อมูล
        $update_sql = "UPDATE sets SET set_name=?, set_price=?, sale_price=?, discount_percentage=?, set_image=? WHERE set_id=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sdddsi", $new_set_name, $new_set_price, $new_sale_price, $new_discount, $set_image, $set_id);

        if ($stmt->execute()) {
            // บันทึก Log เป็นภาษาไทย
            logAction($conn, $user_id, $user_name, "แก้ไขข้อมูล Set", "แก้ไขชุด '$new_set_name' (ราคาขาย: $new_sale_price)");

            // *** Redirect จุดนี้จะทำงานได้ถูกต้อง เพราะยังไม่มี HTML Output ***
            header("Location: additemtoset.php?set_id=$set_id");
            exit();
        } else {
            $error = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
    } else {
        $error = "กรุณากรอกชื่อ Set";
    }
}

// --- 5. เริ่มแสดงผล HTML (ค่อย Include Header ตรงนี้) ---
include 'header.php'; 
?>

<div class="container pb-5 mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="text-primary m-0"><i class="bi bi-pencil-square"></i> แก้ไขข้อมูล Set</h3>
                    <a href="allset.php" class="btn btn-outline-secondary btn-sm">ย้อนกลับ</a>
                </div>
                
                <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ Set</label>
                        <input type="text" name="set_name" class="form-control" value="<?php echo htmlspecialchars($set_name); ?>" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">ราคาเต็ม</label>
                            <input type="number" id="set_price" name="set_price" class="form-control" step="0.01" value="<?php echo $set_price; ?>" oninput="calcPrice()" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">ส่วนลด (%)</label>
                            <?php 
                                // คำนวณ % ส่วนลดกลับมาแสดงในช่อง input
                                $discount_show = 0;
                                if ($set_price > 0 && $sale_price < $set_price) {
                                    $discount_show = round((($set_price - $sale_price) / $set_price) * 100);
                                }
                            ?>
                            <input type="number" id="discount" name="discount" class="form-control" value="<?php echo $discount_show; ?>" oninput="calcPrice()">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ราคาขายจริง</label>
                        <input type="number" id="sale_price" name="sale_price" class="form-control bg-light" value="<?php echo $sale_price; ?>" readonly>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">รูปภาพ</label>
                        <input type="file" name="set_image" class="form-control">
                        <?php if (!empty($set_image)): ?>
                            <div class="mt-2 text-center p-2 border rounded bg-light">
                                <small class="d-block text-muted mb-1">รูปปัจจุบัน</small>
                                <?php 
                                    $img_path = "sets/" . $set_image;
                                    // ตรวจสอบว่าไฟล์มีจริงไหม ถ้าไม่มีให้แสดง placeholder
                                    if (!file_exists(__DIR__ . "/" . $img_path)) {
                                        $img_path = "https://via.placeholder.com/150?text=No+Image";
                                    }
                                ?>
                                <img src="<?php echo $img_path; ?>" alt="Current Image" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">บันทึกการแก้ไข</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function calcPrice() {
    let price = parseFloat(document.getElementById('set_price').value) || 0;
    let disc = parseFloat(document.getElementById('discount').value) || 0;
    let total = price - (price * (disc / 100));
    document.getElementById('sale_price').value = total.toFixed(2);
}
</script>

<?php 
$conn->close(); 
?>
