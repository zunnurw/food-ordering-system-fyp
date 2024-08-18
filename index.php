<?php
session_start();

include "connection.php";

// Initialize session variables for new order on GET request
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $_SESSION['selected_items'] = [];
    $_SESSION['selected_quantities'] = [];
    $_SESSION['items'] = []; // Initialize items session variable
}

// Fetch menu items and store them in the session
$sql = "SELECT category, name, description, price, image FROM menu_items";
$result = $conn->query($sql);

// Initialize an array to hold menu items by category
$menu_items = [];

if ($result->num_rows > 0) {
    // Output data of each row
    while ($row = $result->fetch_assoc()) {
        $menu_items[$row["category"]][] = $row;
        // Store item prices in the session
        $_SESSION['items'][$row["name"]] = $row["price"];
    }
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    foreach ($_POST['items'] as $key => $quantity) {
        if (is_numeric($quantity) && $quantity > 0) {
            // Store selected item and quantity
            $_SESSION['selected_items'][] = $key;
            $_SESSION['selected_quantities'][$key] = $quantity;
        }
    }

    // Redirect to order_type.php after processing form
    header("Location: order_type.php");
    exit();
}

// Fetch customer progress data for approved orders
$progress_sql = "
    SELECT od.order_id, od.table_number, od.progress
    FROM order_details od
    JOIN admin_panel ap ON od.order_id = ap.id
    WHERE (od.order_status != 'Completed' AND ap.action = 'Approved')
    ORDER BY od.order_id DESC";

$progress_result = $conn->query($progress_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="icon" href="WesternHouse.jpg">
    <title>Western House Kulim</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Unna:ital,wght@0,400;0,700;1,400;1,700&display=swap');

    body {
        padding: 0;
        margin: 0;
        width: 100%;
        height: 100%;
        font-family: sans-serif;
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
    h3{
        text-decoration: underline;
        margin-left: 10px;
    }

    h4 {
        font-family: sans-serif;
        margin-left: 10px;
    }

    section {
        margin-top: 50px;
        margin-bottom: 160px;
    }

    img {
        margin: 0 10px;
        border-radius: 10px;
        width: 120px;
        float: left;
    }

    .adjust {
        margin-left: 140px;
    }

    .drinks {
        margin-left: 10px;
    }

    .decrease, .increase {
        width: 28px;
        height: 28px;
        font-size: 20px;
        font-weight: bold;
        border-radius: 20px;
        border-color: black;
        border: 2px solid;
    }

    table {
        align-items: center;
        background-color: white;
        z-index: 999; /* Ensure it's below fixed header */
    }

    .table-design {
        border-collapse: separate;
        border-spacing: 0;
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 1000; /* Ensure it's above other content */
    }

    table tr th a {
        font-family: sans-serif;
        display: block;
        text-decoration: none;   
        text-align: center;
        color: black;
        font-size: 20px;
        font-weight: bold;
        padding: 3px 10px;
        border-radius: 5px;
    }

    table tr th a:hover {
        background-color: gray;
    }

    input[type="number"] {
        border-radius: 5px;
        border: 2px solid black;
    }

    input[type="submit"], input[type="reset"] {
        position: fixed;
        font-size: 20px;
        cursor: pointer;
        color: white;
        padding: 7px;
        transition-duration: 0.4s;
        border: none;
        z-index: 1000; /* Ensure it's above other content */
        border-radius: 10px;
        
    }

    input[type="submit"] {
        background-color: #4CAF50;
        bottom: 100px;
        left: 79%;
        transform: translateX(-80%);
    }

    input[type="reset"] {
        background-color: red;
        bottom: 100px;
        left: 16%;
        transform: translateX(-20%);
    }

    input[type="submit"]:hover {
        background-color: white;
        color: #4CAF50;
        border: 2px solid #4CAF50;
    }

    input[type="reset"]:hover {
        background-color: white;
        color: red;
        border: 2px solid red;
    }

    input[type="number"] {
        width: 24px;
    }

    .progress-table {
        margin-top: 20px;
        width: 100%;
        border-collapse: collapse;
        overflow-y: auto;
    }

    .progress-table th, .progress-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
    }

    .progress-table th {
        background-color: #f2f2f2;
    }

    .progress-cell {
        position: relative;
    }

    .progress-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: bold;
    transition: background-color 0.3s ease, color 0.3s ease; /* Smooth transitions for background-color and text color */
    width: 100px; /* Set a fixed width */
    text-align: center; /* Center align text */
    }

    .progress-Queued,
    .progress-Preparing,
    .progress-Ready {
        width: 80%; /* Match the width for consistency */
    }

    /* Apply animation to .progress-Queued */
    .progress-Queued {
        background-color: #F0F0F0; /* Light grey for Queued status */
        color: #333; /* Dark text color for Queued status */
        animation: pulse-Queued 2s ease-in-out infinite; /* Apply pulse-Queued animation */
    }

    .progress-Preparing {
        background-color: yellow; /* Yellow for Preparing status */
        color: #333; /* Dark text color for Preparing status */
        animation: pulse-Preparing 2s ease-in-out infinite; /* Apply pulse animation */
    }

    /* Apply animation to .progress-Ready */
    .progress-Ready {
        background-color: #0DC813; /* Green for Ready status */
        color: #fff; /* White text color for Ready status */
        animation: glow-Ready 5s ease-in-out infinite; /* Apply glow-Ready animation */
    }

    /* Add animation effect on hover */
    .progress-status:hover {
        transform: scale(1.05); /* Scale effect on hover */
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Optional: Add a subtle box shadow */
    }

    /* Define keyframes for different animations */
    @keyframes pulse-Preparing {
        0% {
            transform: translateY(0);
            opacity: 1;
        }
        50% {
            transform: translateY(-5px);
            opacity: 0.8;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }
    /* Define keyframes for Queued animation */
    @keyframes pulse-Queued {
        0% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.9;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Define keyframes for Ready animation */
    @keyframes glow-Ready {
        0% {
            transform: scale(1);
            opacity: 1;
            box-shadow: 0 0 5px rgba(13, 200, 19, 0.7); /* Initial box-shadow */
        }
        50% {
            transform: scale(1.05);
            opacity: 0.9;
            box-shadow: 0 0 20px rgba(13, 200, 19, 0.9); /* Increased box-shadow for glow */
        }
        100% {
            transform: scale(1);
            opacity: 1;
            box-shadow: 0 0 5px rgba(13, 200, 19, 0.7); /* Return to original box-shadow */
        }
    }
    .scroll-down-button {
        position: fixed;
        bottom: 380px;
        right: -70px;
        background-color: orange;
        color: white;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
        z-index: 1000; /* Ensure it's above other content */
        transform: rotate(90deg); /* Apply initial rotation */
    }

    .scroll-down-button:hover {
        background-color: orange;
    }
        #topcontrol {
        position: fixed;
        bottom: 100px;
        right: 3px;
        background-color: #3D4040;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 13px;
        font-size: 16px;
        cursor: pointer;
        display: none; /* Hide the button initially */
        z-index: 1000; /* Ensure it's above other content */
    }

    #topcontrol:hover {
        background-color: #3FAAFF;
    }
        footer {
        background-color: #333;
        padding: 10px 0;
        color: #fff;
    }

    .card {
        background: none;
        border: none;
    }

    .footer-link img {
        width: 24px; /* Consistent size for all icons */
        height: 24px;
        vertical-align: middle;
        margin-left: 2px;
        margin-right: 2px;
        margin-bottom: 2px;
    }

    .footer-link {
        color: #fff;
        text-decoration: none;
        font-size: 18px; /* Consistent font size for all text */
        vertical-align: middle;
    }

    .footer-link:hover {
        color: #ccc;
    }

    .footer-link:hover {
        color: #ccc; /* Hover color */
    }

    .footer-separator {
        margin: 0 10px; /* Consistent spacing for the separators */
        color: #fff; /* Color for separators */
    }

    .footer-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px; /* Consistent spacing between items */
    }


    .card-header {
        border: 0;
        background: none;
    }

    .card-header h6 {
        margin: 0;
        font-size: 1rem;
    }

    .card-header strong {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
    }

    .card-header i {
        margin-right: 5px;
    }

    .card-header .footer-link {
        display: inline-flex;
        align-items: center;
    }

    @media (max-width: 768px) {
        .card-header strong {
            flex-direction: column;
            gap: 5px;
        }
    }
    </style>
<script>
$(document).ready(function() {
    // Handle click events on progress status buttons
    $('.progress-status').click(function() {
        var $this = $(this); // Store the clicked element
        var orderId = $this.data('orderid');
        var currentProgress = $this.text().trim();

        $.ajax({
            url: 'reload_customer_progress.php',
            method: 'POST',
            data: { orderId: orderId, newProgress: getNextProgress(currentProgress) },
            success: function(response) {
                if (response.success) {
                    // Update UI with new progress status
                    updateProgressUI($this, response.newProgress);
                } else {
                    alert('Failed to update progress. Please try again.');
                }
            },
            error: function() {
                alert('Failed to update progress. Please try again.');
            }
        });
    });

    // Function to update the UI with new progress status
    function updateProgressUI(element, newProgress) {
        // Remove all progress classes first
        element.removeClass('progress-Queued progress-Preparing progress-Ready');
        
        // Add the appropriate class based on the new progress
        switch (newProgress) {
            case 'Queued':
                element.addClass('progress-Queued');
                break;
            case 'Preparing':
                element.addClass('progress-Preparing');
                break;
            case 'Ready':
                element.addClass('progress-Ready');
                break;
            default:
                // Handle any other cases or leave empty for default styling
                break;
        }

        // Update the text to reflect the new progress
        element.text(newProgress);
    }
});

</script>

</head>
<body>
    <header>
        <h2 id="top">WESTERN HOUSE 
            <a href="login.php" class="home-button"><i class="fas fa-home"></i></a>
        </h2>
    </header>


    <section>
        <!-- Your form for selecting menu items -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <?php
            // Define the desired category order
            $category_order = array("Popular Menu", "Nasi Goreng", "Burger", "Side Dish", "Spaghetti", "Drinks");

            // Sort menu items by predefined category order
            foreach ($category_order as $category) {
                if (isset($menu_items[$category])) {
                    echo '<h4 id="' . htmlspecialchars($category) . '"><u>' . htmlspecialchars($category) . '</u></h4>';
                    echo '<div class="category-section">';
                    foreach ($menu_items[$category] as $item) {
                        echo '<div class="menu-item">';
                        if ($item["image"]) {
                            echo '<img src="' . htmlspecialchars($item["image"]) . '" alt="' . htmlspecialchars($item["name"]) . '">';
                        } else {
                            echo '<img src="placeholder.jpg" alt="No Image">';
                        }
                        echo '<div class="adjust">';
                        echo '<p><b>' . htmlspecialchars($item["name"]) . '</b></p>';
                        echo '<p>' . htmlspecialchars($item["description"]) . '</p>';
                        echo '<p>RM' . htmlspecialchars($item["price"]) . '</p>';
                        echo '<p>Quantity: ';
                        echo '<button class="decrease" onclick="updateQuantity(\'' . htmlspecialchars($item["name"]) . '\', -1); return false;">-</button> ';
                        echo '<input type="number" id="' . htmlspecialchars($item["name"]) . '-quantity" class="input" name="items[' . htmlspecialchars($item["name"]) . ']" value="0" readonly>';
                        echo ' <button class="increase" onclick="updateQuantity(\'' . htmlspecialchars($item["name"]) . '\', 1); return false;">+</button>';
                        echo '</p>';
                        echo '</div><br>';
                        echo '</div>';
                    }
                    echo '</div><br>';
                }
            }
            ?>

        <input type="reset" id="reset-button" name="reset" value="Reset Order">
        <input type="submit" id="submit-button" name="submit" value="Confirm Order">

        </form>
    </section>

    <section id="customer-progress">
        <!-- Customer Progress Table -->
        <h3>Customer Progress</h3>
        <table class="progress-table">
        <thead>
        <tr>
            <th>Order ID</th>
            <th>Table Number</th>
            <th>Progress</th>
        </tr>
    </thead>
    <tbody>
    <?php
// Loop through each row in the result set
while ($progress_row = $progress_result->fetch_assoc()) {
    echo '<tr>';
    echo '<td>#' . htmlspecialchars($progress_row['order_id']) . '</td>';
    echo '<td>';
    $table_number = htmlspecialchars($progress_row['table_number']);
    echo $table_number !== 'N/A' ? 'Table ' . $table_number : 'Takeaway';
    echo '</td>';
    echo '<td class="progress-cell">';
    
    // Determine which CSS class to apply based on progress status
    $status = htmlspecialchars($progress_row['progress']);
    $statusClass = '';

    switch ($status) {
        case 'Queued':
            $statusClass = 'progress-Queued';
            break;
        case 'Preparing':
            $statusClass = 'progress-Preparing';
            break;
        case 'Ready':
            $statusClass = 'progress-Ready';
            break;
        default:
            $statusClass = ''; // Handle any other cases or leave empty for default styling
            break;
    }

    // Output the progress status with the determined class
    echo '<span class="progress-status ' . $statusClass . '">' . $status . '</span>';
    echo '</td>';
    echo '</tr>';
}
?>


    </tbody>
    </table>
    </section>

    <table class="table-design">
        <tr>
            <th><a href="#Spaghetti" style="background-color: lightsalmon;">Spaghetti</a></th> 
            <th><a href="#Burger" style="background-color: lemonchiffon;">Burger</a></th>
            <th><a href="#Drinks" style="background-color: lavender;">Drinks</a></th>
        </tr>
        <tr>
            <th><a href="#Popular Menu" style="background-color: palegreen;">Popular Menu</a></th>
            <th><a href="#Nasi Goreng" style="background-color: lightcyan;">Nasi Goreng</a></th>
            <th><a href="#Side Dish" style="background-color: lightpink;">Side Dish</a></th>  
        </tr>      
    </table>

    <!-- Scroll to Bottom button -->
    <button id="scroll-down-button" class="scroll-down-button" onclick="scrollToCustomerProgress()">
        Order Progress <i class="fas fa-chevron-down"></i>
    </button>


        <!-- Scroll to Top button -->
        <div id="topcontrol" title="Scroll To Top"><i class="fas fa-chevron-up"></i></div>

<footer>
  <div id="pages" style="width:90%; margin: 0 auto;">
    <div class="card" style="width:100%; margin-bottom: 0px;">
      <div class="card-header text-center" style="border:0px; background: none;">
      <h6 class="mb-0">
        <strong>
            <a href="https://www.tiktok.com/@westernhousekulim?_t=8oGemGCbUri&_r=1" target="_blank" class="footer-link">
            <img src="/icon/tiktokicon.png" alt="TikTok"> TikTok
            </a>
            <span class="footer-separator">|</span>
            <a href="https://www.facebook.com/profile.php?id=100088884854359&mibextid=ZbWKwL" target="_blank" class="footer-link">
                <img src="/icon/facebookicon.png" alt="Facebook"> Facebook
            </a>
            <span class="footer-separator">|</span>
            <a href="https://www.instagram.com/western.house.kulim?igsh=bDF1aWRvNTJuNGdz" target="_blank" class="footer-link">
                <img src="/icon/instagramicon.png" alt="Instagram"> Instagram
            </a>
            <span class="footer-separator">|</span>
            <a href="https://my.shp.ee/tTLqz5S" target="_blank" class="footer-link">
                <img src="/icon/shopeeicon.png" alt="Shopee" > Shopee
            </a>
            <span class="footer-separator">|</span>
            <a href="https://wa.me/+60166066090" target="_blank" class="footer-link">
                <img src="/icon/whatsappicon.png" alt="WhatsApp"> WhatsApp
            </a>
        </strong>
    </h6>
        <p class="footer-copyright" style="text-align: center;">
          &copy; <?php echo date("Y"); ?> Wafiy &#183; Najmi &#183; Azhan. All Rights Reserved.
        </p>
      </div>
      <p class="footer-copyright" style="text-align: center; font-size: 16px; color: #777;">
        &copy; <?php echo date("Y"); ?> Western House Kulim. All rights reserved. <!--No part of this website or any of its contents may be reproduced, 
        copied, modified, or adapted, without the prior written consent of the author, unless otherwise indicated for stand-alone materials. 
        Commercial use and distribution of the contents of the website are strictly prohibited without express and prior written consent 
        from the author. Unauthorized use and/or duplication of this material without express and written permission from the author 
        and/or owner is strictly prohibited. Excerpts and links may be used, provided that full and clear credit is given to Your Company 
        Name with appropriate and specific direction to the original content.
        </p>
        <!-- WhatsApp links for Najmi and Azhan 
        <span class="footer-separator">|</span>
        <a href="https://wa.me/+60172404349" target="_blank" class="footer-link">
          <i class="fab fa-whatsapp"></i> WhatsApp (Najmi)
        </a>
        <span class="footer-separator">|</span>
        <a href="https://wa.me/+601121998271" target="_blank" class="footer-link">
          <i class="fab fa-whatsapp"></i> WhatsApp (Azhan) -->
        </a>
      </div>
    </div>
  </div>
</footer>
    <script src="script.js"></script>
</body>
</html>
