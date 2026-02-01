<?php
// Include the database connection
include('db.php');

// Start the session
session_start();

// Check if the user is logged in and retrieve user details
$user_name = $hospital_name = $department_name = "";

// Check if session contains 'user_id'
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

// Set default page and items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$items_per_page = 10; // Number of sets per page
$offset = ($page - 1) * $items_per_page; // Offset for SQL query

// Query to get total sets count
$total_query = "SELECT COUNT(*) AS total_sets FROM sets";
$total_result = $conn->query($total_query);
$total_sets = $total_result->fetch_assoc()['total_sets'];
$total_pages = ceil($total_sets / $items_per_page); // Calculate total pages

// Query to get the sets with pagination
$query = "
    SELECT s.set_id, s.set_name, s.set_image, s.set_price AS set_price, s.sale_price AS set_sale_price, 
           i.item_name, i.item_image, i.item_price AS item_price, si.quantity
    FROM sets s
    LEFT JOIN set_items si ON s.set_id = si.set_id
    LEFT JOIN items i ON si.item_id = i.item_id
    ORDER BY s.set_id, i.item_id
    LIMIT $offset, $items_per_page
";

$result = $conn->query($query);

// Check if the query was successful
if (!$result) {
    die("Query Error: " . $conn->error);
}

// Create an array to store the data
$sets = [];
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="index.css" rel="stylesheet">
</head>
<body>

    <div class="header">
    <div class="header-left">
        <img src="user.png" alt="User Icon">
        <div>
            <strong>ยินดีต้อนรับ คุณ:<?php echo htmlspecialchars($user_name); ?></strong>
            <span>จากโรงพยาบาล:<?php echo htmlspecialchars($hospital_name); ?></span>
            <span>แผนก:<?php echo htmlspecialchars($department_name); ?></span>
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

<div class="center-button">
    <a href="addset.php" class="btn btn-icon">
        <img src="add.png" alt="Add Set" width="32" height="32">
    </a>
</div>

    <!-- Display Sets -->
    <div class="container">
        <div class="row">
            <?php foreach ($sets as $set_id => $set): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="set">
                        <h3><?php echo htmlspecialchars($set['set_name']); ?></h3>
                        <?php 
                            $set_image_path = "sets/" . $set['set_image'];
                            if (!empty($set['set_image']) && file_exists("C:/xampp/htdocs/project/" . $set_image_path)): 
                        ?>
                            <img src="<?php echo $set_image_path; ?>" alt="Set Image">
                        <?php else: ?>
                            <img src="sets/default.jpg" alt="Default Image">
                        <?php endif; ?>
                        <?php if ($set['set_sale_price'] < $set['set_price']): ?>
                        <p><span class="set-price">ราคา: <?php echo htmlspecialchars($set['set_price']); ?> บาท</span></p>
                        <p><span class="sale-price">ลดเหลือ: <?php echo htmlspecialchars($set['set_sale_price']); ?> บาท</span></p>
                        <?php else: ?>
                        <p><span class="set-price normal-price">ราคา: <?php echo htmlspecialchars($set['set_price']); ?> บาท</span></p>
                        <?php endif; ?>

                        <a href="edit_set.php?set_id=<?php echo $set_id; ?>" class="btn btn-icon">
                        <img src="pencil.png" alt="Edit" width="24" height="24">
                        </a>
                        <a href="delete_set.php?set_id=<?php echo $set_id; ?>" class="btn btn-icon" onclick="return confirm('Are you sure you want to delete this set?');">
                        <img src="x.png" alt="Delete" width="24" height="24">
                        </a>

                        <div class="items">
                            <?php foreach ($set['items'] as $item): ?>
                                <div class="item">
                                    <?php 
                                        $item_image_path = "items/" . $item['item_image'];
                                        if (!empty($item['item_image']) && file_exists("C:/xampp/htdocs/project/" . $item_image_path)): 
                                    ?>
                                        <img src="<?php echo $item_image_path; ?>" alt="Item Image">
                                    <?php else: ?>
                                        <img src="items/default.jpg" alt="Default Item Image">
                                    <?php endif; ?>
                                    <p><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></p>
                                    <p>ราคา: <?php echo htmlspecialchars($item['item_price']); ?>บาท</p>
                                    <p>จำนวน: <?php echo htmlspecialchars($item['quantity']); ?>ชิ้น</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>

</body>
</html>
