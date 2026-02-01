<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$set_id = $_GET['set_id'] ?? null;

if (!$set_id) {
    echo "Set ID is required.";
    exit;
}

function logAction($user_name, $action, $details, $conn) {
    $stmt = $conn->prepare("INSERT INTO logs (user_name, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user_name, $action, $details);
    $stmt->execute();
}

// Adding, updating, and deleting items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['item_id'], $_POST['quantity'])) {
        $item_id = $_POST['item_id'];
        $quantity = $_POST['quantity'];

        // Check if item already exists in the set
        $stmt = $conn->prepare("SELECT * FROM set_items WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $set_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // If item exists, update the quantity
            $stmt = $conn->prepare("UPDATE set_items SET quantity = ? WHERE set_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $quantity, $set_id, $item_id);
            $stmt->execute();
            logAction($_SESSION['user_name'], "Updated quantity of item_id $item_id in set_id $set_id.", "", $conn);
        } else {
            // If item does not exist, add it
            $stmt = $conn->prepare("INSERT INTO set_items (set_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $set_id, $item_id, $quantity);
            $stmt->execute();
            logAction($_SESSION['user_name'], "Added item_id $item_id to set_id $set_id with quantity $quantity.", "", $conn);
        }
    }

    if (isset($_POST['update_item_id'], $_POST['new_quantity'])) {
        $update_item_id = $_POST['update_item_id'];
        $new_quantity = $_POST['new_quantity'];

        // Overwrite the quantity with the new value
        $stmt = $conn->prepare("UPDATE set_items SET quantity = ? WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("iii", $new_quantity, $set_id, $update_item_id);
        $stmt->execute();
        logAction($_SESSION['user_name'], "Updated item_id $update_item_id in set_id $set_id to new quantity $new_quantity.", "", $conn);

        // Redirect after updating the item
        header("Location: edititemtoset.php?set_id=$set_id");
        exit;
    }

    if (isset($_POST['delete_item_id'])) {
        $delete_item_id = $_POST['delete_item_id'];

        $stmt = $conn->prepare("DELETE FROM set_items WHERE set_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $set_id, $delete_item_id);
        $stmt->execute();
        logAction($_SESSION['user_name'], "Deleted item_id $delete_item_id from set_id $set_id.", "", $conn);
    }

    if (isset($_POST['finish'])) {
        logAction($_SESSION['user_name'], "Finished managing set_id $set_id.", "", $conn);
        unset($_SESSION['group_id']); // End the current group
        header("Location: midprice.php?set_id=" . $set_id);
        exit;
    }
}

// Searching for items
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

        <!-- Search Form -->
        <form method="POST">
            <label for="search">ค้นหาวัสดุ</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter item name">
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

        <!-- Select Item Section -->
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
                        <?php echo $item['item_name']; ?> - $<?php echo $item['item_price']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="number" name="quantity" value="1" min="1" required>
            <button type="submit">เพิ่มวัสดุ</button>
        </form>

        <!-- Items Added to Set -->
        <h3>Items Added to Set</h3>
        <table>
            <thead>
                <tr>
                    <th>รูป</th>
                    <th>ชื่อ</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>ผู้เพิ่ม</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT i.item_image, i.item_name, i.item_price, si.quantity, (i.item_price * si.quantity) AS total, si.item_id
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

        <!-- Finish Button -->
        <form method="POST">
            <button type="submit" name="finish">ถัดไป</button>
        </form>
    </div>
</body>
</html>
