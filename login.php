<?php
// login.php
session_start();
include 'db.php'; // เชื่อมต่อฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_name = $_POST['user_name'];  // รับค่าชื่อผู้ใช้จากฟอร์ม

    // เตรียมคำสั่ง SQL เพื่อตรวจสอบชื่อผู้ใช้ในฐานข้อมูล
    $stmt = $conn->prepare("SELECT user_id, user_name, hospital_name FROM users WHERE user_name = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);  // หากคำสั่งไม่สำเร็จให้หยุดการทำงาน
    }
    $stmt->bind_param("s", $user_name);  // ใช้คำสั่ง SQL ที่เตรียมไว้
    $stmt->execute();
    $result = $stmt->get_result();  // ดึงผลลัพธ์

    // ตรวจสอบว่ามีผู้ใช้ที่ตรงกับชื่อที่กรอกไว้หรือไม่
    if ($result->num_rows > 0) {  // Corrected 'iif' to 'if'
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['user_name'];  // เก็บชื่อผู้ใช้ที่ล็อกอิน
        $_SESSION['hospital_name'] = $user['hospital_name'];

        // Log the login event
        $log_stmt = $conn->prepare("INSERT INTO login_logs (user_id, login_time, user_name) VALUES (?, NOW(), ?)");
        if (!$log_stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $log_stmt->bind_param("is", $user['user_id'], $user['user_name']);  // 'i' สำหรับ integer, 's' สำหรับ string
        if (!$log_stmt->execute()) {
            die("Execute failed: " . $log_stmt->error);
        }
        $log_stmt->close();

        // เปลี่ยนเส้นทางไปยังหน้า index.php
        header('Location: index.php');
        exit;
    } else {
        $error = "User not found";  // หากไม่พบชื่อผู้ใช้
    }
    $stmt->close();  // ปิดคำสั่ง SQL
}

$conn->close();  // ปิดการเชื่อมต่อกับฐานข้อมูล
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="theme.css">
    <title>Login</title>
</head>
<body>
    <div class="container mt-5">
        <div class="card mx-auto shadow-sm" style="max-width: 400px;">
            <div class="card-body">
                <img src="logo.png" alt="Logo" class="d-block mx-auto mb-4" style="max-width: 100px;">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="user_name" class="form-label">กรุณาใส่ชื่อ-นามสกุล</label>
                        <input type="text" id="user_name" name="user_name" class="form-control" placeholder="" required>
                    </div>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"> <?= $error ?> </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-success w-100">เข้าสู่ระบบ</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
