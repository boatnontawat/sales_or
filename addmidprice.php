<?php
session_start();
include 'db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Unknown';

// ฟังก์ชันสำหรับบันทึก Log 
function logAction($conn, $user_id, $created_by, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO logs (user_id, created_by, action, details) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $created_by, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

$success_msg = "";
$error_msg = "";

// เมื่อมีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mid_name = trim($_POST['mid_name']);
    $mid_price = floatval($_POST['mid_price']);

    // ตรวจสอบความถูกต้องของข้อมูล
    if (!empty($mid_name) && $mid_price > 0) {
        $query = "INSERT INTO mid_prices (mid_name, mid_price) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sd', $mid_name, $mid_price);

        if ($stmt->execute()) {
            // บันทึกลง Log
            logAction($conn, $user_id, $user_name, "Add Mid Price", "Added Set กลาง: '$mid_name' ราคา: $mid_price");
            
            $success_msg = "เพิ่มข้อมูล Set กลางเรียบร้อยแล้ว";
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการเพิ่มข้อมูล: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_msg = "กรุณากรอกข้อมูลให้ครบถ้วน และราคาต้องมากกว่า 0";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข้อมูล Set กลาง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="mid.css">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 500px; margin-top: 50px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center text-primary mb-4">เพิ่มข้อมูล Set กลาง</h2>
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success text-center"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger text-center"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="mid_name" class="form-label text-start w-100">ชื่อ Set กลาง:</label>
                <input type="text" name="mid_name" id="mid_name" class="form-control" placeholder="กรอกชื่อ Set กลาง" required>
            </div>
            <div class="mb-4">
                <label for="mid_price" class="form-label text-start w-100">ราคา Set กลาง:</label>
                <input type="number" name="mid_price" id="mid_price" class="form-control" placeholder="0.00" required step="0.01" min="0">
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-2">บันทึกข้อมูล</button>
            <a href="setting.php" class="btn btn-secondary w-100">ย้อนกลับ</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
