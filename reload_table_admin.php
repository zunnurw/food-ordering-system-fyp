<?php
// Include your database connection file
include "connection.php";

// Set the default timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Query to select updated data from admin_panel table
$orderQuery = "
    SELECT ap.id, ap.order_menu, ap.table_no, ap.total_price, ap.order_type, ap.action, ap.status, ap.operation
    FROM admin_panel ap
    WHERE ap.action = 'Pending' OR ap.status = 'Completed' OR ap.action = 'Approved'
    ORDER BY ap.id DESC
";

$orderResult = $conn->query($orderQuery);

// Check if there are rows returned
if ($orderResult->num_rows > 0) {
    // Output the table rows
    while ($row = $orderResult->fetch_assoc()) {
        // Format date and time
        $formattedDate = date('d/m/Y h:i A', strtotime($row['operation']));

        echo "<tr>";
        echo "<td>#" . $row['id'] . "</td>";
        echo "<td>" . $row['order_menu'] . "</td>";
        echo "<td>" . $row['table_no'] . "</td>";
        echo "<td>RM " . number_format($row['total_price'], 2) . "</td>"; // Format total_price with RM and two decimal places
        echo "<td>" . $row['order_type'] . "</td>";
        echo "<td class='action-buttons'>";
        if ($row['action'] == 'Pending') {
            echo "<button class='approve-button' onclick='approveOrder(" . $row['id'] . ")'>Approve</button>";
        } elseif ($row['action'] == 'Approved') {
            echo "<span class='status-approved'>Approved</span>";
        }
        echo "</td>";
        echo "<td class='status-buttons'>";
        if ($row['status'] == 'Completed') {
            echo "<span class='status-completed'>Completed</span>";
        } else {
            echo "<div class='button-group'>";
            echo "<button class='complete-button' onclick='completeOrder(" . $row['id'] . ")'>Complete</button>";
            echo "<button class='delete-button' onclick='deleteOrder(" . $row['id'] . ")'>Delete</button>";
            echo "</div>";
        }
        echo "</td>";
        echo "<td>" . $formattedDate . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='9'>No orders found.</td></tr>";
}
?>
