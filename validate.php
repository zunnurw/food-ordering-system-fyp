<?php
    // Include the file for database connection
    include "connection.php";

    // Start a session to store user information
    session_start();
    
    // Check if the form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get username and password from the form submission
        $username = $_POST['username'];
        $password = $_POST['password'];

        // SQL query to check if the username and password exist in the database
        $sql = "SELECT * FROM staff_details WHERE username='$username' AND password='$password'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // User found, proceed with checking status and role
            $row = $result->fetch_assoc();
            $action = $row['action'];
            $job = $row['job'];
            
            // Check if the user status is Approved
            if ($action == 'Approved') {
                // Set session variables for username and role
                $_SESSION['username'] = $username;
                $_SESSION['job'] = $job;

                // Redirect based on user role
                if ($job == 'Admin') {
                    header("Location: admin_control.php");
                } elseif ($job == 'Chef') {
                    header("Location: chef.php");
                } else {
                    // Redirect to a default page if role is not recognized
                    header("Location: default.php");
                }
                exit();
            }
            // Check if the user status is Rejected
            elseif ($action == 'Rejected') {
                // Account registration has been rejected
                echo '<script>alert("Your account registration has been rejected. Please contact the administrator for further information."); 
                window.location.href="login.php";</script>';
                exit();
            }
            // Check if the user status is Pending
            else {
                // Account has not been approved by the administrator
                echo '<script>alert("Your account has not been approved. Please contact the administrator for approval."); 
                window.location.href="login.php";</script>';
                exit();
            }
        } else {
            // User not found, Account has not been registered
            echo '<script>alert("Wrong username and password."); window.location.href="login.php";</script>';
            exit();
        }
    } else {
        // If the request method is not POST, redirect to login page
        header("Location: login.php");
        exit();
    }
?>
