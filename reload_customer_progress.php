<?php
// Include database connection
include "connection.php";

// Fetch customer progress data for approved orders
$progress_sql = "
    SELECT od.order_id, od.table_number, od.progress
    FROM order_details od
    JOIN admin_panel ap ON od.order_id = ap.id
    WHERE (od.order_status != 'Completed' AND ap.action = 'Approved')
    ORDER BY od.order_id DESC";

$progress_result = $conn->query($progress_sql);

// Create an array to hold the progress data
$progress_data = [];

if ($progress_result->num_rows > 0) {
    while ($row = $progress_result->fetch_assoc()) {
        $progress_data[] = $row;
    }
}

// Close the database connection
$conn->close();

// Return the progress data as JSON
header('Content-Type: application/json');
echo json_encode(['progress_data' => $progress_data]);

exit();
?>
