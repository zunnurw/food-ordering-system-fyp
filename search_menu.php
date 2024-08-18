<?php
include "connection.php";

$search_term = isset($_GET['search']) ? $_GET['search'] : '';

$response = [];

if ($search_term) {
    $search_term = '%' . $search_term . '%';
    $sql = "SELECT id, name, description, price, image FROM menu_items WHERE name LIKE ? ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }

    $stmt->close();
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>
