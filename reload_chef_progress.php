<?php
// Include the file for database connection
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from POST request
    $orderId = $_POST['order_id'];
    $newProgress = $_POST['progress'];

    // Retrieve current progress from database
    $getCurrentProgressQuery = "SELECT progress FROM order_details WHERE order_id='$orderId'";
    $result = $conn->query($getCurrentProgressQuery);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentProgress = $row['progress'];

        // Check if the requested progression is valid
        switch ($newProgress) {
            case 'Preparing':
                if ($currentProgress == 'Queued') {
                    updateProgress($conn, $orderId, $newProgress);
                } else {
                    echo "Invalid progression: Cannot progress from '$currentProgress' to '$newProgress'.";
                }
                break;
            case 'Ready':
                if ($currentProgress == 'Preparing') {
                    updateProgress($conn, $orderId, $newProgress);
                } else {
                    echo "Invalid progression: Cannot progress from '$currentProgress' to '$newProgress'.";
                }
                break;
            default:
                echo "Invalid progression request.";
                break;
        }
    } else {
        echo "Order not found.";
    }
}

// Function to update progress in the database
function updateProgress($conn, $orderId, $newProgress) {
    $updateQuery = "UPDATE order_details SET progress='$newProgress' WHERE order_id='$orderId'";
    if ($conn->query($updateQuery) === TRUE) {
        echo "Progress updated successfully";
    } else {
        echo "Error updating progress: " . $conn->error;
    }
}

$orderQuery = "
    SELECT od.order_id, od.order_item, od.total_price, od.table_number, od.order_status, od.progress
    FROM order_details od
    JOIN admin_panel ap ON od.order_id = ap.id
    WHERE od.order_status != 'Completed'
    AND ap.action = 'Approved'
    ORDER BY od.order_id DESC;
";

$orderResult = $conn->query($orderQuery);

// Check if there are rows returned
if ($orderResult->num_rows > 0) {
    // Output the table rows
    while ($row = $orderResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>#" . $row['order_id'] . "</td>";
        echo "<td>" . $row['order_item'] . "</td>";
        echo "<td>RM " . number_format($row['total_price'], 2) . "</td>";
        echo "<td>";
        if ($row['table_number'] === 'N/A') {
            echo 'Takeaway';
        } else {
            echo 'Table ' . $row['table_number'];
        }
        echo "</td>";
        echo "<td>" . $row['order_status'] . "</td>";
        echo "<td>";
        if ($row['progress'] == 'Ready') {
            echo "<button class='progress-button ready-state' data-order-id='" . $row['order_id'] . "' data-current-state='" . $row['progress'] . "'>";
        } else {
            echo "<button class='progress-button' data-order-id='" . $row['order_id'] . "' data-current-state='" . $row['progress'] . "'>";
        }
        echo $row['progress'];
        echo "</button>";
        echo "</td>";
        echo "<td>";
        echo "<form method='POST' style='display:inline;'>";
        echo "<input type='hidden' name='order_id' value='" . $row['order_id'] . "'>";
        echo "<button type='submit' name='delete_order' class='delete-button'>Cancel Order</button>";
        echo "</form>";
        echo "</td>";
        echo "<td>";
        echo "<form method='POST' style='display:inline;'>";
        echo "<input type='hidden' name='order_id' value='" . $row['order_id'] . "'>";
        echo "<button type='submit' name='complete_order' class='complete-button'>Complete Order</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
}
?>

