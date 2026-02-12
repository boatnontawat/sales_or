<?php
session_start();
include 'db.php';

// ตรวจสอบว่า user ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// รับค่า set_id และป้องกัน Error
$set_id = $_GET['set_id'] ?? null;

if (!$set_id) {
    die("<div class='alert alert-danger'>Error: Set ID is required.</div>");
}

// ดึงชื่อ User อย่างปลอดภัย
$current_user = $_SESSION['user_name'] ?? 'Unknown';

// -----------------------------------------------------------
// จุดที่แก้ไข: ปรับปรุงฟังก์ชัน logAction ให้บันทึก user_id ด้วย
// -----------------------------------------------------------
function logAction($user_id, $action, $details, $conn) {
    if (!$conn instanceof mysqli) {
        return;
    }
    $created_by = $_SESSION['user_name'] ?? 'Guest';
    
    // เพิ่ม user_id เข้าไปในคำสั่ง SQL
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, created_by) VALUES (?, ?, ?, ?)");
    
    if ($stmt) {
        // bind_param: i = int (user_id), s = string
        $stmt->bind_param("isss", $user_id, $action, $details, $created_by);
        $stmt->execute();
        $stmt->close();
    }
}
// -----------------------------------------------------------

// --- ส่วนจัดการข้อมูล (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. เพิ่ม Item ลงใน Set
    if (isset($_POST['item_id'], $_POST['quantity'])) {
        $item_id = $_POST['item_id'];
        $quantity = $_POST['quantity'];

        // ตรวจสอบว่ามีของเดิมอยู่ไหม
        $stmt = $conn->prepare("SELECT * FROM set_items WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $set_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // มีแล้ว -> บวกเพิ่ม
            $stmt = $conn->prepare("UPDATE set_items SET quantity = quantity + ? WHERE set_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $quantity, $set_id, $item_id);
            $stmt->execute();
            logAction($_SESSION['user_id'], "Updated Set", "Added $quantity more of item $item_id to set $set_id", $conn);
        } else {
            // ยังไม่มี -> เพิ่มใหม่
            $stmt = $conn->prepare("INSERT INTO set_items (set_id, item_id, quantity, add_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $set_id, $item_id, $quantity, $current_user);
            $stmt->execute();
            logAction($_SESSION['user_id'], "Add to Set", "Added item $item_id to set $set_id (Qty: $quantity)", $conn);
        }
    }

    // 2. แก้ไขจำนวน (Update)
    if (isset($_POST['update_item_id'], $_POST['new_quantity'])) {
        $update_item_id = $_POST['update_item_id'];
        $new_quantity = $_POST['new_quantity'];

        $stmt = $conn->prepare("UPDATE set_items SET quantity = ? WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("iii", $new_quantity, $set_id, $update_item_id);
        $stmt->execute();
        logAction($_SESSION['user_id'], "Update Qty", "Updated item $update_item_id in set $set_id to $new_quantity", $conn);
    }

    // 3. ลบรายการ (Delete)
    if (isset($_POST['delete_item_id'])) {
        $delete_item_id = $_POST['delete_item_id'];

        $stmt = $conn->prepare("DELETE FROM set_items WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $set_id, $delete_item_id);
        $stmt->execute();
        logAction($_SESSION['user_id'], "Delete from Set", "Deleted item $delete_item_id from set $set_id", $conn);
    }

    // 4. กดปุ่ม Finish (เสร็จสิ้น)
    if (isset($_POST['finish'])) {
        logAction($_SESSION['user_id'], "Finish Set", "Finished managing set $set_id", $conn);
        header("Location: midprice.php?set_id=" . $set_id);
        exit;
    }
}

// --- ส่วนค้นหาข้อมูล ---
$search = $_POST['search'] ?? '';
$search_result = null;
if ($search) {
    $search_query = "SELECT * FROM items WHERE item_name LIKE ?";
    $stmt = $conn->prepare($search_query);
    $search_term = "%$search%";
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $search_result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรายการใน Set</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .item-img { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .table-middle td { vertical-align: middle; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5 bg-white p-4 rounded shadow-sm">
    <h2 class="text-center mb-4 text-primary">จัดการรายการสินค้าใน Set</h2>

    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="?set_id=<?php echo $set_id; ?>" class="row g-3">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control" placeholder="พิมพ์ชื่อวัสดุเพื่อค้นหา..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-info w-100 text-white">ค้นหา</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($search_result && $search_result->num_rows > 0): ?>
        <div class="alert alert-info">ผลการค้นหา: พบ <?php echo $search_result->num_rows; ?> รายการ</div>
        <table class="table table-bordered table-hover table-middle">
            <thead class="table-dark">
                <tr>
                    <th>รูปภาพ</th>
                    <th>ชื่อรายการ</th>
                    <th>ราคา</th>
                    <th style="width: 200px;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $search_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if(!empty($item['item_image'])): ?>
                                <img src="items/<?php echo htmlspecialchars($item['item_image']); ?>" class="item-img" alt="Item">
                            <?php else: ?>
                                <span class="text-muted">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo number_format($item['item_price'], 2); ?></td>
                        <td>
                            <form method="POST" action="?set_id=<?php echo $set_id; ?>" class="d-flex gap-2">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" class="form-control" style="width: 80px;">
                                <button type="submit" class="btn btn-success btn-sm">เพิ่ม</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php elseif ($search): ?>
        <div class="alert alert-warning">ไม่พบสินค้าที่ค้นหา</div>
    <?php endif; ?>

    <hr class="my-4">

    <h4 class="mb-3">รายการสินค้าใน Set นี้</h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-middle">
            <thead class="table-secondary">
                <tr>
                    <th>รูปภาพ</th>
                    <th>ชื่อรายการ</th>
                    <th>ราคาต่อหน่วย</th>
                    <th style="width: 150px;">จำนวน</th>
                    <th>ราคารวม</th>
                    <th>ผู้เพิ่ม</th>
                    <th>ลบ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // ดึงรายการใน Set
                $query = "SELECT i.item_image, i.item_name, i.item_price, si.quantity, (i.item_price * si.quantity) AS total, si.item_id, si.add_by
                          FROM set_items si
                          JOIN items i ON si.item_id = i.item_id
                          WHERE si.set_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $set_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $grand_total = 0;
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $grand_total += $row['total'];
                ?>
                    <tr>
                        <td>
                             <?php if(!empty($row['item_image'])): ?>
                                <img src="items/<?php echo htmlspecialchars($row['item_image']); ?>" class="item-img">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td><?php echo number_format($row['item_price'], 2); ?></td>
                        <td>
                            <form method="POST" action="?set_id=<?php echo $set_id; ?>" class="d-flex gap-1">
                                <input type="hidden" name="update_item_id" value="<?php echo $row['item_id']; ?>">
                                <input type="number" name="new_quantity" value="<?php echo $row['quantity']; ?>" min="1" class="form-control px-1 text-center">
                                <button type="submit" class="btn btn-warning btn-sm p-1">แก้</button>
                            </form>
                        </td>
                        <td><?php echo number_format($row['total'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['add_by'] ?? '-'); ?></td>
                        <td>
                            <form method="POST" action="?set_id=<?php echo $set_id; ?>" onsubmit="return confirm('ยืนยันการลบ?');">
                                <input type="hidden" name="delete_item_id" value="<?php echo $row['item_id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                else:
                ?>
                    <tr><td colspan="7" class="text-center text-muted">ยังไม่มีรายการสินค้าใน Set นี้</td></tr>
                <?php endif; ?>
            </tbody>
            <?php if ($grand_total > 0): ?>
            <tfoot>
                <tr class="table-dark">
                    <td colspan="4" class="text-end"><strong>รวมทั้งหมด:</strong></td>
                    <td colspan="3"><strong><?php echo number_format($grand_total, 2); ?> บาท</strong></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="allset.php" class="btn btn-secondary">ย้อนกลับ</a>
        <form method="POST" action="?set_id=<?php echo $set_id; ?>">
            <button type="submit" name="finish" class="btn btn-primary btn-lg px-5">เสร็จสิ้น & ถัดไป</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
