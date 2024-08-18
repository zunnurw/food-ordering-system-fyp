<?php
include "connection.php";

$message = ''; // To store messages to display after form processing

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $itemId = $_GET['id'];

    $sql = "DELETE FROM menu_items WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $itemId);
        $stmt->execute();

        if ($stmt->error) {
            $message = "Error: " . $stmt->error;
        } else {
            $message = "Menu item deleted successfully";
        }

        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
    }

    // Redirect to menu management page
    header("Location: menu_management.php");
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Menu Item</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your existing CSS */
    </style>
</head>
<body>
    <div class="container">
        <h1>Delete Menu Item</h1>
        <?php if ($message) echo "<p>$message</p>"; // Display messages here ?>
        <a href="menu_management.php" class="btn">Back to Menu Management</a>
    </div>
</body>
</html>
