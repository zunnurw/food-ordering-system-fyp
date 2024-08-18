<?php
session_start(); // Ensure the session is started

include "connection.php"; // Include your database connection or any other necessary files

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['order_type'] = $_POST['order_type'];
    if ($_SESSION['order_type'] == 'Dine-in') {
        header("Location: table.php");
        exit();
    } elseif ($_SESSION['order_type'] == 'Takeaway') {
        header("Location: checkout.php");
        exit();
    } else {
        // Handle unexpected order types if needed
        echo "Invalid order type selected.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Western House - Enter Order Type</title>
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
        .radio-group {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .radio-group input[type="radio"] {
            display: none;
        }
        .radio-group label {
            font-size: 16px;
            padding: 10px 20px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }
        .radio-group input[type="radio"]:checked + label {
            background-color: #ff6600;
            color: white;
            border-color: #ff6600;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Choose Order Type</h1>
        </div>
        <div class="order-section">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="radio-group">
                    <input type="radio" id="dine-in" name="order_type" value="Dine-in" required>
                    <label for="dine-in">Dine-In</label>
                    <input type="radio" id="takeaway" name="order_type" value="Takeaway" required>
                    <label for="takeaway">Takeaway</label>
                </div>
                <button type="button" class="back-button" onclick="window.location.href='index.php'">Back</button>
                <input type="submit" class="confirm-button" value="Confirm">
            </form>
        </div>
    </div>
</body>
</html>
