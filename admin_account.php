<?php
// Include the file for database connection
include "connection.php";

// Start the session
session_start();
if (!isset($_SESSION['username']) || $_SESSION['job'] == 'Chef') {
    header("Location: chef.php");
    exit();
}

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_staff':
            $name = $_POST['staff_name'];
            $username = $_POST['username'];
            $password = $_POST['password'];
            $job = $_POST['job'];

            // Check if username already exists
            $checkSql = "SELECT COUNT(*) FROM staff_details WHERE username=?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                echo json_encode(['message' => 'Username already exists.']);
                exit();
            }

            // SQL query to insert new staff details
            $sql = "INSERT INTO staff_details (staff_name, username, password, job) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $username, $password, $job);

            if ($stmt->execute()) {
                echo json_encode(['message' => 'New staff member added successfully']);
            } else {
                echo json_encode(['message' => 'Error adding new staff member: ' . $conn->error]);
            }
            $stmt->close();
            break;

        case 'save':
            $id = $_POST['id'];
            $name = $_POST['name'];
            $username = $_POST['username'];
            $password = $_POST['password'];
            $job = $_POST['job'];

            // Check if the new username already exists for other staff members
            $checkSql = "SELECT COUNT(*) FROM staff_details WHERE username=? AND id != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("si", $username, $id);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                echo json_encode(['message' => 'Username already exists.']);
                exit();
            }

            // SQL query to update staff details
            $sql = "UPDATE staff_details SET staff_name=?, username=?, password=?, job=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $name, $username, $password, $job, $id);

            if ($stmt->execute()) {
                echo json_encode(['message' => 'Staff member updated successfully']);
            } else {
                echo json_encode(['message' => 'Error updating staff member: ' . $conn->error]);
            }
            $stmt->close();
            break;

        case 'remove':
            $id = $_POST['id']; // Fetch ID from POST
    
            // SQL to delete staff details based on ID
            $sql = "DELETE FROM staff_details WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
    
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Staff member removed successfully']);
            } else {
                echo json_encode(['message' => 'Error removing staff member: ' . $stmt->error]);
            }
            $stmt->close();
            break;
    
        // Default case
        default:
            echo json_encode(['message' => 'Invalid action']);
            break;
    }
    exit();
}

// Check if logout button is clicked
if (isset($_POST['logout'])) {
    $_SESSION = array();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page - Staff Management</title>
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
$(document).ready(function() {
    // Function to show notification banner
    function showNotification(message, type) {
        var banner = $('#notification-banner');
        banner.text(message).removeClass('error success').addClass(type).addClass('show');
        setTimeout(function() {
            banner.removeClass('show').addClass('hide');
        }, 1000); // Adjust the time as needed
    }

    // Handle Add Staff
    $('#add-staff-form').submit(function(event) {
        event.preventDefault(); // Prevent the form from submitting the traditional way
        
        $.ajax({
            type: 'POST',
            url: 'admin_account.php',
            data: $(this).serialize() + '&action=add_staff',
            dataType: 'json',
            success: function(response) {
                if (response.message.includes('Username already exists')) {
                    showNotification(response.message, 'error');
                } else {
                    showNotification(response.message, 'success');
                    setTimeout(function() {
                        location.reload(); // Reload the page after hiding the notification
                    }, 1000); // Align with the banner's visibility duration
                }
            },
            error: function() {
                showNotification('Error adding staff member.', 'error');
            }
        });
    });

    // Handle Save Staff
    $(document).on('submit', '.edit-form', function(event) {
        event.preventDefault();
        
        $.ajax({
            type: 'POST',
            url: 'admin_account.php',
            data: $(this).serialize() + '&action=save',
            dataType: 'json',
            success: function(response) {
                if (response.message.includes('Username already exists')) {
                    showNotification(response.message, 'error');
                } else {
                    showNotification(response.message, 'success');
                    setTimeout(function() {
                        location.reload(); // Reload the page after hiding the notification
                    }, 1000); // Align with the banner's visibility duration
                }
            },
            error: function() {
                showNotification('Error saving staff member.', 'error');
            }
        });
    });

    // Handle Remove Staff
    $(document).on('click', '.remove-button', function(event) {
        event.preventDefault();
        
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, remove it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: 'admin_account.php',
                    data: { action: 'remove', id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.message.includes('Error')) {
                            Swal.fire('Error!', response.message, 'error');
                        } else {
                            Swal.fire('Removed!', response.message, 'success');
                            setTimeout(function() {
                                location.reload(); // Reload the page after hiding the notification
                            }, 1000); // Align with the banner's visibility duration
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Error removing staff member.', 'error');
                    }
                });
            }
        });
    });

    // Close button functionality
    $(document).on('click', '#notification-close', function() {
        $('#notification-banner').removeClass('show').addClass('hide');
    });
});

    </script>

<style>
/* Style for the body */
body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    margin: 0;
    display: flex;
    justify-content: center;
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
    max-height: 100vh;
    padding: 20px;
}
/* Notification banner styles */
.notification-banner {
    display: none; /* Hidden by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    padding: 10px;
    background-color: #28a745; /* Green background for success */
    color: #ffffff;
    text-align: center;
    font-size: 16px;
    font-weight: bold;
    z-index: 9999;
    transition: opacity 0.5s ease, transform 0.5s ease;
}

.notification-banner.error {
    background-color: #dc3545; /* Red background for errors */
}

.notification-banner.success {
    background-color: #28a745; /* Green background for success */
}

.notification-banner.show {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

.notification-banner.hide {
    opacity: 0;
    transform: translateY(-100%);
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
    word-wrap: break-word; /* Prevents long text from overflowing */
}

th {
    background-color: #007bff;
    color: #ffffff;
    font-weight: bold;
}

tr:nth-child(even) {
    background-color: #f2f2f2;
}

/* Style for input fields and select dropdowns */
input[type="text"], input[type="password"], select {
    width: 100%;
    padding: 8px;
    margin: 4px 0;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
}

input[type="text"]:focus,  
select:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    outline: none;
}

button {
    padding: 5px 10px;
    margin-right: 5px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

/* Style for buttons */
.action-buttons a {
    display: inline-block;
    margin: 0 5px;
    padding: 5px 10px;
    color: #fff;
    border-radius: 3px;
    text-decoration: none;
    font-size: 14px;
}

.approve-button {
    background-color: #28a745;
}

.reject-button {
    background-color: #dc3545;
}

.edit-button {
    background-color: #ffc107;
    color: #fff;
}

.remove-button {
    background-color: #6c757d;
    color: #fff;
}

.action-buttons .fa {
    margin-right: 5px;
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
    margin: 0 10px; 
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

/* Dropdown styles */
.dropdown {
    position: relative;
    display: inline-block;
    margin-right: 10px;
}

.dropbtn {
    padding: 10px 20px;
    font-size: 16px;
    margin-right: 5px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    background-color: #007bff; /* Button color */
    color: white;
}

.dropdown:hover .dropbtn {
    background-color: grey;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
    z-index: 1;
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

/* Add Staff Button */
.add-button {
    background-color: #28a745;
    color: #ffffff;
    border: none;
    padding: 8px 100px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    margin: 4px 2px;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.add-button:hover {
    background-color: #218838;
}

/* Save Button */
.save-button {
    background-color: #007bff;
    color: #ffffff;
    border: none;
    padding: 8px 5px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    margin: 4px 2px;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s, box-shadow 0.3s;
}

.save-button:hover {
    background-color: #0056b3;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
}

/* Remove Button */
.remove-button {
    background-color: #dc3545;
    color: #ffffff;
    border: none;
    padding: 8px 5px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    margin: 4px 2px;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s, box-shadow 0.3s;
}

.remove-button:hover {
    background-color: #c82333;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
}

/* Responsive design for mobile */
@media (max-width: 768px) {
    .container {
        width: 100%;
        margin: 10px;
    }

    .header h1 {
        font-size: 18px;
    }

    .logout-button input[type="submit"],
    .admin-link {
        padding: 12px; /* Increase padding to make the button larger */
        font-size: 20px; /* Adjust font size as needed */
    }

    table {
        width: 100%; /* Ensure table spans full width of container */
        max-width: 100%;
        display: block;
        overflow-x: auto; /* Allows horizontal scrolling on small screens */
    }

    th, td {
        padding: 15px; /* Adjust padding for better visibility */
        text-align: center; /* Center-align content for better readability */
    }

    th {
        white-space: nowrap; /* Prevent line breaks in table headers */
    }

    /* Adjust width for table columns */
    th:nth-child(1), td:nth-child(1) { width: 35%; } /* Increased from 25% to 35% */
    th:nth-child(2), td:nth-child(2) { width: 30%; } /* Maintained at 30% */
    th:nth-child(3), td:nth-child(3) { width: 25%; } /* Increased from 20% to 25% */
    th:nth-child(4), td:nth-child(4) { width: 20%; } /* Decreased from 25% to 20% */

    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 5px;
    }
}

</style>
</head>
<body>
<div id="notification-banner" class="notification-banner">
    <span id="notification-message"></span>
    <button id="notification-close">×</button>
</div>

    <div class="container">
        <div class="header">
            <?php if (isset($_SESSION['staff_name'])) : ?>
                <h1>Hello <?php echo $_SESSION['staff_name']; ?>!</h1>
            <?php endif; ?>
            <h2>(ADMIN PAGE - ACCOUNT)</h2>
        </div>
        
        <!-- Dropdown for Chef, Account, and Log Out -->
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
        
        <table>
            <tr>
                <th>ID</th>
                <th>Staff Name</th>
                <th>Username</th>
                <th>Password</th>
                <th>Job</th>
                <th>Action</th>
            </tr>
            <tr>
            <form id="add-staff-form">
    <td>#</td>
    <td><input type="text" name="staff_name" placeholder="Staff Name" required></td>
    <td><input type="text" name="username" placeholder="Username" required></td>
    <td><input type="text" name="password" placeholder="Password" required></td>
    <td>
        <select name="job" required>
            <option value="">Select Job</option>
            <option value="Admin">Admin</option>
            <option value="Chef">Chef</option>
        </select>
    </td>
    <td><button type="submit" class="add-button"><i class="fas fa-plus"></i> Add</button></td>
</form>

<!-- Existing code for displaying staff members -->
<?php
$sql = "SELECT * FROM staff_details ORDER BY id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<form class='edit-form'>";
        echo "<td>" . $row["id"] . "</td>";
        echo "<td><input type='text' name='name' value='" . $row["staff_name"] . "' required></td>";
        echo "<td><input type='text' name='username' value='" . $row["username"] . "' required></td>";
        echo "<td><input type='text' name='password' value='" . $row["password"] . "' required></td>";
        echo "<td>
                <select name='job' required>
                    <option value='Admin'" . ($row["job"] == "Admin" ? " selected" : "") . ">Admin</option>
                    <option value='Chef'" . ($row["job"] == "Chef" ? " selected" : "") . ">Chef</option>
                </select>
              </td>";
        echo "<input type='hidden' name='id' value='" . $row["id"] . "'>";
        echo "<td><button type='submit' class='save-button'><i class='fas fa-save'></i> Save</button>";
        echo "<a href='#' class='remove-button' data-id='" . $row["id"] . "'><i class='fas fa-trash'></i> Remove</a></td>";
        echo "</form>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>No staff members found.</td></tr>";
}
$conn->close();
?>


        </table>
        
    </div>

</body>
</html>

