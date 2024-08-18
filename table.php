<?php
session_start();
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $table_number = htmlspecialchars(trim($_POST['table_number']));
    if (!empty($table_number) && is_numeric($table_number) && $table_number >= 1 && $table_number <= 25) {
        $_SESSION['table_number'] = $table_number;
        header("Location: checkout.php");
        exit();
    } else {
        echo "<script>showToast('Please enter a valid table number between 1 and 24.');</script>";
    }
}

// Ensure session variables are set and not empty
if (!isset($_SESSION['selected_items']) || !isset($_SESSION['selected_quantities']) || !isset($_SESSION['items'])) {
    // Redirect to menu.php or handle the case where data is not set
    header("Location: menu.php");
    exit();
}

// Retrieve session data
$selectedItems = $_SESSION['selected_items'];
$selectedQuantities = $_SESSION['selected_quantities'];

$items = $_SESSION['items'];

// Optionally, you can also retrieve other session data like $items if needed

// Display table.php content with the stored data
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Western House - Enter Table Number</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 95vh;
        }
        .container {
            width: 400px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #000000;
            color: #ff6600;
            text-align: center;
            padding: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .order-section {
            padding: 20px;
        }
        input[type="text"] {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 93%;
            text-align: center;
        }
        .back-button,
        .confirm-button {
            width: 48%;
            padding: 10px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .back-button {
            background-color: #333333;
            color: #ffffff;
        }
        .confirm-button {
            background-color: #ff6600;
            color: #ffffff;
        }
        @media (max-width: 600px) {
            .container {
                width: 100%;
                box-sizing: border-box;
                margin: 0 10px;
            }
        }
        /* Toast styling */
        #toast {
            visibility: hidden;
            max-width: 50%;
            margin: auto;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
            transform: translateX(-50%);
        }

        #toast.show {
            visibility: visible;
            animation: fadeInOut 3s;
        }

        @keyframes fadeInOut {
            0% {bottom: 0; opacity: 0;}
            30% {bottom: 30px; opacity: 1;}
            70% {bottom: 30px; opacity: 1;}
            100% {bottom: 0; opacity: 0;}
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TABLE NUMBER</h1>
        </div>
        <div class="order-section">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <input type="text" name="table_number" placeholder="Enter your table number" pattern="\d+" title="Please enter digits only" required min="1" max="25" oninput="this.setCustomValidity(''); if (this.validity.rangeOverflow || this.validity.rangeUnderflow) this.setCustomValidity('Please enter a table number between 1 and 25.');">
                <button type="button" class="back-button" onclick="window.history.back()">BACK</button>
                <input type="submit" class="confirm-button" value="Confirm">
            </form>
        </div>
    </div>

    <!-- Toast notification container -->
    <div id="toast"></div>

    <script>
        function showToast(message) {
            var toast = document.getElementById("toast");
            toast.innerText = message;
            toast.className = "show";
            setTimeout(function() {
                toast.className = toast.className.replace("show", "");
            }, 3000);
        }

        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && (!empty($table_number) && (!is_numeric($table_number) || $table_number < 1 || $table_number > 25))): ?>
            showToast('Please enter a valid table number between 1 and 25.');
        <?php endif; ?>
    </script>
</body>
</html>
