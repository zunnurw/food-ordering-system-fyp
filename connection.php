<?php
    // Database Connection
    $conn = new mysqli(getenv("DB_HOST"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"), getenv("DB_NAME"));

    // Check if the connection was successful
    if ($conn->connect_error) {
        // Display an error message if the database connection failed to connect
        die("Connection failed: " . $conn->connect_error);
    }

    // Set the timezone for the current session
    if (!$conn->query("SET time_zone = '+08:00'")) {
        die("Failed to set time zone: " . $conn->error);
    }

    // You can now proceed with other database operations
?>
