<?php
// addset.php
include 'header.php';
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$set_name = $set_price = $sale_price = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $set_name = trim($_POST["set_name"]);
    $set_price = floatval($_POST["set_price"]);
    $sale_price = floatval($_POST["sale_price"]);
    $discount = floatval($_POST['discount'] ?? 0);
    $set_image = "";

    // Upload Image Logic (เหมือนเดิม)
    if (!empty($_FILES['set_image']['name'])) {
        $target_dir = __DIR__ . "/sets/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['set_image']['name'], PATHINFO_EXTENSION));
        $new_name = uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES['set_image']['tmp_name'], $target_dir . $new_name)) {
            $set_image = $new_name;
        }
    }

    if (!empty($set_name)) {
        $created_by = $_SESSION['user_name'];
        $stmt = $conn->prepare("INSERT INTO sets (set_name, set_price, sale_price, discount_percentage, set_image, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddss", $set_name, $set_price, $sale_price, $discount, $set_image, $created_by);

        if ($stmt->execute()) {
            $new_set_id = $stmt->insert_id;
            
            // *** จุดที่แก้: Log เป็นภาษาไทย ***
            logAction($conn, "สร้าง Set ใหม่", "สร้างชุด '$set_name' (ส่วนลด $discount%) โดย $created_by");

            echo "<script>window.location.href='additemtoset.php?set_id=$new_set_id';</script>";
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
    } else {
        $error = "กรุณากรอกชื่อ Set";
    }
}
?>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow p-4">
                <h3 class="text-center text-success mb-4"><i class="bi bi-folder-plus"></i> สร้าง Set ใหม่</h3>
                
                <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ Set</label>
                        <input type="text" name="set_name" class="form-control" placeholder="เช่น ชุดผ่าตัดเล็ก, ชุดทำแผล..." required value="<?php echo $set_name; ?>">
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">ราคาเต็ม</label>
                            <input type="number" id="set_price" name="set_price" class="form-control" step="0.01" oninput="calcPrice()" required value="<?php echo $set_price; ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">ส่วนลด (%)</label>
                            <input type="number" id="discount" name="discount" class="form-control" value="0" oninput="calcPrice()">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ราคาขายจริง</label>
                        <input type="number" id="sale_price" name="sale_price" class="form-control bg-light" readonly value="<?php echo $sale_price; ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">รูปภาพ (ถ้ามี)</label>
                        <input type="file" name="set_image" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2">สร้างและไปเพิ่มของ</button>
                    <a href="index.php" class="btn btn-link w-100 mt-2 text-decoration-none text-secondary">ยกเลิก</a>
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
