<?php
// Start session at the very beginning
session_start();
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include "connection.php";

    $username = $_POST["username"];
    $password = $_POST["password"];

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM staff_details WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['username'] = $username;
        $_SESSION['job'] = $row['job']; // Store the 'job' in session

        // Redirect based on job role
        switch ($_SESSION['job']) {
            case 'Admin':
                header("Location: dashboard.php");
                exit();
            case 'Chef':
                header("Location: chef.php");
                exit();
            default:
                // Handle other roles or unexpected cases
                $errorMessage = "Unknown job role: " . $_SESSION['job'];
        }
    } else {
        $errorMessage = "Invalid username or password. Please try again.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Include Font Awesome for the eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('login_pic.jpg'); 
            background-size: cover;
            background-color: #f0f0f0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 500px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 30px;
            box-sizing: border-box;
        }

/* Header section */
.header {
    background-color: #000000;
    color: #ff6600;
    text-align: center;
    padding: 20px;
    margin-left: -30px; /* Compensate for container padding */
    margin-right: -30px; /* Compensate for container padding */
    margin-top: -30px; /* Adjust margin to extend to the top */
    border-top-left-radius: 0; /* Remove border radius for top-left corner */
    border-top-right-radius: 0; /* Remove border radius for top-right corner */
}

.header h1 {
    margin: 0;
    font-size: 24px;
}




        .form-section {
            padding: 20px;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px; 
            text-align: left;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px; 
            margin: 10px 0; 
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px; 
        }

        .password-container {
            position: relative;
            width: 100%;
        }

        .password-container .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #888;
        }

        input[type="submit"] {
            width: 100%;
            padding: 15px;
            margin: 20px 0;
            background-color: #ff6600;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        input[type="submit"]:hover {
            background-color: #e65c00;
        }
        a {
            color: #4CAF50;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
        header {
        font-family: 'Unna', serif;
        position: fixed;
        background-color: black;
        color: orange;
        text-align: center;
        padding: 5px 0;
        width: 100%;
        top: 0;
        border-radius: 0 0 15px 15px;
        z-index: 1000; /* Ensure it's above other content */
    }

    header h2 {
        margin: 0;
        margin-left: 45px;
        cursor: pointer;
    }
    .home-button {
        padding: 5px 16px;
        background-color: black;
        margin-left: 45px;
        color: white;
        text-decoration: none;
    }

    .home-button:hover {
        background-color: grey;
    }

    @media (max-width: 600px) {
    .container {
        padding: 20px;
    }

    .header h1 {
        font-size: 20px;
    }

    th, td {
        padding: 10px;
    }

    input[type="text"], input[type="password"] {
        padding: 12px;
        font-size: 16px;
        width: 100%;
        margin-left: 0;
        margin-right: 0;
    }

    input[type="submit"] {
        padding: 15px;
        font-size: 16px;
        width: 100%;
        margin-left: 0;
        margin-right: 0;
    }

    /* Adjust styles for header and its children */
    header {
        font-family: 'Unna', serif;
        position: fixed;
        background-color: black;
        color: orange;
        text-align: center;
        padding: 5px 0;
        width: 100%;
        top: 0;
        border-radius: 0 0 15px 15px;
        z-index: 1000; /* Ensure it's above other content */
    }

    header h2 {
        margin: 0;
        margin-left: 15px; /* Adjust margin for smaller screens */
        cursor: pointer;
        font-size: 22px; /* Adjust font size for smaller screens */
    }

    .home-button {
        padding: 5px 10px; /* Adjust padding for smaller screens */
        margin-left: 15px; /* Adjust margin for smaller screens */
        font-size: 18px; /* Adjust font size for smaller screens */
    }

    .home-button i {
        font-size: 18px; /* Adjust icon size for smaller screens */
    }

    .home-button:hover {
        background-color: grey;
    }
}

        
    </style>
    <script>
        // JavaScript to handle clicking on the h2 element with id "top"
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('top').addEventListener('click', function() {
                window.location.href = 'index.php'; // Navigate to index.php
            });
        });
    </script>
<body>
</head>
    <header>
        <h2 id="top">WESTERN HOUSE 
        <a href="login.php" class="home-button"><i class="fas fa-home"></i></a>
        </h2>
    </header>

    <div class="container">
    <div class="header">
        <h1>Login</h1>
    </div>
    <div class="form-section">

        <?php if (!empty($errorMessage)) { echo '<div class="error-message">' . $errorMessage . '</div>'; } ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
            <table>
                <tr>
                    <th>USERNAME:</th>
                    <td><input type="text" name="username" placeholder="Username" required></td>
                </tr>
                <tr>
                    <th>PASSWORD:</th>
                    <td>
                        <div class="password-container">
                            <input type="password" name="password" id="password" placeholder="Password" required>
                            <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                        </div>
                    </td>
                </tr>
            </table>
            <input type="submit" value="Login">
        </form>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordField = document.getElementById('password');
        const toggleIcon = document.querySelector('.toggle-password');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
</script>
</body>
</html>
