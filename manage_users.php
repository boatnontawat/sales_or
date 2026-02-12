<?php
// manage_users.php

// เชื่อมต่อฐานข้อมูล (ใช้ Config เดียวกับ setting.php)
$host = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$port = 4000;
$user = '3WUQLTeLKsCs6W4.root'; 
$password = 'wknpq6pvH9P0rVdH';
$dbname = 'project';

$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL); 
$conn->real_connect($host, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$msg = "";

// --- จัดการการ เพิ่ม / แก้ไข / ลบ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. เพิ่มผู้ใช้ใหม่
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $username = $_POST['user_name'];
        $hospital = $_POST['hospital_name'];
        $dept = $_POST['department_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone_number'];
        $pwd = $_POST['password']; // ควรเข้ารหัสด้วย password_hash() ในโปรดักชั่น

        // เช็คว่ามี username นี้หรือยัง
        $check = $conn->query("SELECT * FROM users WHERE user_name = '$username'");
        if ($check->num_rows > 0) {
            $msg = "<div class='alert alert-danger'>ชื่อผู้ใช้นี้มีอยู่แล้ว</div>";
        } else {
            $sql = "INSERT INTO users (user_name, password, hospital_name, department_name, email, phone_number) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $username, $pwd, $hospital, $dept, $email, $phone);
            if ($stmt->execute()) {
                $msg = "<div class='alert alert-success'>เพิ่มผู้ใช้สำเร็จ</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
            }
        }
    }

    // 2. แก้ไขผู้ใช้
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $uid = $_POST['user_id'];
        $username = $_POST['user_name'];
        $hospital = $_POST['hospital_name'];
        $dept = $_POST['department_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone_number'];
        
        // ถ้ามีการกรอกรหัสผ่านใหม่ค่อยอัปเดต
        $pwd_sql = "";
        if (!empty($_POST['password'])) {
            $pwd = $_POST['password']; 
            $sql = "UPDATE users SET user_name=?, password=?, hospital_name=?, department_name=?, email=?, phone_number=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $username, $pwd, $hospital, $dept, $email, $phone, $uid);
        } else {
            $sql = "UPDATE users SET user_name=?, hospital_name=?, department_name=?, email=?, phone_number=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $username, $hospital, $dept, $email, $phone, $uid);
        }

        if ($stmt->execute()) {
            $msg = "<div class='alert alert-success'>แก้ไขข้อมูลสำเร็จ</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }

    // 3. ลบผู้ใช้
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $uid = $_POST['delete_id'];
        // ป้องกันไม่ให้ลบตัวเอง
        if ($uid == $_SESSION['user_id']) {
            $msg = "<div class='alert alert-warning'>ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้</div>";
        } else {
            $sql = "DELETE FROM users WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $uid);
            if ($stmt->execute()) {
                $msg = "<div class='alert alert-success'>ลบผู้ใช้สำเร็จ</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้งาน</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <?php echo $msg; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>จัดการผู้ใช้งาน</h2>
        <div>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                + เพิ่มผู้ใช้ใหม่
            </button>
            <a href="setting.php" class="btn btn-secondary">กลับเมนูตั้งค่า</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>โรงพยาบาล</th>
                            <th>แผนก</th>
                            <th>เบอร์โทร</th>
                            <th>Email</th>
                            <th width="150">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM users ORDER BY user_id DESC");
                        while ($row = $result->fetch_assoc()) {
                        ?>
                        <tr>
                            <td><?php echo $row['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['hospital_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm edit-btn" 
                                    data-id="<?php echo $row['user_id']; ?>"
                                    data-username="<?php echo $row['user_name']; ?>"
                                    data-hospital="<?php echo $row['hospital_name']; ?>"
                                    data-dept="<?php echo $row['department_name']; ?>"
                                    data-phone="<?php echo $row['phone_number']; ?>"
                                    data-email="<?php echo $row['email']; ?>"
                                    data-bs-toggle="modal" data-bs-target="#editUserModal">
                                    แก้ไข
                                </button>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('ยืนยันที่จะลบผู้ใช้นี้?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="delete_id" value="<?php echo $row['user_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
          <div class="modal-header">
            <h5 class="modal-title">เพิ่มผู้ใช้ใหม่</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="mb-2"><label>Username</label><input type="text" name="user_name" class="form-control" required></div>
            <div class="mb-2"><label>Password</label><input type="password" name="password" class="form-control" required></div>
            <div class="mb-2"><label>ชื่อโรงพยาบาล</label><input type="text" name="hospital_name" class="form-control"></div>
            <div class="mb-2"><label>แผนก</label><input type="text" name="department_name" class="form-control"></div>
            <div class="mb-2"><label>เบอร์โทร</label><input type="text" name="phone_number" class="form-control"></div>
            <div class="mb-2"><label>Email</label><input type="email" name="email" class="form-control"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary">บันทึก</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
          <div class="modal-header">
            <h5 class="modal-title">แก้ไขข้อมูลผู้ใช้</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="mb-2"><label>Username</label><input type="text" name="user_name" id="edit_username" class="form-control" required></div>
            <div class="mb-2">
                <label>Password ใหม่ (ว่างไว้ถ้าไม่เปลี่ยน)</label>
                <input type="password" name="password" class="form-control" placeholder="กรอกเพื่อเปลี่ยนรหัสผ่าน">
            </div>
            <div class="mb-2"><label>ชื่อโรงพยาบาล</label><input type="text" name="hospital_name" id="edit_hospital" class="form-control"></div>
            <div class="mb-2"><label>แผนก</label><input type="text" name="department_name" id="edit_dept" class="form-control"></div>
            <div class="mb-2"><label>เบอร์โทร</label><input type="text" name="phone_number" id="edit_phone" class="form-control"></div>
            <div class="mb-2"><label>Email</label><input type="email" name="email" id="edit_email" class="form-control"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-warning">อัปเดต</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    // สคริปต์สำหรับดึงข้อมูลใส่ Modal แก้ไข
    var editModal = document.getElementById('editUserModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        
        // ดึงข้อมูลจากปุ่ม
        var id = button.getAttribute('data-id');
        var username = button.getAttribute('data-username');
        var hospital = button.getAttribute('data-hospital');
        var dept = button.getAttribute('data-dept');
        var phone = button.getAttribute('data-phone');
        var email = button.getAttribute('data-email');
        
        // ใส่ข้อมูลลงใน input
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_hospital').value = hospital;
        document.getElementById('edit_dept').value = dept;
        document.getElementById('edit_phone').value = phone;
        document.getElementById('edit_email').value = email;
    });
</script>

</body>
</html>
