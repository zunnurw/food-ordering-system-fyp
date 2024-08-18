<?php
// Include the file for database connection
include "connection.php";

// Set the default timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Start the session
session_start();
// Function to show JavaScript alerts using SweetAlert2
function showAlert($message, $type = 'success') {
    echo "<script>
            Swal.fire({
                icon: '{$type}',
                title: '{$message}',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                reloadTable(); // Reload the table after 1.5 seconds
            });
          </script>";
}

if (!isset($_SESSION['username']) || $_SESSION['job'] == 'Chef') {
    // Redirect to the login page or another page if not logged in as admin
    header("Location: chef.php");
    exit();
}

// Check if the logout button is clicked
if (isset($_POST['logout'])) {
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to the login page after logout
    header("Location: login.php");
    exit();
}

// Check if remove button is clicked
if (isset($_GET['remove_id'])) {
    $id = $_GET['remove_id'];

    // SQL to delete admin control entry based on ID
    $sql = "DELETE FROM admin_panel WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redirect to refresh the page after removal
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error removing entry: " . $stmt->error;
    }
}

// Check if the approve or reject action is triggered via AJAX
if (isset($_POST['action'], $_POST['id'])) {
    $id = $_POST['id'];
    $action = $_POST['action'];

    try {
        if ($action === 'approve') {
            // Update SQL query to approve
            $sql = "UPDATE admin_panel SET action='Approved' WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Entry approved successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update entry']);
            }
        } elseif ($action === 'reject') {
            // SQL to delete admin control entry based on ID
            $sql = "DELETE FROM admin_panel WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Entry deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete entry']);
            }
        }
    } catch (Exception $e) {
        // Output JSON response for exception
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }
    exit(); // Exit after AJAX processing
}

// Check if the complete button is clicked
if (isset($_POST['complete_order'])) {
    $orderId = $_POST['order_id'];

    // Update the order status to 'completed' in the database
    $completeOrderQuery = "UPDATE order_details SET order_status='Completed' WHERE order_id=?";
    $stmt = $conn->prepare($completeOrderQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    
    $completeOrderQueryV2 = "UPDATE admin_panel SET status='Completed' WHERE id=?";
    $stmtV2 = $conn->prepare($completeOrderQueryV2);
    $stmtV2->bind_param("i", $orderId);
    $stmtV2->execute();

    if ($stmt->execute()) {
        // Success message
        showAlert('Order completed successfully');
    } else {
        // Error message
        showAlert('Failed to complete order', 'error');
    }
}

// Check if the delete button is clicked
if (isset($_POST['delete_order'])) {
    $orderId = $_POST['order_id'];

    // Delete from admin_panel table
    $deleteOrderQuery = "DELETE FROM admin_panel WHERE id=?";
    $stmt = $conn->prepare($deleteOrderQuery);
    $stmt->bind_param("i", $orderId);

    if ($stmt->execute()) {
        // Success message
        showAlert('Order cancelled successfully');
        
        // Optionally delete from order_details table or perform other actions
        $deleteOrderDetailsQuery = "DELETE FROM order_details WHERE order_id=?";
        $stmt_details = $conn->prepare($deleteOrderDetailsQuery);
        $stmt_details->bind_param("i", $orderId);
        $stmt_details->execute(); // Execute without checking the result (optional)

    } else {
        // Error message
        showAlert('Failed to cancel order', 'error');
    }
}

// Select orders from admin_panel table that are pending or completed
$orderQuery = "
   SELECT ap.id, ap.order_menu, ap.table_no, ap.total_price, ap.order_type, ap.action, ap.status, ap.operation
   FROM admin_panel ap
   WHERE ap.action = 'Pending' OR ap.status = 'Completed' OR ap.action = 'Approved'
   ORDER BY ap.id DESC
";

$orderResult = $conn->query($orderQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page - Admin Control</title>
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Function to reload the table content via AJAX
        function reloadTable() {
            $.ajax({
                url: 'reload_table_admin.php', // URL to reload data from server
                type: 'GET', // Assuming you want to reload using GET method
                success: function(response) {
                    // Replace table content with reloaded data
                    $('#order-table tbody').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error reloading table:', error);
                }
            });
        }

        // Call reloadTable() periodically every 3 seconds
        setInterval(reloadTable, 3000);
    </script>
    <style>
/* Style for the body */
body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    margin: 0;
    display: flex;
    align-items: center;
    height: 100vh;
    flex-direction: column;
}

/* Style for the container */
.container {
    width: 90%;
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    max-height: 10000%; /* Use a large percentage */
    padding: 20px;
}

/* Style for the header */
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

/* Style for tables */
table {
    width: 100%;
    border-collapse: collapse;
    background-color: #ffffff;
}

th, td {
    border: 1px solid #dddddd;
    padding: 10px;
    text-align: center;
}

th {
    background-color: #007bff;
    color: #ffffff;
    font-weight: bold;
}

tr:nth-child(even) {
    background-color: #f2f2f2;
}

button {
    padding: 5px 10px;
    margin-right: 5px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

/* Style for action buttons */
.action-buttons {
    text-align: center;
}

.action-buttons button,
.action-buttons input[type="submit"] {
    display: inline-block;
    padding: 10px 20px;
    margin: 5px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s;
    color: white;
    text-align: center;
}


.action-buttons .approve-button {
    background-color: #28a745; /* Green for approval */
}

.action-buttons .reject-button {
    background-color: #dc3545; /* Red for rejection */
}

.action-buttons button:hover,
.action-buttons input[type="submit"]:hover {
    opacity: 0.9;
}

/* Additional styles for other buttons */

.complete-button {
    background-color: #4CAF50; /* Green for completion */
}

.delete-button {
    background-color: #f44336; /* Red for deletion */
}

.complete-button:hover,
.delete-button:hover {
    opacity: 0.9;
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
    margin: 0 10px; /* Add margin to separate the buttons */
}

.logout-link {
    display: block;
    font-size: 18px;
    color: white;
    background-color: #333;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    text-align: center;
    margin: 20px auto; /* Center the link */
    width: fit-content;
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

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
    z-index: 100; /* Ensure a high z-index */
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

.dropbtn {
    padding: 10px 20px;
    font-size: 16px;
    margin-right: 5px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    background-color: #007bff; /* Match dashboard button background */
    color: white;
}

.dropdown:hover .dropbtn {
    background-color: grey;
}

/* General button styling */
.action-buttons button, .status-buttons button {
    margin: 0 5px;
    padding: 8px 12px;
    width: 100px; /* Fixed width */
    height: 40px; /* Fixed height */
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
    text-align: center; /* Center text */
    line-height: 24px; /* Center text vertically */
}

/* Approve button styling */
.approve-button {
    background-color: #5cb85c;
    color: white;
}

.approve-button:hover {
    background-color: #4cae4c;
}

/* Complete button styling */
.complete-button {
    background-color: #5bc0de;
    color: white;
}

.complete-button:hover {
    background-color: #31b0d5;
}

/* Delete button styling */
.delete-button {
    background-color: #d9534f;
    color: white;
}

.delete-button:hover {
    background-color: #c9302c;
}

/* Status text styling */
.status-approved {
    background-color: #dff0d8;
    color: #3c763d;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
}

.status-completed {
    background-color: #d9edf7;
    color: #31708f;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
}

/* Button group styling */
.button-group {
    display: flex;
    gap: 10px; /* Adjust the spacing between buttons */
}

/* Table column widths */
table td {
    padding: 8px;
    border: 1px solid #ddd;
}

/* Set specific column widths */
table td:nth-child(4) { /* 4th column - Total Price */
    width: 150px; /* Adjust this value as needed */
}

/* Responsive design for mobile */
@media (max-width: 768px) {
    .container {
        width: 100%;
        margin: 10px;
    }

    table {
        width: 100%;
        max-width: 100%;
        display: block;
        overflow-x: auto;
    }

    th, td {
        padding: 10px;
        text-align: center;
    }

    th {
        background-color: #007bff;
        color: #ffffff;
        font-weight: bold;
        white-space: nowrap;
    }

    td {
        border: 1px solid #dddddd;
    }

    .complete-button,
    .delete-button {
        padding: 8px 12px; /* Adjusted padding */
        width: 100px; /* Fixed width */
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s;
        color: white;
        text-align: center;
        display: inline-block; /* Ensure buttons are inline */
    }

    .complete-button {
        background-color: #4CAF50; /* Green */
    }

    .delete-button {
        background-color: #f44336; /* Red */
    }

    .complete-button:hover,
    .delete-button:hover {
        opacity: 0.9;
    }

    /* Make buttons appear on the same line with spacing */
    .button-group {
        display: flex;
        justify-content: center;
        gap: 10px; /* Space between buttons */
    }

    /* Set specific column widths */
    table td:nth-child(4) { /* 4th column - Total Price */
        width: 150px; /* Adjust this value as needed */
    }
}

    </style>
</head>
<body>
<div class="container">
        <div class="header">
            <?php if (isset($_SESSION['username'])) : ?>
                <h1>Hello <?php echo $_SESSION['username']; ?>!</h1>
            <?php endif; ?>
            <h2>(ADMIN PAGE - ORDER)</h2>
        </div>

        <!-- Dropdown for Menu, Account, and Log Out -->
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
        
        <table id="order-table">
        <thead>
        <tr>
            <th>Order ID</th>
            <th>Order Menu</th>
            <th>Table No.</th>
            <th>Total Price</th>
            <th>Order Type</th>
            <th>Action</th>
            <th>Status</th>
            <th>Operation Time</th>
        </tr>
    </thead>
    <tbody>
    <?php
        if ($orderResult->num_rows > 0) {
            while ($row = $orderResult->fetch_assoc()) {
                // Format the date and time in 12-hour format with AM/PM
                $formattedDate = date('m/d/Y h:i A', strtotime($row['operation']));
                
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


    </tbody>
</table>
    </div>

    <script>
    // Function to approve an order via AJAX
    function approveOrder(id) {
        $.ajax({
            url: 'admin.php',
            type: 'post',
            data: { action: 'approve', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: response.message,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        reloadTable(); // Reload the table after approval
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: response.message
                    });
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong!'
                });
            }
        });
    }


    // Function to delete an order via AJAX
    function deleteOrder(order_id) {
        Swal.fire({
            title: 'Delete Order?',
            text: 'Are you sure you want to delete this order?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'admin.php',
                    type: 'post',
                    data: { delete_order: true, order_id: order_id },
                    dataType: 'html',
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order deleted successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            reloadTable(); // Reload the table after deletion
                        });
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Failed to reject order'
                        });
                    }
                });
            }
        });
    }

// Function to complete an order via AJAX
function completeOrder(order_id) {
    $.ajax({
        url: 'admin.php',
        type: 'post',
        data: { complete_order: true, order_id: order_id },
        dataType: 'html',
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Order completed successfully',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                reloadTable(); // Reload the table after completion
            });
        },
        error: function(xhr, ajaxOptions, thrownError) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Failed to complete order'
            });
        }
    });
}

</script>

</body>
</html>