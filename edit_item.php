<?php
// Include the database connection
include('db.php');

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the item_id from the URL
if (isset($_GET['item_id'])) {
    $item_id = $_GET['item_id'];

    // Fetch the item details from the database
    $query = "SELECT * FROM items WHERE item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
    } else {
        die("Item not found");
    }
} else {
    die("Item ID not specified");
}

// Process form submission for updating item
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = $_POST['item_name'];
    $item_price = $_POST['item_price'];
    $item_image = $item['item_image'];  // Default to existing image

    // Handle image upload (if a new image is provided)
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

    // Update the item details in the database
    $update_query = "UPDATE items SET item_name = ?, item_price = ?, item_image = ? WHERE item_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sdsi", $item_name, $item_price, $item_image, $item_id);
    
    if ($update_stmt->execute()) {
        // Log the update action
        $user_name = $_SESSION['user_name'];
        $action = "Updated item ID: $item_id, Name: $item_name, Price: $item_price";
        $log_query = "INSERT INTO logs (user_name, details) VALUES (?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("ss", $user_name, $action);
        $log_stmt->execute();
        
        header("Location: allitem.php");  // Redirect after successful update
        exit();
    } else {
        echo "Error updating item: " . $update_stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="form.css" rel="stylesheet"> <!-- ลิงค์ไปที่ form.css -->
</head>
<body>
   
    <div class="container mt-5">
    <div class="d-flex justify-content-end mb-3">
            <a href="allitem.php" class="btn btn-secondary">Back</a> <!-- Adjust the link as needed -->
    </div>

        <h2>Edit Item</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="item_name">ชื่อ</label>
                <input type="text" class="form-control" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="item_price">ราคา</label>
                <input type="number" class="form-control" id="item_price" name="item_price" value="<?php echo htmlspecialchars($item['item_price']); ?>" required>
            </div>
            <div class="form-group">
                <label for="item_image">Item Image</label>
                <input type="file" class="form-control" id="item_image" name="image">
                <?php if ($item['item_image']) : ?>
                    <img src="items/<?php echo $item['item_image']; ?>" alt="Item Image" width="100">
                <?php endif; ?>
                <small class="form-text text-muted">Leave empty if you don't want to change the image.</small>
            </div>
            <button type="submit" class="btn btn-primary mt-3">แก้ไข</button>
        </form>
    </div>
</body>
</html>
