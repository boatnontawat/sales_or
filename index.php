<?php
// Include the database connection
include('db.php');

// Start the session
session_start();

// Check if the user is logged in
$user_name = $hospital_name = $department_name = "";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT user_name, hospital_name, department_name FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_name, $hospital_name, $department_name);
    $stmt->fetch();
    $stmt->close();
} else {
    header("Location: login.php");
    exit();
}

// --- Search Logic ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%{$search}%";

// --- Pagination Logic (Updated) ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 6; // แก้ไข: แสดงสูงสุด 6 Set ต่อหน้า
$offset = ($page - 1) * $items_per_page;

// 1. Get Total Sets Count (With Search)
// ต้องนับเฉพาะรายการที่ตรงกับคำค้นหา
$total_query = "SELECT COUNT(*) AS total_sets FROM sets WHERE set_name LIKE ?";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("s", $search_param);
$stmt->execute();
$total_result = $stmt->get_result();
$total_sets = $total_result->fetch_assoc()['total_sets'];
$stmt->close();

$total_pages = ceil($total_sets / $items_per_page);

// 2. Get the specific Set IDs for this page (With Search & Limit)
$id_query = "SELECT set_id FROM sets WHERE set_name LIKE ? ORDER BY set_id DESC LIMIT ?, ?";
$stmt = $conn->prepare($id_query);
$stmt->bind_param("sii", $search_param, $offset, $items_per_page);
$stmt->execute();
$id_result = $stmt->get_result();

$set_ids = [];
while ($row = $id_result->fetch_assoc()) {
    $set_ids[] = $row['set_id'];
}
$stmt->close();

// 3. Fetch details only if we have sets to show
$sets = [];
if (!empty($set_ids)) {
    // Create a comma-separated string of IDs for the IN clause
    $ids_placeholder = implode(',', $set_ids);
    
    // ตรงนี้ไม่ต้อง Bind Param เพิ่ม เพราะ ID มาจาก Query ข้างบนที่ปลอดภัยแล้ว
    $query = "
        SELECT s.set_id, s.set_name, s.set_image, s.set_price, s.sale_price AS set_sale_price, 
               i.item_name, i.item_image, i.item_price, si.quantity
        FROM sets s
        LEFT JOIN set_items si ON s.set_id = si.set_id
        LEFT JOIN items i ON si.item_id = i.item_id
        WHERE s.set_id IN ($ids_placeholder)
        ORDER BY s.set_id DESC, i.item_id
    ";

    $result = $conn->query($query);

    if (!$result) {
        die("Query Error: " . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $set_id = $row['set_id'];
        if (!isset($sets[$set_id])) {
            $sets[$set_id] = [
                'set_name' => $row['set_name'],
                'set_image' => $row['set_image'],
                'set_price' => $row['set_price'],
                'set_sale_price' => $row['set_sale_price'],
                'items' => []
            ];
        }

        if (!empty($row['item_name'])) {
            $sets[$set_id]['items'][] = [
                'item_name' => $row['item_name'],
                'item_image' => $row['item_image'],
                'item_price' => $row['item_price'],
                'quantity' => $row['quantity']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="index.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* CSS Fix for images */
        .set img, .item img { max-width: 100%; height: auto; object-fit: cover; }
        .item { border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <img src="user.png" alt="User Icon">
            <div>
                <strong>ยินดีต้อนรับ คุณ: <?php echo htmlspecialchars($user_name); ?></strong>
                <span>จากโรงพยาบาล: <?php echo htmlspecialchars($hospital_name); ?></span>
                <span>แผนก: <?php echo htmlspecialchars($department_name); ?></span>
            </div>
        </div>
        
        <div class="header-center">
            <a href="index.php">
                <img src="logo.png" alt="Logo" class="logo">
            </a>      
        </div>
        
        <div class="header-right">
            <a href="setting.php"><img src="gear.png" alt="Settings" width="30" height="30"></a>
            <a href="logout.php"><img src="door.png" alt="Logout" width="30" height="30"></a>
        </div>
    </div>

    <div class="container mt-4">
        
        <div class="row justify-content-center mb-3">
            <div class="col-md-6">
                <form action="index.php" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อ Set..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> ค้นหา</button>
                    <?php if(!empty($search)): ?>
                        <a href="index.php" class="btn btn-secondary">ล้างค่า</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="center-button text-center mb-4">
            <a href="addset.php" class="btn btn-primary btn-lg rounded-circle p-3">
                <img src="add.png" alt="Add Set" width="32" height="32" style="filter: invert(1);">
            </a>
        </div>

        <div class="row g-4">
            <?php if (empty($sets)): ?>
                <div class="col-12 text-center py-5">
                    <h3 class="text-muted">ไม่พบข้อมูล Set ที่ค้นหา</h3>
                </div>
            <?php else: ?>
                <?php foreach ($sets as $set_id => $set): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-4"> <div class="card h-100 shadow-sm">
                            
                            <?php 
                                $set_img_file = "sets/" . $set['set_image'];
                                if (!empty($set['set_image']) && file_exists(__DIR__ . "/" . $set_img_file)) {
                                    $display_img = $set_img_file;
                                } else {
                                    $display_img = "sets/default.jpg"; 
                                }
                            ?>
                            <img src="<?php echo $display_img; ?>" class="card-img-top" alt="Set Image" style="height: 200px; object-fit: cover;">
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($set['set_name']); ?></h5>
                                
                                <div class="mb-2">
                                    <?php if ($set['set_sale_price'] < $set['set_price']): ?>
                                        <span class="text-decoration-line-through text-muted"><?php echo htmlspecialchars($set['set_price']); ?> บาท</span>
                                        <span class="text-danger fw-bold ms-2"><?php echo htmlspecialchars($set['set_sale_price']); ?> บาท</span>
                                    <?php else: ?>
                                        <span class="fw-bold"><?php echo htmlspecialchars($set['set_price']); ?> บาท</span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-auto mb-3">
                                    <a href="edit_set.php?set_id=<?php echo $set_id; ?>" class="btn btn-warning btn-sm">
                                        <img src="pencil.png" alt="Edit" width="16"> แก้ไข
                                    </a>
                                    <a href="delete_set.php?set_id=<?php echo $set_id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('ยืนยันการลบ?');">
                                        <img src="x.png" alt="Delete" width="16"> ลบ
                                    </a>
                                </div>

                                <div class="items-list bg-light p-2 rounded" style="font-size: 0.9em; max-height: 150px; overflow-y: auto;">
                                    <?php foreach ($set['items'] as $item): ?>
                                        <div class="d-flex align-items-center mb-2 border-bottom pb-2">
                                            <?php 
                                                $item_img_file = "items/" . $item['item_image'];
                                                if (!empty($item['item_image']) && file_exists(__DIR__ . "/" . $item_img_file)) {
                                                    $i_img = $item_img_file;
                                                } else {
                                                    $i_img = "items/default.jpg";
                                                }
                                            ?>
                                            <img src="<?php echo $i_img; ?>" alt="Item" width="40" height="40" class="rounded me-2">
                                            <div>
                                                <div class="fw-bold text-truncate" style="max-width: 120px;"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                <small><?php echo htmlspecialchars($item['quantity']); ?> ชิ้น (<?php echo htmlspecialchars($item['item_price']); ?> บ.)</small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4 mb-5">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">ก่อนหน้า</a>
                </li>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">ถัดไป</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
