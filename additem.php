<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user_name from the database using user_id from the session
$user_id = $_SESSION['user_id'];

// Fetch user_name from users table
$stmt = $conn->prepare("SELECT user_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();

if (!$user_name) {
    die("Error: User name not found for user_id: $user_id.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect item data from the form
    $item_name = trim($_POST['item_name']);
    $item_price = floatval($_POST['price']);
    $item_image = null;

    // Validate required fields
    if (empty($item_name) || $item_price <= 0) {
        die("Invalid item name or price.");
    }

    // Handle image upload if file is provided
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "C:/xampp/htdocs/project/items/";
        $original_filename = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');

        // Validate file type
        if (!in_array($extension, $allowed_types)) {
            die("Only JPG, JPEG, PNG, and GIF files are allowed.");
        }

        // Validate file size (max 2MB)
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            die("File size exceeds the 2MB limit.");
        }

        // Generate a unique filename to prevent overwriting
        $item_image = uniqid($original_filename . '_', true) . '.' . $extension;
        $target_file = $target_dir . $item_image;

        // Move the uploaded file
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            die("Error uploading image.");
        }
    }

    // Insert item into the database with image path stored as VARCHAR (NULL if no image)
    $stmt = $conn->prepare("INSERT INTO items (item_name, item_price, item_image) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $item_name, $item_price, $item_image);
    if ($stmt->execute()) {
        // Log the creation of the item
        $log_action = "Create Item";
        $log_details = "Item '$item_name' created with price $item_price";
        log_action($conn, $user_name, $log_action, $log_details);

        // Redirect to settings page with success message
        header('Location: setting.php?success=Item added successfully');
        exit;
    } else {
        die("Error: " . $stmt->error);
    }
}

$conn->close();

// Function to log actions in logs table
function log_action($conn, $created_by, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO logs (created_by, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $created_by, $action, $details);
    if (!$stmt->execute()) {
        die("Error inserting log: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้าง Item</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="form.css">
</head>
<body>
    <div class="container">
        <h2>ทำการสร้าง Item</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-12">
                    <label for="item_name" class="form-label">ชื่อ Item</label>
                    <input type="text" id="item_name" name="item_name" class="form-control" placeholder="Enter item name" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 price-col">
                    <label for="price" class="form-label">ราคา Item</label>
                    <input type="number" step="0.01" id="price" name="price" class="form-control" placeholder="Enter item price" required>
                </div>
                <div class="col-md-6">
                    <label for="image" class="form-label">Upload Item Image (Optional)</label>
                    <input type="file" id="image" name="image" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">สร้าง</button>
        </form>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
