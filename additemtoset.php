<?php
// additemtoset.php
include 'header.php'; // ใช้ Header ใหม่ที่สวยงาม
include 'db.php';     // เชื่อมต่อฐานข้อมูลและฟังก์ชัน logAction

// ตรวจสอบ Login
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$set_id = $_GET['set_id'] ?? null;
if (!$set_id) {
    die("<div class='container mt-5 alert alert-danger'>ไม่พบรหัส Set (Set ID is required)</div>");
}

// ดึงชื่อ Set มาเตรียมไว้สำหรับแสดงผลและบันทึก Log
$set_name_query = $conn->query("SELECT set_name FROM sets WHERE set_id = '$set_id'");
$current_set_name = ($set_name_query->num_rows > 0) ? $set_name_query->fetch_assoc()['set_name'] : "Set #$set_id";

// --- ส่วนจัดการข้อมูล (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. เพิ่ม Item ลงใน Set
    if (isset($_POST['item_id'], $_POST['quantity'])) {
        $item_id = $_POST['item_id'];
        $quantity = $_POST['quantity'];

        // ดึงชื่อ Item เพื่อบันทึก Log ให้เป็นภาษาคน
        $item_query = $conn->query("SELECT item_name FROM items WHERE item_id = '$item_id'");
        $item_name_log = ($item_query->num_rows > 0) ? $item_query->fetch_assoc()['item_name'] : "รหัส $item_id";

        // ตรวจสอบของเดิม
        $stmt = $conn->prepare("SELECT * FROM set_items WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $set_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // มีแล้ว -> บวกเพิ่ม
            $stmt = $conn->prepare("UPDATE set_items SET quantity = quantity + ? WHERE set_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $quantity, $set_id, $item_id);
            $stmt->execute();
            
            // Log ภาษาไทย
            logAction($conn, "แก้ไขปริมาณใน Set", "เพิ่มจำนวน '$item_name_log' อีก $quantity ชิ้น ในชุด '$current_set_name'");
        } else {
            // ยังไม่มี -> เพิ่มใหม่
            $stmt = $conn->prepare("INSERT INTO set_items (set_id, item_id, quantity, add_by) VALUES (?, ?, ?, ?)");
            $user_name = $_SESSION['user_name'];
            $stmt->bind_param("iiis", $set_id, $item_id, $quantity, $user_name);
            $stmt->execute();

            // Log ภาษาไทย
            logAction($conn, "เพิ่มของใน Set", "เพิ่ม '$item_name_log' (จำนวน $quantity) ลงในชุด '$current_set_name'");
        }
    }

    // 2. แก้ไขจำนวน (Update Qty)
    if (isset($_POST['update_item_id'], $_POST['new_quantity'])) {
        $update_item_id = $_POST['update_item_id'];
        $new_quantity = $_POST['new_quantity'];

        // ดึงชื่อ Item
        $item_query = $conn->query("SELECT item_name FROM items WHERE item_id = '$update_item_id'");
        $item_name_log = ($item_query->num_rows > 0) ? $item_query->fetch_assoc()['item_name'] : "รหัส $update_item_id";

        $stmt = $conn->prepare("UPDATE set_items SET quantity = ? WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("iii", $new_quantity, $set_id, $update_item_id);
        $stmt->execute();

        logAction($conn, "แก้ไขปริมาณ", "ปรับแก้จำนวน '$item_name_log' เป็น $new_quantity ชิ้น ในชุด '$current_set_name'");
    }

    // 3. ลบรายการ (Delete)
    if (isset($_POST['delete_item_id'])) {
        $delete_item_id = $_POST['delete_item_id'];

        // ดึงชื่อ Item ก่อนลบ
        $item_query = $conn->query("SELECT item_name FROM items WHERE item_id = '$delete_item_id'");
        $item_name_log = ($item_query->num_rows > 0) ? $item_query->fetch_assoc()['item_name'] : "รหัส $delete_item_id";

        $stmt = $conn->prepare("DELETE FROM set_items WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $set_id, $delete_item_id);
        $stmt->execute();

        logAction($conn, "ลบของออกจาก Set", "ลบ '$item_name_log' ออกจากชุด '$current_set_name'");
    }

    // 4. เสร็จสิ้น
    if (isset($_POST['finish'])) {
        // Log จบงาน
        // logAction($conn, "จัดการ Set เสร็จสิ้น", "บันทึกข้อมูลชุด '$current_set_name' เรียบร้อย");
        echo "<script>window.location.href='midprice.php?set_id=$set_id';</script>";
        exit;
    }
}

// Search Logic
$search = $_POST['search'] ?? '';
$search_result = null;
if($search) {
    $stmt = $conn->prepare("SELECT * FROM items WHERE item_name LIKE ?");
    $term = "%$search%";
    $stmt->bind_param("s", $term);
    $stmt->execute();
    $search_result = $stmt->get_result();
}
?>

<div class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary"><i class="bi bi-box-seam"></i> จัดการรายการ: <?php echo htmlspecialchars($current_set_name); ?></h3>
        <a href="allset.php" class="btn btn-outline-secondary btn-sm">ย้อนกลับ</a>
    </div>

    <div class="card p-4 mb-4">
        <h5 class="card-title mb-3">🔍 ค้นหาและเพิ่มวัสดุ</h5>
        <form method="POST" class="row g-2">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="พิมพ์ชื่อวัสดุ (เช่น เข็ม, ผ้าก็อซ)..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> ค้นหา</button>
            </div>
        </form>

        <?php if ($search_result && $search_result->num_rows > 0): ?>
            <div class="mt-3 table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light"><tr><th>รูปภาพ</th><th>ชื่อรายการ</th><th>ราคา</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while ($item = $search_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php $img = !empty($item['item_image']) ? "items/".$item['item_image'] : "https://via.placeholder.com/50"; ?>
                                    <img src="<?php echo $img; ?>" width="50" height="50" class="rounded object-fit-cover">
                                </td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo number_format($item['item_price'], 2); ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <input type="number" name="quantity" value="1" min="1" class="form-control form-control-sm" style="width: 70px;">
                                        <button type="submit" class="btn btn-success btn-sm text-nowrap"><i class="bi bi-plus-circle"></i> เพิ่ม</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($search): ?>
            <div class="alert alert-warning mt-3">ไม่พบวัสดุที่ค้นหา</div>
        <?php endif; ?>
    </div>

    <div class="card p-4">
        <h5 class="card-title mb-3 text-success">📋 รายการในชุดนี้</h5>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>รูป</th>
                        <th>ชื่อรายการ</th>
                        <th class="text-end">ราคาต่อหน่วย</th>
                        <th class="text-center" width="150">จำนวน</th>
                        <th class="text-end">รวม</th>
                        <th class="text-center">ผู้เพิ่ม</th>
                        <th class="text-center">ลบ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT i.item_image, i.item_name, i.item_price, si.quantity, (i.item_price * si.quantity) AS total, si.item_id, si.add_by
                              FROM set_items si
                              JOIN items i ON si.item_id = i.item_id
                              WHERE si.set_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $set_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $grand_total = 0;

                    if ($res->num_rows > 0):
                        while ($row = $res->fetch_assoc()):
                            $grand_total += $row['total'];
                    ?>
                        <tr>
                            <td>
                                <?php $img = !empty($row['item_image']) ? "items/".$row['item_image'] : "https://via.placeholder.com/50"; ?>
                                <img src="<?php echo $img; ?>" width="40" height="40" class="rounded">
                            </td>
                            <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td class="text-end"><?php echo number_format($row['item_price'], 2); ?></td>
                            <td>
                                <form method="POST" class="d-flex justify-content-center gap-1">
                                    <input type="hidden" name="update_item_id" value="<?php echo $row['item_id']; ?>">
                                    <input type="number" name="new_quantity" value="<?php echo $row['quantity']; ?>" min="1" class="form-control form-control-sm text-center px-1">
                                    <button type="submit" class="btn btn-warning btn-sm p-1"><i class="bi bi-arrow-clockwise"></i></button>
                                </form>
                            </td>
                            <td class="text-end fw-bold"><?php echo number_format($row['total'], 2); ?></td>
                            <td class="text-center small text-muted"><?php echo htmlspecialchars($row['add_by']); ?></td>
                            <td class="text-center">
                                <form method="POST" onsubmit="return confirm('ลบรายการนี้?');">
                                    <input type="hidden" name="delete_item_id" value="<?php echo $row['item_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">ยังไม่มีรายการในชุดนี้</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary fw-bold">
                        <td colspan="4" class="text-end">ราคารวมทั้งหมด:</td>
                        <td class="text-end text-primary fs-5"><?php echo number_format($grand_total, 2); ?> ฿</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <form method="POST">
                <button type="submit" name="finish" class="btn btn-success btn-lg px-5 shadow">
                    <i class="bi bi-check-circle-fill"></i> เสร็จสิ้น & คำนวณราคา
                </button>
            </form>
        </div>
    </div>
</div>
