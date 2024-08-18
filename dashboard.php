<?php
// Include the file for database connection
include "connection.php";

// Start the session
session_start();
// Check if the user is logged in
if (!isset($_SESSION['username']) || $_SESSION['job'] != 'Admin') {
    // Redirect to the login page or another page if not logged in as chef
    header("Location: chef.php");
    exit();
}
// Function to show JavaScript alerts using SweetAlert2
function showAlert($message, $type) {
    echo "<script>
            Swal.fire({
                icon: '{$type}',
                title: '{$message}',
                showConfirmButton: false,
                timer: 1500
            });
         </script>";
}

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to the login page if not logged in
    header("Location: login.php");
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

// Get the selected timeline from the form, default is 1 month
$selectedTimeline = isset($_POST['timeline']) ? intval($_POST['timeline']) : 1;

date_default_timezone_set('Asia/Kuala_Lumpur'); // Replace 'Your/Timezone' with the appropriate time zone

// Get the current date and calculate the start date based on the selected timeline
$currentDate = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+1 day', strtotime($currentDate)));
$startDate = date('Y-m-d', strtotime("-{$selectedTimeline} month", strtotime($currentDate)));

// SQL query to get total orders and total sales for the selected timeline with status 'Completed'
$sql = "SELECT COUNT(id) as total_orders, SUM(total_price) as total_sales
        FROM admin_panel
        WHERE status = 'Completed'
        AND operation BETWEEN ? AND ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$stmt->bind_result($totalOrders, $totalSales);
$stmt->fetch();
$stmt->close();

// SQL query to get the most ordered menu items for the selected timeline with status 'Completed'
$sqlMenuItems = "SELECT order_menu
                 FROM admin_panel
                 WHERE status = 'Completed'
                 AND operation BETWEEN ? AND ?";

$stmtMenuItems = $conn->prepare($sqlMenuItems);
$stmtMenuItems->bind_param("ss", $startDate, $endDate);
$stmtMenuItems->execute();
$resultMenuItems = $stmtMenuItems->get_result();

$menuItemsCount = [];

// Process the order_menu column to count each menu item
while ($row = $resultMenuItems->fetch_assoc()) {
    $orderMenu = $row['order_menu'];
    $items = explode(', ', $orderMenu);
    foreach ($items as $item) {
        $itemName = trim(explode(' x ', $item)[0]);
        if (!isset($menuItemsCount[$itemName])) {
            $menuItemsCount[$itemName] = 0;
        }
        $menuItemsCount[$itemName]++;
    }
}

$stmtMenuItems->close();

// Sort menu items by count in descending order
arsort($menuItemsCount);

// SQL query to get daily sales for the selected timeline
$sqlDailySales = "SELECT DATE(operation) as day, SUM(total_price) as sales
                  FROM admin_panel
                  WHERE status = 'Completed'
                  AND operation BETWEEN ? AND ?
                  GROUP BY DATE(operation)";

$stmtDailySales = $conn->prepare($sqlDailySales);
$stmtDailySales->bind_param("ss", $startDate, $endDate);
$stmtDailySales->execute();
$resultDailySales = $stmtDailySales->get_result();

$dailySalesData = [];
while ($row = $resultDailySales->fetch_assoc()) {
    $dailySalesData[$row['day']] = $row['sales'];
}

$stmtDailySales->close();

// Generate the labels and data for the chart
$period = new DatePeriod(
    new DateTime($startDate),
    new DateInterval('P1D'),
    new DateTime($endDate) // Use $endDate instead of $currentDate
);
$chartLabels = [];
$chartData = [];

foreach ($period as $date) {
    $day = $date->format('Y-m-d');
    $chartLabels[] = $day;
    $chartData[] = isset($dailySalesData[$day]) ? $dailySalesData[$day] : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Include Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Style for the body */
body {
    font-family: 'Roboto', sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 20px;
    display: flex;
    justify-content: center;
    min-height: 100vh;
    color: #333;
}

/* Style for the container */
.container {
    width: 100%;
    max-width: 1200px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin: 20px 0;
    padding: 20px;
}

/* Style for the header */
.header {
    background-color: black;
    color: #fff;
    text-align: center;
    padding: 20px;
    border-radius: 8px 8px 0 0;
}

.header h1 {
    margin: 0;
    font-size: 28px;
    color: orange;
}

.header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: normal;
}

/* Dropdown styles */
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
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
    border-radius: 5px;
}

.dropdown-content a {
    color: black;
    padding: 20px 16px;
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
    background-color: #007bff;
    color: white;
}

.dropdown:hover .dropbtn {
    background-color: grey;
}

/* Style for the analytics section */
.analytics {
    padding: 20px;
}

.analytics h3 {
    margin-bottom: 20px;
    
}

/* Style for table improvements */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

th, td {
    padding: 15px;
    text-align: left;
}

th {
    background-color: #007bff;
    color: white;
    font-weight: bold;
}

tr:nth-child(even) {
    background-color: #f2f2f2;
}

tr:hover {
    background-color: #ddd;
}

td {
    border-bottom: 1px solid #ddd;
}

th:first-child, td:first-child {
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
}

th:last-child, td:last-child {
    border-top-right-radius: 10px;
    border-bottom-right-radius: 10px;
}
/* Style for cards */
.cards-container {
    display: flex;
    gap: 20px; /* Adjust the gap between cards as needed */
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
.card {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    margin-top: 15px;
    border-radius: 8px;
    padding: 20px;
    background-color: white;
    margin-left: 25px;
    width: 300px;
    height: 100px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.card img {
    width: 90px;
    height: 60px;
    margin-right: 15px;
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

/* Style for buttons */
button {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

button:hover {
    background-color: grey;
}

button:disabled {
    background-color: #ddd;
    cursor: not-allowed;
}

/* Styles for forms */
form {
    margin-top: 20px;
}

/* Responsive styles */
@media (max-width: 768px) {
    .header h1 {
        font-size: 24px;
    }

    .header h2 {
        font-size: 18px;
    }
}
    </style>
</head>
<body>
<div class="container">
     <!-- Dropdown for navigation -->
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
   
    <div class="analytics">
        <h3>Analytics</h3>
        
        <div class="cards-container">
        <div class="card">
            <img src="images/order.png" alt="Image">
            <h3>Total Orders (Current Month): <?php echo $totalOrders; ?></h3>
        </div>
        <div class="card">
            <img src="images/money.png" alt="Image">
            <h3>Total Sales (Current Month): RM <?php echo number_format($totalSales, 2); ?></h3>
        </div>
        <div class="card">
            
            <h3>Coming Soon !</h3>
        </div>
    </div>
        <h4>Most Ordered Menu Items</h4>
        <table>
            <thead>
                <tr>
                    <th>Menu Item</th>
                    <th>Order Count</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($menuItemsCount as $item => $count): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item); ?></td>
                    <td><?php echo $count; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <h4>Daily Sales</h4>
        <form method="post">
            <label for="timeline">Select Timeline:</label>
            <select name="timeline" id="timeline" onchange="this.form.submit()">
                <option value="1" <?php if ($selectedTimeline == 1) echo 'selected'; ?>>1 Month</option>
                <option value="2" <?php if ($selectedTimeline == 2) echo 'selected'; ?>>2 Months</option>
                <option value="3" <?php if ($selectedTimeline == 3) echo 'selected'; ?>>3 Months</option>
                <option value="6" <?php if ($selectedTimeline == 6) echo 'selected'; ?>>6 Months</option>
                <option value="12" <?php if ($selectedTimeline == 12) echo 'selected'; ?>>1 Year</option>
            </select>
        </form>
        <canvas id="salesChart"></canvas>
    </div>
</div>
<script>
    // Data for the chart
    const chartLabels = <?php echo json_encode($chartLabels); ?>;
    const chartData = <?php echo json_encode($chartData); ?>;

    // Configuration for the chart
    const config = {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Daily Sales (RM) ',
                data: chartData,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    };

    // Render the chart
    const salesChart = new Chart(
        document.getElementById('salesChart'),
        config
    );
</script>
</body>
</html>
