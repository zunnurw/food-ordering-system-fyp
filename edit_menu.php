<?php
include "connection.php";

$message = ''; // To store messages to display after form processing

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['itemId'])) {
    $itemId = $_POST['itemId'];
    $name = isset($_POST['itemName']) ? $_POST['itemName'] : null;
    $description = isset($_POST['itemDescription']) ? $_POST['itemDescription'] : null;
    $price = isset($_POST['itemPrice']) ? $_POST['itemPrice'] : null;
    $category = isset($_POST['itemCategory']) ? $_POST['itemCategory'] : null;

    if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] == 0) {
        $imagePath = 'uploads/' . basename($_FILES['itemImage']['name']);
        if (move_uploaded_file($_FILES['itemImage']['tmp_name'], $imagePath)) {
            $image = $imagePath;
        } else {
            $image = null;
        }
    } else {
        $image = null;
    }

    $sql = "UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssdssi", $name, $description, $price, $category, $image, $itemId);
        $stmt->execute();

        if ($stmt->error) {
            $message = "Error: " . $stmt->error;
        } else {
            $message = "Menu item updated successfully";
        }

        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
    }

    // Redirect to menu management page
    header("Location: menu_management.php");
    exit;
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $itemId = $_GET['id'];

    $sql = "SELECT * FROM menu_items WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editItem = $result->fetch_assoc();

        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu Item</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }

        form {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        form input[type="text"],
        form input[type="number"],
        form textarea,
        form select {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        form button {
            display: inline-block;
            background: #333;
            color: #fff;
            border: none;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        form button:hover {
            background: #77b300;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Menu Item</h1>
        <?php if ($message) echo "<p>$message</p>"; // Display messages here ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="itemId" value="<?php echo $editItem['id']; ?>">

            <label for="itemName">Item Name:</label>
            <input type="text" id="itemName" name="itemName" value="<?php echo htmlspecialchars($editItem['name']); ?>" required>

            <label for="itemDescription">Description:</label>
            <textarea id="itemDescription" name="itemDescription"><?php echo htmlspecialchars($editItem['description']); ?></textarea>

            <label for="itemPrice">Price:</label>
            <input type="number" id="itemPrice" name="itemPrice" step="0.01" value="<?php echo htmlspecialchars($editItem['price']); ?>" required>

            <label for="itemCategory">Category:</label>
            <select id="itemCategory" name="itemCategory">
                <option value="Popular Menu" <?php echo $editItem['category'] == 'Popular Menu' ? 'selected' : ''; ?>>Popular Menu</option>
                <option value="Nasi Goreng" <?php echo $editItem['category'] == 'Nasi Goreng' ? 'selected' : ''; ?>>Nasi Goreng</option>
                <option value="Burger" <?php echo $editItem['category'] == 'Burger' ? 'selected' : ''; ?>>Burger</option>
                <option value="Side Dish" <?php echo $editItem['category'] == 'Side Dish' ? 'selected' : ''; ?>>Side Dish</option>
                <option value="Spaghetti" <?php echo $editItem['category'] == 'Spaghetti' ? 'selected' : ''; ?>>Spaghetti</option>
                <option value="Drinks" <?php echo $editItem['category'] == 'Drinks' ? 'selected' : ''; ?>>Drinks</option>
            </select>

            <label for="itemImage">Upload Image:</label>
            <input type="file" id="itemImage" name="itemImage">
            <br><br><br>

            <button a href="menu_management.php" class="btn">Back</button>
            <button type="submit">Update Item</button>
        </form>
    </div>
</body>
</html>
