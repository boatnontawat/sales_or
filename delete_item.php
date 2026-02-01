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

// Check if item_id is provided
if (isset($_GET['item_id'])) {
    $item_id = $_GET['item_id'];

    // Fetch item details for logging
    $query = "SELECT * FROM items WHERE item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();

        // Prepare and execute the DELETE query
        $sql = "DELETE FROM items WHERE item_id = ?";
        $delete_stmt = $conn->prepare($sql);
        $delete_stmt->bind_param("i", $item_id);

        if ($delete_stmt->execute()) {
            // Log the deletion action
            $user_name = $_SESSION['user_name'];
            $action = "Deleted item ID: $item_id, Name: {$item['item_name']}, Price: {$item['item_price']}";
            $log_query = "INSERT INTO logs (user_name, details) VALUES (?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("ss", $user_name, $action);
            $log_stmt->execute();

            header("Location: allitem.php");  // Redirect after successful deletion
            exit();
        } else {
            echo "Error deleting item: " . $delete_stmt->error;
        }
    } else {
        echo "Item not found";
    }
} else {
    echo "Item ID not specified";
}

?>
