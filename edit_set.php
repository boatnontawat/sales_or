<?php
// เริ่มต้น session
session_start();

// เชื่อมต่อกับฐานข้อมูล
include 'db.php'; 

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$set_id = $set_name = $set_price = $sale_price = $set_image = "";
$set_name_err = $set_price_err = $sale_price_err = $set_image_err = "";

// ฟังก์ชันสำหรับบันทึกข้อมูลการกระทำ
function logAction($user_name, $details, $conn) {
    // ตรวจสอบว่า user_name เป็นค่าที่ไม่ใช่ null
    if ($user_name == null) {
        echo "Error: User not logged in.";
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO logs (user_name, action, details) VALUES (?, 'Updated Set', ?)");
    $stmt->bind_param("ss", $user_name, $details);
    $stmt->execute();
    $stmt->close();
}

// รับค่า set_id จาก URL
if (isset($_GET['set_id']) && !empty($_GET['set_id'])) {
    $set_id = $_GET['set_id'];

    // ดึงข้อมูล set จากฐานข้อมูล
    $sql = "SELECT * FROM sets WHERE set_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $set_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $set_name = $row['set_name'];
            $set_price = $row['set_price'];
            $sale_price = $row['sale_price'];
            $set_image = $row['set_image'];
        } else {
            echo "Set not found.";
            exit();
        }
        $stmt->close();
    }
} else {
    echo "Set ID not provided.";
    exit();
}

// เมื่อมีการ submit ฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate set_name
    if (empty(trim($_POST["set_name"]))) {
        $set_name_err = "Please enter the set name.";
    } else {
        $set_name = trim($_POST["set_name"]);
    }

    // Validate set_price
    if (empty(trim($_POST["set_price"]))) {
        $set_price_err = "Please enter the set price.";
    } else {
        $set_price = trim($_POST["set_price"]);
    }

    // Validate sale_price
    if (empty(trim($_POST["sale_price"]))) {
        $sale_price_err = "Please enter the sale price.";
    } else {
        $sale_price = trim($_POST["sale_price"]);
    }

    // Handle image upload
    if (isset($_FILES['set_image']) && $_FILES['set_image']['error'] == 0) {
        $image_tmp = $_FILES['set_image']['tmp_name'];
        $image_name = $_FILES['set_image']['name'];
        $target_dir = "C:/xampp/htdocs/project/sets/";  // Change to your desired folder
        $target_file = $target_dir . basename($image_name);
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "gif"];

        // Check if the file type is allowed
        if (!in_array($image_file_type, $allowed_types)) {
            $set_image_err = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        } else {
            // Try to upload the image
            if (move_uploaded_file($image_tmp, $target_file)) {
                $set_image = $image_name;  // Store the image file name in the database
            } else {
                $set_image_err = "Sorry, there was an error uploading your image.";
            }
        }
    }

    // If no errors, update the database
    if (empty($set_name_err) && empty($set_price_err) && empty($sale_price_err) && empty($set_image_err)) {
        $sql = "UPDATE sets SET set_name = ?, set_price = ?, sale_price = ?, set_image = ? WHERE set_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssi", $set_name, $set_price, $sale_price, $set_image, $set_id);
            if ($stmt->execute()) {
                // Log the action
                $user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null; // Get user name from session

                logAction($user_name, "Updated set details for set_id $set_id. Name: $set_name, Price: $set_price, Sale Price: $sale_price, Image: $set_image", $conn);

                // Redirect to a success page
                header("Location: edititemtoset.php?set_id=$set_id");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Set</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="form.css">
    <script>
        function calculateSalePrice() {
            const setPrice = parseFloat(document.getElementById('set_price').value) || 0;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const salePrice = setPrice - (setPrice * (discount / 100));
            document.getElementById('sale_price').value = salePrice.toFixed(2);  // Update sale price
        }
    </script>
</head>
<body>
    <div class="container mt-5">
    <div class="d-flex justify-content-end mb-3">
            <a href="allset.php" class="btn btn-secondary">กลับ</a> <!-- Adjust the link as needed -->
    </div>
        <h2 class="text-center text-primary">แก้ไขข้อมูล</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?set_id=" . $set_id); ?>" method="post" enctype="multipart/form-data" class="form-group">
            <div class="mb-3">
                <label for="set_name" class="form-label">ชื่อ:</label>
                <input type="text" name="set_name" class="form-control" value="<?php echo $set_name; ?>">
                <span class="text-danger"><?php echo $set_name_err; ?></span>
            </div>

            <div class="mb-3">
                <label for="set_price" class="form-label">ราคา:</label>
                <input type="text" id="set_price" name="set_price" class="form-control" value="<?php echo $set_price; ?>" oninput="calculateSalePrice()">
                <span class="text-danger"><?php echo $set_price_err; ?></span>
            </div>

            <div class="mb-3">
                <label for="discount" class="form-label">เปอร์เซ็นต์ที่ลด:</label>
                <input type="text" id="discount" name="discount" class="form-control" value="0" oninput="calculateSalePrice()">
            </div>

            <div class="mb-3">
                <label for="sale_price" class="form-label">ลดเหลือ:</label>
                <input type="text" id="sale_price" name="sale_price" class="form-control" value="<?php echo $sale_price; ?>" readonly>
                <span class="text-danger"><?php echo $sale_price_err; ?></span>
            </div>

            <div class="mb-3">
                <label for="set_image" class="form-label">Set Image:</label>
                <input type="file" name="set_image" class="form-control">
                <span class="text-danger"><?php echo $set_image_err; ?></span>
            </div>

            <button type="submit" class="btn btn-primary w-100">แก้ไข</button>
        </form>
    </div>
</body>
</html>
