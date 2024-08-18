<?php
session_start();
include "connection.php";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle cancel button
    if (isset($_POST['cancel'])) {
        unset($_SESSION['table_number']);
        header("Location: order_type.php");
        exit();
    }

    // Ensure required session variables are set
    if (isset($_SESSION['selected_items'], $_SESSION['selected_quantities'], $_SESSION['order_type'], $_SESSION['items'])) {
        $selectedItems = $_SESSION['selected_items'];
        $selectedQuantities = $_SESSION['selected_quantities'];
        $items = $_SESSION['items'];
        $orderType = $_SESSION['order_type'];

        // Set table_number to a numeric value or 'N/A' for takeaway orders
        $tableNumber = ($orderType === 'Dine-in' && isset($_SESSION['table_number'])) ? intval($_SESSION['table_number']) : 'N/A';

        $totalPrice = 0;
        $menuNames = [];

        // Calculate total price and prepare menu names
        foreach ($selectedItems as $item) {
            if (isset($selectedQuantities[$item], $items[$item])) {
                $quantity = $selectedQuantities[$item];
                $price = $items[$item];
                $itemTotal = $quantity * $price;
                $totalPrice += $itemTotal;
                $menuNames[] = htmlspecialchars($item) . ' x ' . htmlspecialchars($quantity);
            } else {
                $menuNames[] = htmlspecialchars($item) . ' x 0';
            }
        }

        // Prepare to insert into database
        $orderMenu = implode(', ', $menuNames);
        $action = 'pending';
        $orderStatus = 'Not Completed';
        $status = 'Not Completed';

        // Start database transaction
        $conn->begin_transaction();
        try {
            // Insert into admin_panel table
            $sqlAdminPanel = "INSERT INTO admin_panel (order_menu, table_no, total_price, order_type, action, status, operation) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            $stmtAdminPanel = $conn->prepare($sqlAdminPanel);
            $stmtAdminPanel->bind_param("ssisss", $orderMenu, $tableNumber, $totalPrice, $orderType, $action, $status);
            
            if ($stmtAdminPanel->execute()) {
                $orderID = $conn->insert_id;

                // Insert into order_details table
                $sqlOrderDetails = "INSERT INTO order_details (order_id, order_item, total_price, table_number, order_status) VALUES (?, ?, ?, ?, ?)";
                $stmtOrderDetails = $conn->prepare($sqlOrderDetails);
                $stmtOrderDetails->bind_param("isdss", $orderID, $orderMenu, $totalPrice, $tableNumber, $orderStatus);

                if ($stmtOrderDetails->execute()) {
                    // Clear session variables after successful order placement
                    unset($_SESSION['selected_items'], $_SESSION['selected_quantities'], $_SESSION['table_number'], $_SESSION['order_type'], $_SESSION['items']);
                    $conn->commit();
                    header("Location: order_placed.php?order_id=" . $orderID); // Pass order_id via GET 
                    exit();
                } else {
                    throw new Exception("Error executing insert statement for order_details: " . $stmtOrderDetails->error);
                }
            } else {
                throw new Exception("Error executing insert statement for admin_panel: " . $stmtAdminPanel->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "Transaction failed: " . $e->getMessage();
            error_log("Database Transaction Error: " . $e->getMessage());
        } finally {
            $stmtAdminPanel->close();
            $stmtOrderDetails->close();
        }
    } else {
        echo "Required session variables are not set.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Page</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 90vh;
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
        .order-section h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            color: #333333;
        }
        .order-list {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        .order-list li {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .order-list li:last-child {
            border-bottom: none;
        }
        .order-list .food-name {
            width: 50%;
        }
        .order-list .quantity,
        .order-list .price {
            width: 25%;
            text-align: center;
        }
        .table-number {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            margin-bottom: 20px;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
        }
        .table-value {
            font-weight: bold;
        }
        .order-type {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .type-value {
            font-weight: bold;
        }
        .total-price {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .price-value {
            font-weight: bold;
        }
        .buttons {
            display: flex;
            justify-content: space-between;
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
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>WESTERN HOUSE</h1>
    </div>
    <div class="order-section">
        <h2>Your Order</h2>
        <form id="orderForm" method="POST" action="">
            <ul class="order-list">
            <?php
                    // Display selected items from session
                    if (isset($_SESSION['selected_items'], $_SESSION['selected_quantities'], $_SESSION['items'])) {
                        $selectedItems = $_SESSION['selected_items'];
                        $selectedQuantities = $_SESSION['selected_quantities'];
                        $items = $_SESSION['items'];
                        $totalPrice = 0; // Initialize total price

                        foreach ($selectedItems as $item) {
                            if (isset($selectedQuantities[$item], $items[$item])) {
                                $quantity = $selectedQuantities[$item];
                                $price = $items[$item];
                                echo '<li><span class="food-name">' . htmlspecialchars($item) . '</span><span class="quantity">' . htmlspecialchars($quantity) . '</span><span class="price">RM ' . number_format($price * $quantity, 2) . '</span></li>';
                                $totalPrice += $price * $quantity;
                            } else {
                                echo '<li><span class="food-name">' . htmlspecialchars($item) . '</span><span class="quantity">0</span><span class="price">RM 0.00</span></li>';
                            }
                        }
                    } else {
                        echo '<li>No items selected</li>';
                    }
                    ?>
            </ul>
            <div class="table-number">
                <span>Table Number:</span>
                <span class="table-value"><?php echo isset($_SESSION['table_number']) ? htmlspecialchars($_SESSION['table_number']) : 'N/A'; ?></span>
            </div>
            <div class="order-type">
                <span>Order Type:</span>
                <span class="type-value"><?php echo htmlspecialchars($_SESSION['order_type']); ?></span>
            </div>
            <div class="total-price">
                <span>Total Price:</span>
                <span class="price-value">RM <?php echo number_format($totalPrice, 2); ?></span>
            </div>
            <div class="buttons">
                <button type="submit" name="cancel" class="back-button">Back</button>
                <button type="button" class="confirm-button" onclick="confirmOrder()">Confirm Order</button>
            </div>
        </form>
    </div>
</div>

    <script>

        // Ensure table number is displayed correctly
        document.addEventListener('DOMContentLoaded', function() {
            var tableNumber = "<?php echo isset($_SESSION['table_number']) ? ($_SESSION['table_number']) : 'N/A'; ?>";
            document.querySelector('.table-value').textContent = tableNumber;
    });

    function confirmOrder() {
            const modal = document.createElement('div');
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            modal.style.zIndex = '1000';

            const modalContent = document.createElement('div');
            modalContent.style.backgroundColor = '#ffffff';
            modalContent.style.padding = '20px';
            modalContent.style.borderRadius = '8px';
            modalContent.style.textAlign = 'center';
            modalContent.style.width = '90%';
            modalContent.style.maxWidth = '400px';

            const message = document.createElement('p');
            message.textContent = 'Confirm Order?';
            message.style.marginBottom = '20px';

            const buttonContainer = document.createElement('div');
            buttonContainer.style.display = 'flex';
            buttonContainer.style.justifyContent = 'space-between';
            buttonContainer.style.gap = '10px';

            const cancelButton = document.createElement('button');
            cancelButton.textContent = 'Cancel';
            cancelButton.style.padding = '10px';
            cancelButton.style.backgroundColor = '#333333';
            cancelButton.style.color = '#ffffff';
            cancelButton.style.border = 'none';
            cancelButton.style.borderRadius = '5px';
            cancelButton.style.cursor = 'pointer';
            cancelButton.addEventListener('click', () => {
                document.body.removeChild(modal);
            });

            const confirmButton = document.createElement('button');
            confirmButton.textContent = 'Proceed';
            confirmButton.style.padding = '10px';
            confirmButton.style.backgroundColor = '#ff6600';
            confirmButton.style.color = '#ffffff';
            confirmButton.style.border = 'none';
            confirmButton.style.borderRadius = '5px';
            confirmButton.style.cursor = 'pointer';
            confirmButton.addEventListener('click', () => {
                document.body.removeChild(modal);
                showOrderPlacedModal();
                document.getElementById('orderForm').submit();
            });

            buttonContainer.appendChild(cancelButton);
            buttonContainer.appendChild(confirmButton);

            modalContent.appendChild(message);
            modalContent.appendChild(buttonContainer);

            modal.appendChild(modalContent);

            document.body.appendChild(modal);
        }

        function showOrderPlacedModal() {
            const orderPlacedModal = document.createElement('div');
            orderPlacedModal.style.position = 'fixed';
            orderPlacedModal.style.top = '0';
            orderPlacedModal.style.left = '0';
            orderPlacedModal.style.width = '100%';
            orderPlacedModal.style.height = '100%';
            orderPlacedModal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            orderPlacedModal.style.display = 'flex';
            orderPlacedModal.style.justifyContent = 'center';
            orderPlacedModal.style.alignItems = 'center';
            orderPlacedModal.style.zIndex = '1000';

            const orderPlacedModalContent = document.createElement('div');
            orderPlacedModalContent.style.backgroundColor = '#ffffff';
            orderPlacedModalContent.style.padding = '20px';
            orderPlacedModalContent.style.borderRadius = '8px';
            orderPlacedModalContent.style.textAlign = 'center';
            orderPlacedModalContent.style.width = '90%';
            orderPlacedModalContent.style.maxWidth = '400px';

            const orderPlacedMessage = document.createElement('p');
            orderPlacedMessage.textContent = 'Order Placed Successfully!';
            orderPlacedMessage.style.marginBottom = '20px';

            orderPlacedModalContent.appendChild(orderPlacedMessage);

            orderPlacedModal.appendChild(orderPlacedModalContent);

            document.body.appendChild(orderPlacedModal);

            setTimeout(() => {
                document.body.removeChild(orderPlacedModal);
            }, 10000);
        }
        
        </script>

</body>
</html>
