<?php
session_start();

// Include the database connection
include 'connection.php';

// Initialize variables for form values and errors
$name = $price = $description = $image = "";
$nameErr = $priceErr = $descriptionErr = $imageErr = "";

// Function to sanitize input
function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($input))));
}

// Function to handle file upload
function uploadImage($file) {
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        $uploadOk = 0;
    }

    // Check file size (500KB limit)
    if ($file["size"] > 500000) {
        $uploadOk = 0;
    }

    // Allow certain file formats
    $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
    if (!in_array($imageFileType, $allowedTypes)) {
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        return false;
    } else {
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            return $targetFile;
        } else {
            return false;
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate name
    if (empty($_POST["name"])) {
        $nameErr = "Name is required";
    } else {
        $name = sanitize($_POST["name"]);
    }

    // Validate price
    if (empty($_POST["price"])) {
        $priceErr = "Price is required";
    } else {
        $price = sanitize($_POST["price"]);
        // Validate price as numeric
        if (!is_numeric($price)) {
            $priceErr = "Price must be a number";
        }
    }

    // Validate description
    if (empty($_POST["description"])) {
        $descriptionErr = "Description is required";
    } else {
        $description = sanitize($_POST["description"]);
    }

    // Validate image
    if ($_FILES["image"]["error"] == 4) {
        $imageErr = "Image is required";
    } else {
        $image = uploadImage($_FILES["image"]);
        if (!$image) {
            $imageErr = "Sorry, there was an error uploading your file.";
        }
    }

    // If all fields are valid, insert into database
    if (!empty($name) && !empty($price) && !empty($description) && !empty($image)) {
        // Prepare image path for database insertion
        $imagePath = mysqli_real_escape_string($conn, $image);

        $sql = "INSERT INTO menu_items (name, price, description, image_path) VALUES ('$name', '$price', '$description', '$imagePath')";

        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully";
            // Clear form values after successful insertion
            $name = $price = $description = $image = "";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Menu Item</title>
</head>
<body>
    <h2>Add New Menu Item</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>">
        <span class="error"><?php echo $nameErr; ?></span><br><br>

        <label for="price">Price:</label><br>
        <input type="text" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>">
        <span class="error"><?php echo $priceErr; ?></span><br><br>

        <label for="description">Description:</label><br>
        <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
        <span class="error"><?php echo $descriptionErr; ?></span><br><br>

        <label for="image">Image:</label><br>
        <input type="file" id="image" name="image">
        <span class="error"><?php echo $imageErr; ?></span><br><br>

        <input type="submit" name="submit" value="Add Menu Item">
    </form>
</body>
</html>
