<?php
// register.php
include 'db.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_name = $_POST['user_name'];  // Changed 'name' to 'user_name'
    $hospital_name = $_POST['hospital_name'];
    $department_name = $_POST['department_name'];
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'];

    // Prepare and execute the query to insert the new user into the database
    $stmt = $conn->prepare("INSERT INTO users (user_name, hospital_name, department_name, email, phone_number) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sssss", $user_name, $hospital_name, $department_name, $email, $phone);
    if ($stmt->execute()) {
        // Get the new user's ID
        $user_id = $conn->insert_id;

        // Start a session and store user details
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $user_name;
        $_SESSION['hospital_name'] = $hospital_name;

        // Log the registration event
        $action = 'User registered: ' . $user_name;
        $log_stmt = $conn->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $user_id, $action);
        $log_stmt->execute();

        // Redirect to index.php after registration
        header('Location: index.php');
        exit;
    } else {
        die("Error: " . $stmt->error);
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="theme.css">
    <title>กรุณา ลงทะเบียน</title>
</head>
<body>
    <div class="container mt-5">
        <div class="card mx-auto shadow-sm" style="max-width: 500px;">
            <div class="card-body">
                <img src="logo.png" alt="Logo" class="d-block mx-auto mb-4" style="max-width: 100px;">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="user_name" class="form-label">ชื่อ-นามสกุล</label>  
                        <input type="text" id="user_name" name="user_name" class="form-control" placeholder="" required>  
                    </div>
                    <div class="mb-3">
                        <label for="hospital_name" class="form-label">ชื่อโรงพยาบาล</label>
                        <input type="text" id="hospital_name" name="hospital_name" class="form-control" placeholder="" required>
                    </div>
                    <div class="mb-3">
                        <label for="department_name" class="form-label">ชื่อแผนก</label>
                        <input type="text" id="department_name" name="department_name" class="form-control" placeholder="" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">ลงทะเบียน</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
