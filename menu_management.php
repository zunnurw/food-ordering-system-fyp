<?php
include "connection.php";

session_start();

if (!isset($_SESSION['username']) || $_SESSION['job'] == 'Chef') {
    // Redirect to the login page or another page if not logged in as chef
    header("Location: chef.php");
    exit();
}

if (isset($_POST['logout'])) {
    $_SESSION = array();
    session_destroy();
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] == 'add') {
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

    if ($name && $price) {
        $sql = "INSERT INTO menu_items (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssdss", $name, $description, $price, $category, $image);
            $stmt->execute();

            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
            } else {
                $message = "Menu item added successfully";
            }

            $stmt->close();
        } else {
            $message = "Error preparing statement: " . $conn->error;
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $message = "Please ensure all required fields are filled out.";
    }
}

$category_order = array("Popular Menu", "Nasi Goreng", "Burger", "Side Dish", "Spaghetti", "Drinks");

$search_term = isset($_GET['search']) ? $_GET['search'] : '';

$menu_items = [];
foreach ($category_order as $category) {
    $item_sql = "SELECT id, name, description, price, image FROM menu_items WHERE category = ? ORDER BY id ASC";
    $stmt = $conn->prepare($item_sql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    $menu_items[$category] = [];
    while ($row = $result->fetch_assoc()) {
        $menu_items[$category][] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    margin: 0;
    padding: 20px;
    background-image: url('adminpage.jpg');
    background-size: cover;
}

.container {
    width: 80%;
    margin: 0 auto;
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    padding: 20px;
    overflow-x: auto;
}

h1 {
    text-align: center;
    color: #333333;
    margin-top: 20px;
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
form select,
form input[type="file"] {
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

table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
}

th, td {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
    vertical-align: middle;
}

th {
    background-color: #f2f2f2;
}

tr:nth-child(even) {
    background-color: #f2f2f2;
}

.btn {
    display: inline-block;
    font-size: 18px;
    color: white;
    background-color: #333;
    padding: 15px 30px;
    text-decoration: none;
    border-radius: 5px;
    text-align: center;
}

.btn:hover {
    background-color: #77b300;
}

.actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

img {
    max-width: 100px;
    height: auto;
}

.button-container {
    text-align: center;
    margin-top: 20px;
    margin-bottom: 20px;
}

.button-container a,
.button-container form input[type="submit"] {
    display: inline-block;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s;
    border: none;
    cursor: pointer;
    color: #fff;
    font-size: 16px;
    margin: 0 10px;
}

.logout-link {
    display: inline-block;
    font-size: 18px;
    color: white;
    background-color: #333;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    text-align: center;
}

.logout-link:hover {
    background-color: grey;
}

/* Dropdown styles updated to match the dashboard */
.dropdown {
    position: relative;
    display: inline-block;
    margin-right: 10px;
}

.dropbtn {
    padding: 10px 20px;
    font-size: 16px;
    margin-right: 5px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    background-color: #007bff; /* Button color */
    color: white;
}

.dropdown:hover .dropbtn {
    background-color: grey;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
    z-index: 1;
    border-radius: 5px;
}

.dropdown-content a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    text-align: left;
    transition: background-color 0.3s;
}

.dropdown-content a:hover {
    background-color: #ddd;
}

.dropdown:hover .dropdown-content {
    display: block;
}

@media screen and (max-width: 768px) {
    .container {
        width: 100%;
        padding: 10px;
    }

    table th, table td {
        font-size: 14px;
        padding: 6px;
    }

    .button-container a,
    .button-container form input[type="submit"] {
        font-size: 14px;
        padding: 8px 16px;
    }

    .btn {
        font-size: 16px;
        padding: 10px 20px;
    }
}

.search-results {
    border: 1px solid #ddd;
    background-color: #fff;
    max-height: 300px;
    overflow-y: auto;
    display: none;
    position: absolute;
    z-index: 1000;
    width: calc(100% - 22px);
    margin-top: -1px;
}

.search-results .result-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    border-bottom: 1px solid #ddd;
    align-items: center;
}

.search-results .result-item:last-child {
    border-bottom: none;
}

.search-results img {
    max-width: 100px;
    height: auto;
    margin-right: 10px;
    vertical-align: middle;
}

.search-results .result-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 0 10px;
}

.search-results .result-details strong {
    margin-bottom: 5px;
}

.search-results .result-description {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 5px;
}

.search-results .result-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.search-results .result-actions a {
    display: inline-block;
    font-size: 18px;
    padding: 15px 30px;
    text-decoration: none;
    border-radius: 3px;
    background-color: #333;
    color: #fff;
    text-align: center;
}

.search-results .result-actions a:hover {
    background-color: #77b300;
}

.search-results .result-details span {
    margin-bottom: 5px;
    display: block;
}

    </style>
</head>
<body>
    <div class="container">
        <div class="dropdown">
            <button class="dropbtn">Menu <i class="fas fa-caret-down"></i></button>
            <div class="dropdown-content">
            <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="admin.php"><i class="fa-solid fa-user"></i> Admin</a>
                <a href="chef.php"><i class="fas fa-user-friends"></i> Chef</a>
                <a href="admin_account.php"><i class="fas fa-cogs"></i> Account</a>
                <a href="menu_management.php"><i class="fas fa-utensils"></i> Menu Management</a>
                <form method="POST">
                    <input type="submit" class="logout-link" name="logout" value="Log Out">
                </form>
            </div>
        </div>
        <h1>Add New Menu</h1>
        <?php if ($message) echo "<p>$message</p>"; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">

            <label for="itemName">Item Name:</label>
            <input type="text" id="itemName" name="itemName" required>

            <label for="itemDescription">Description:</label>
            <textarea id="itemDescription" name="itemDescription"></textarea>

            <label for="itemPrice">Price:</label>
            <input type="number" id="itemPrice" name="itemPrice" step="0.01" required>

            <label for="itemCategory">Category:</label>
            <select id="itemCategory" name="itemCategory">
                <option value="Popular Menu">Popular Menu</option>
                <option value="Nasi Goreng">Nasi Goreng</option>
                <option value="Burger">Burger</option>
                <option value="Side Dish">Side Dish</option>
                <option value="Spaghetti">Spaghetti</option>
                <option value="Drinks">Drinks</option>
            </select>

            <label for="itemImage">Upload Image:</label>
            <input type="file" id="itemImage" name="itemImage">

            <button type="submit">Add Item</button>
        </form>

        <!-- Search form -->
        <form action="" method="GET" style="margin-top: 20px; position: relative;">
            <label for="search">Search Menu Items:</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
            <div class="search-results" id="search-results"></div>
        </form><br>

        <h1>Menu Items</h1>
        <?php foreach ($menu_items as $category => $items): ?>
            <h2><?php echo htmlspecialchars($category); ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['id']); ?></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><?php echo htmlspecialchars($item['price']); ?></td>
                            <td>
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Image">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="edit_menu.php?id=<?php echo $item['id']; ?>" class="btn">Edit</a>
                                <a href="delete_menu.php?id=<?php echo $item['id']; ?>" class="btn" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </div>
    <script>
        document.getElementById('search').addEventListener('input', function() {
            let searchValue = this.value;
            if (searchValue.length > 1) {
                fetch('search_menu.php?search=' + searchValue)
                    .then(response => response.json())
                    .then(data => {
                        let searchResults = document.getElementById('search-results');
                        searchResults.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(item => {
                                let div = document.createElement('div');
                                div.classList.add('result-item');
                                div.innerHTML = `
                                    <img src="${item.image ? item.image : 'placeholder.jpg'}" alt="Image" style="max-width: 150px; height: auto;">
                                    <div class="result-details">
                                        <strong>ID:</strong> <span>${item.id}</span> <br>
                                        <strong>Name:</strong> <span>${item.name}</span> <br>
                                        <strong>Description:</strong> <span class="result-description">${item.description}</span> <br>
                                        <strong>Price:</strong> <span>${item.price}</span>
                                    </div>
                                    <div class="result-actions">
                                        <a href="edit_menu.php?id=${item.id}" class="btn">Edit</a>
                                        <a href="delete_menu.php?id=${item.id}" class="btn" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                                    </div>
                                `;
                                searchResults.appendChild(div);
                            });
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.style.display = 'none';
                        }
                    });
            } else {
                document.getElementById('search-results').style.display = 'none';
            }
        });
    </script>
</body>
</html>
