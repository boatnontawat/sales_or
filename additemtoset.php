<?php
session_start();
include 'db.php';

// ตรวจสอบว่า user ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$set_id = $_GET['set_id'] ?? null;

if (!$set_id) {
    echo "Set ID is required.";
    exit;
}

// ฟังก์ชันสำหรับบันทึก Log
function logAction($user_id, $action, $details, $conn) {
    if (!$conn instanceof mysqli) {
        echo "Database connection is invalid.";
        return;
    }

    $created_by = $_SESSION['user_name'] ?? 'Guest'; // ใช้ชื่อผู้ใช้ที่ล็อกอินหรือ 'Guest'
    $stmt = $conn->prepare("INSERT INTO logs (action, details, created_by) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $action, $details, $created_by);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Failed to prepare statement: " . $conn->error;
    }
}

// การเพิ่ม, แก้ไข, ลบ รายการ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $add_by = $_SESSION['user_name']; // ผู้ที่เพิ่มรายการ

    // เมื่อเพิ่ม item ใหม่
    if (isset($_POST['item_id'], $_POST['quantity'])) {
        $item_id = $_POST['item_id'];
        $quantity = $_POST['quantity'];

        // ตรวจสอบว่า item มีอยู่ในเซ็ตแล้วหรือไม่
        $stmt = $conn->prepare("SELECT * FROM set_items WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $set_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // หากมีอยู่แล้ว ให้แก้ไขจำนวน
            $stmt = $conn->prepare("UPDATE set_items SET quantity = quantity + ? WHERE set_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $quantity, $set_id, $item_id);
            $stmt->execute();
            logAction($_SESSION['user_id'], "Updated quantity of item_id $item_id in set_id $set_id.", "Added $quantity more.", $conn);
        } else {
            // เพิ่มรายการใหม่เข้าไปใน set
            $stmt = $conn->prepare("INSERT INTO set_items (set_id, item_id, quantity, add_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $set_id, $item_id, $quantity, $add_by);
            $stmt->execute();
            logAction($_SESSION['user_id'], "Added item_id $item_id to set_id $set_id with quantity $quantity.", "", $conn);
        }
    }

    // เมื่อแก้ไขจำนวน item
    if (isset($_POST['update_item_id'], $_POST['new_quantity'])) {
        $update_item_id = $_POST['update_item_id'];
        $new_quantity = $_POST['new_quantity'];

        $stmt = $conn->prepare("UPDATE set_items SET quantity = ? WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("iii", $new_quantity, $set_id, $update_item_id);
        $stmt->execute();
        logAction($_SESSION['user_id'], "Updated item_id $update_item_id in set_id $set_id to new quantity $new_quantity.", "", $conn);
    }

    // เมื่อทำการลบ item ออกจาก set
    if (isset($_POST['delete_item_id'])) {
        $delete_item_id = $_POST['delete_item_id'];

        $stmt = $conn->prepare("DELETE FROM set_items WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $set_id, $delete_item_id);
        $stmt->execute();
        logAction($_SESSION['user_id'], "Deleted item_id $delete_item_id from set_id $set_id.", "", $conn);
    }

    // เมื่อเสร็จสิ้นการจัดการเซ็ต
    if (isset($_POST['finish'])) {
        logAction($_SESSION['user_id'], "Finished managing set_id $set_id.", "Completed adding/removing items.", $conn);
        header("Location: midprice.php?set_id=" . $set_id);
        exit;
    }
}

// ค้นหาสินค้า
$search = $_POST['search'] ?? '';
$search_query = "SELECT * FROM items WHERE item_name LIKE ?";
$stmt = $conn->prepare($search_query);
$search_term = "%$search%";
$stmt->bind_param("s", $search_term);
$stmt->execute();
$search_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="form.css">
    <title>เพิ่มวัสดุ</title>
</head>
<body>
    
    <div class="container">
        
        <h2>เพิ่มวัสดุ</h2>

        <!-- Form ค้นหาสินค้า -->
        <form method="POST">
            <label for="search">ค้นหาวัสดุ</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="">
            <button type="submit">ค้นหา</button>
        </form>

        <?php if ($search): ?>
            <h3>ผลการค้นหา:</h3>
            <table>
                <thead>
                    <tr>
                        <th>รูป</th>
                        <th>ชื่อ</th>
                        <th>ราคา</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $search_result->fetch_assoc()): ?>
                        <tr>
                            <td><img src="items/<?php echo $item['item_image']; ?>" alt="Item Image" style="width: 50px; height: 50px;"></td>
                            <td><?php echo $item['item_name']; ?></td>
                            <td><?php echo $item['item_price']; ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <input type="number" name="quantity" value="1" min="1">
                                    <button type="submit">เพิ่มวัสดุ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Form เลือกสินค้า -->
        <form method="POST">
            <label for="select_item_id">เลือกวัสดุ</label>
            <select name="item_id" id="select_item_id" required>
                <option value=""></option>
                <?php
                $items_query = "SELECT * FROM items";
                $items_result = $conn->query($items_query);
                while ($item = $items_result->fetch_assoc()):
                ?>
                    <option value="<?php echo $item['item_id']; ?>">
                        <?php echo $item['item_name']; ?> <?php echo $item['item_price']; ?>  บาท
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="number" name="quantity" value="1" min="1" required>
            <button type="submit">เพิ่มวัสดุ</button>
        </form>

        <!-- ตารางแสดงรายการที่เพิ่มไปแล้ว -->
        <table>
            <thead>
                <tr>
                    <th>รูป</th>
                    <th>ชื่อ</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>ทั้งสิ้น</th>
                    <th>ผู้เพิ่ม</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // ดึงรายการสินค้าที่ถูกเพิ่มในเซ็ต
                $query = "SELECT i.item_image, i.item_name, i.item_price, si.quantity, (i.item_price * si.quantity) AS total, si.item_id, si.add_by
                          FROM set_items si
                          JOIN items i ON si.item_id = i.item_id
                          WHERE si.set_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $set_id);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><img src="items/<?php echo $row['item_image']; ?>" alt="Item Image" style="width: 50px; height: 50px;"></td>
                        <td><?php echo $row['item_name']; ?></td>
                        <td><?php echo $row['item_price']; ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="update_item_id" value="<?php echo $row['item_id']; ?>">
                                <input type="number" name="new_quantity" value="<?php echo $row['quantity']; ?>" min="1">
                                <button type="submit">แก้ไข</button>
                            </form>
                        </td>
                        <td><?php echo $row['total']; ?></td>
                        <td><?php echo htmlspecialchars($row['add_by']); ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="delete_item_id" value="<?php echo $row['item_id']; ?>">
                                <button type="submit">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- ปุ่ม Finish Set -->
        <form method="POST">
            <button type="submit" name="finish">ถัดไป</button>
        </form>
    </div>
</body>
</html>
