<?php
session_start();
include "connection.php"; // Ensure correct path and content of your connection script

// Check if order_id is provided in the URL
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    // Redirect to a suitable error page or back to the previous page
    header("Location: index.php");
    exit();
}

$orderId = $_GET['order_id'];

// Initialize the viewed_orders session array if it doesn't exist
if (!isset($_SESSION['viewed_orders'])) {
    $_SESSION['viewed_orders'] = [];
}

// Check if the order has already been viewed
if (in_array($orderId, $_SESSION['viewed_orders'])) {
    // Redirect to a suitable error page or back to the previous page
    header("Location: index.php");
    exit();
}

// Fetch order details from the database
$sql = "SELECT * FROM admin_panel WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $orderMenu = $row['order_menu'];
    $tableNo = $row['table_no'];
    $totalPrice = $row['total_price'];
    $orderType = $row['order_type'];
    $status = $row['status'];

    // Mark the order as viewed by adding it to the session array
    $_SESSION['viewed_orders'][] = $orderId;

    // Close session write to allow session destruction later via JavaScript
    session_write_close();

    // Display the order placed message and buttons
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Placed</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Stylish font */
                background-color: #f0f0f0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .container {
                text-align: center;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                width: 100%;
                height: 100%;
            }
            .circle-success {
                display: inline-block;
                width: 180px;
                height: 180px;
                line-height: 200px; /* Match the line height with the circle's height */
                border-radius: 50%;
                background-color: #ffffff; /* Start with white */
                color: transparent; /* Hide the tick symbol initially */
                font-size: 100px; /* Adjust font size to match the circle size */
                font-weight: bold;
                text-align: center;
                margin-bottom: -10px;
                animation: pulse 2s ease-in-out forwards; /* Animation definition */
            }
            
            .circle-success .fa-check {
                font-size: 100px; /* Ensure the icon size matches the circle */
            }
            
            @keyframes pulse {
                0% {
                    background-color: #ffffff; /* White at the start */
                }
                50% {
                    background-color: #ffffff; /* Still white at the midway */
                }
                100% {
                    background-color: #28a745; /* Green at the end */
                    color: #ffffff; /* Show icon when green */
                }
            }
            .order-placed-text {
                font-size: 24px;
                margin-bottom: 20px;
                font-style: italic; /* Stylish italic font */
                color: #333; /* Darker text color */
            }
            .order-placed-text br {
                display: none; /* Hide the <br> tag */
            }
            .buttons {
                margin-top: auto; /* Push the buttons to the bottom */
                margin-bottom: 100px; /* Add some space at the bottom */
            }
            .buttons a, .buttons button {
                display: inline-block;
                margin: 10px;
                padding: 10px 20px;
                text-decoration: none;
                color: black;
                background-color: #EAE7E7; /* Grey background color */
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                border: none;
                transition: background-color 0.3s ease; /* Smooth transition */
            }

            .buttons a i, .buttons button i {
                margin-right: 8px;
            }

            .buttons a:hover, .buttons button:hover {
                background-color: #CCCCCC; /* Lighter grey on hover */
            }

        </style>
    </head>
    <body>
        <div class="container">
            <div class="circle-success">
                <i class="fas fa-check"></i> <!-- Font Awesome check icon -->
            </div>
            <div class="dismissal-info">
                <p id="dismissal-info">The screen will auto dismiss in <span id="countdown">60</span> seconds</p>
            </div>
            <div class="order-placed-text">
                Order #$orderId has been placed
            </div>
            PLEASE MAKE PAYMENT AT COUNTER
            <div class="order-details">
                <p><strong>Order Details:</strong></p>
                <p><strong>Menu:</strong> $orderMenu</p>
                <p><strong>Table Number:</strong> $tableNo</p>
                <p><strong>Total Price:</strong> RM $totalPrice</p>
                <p><strong>Order Type:</strong> $orderType</p>
                <!-- TAK BAYAR.BASUH PINGGAN :0 -->
            </div>
            <div class="buttons">
                <a href="index.php#customer-progress"><i class="fas fa-tasks"></i>View Order</a>
                <button onclick="printPDF($orderId)" class="btn btn-primary"><i class="fas fa-print"></i>Print Invoice</button>
            </div>
        </div>
        <script>
            // Function to update countdown timer and close window after 60 seconds
            function updateCountdown() {
                var seconds = 60;
                var countdownElement = document.getElementById('countdown');
                var interval = setInterval(function() {
                    seconds--;
                    countdownElement.textContent = seconds;
                    if (seconds <= 0) {
                        clearInterval(interval);
                        window.location.href = 'index.php'; // Redirect to index.php after countdown
                    }
                }, 1000); // Update countdown every second
            }

            // Call updateCountdown function when the page loads
            document.addEventListener('DOMContentLoaded', function() {
                updateCountdown();
            });

            // Function to print PDF (redirect to generate_pdf.php)
            function printPDF(orderId) {
                window.location.href = 'generate_pdf.php?order_id=' + orderId;
            }
        </script>
    </body>
    </html>
    HTML;
} else {
    // If order not found, redirect to an error page or handle it accordingly
    header("Location: index.php");
    exit();
}

$stmt->close();
?>
