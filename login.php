<?php
include 'db.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_name = trim($_POST['user_name']);

    if (!empty($user_name)) {
        // ใช้ Prepared Statement ป้องกัน SQL Injection
        $stmt = $conn->prepare("SELECT user_id, user_name, hospital_name, department_name FROM users WHERE user_name = ?");
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Regenerate Session ID เพื่อความปลอดภัย
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['hospital_name'] = $user['hospital_name'];
            $_SESSION['department_name'] = $user['department_name'];

            // บันทึก Log การเข้าสู่ระบบ (ใช้ logAction กลาง)
            logAction($conn, "Login", "User logged in successfully");

            // บันทึกลง login_logs (Legacy table)
            $log_stmt = $conn->prepare("INSERT INTO login_logs (user_id, user_name) VALUES (?, ?)");
            $log_stmt->bind_param("is", $user['user_id'], $user['user_name']);
            $log_stmt->execute();
            $log_stmt->close();

            header('Location: index.php');
            exit;
        } else {
            $error = "ไม่พบรายชื่อผู้ใช้นี้ในระบบ";
        }
        $stmt->close();
    } else {
        $error = "กรุณากรอกชื่อผู้ใช้";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; border-radius: 15px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .logo-img { max-width: 120px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="card login-card p-4">
        <div class="card-body text-center">
            <img src="logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none'"> 
            <h4 class="mb-4 text-success fw-bold">เข้าสู่ระบบ</h4>
            
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="user_name" name="user_name" placeholder="ชื่อ-นามสกุล" required>
                    <label for="user_name">ชื่อผู้ใช้งาน (Username)</label>
                </div>
                <button type="submit" class="btn btn-success w-100 btn-lg shadow-sm">เข้าใช้งาน</button>
            </form>
            <div class="mt-3 text-muted small">
                ยังไม่มีบัญชี? <a href="register.php" class="text-decoration-none text-success">ลงทะเบียนที่นี่</a>
            </div>
        </div>
    </div>
</body>
</html>
