<?php
// เริ่มต้น session
session_start();

// เชื่อมต่อกับฐานข้อมูล
include 'db.php'; 
include 'header.php';

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check login status
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Unknown';

// Initialize variables
$set_id = $set_name = $set_price = $sale_price = $set_image = "";
$set_name_err = $set_price_err = $sale_price_err = $set_image_err = "";

// -----------------------------------------------------------
// ฟังก์ชันบันทึก Log (Corrected for your DB structure)
// -----------------------------------------------------------
function logAction($conn, $user_id, $created_by, $action, $details) {
    // Assuming your logs table has: user_id, created_by, action, details
    $stmt = $conn->prepare("INSERT INTO logs (user_id, created_by, action, details) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $created_by, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}
// -----------------------------------------------------------

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
            $set_image = $row['set_image']; // Keep existing image
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
        $image_name = basename($_FILES['set_image']['name']);
        
        // --- Fix: Use __DIR__ for Linux/Render compatibility ---
        $target_dir = __DIR__ . "/sets/";
        
        // Create folder if not exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . $image_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "gif"];

        // Check if the file type is allowed
        if (!in_array($image_file_type, $allowed_types)) {
            $set_image_err = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        } else {
            // Try to upload the image
            if (move_uploaded_file($image_tmp, $target_file)) {
                $set_image = $image_name; // Update variable only on successful upload
            } else {
                $set_image_err = "Sorry, there was an error uploading your image.";
            }
        }
    }

    // If no errors, update the database
    if (empty($set_name_err) && empty($set_price_err) && empty($sale_price_err) && empty($set_image_err)) {
        
        // Use prepared statement to update
        $sql = "UPDATE sets SET set_name = ?, set_price = ?, sale_price = ?, set_image = ? WHERE set_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssi", $set_name, $set_price, $sale_price, $set_image, $set_id);
            
            if ($stmt->execute()) {
                // Log the action (Fixed function call)
                logAction($conn, $user_id, $user_name, "Update Set", "Updated set ID: $set_id ($set_name)");

                // Redirect to a success page or item editor
                header("Location: additemtoset.php?set_id=$set_id"); // Changed to match your previous file names
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="form.css">
    <style>
        .current-img { max-width: 150px; border-radius: 5px; margin-top: 10px; border: 1px solid #ddd; padding: 5px; }
    </style>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h2 class="text-primary m-0">แก้ไขข้อมูล Set</h2>
            <a href="allset.php" class="btn btn-secondary">กลับ</a> 
        </div>
       
        <div class="card p-4 shadow-sm">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?set_id=" . $set_id); ?>" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="set_name" class="form-label">ชื่อ Set:</label>
                    <input type="text" name="set_name" class="form-control" value="<?php echo htmlspecialchars($set_name); ?>">
                    <span class="text-danger"><?php echo $set_name_err; ?></span>
                </div>

                <div class="mb-3">
                    <label for="set_price" class="form-label">ราคาเต็ม:</label>
                    <input type="text" id="set_price" name="set_price" class="form-control" value="<?php echo htmlspecialchars($set_price); ?>" oninput="calculateSalePrice()">
                    <span class="text-danger"><?php echo $set_price_err; ?></span>
                </div>

                <div class="mb-3">
                    <label for="discount" class="form-label">เปอร์เซ็นต์ส่วนลด (%):</label>
                    <?php 
                        $discount_display = 0;
                        if($set_price > 0 && $sale_price > 0 && $sale_price < $set_price) {
                            $discount_display = round((($set_price - $sale_price) / $set_price) * 100);
                        }
                    ?>
                    <input type="text" id="discount" name="discount" class="form-control" value="<?php echo $discount_display; ?>" oninput="calculateSalePrice()">
                </div>

                <div class="mb-3">
                    <label for="sale_price" class="form-label">ราคาขายจริง (หลังหักส่วนลด):</label>
                    <input type="text" id="sale_price" name="sale_price" class="form-control" value="<?php echo htmlspecialchars($sale_price); ?>" readonly>
                    <span class="text-danger"><?php echo $sale_price_err; ?></span>
                </div>

                <div class="mb-3">
                    <label for="set_image" class="form-label">รูปภาพ Set:</label>
                    <input type="file" name="set_image" class="form-control">
                    <span class="text-danger"><?php echo $set_image_err; ?></span>
                    
                    <?php if (!empty($set_image)): ?>
                        <div class="mt-2">
                            <p class="text-muted small mb-1">รูปภาพปัจจุบัน:</p>
                            <img src="sets/<?php echo $set_image; ?>" alt="Set Image" class="current-img">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">บันทึกการแก้ไข</button>
            </form>
        </div>
    </div>
</body>
</html>
