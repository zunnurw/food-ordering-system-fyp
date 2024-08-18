//Quantity button
function updateQuantity(item, change) {
    let quantityElement = document.getElementById(`${item}-quantity`);
    let currentQuantity = parseInt(quantityElement.value);
    let newQuantity = currentQuantity + change;

    if (newQuantity < 0) {
        newQuantity = 0;    }

    quantityElement.value = newQuantity;
    }    
// JavaScript for scroll-down button behavior and hiding submit/reset buttons
function scrollToCustomerProgress() {
    // Scroll to the customer progress section
    document.getElementById('customer-progress').scrollIntoView({ behavior: 'smooth' });

    // Hide the scroll-down button after clicking
    document.querySelector('.scroll-down-button').style.display = 'none';

    // Hide the submit and reset buttons
    document.getElementById('reset-button').style.display = 'none';
    document.getElementById('submit-button').style.display = 'none';

    // Hide the table-design
    document.querySelector('.table-design').style.visibility = 'hidden';
}

// Function to get the position of an element relative to the document
function getOffset(element) {
    let rect = element.getBoundingClientRect();
    return rect.top + window.scrollY;
}

// Show the scroll-down button and control submit/reset buttons visibility based on scroll position
window.addEventListener('scroll', function() {
    const scrollButton = document.querySelector('.scroll-down-button');
    const resetButton = document.getElementById('reset-button');
    const submitButton = document.getElementById('submit-button');
    const customerProgress = document.getElementById('customer-progress');
    const tableDesign = document.querySelector('.table-design');
    const footer = document.querySelector('footer'); // Assuming footer is the footer element in your HTML
    
    if (scrollButton && resetButton && submitButton && customerProgress && tableDesign && footer) {
        const customerProgressTop = customerProgress.offsetTop; // Top position of customer progress section
        const customerProgressHeading = document.querySelector('#customer-progress h3');
        const headingOffset = customerProgressHeading.getBoundingClientRect().top + window.scrollY; // Offset of the heading
        
        // Calculate the offset where elements should be hidden
        const hideOffset = customerProgressTop - window.innerHeight; // Adjust as needed
        
        if (window.scrollY < hideOffset) {
            // Show scroll-down button and submit/reset buttons when above hideOffset
            scrollButton.style.display = 'block';
            resetButton.style.display = 'block';
            submitButton.style.display = 'block';
            
            // Show the table-design if not in customer progress section
            tableDesign.style.visibility = 'visible';
        } else {
            // Hide scroll-down button and submit/reset buttons when at or below hideOffset
            scrollButton.style.display = 'none';
            resetButton.style.display = 'none';
            submitButton.style.display = 'none';
            
            // Hide the table-design when in or below customer progress section
            tableDesign.style.visibility = 'hidden';
        }
        
        // Check if scrolled to or past the footer
        const footerTop = getOffset(footer); // Top position of footer
        if (window.scrollY > footerTop) {
            // Ensure buttons are hidden completely at footer
            scrollButton.style.display = 'none';
            resetButton.style.display = 'none';
            submitButton.style.display = 'none';
        }
    }
});

    // Show the scroll-to-top button when the user scrolls down
    window.onscroll = function() {
        var topButton = document.getElementById("topcontrol");
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            topButton.style.display = "block";
        } else {
            topButton.style.display = "none";
        }
    };

    // Scroll to the top of the document when the button is clicked
    document.getElementById("topcontrol").onclick = function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // Function to trigger fonts loaded events (if needed)
    let customifyTriggerFontsLoadedEvents = function() {
        window.dispatchEvent(new Event('wf-active'));
        document.getElementsByTagName('html')[0].classList.add('wf-active');
    };

// Function to reload or refresh the customer progress table
function reloadCustomerProgressTable() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'reload_customer_progress.php', true);

    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 400) {
            // Success! Parse the JSON response
            var response = JSON.parse(xhr.responseText);
            var data = response.progress_data;
            var tableBody = document.querySelector('.progress-table tbody'); // Select tbody of the progress table

            // Clear existing table rows
            tableBody.innerHTML = '';

            // Populate table rows with new data
            data.forEach(function (progress_row) {
                var row = document.createElement('tr');
                var tableNumberDisplay = progress_row.table_number !== 'N/A' ? 'Table ' + progress_row.table_number : 'Takeaway'; // Display 'Takeaway' for 'N/A'
                row.innerHTML = '<td>#' + progress_row.order_id + '</td>' +
                '<td>' + tableNumberDisplay + '</td>' +
                '<td class="progress-cell">' +
                '<span class="progress-status ' + getStatusClass(progress_row.progress) + '">' + progress_row.progress + '</span>' +
                '</td>';
                tableBody.appendChild(row);
            });
        } else {
            // Error handling
            console.error('Error fetching data: ' + xhr.statusText);
        }

        // Re-initiate long polling after a short delay (e.g., 1 second)
        setTimeout(reloadCustomerProgressTable, 1000);
    };

    xhr.onerror = function () {
        // Network errors
        console.error('Network error');

        // Retry after a short delay (e.g., 5 seconds)
        setTimeout(reloadCustomerProgressTable, 5000);
    };

    // Send the AJAX request
    xhr.send();
}

// Initial call to start long polling
document.addEventListener('DOMContentLoaded', function() {
    reloadCustomerProgressTable();
});


function getStatusClass(status) {
    console.log('Status:', status); // Check the value of status being passed
    
    switch (status) {
        case 'Queued':
            return 'progress-Queued';
        case 'Preparing':
            return 'progress-Preparing';
        case 'Ready':
            return 'progress-Ready';
        default:
            return ''; // Ensure default behavior is handled correctly
    }
}

// Initial call to start long polling
document.addEventListener('DOMContentLoaded', function() {
    reloadCustomerProgressTable();
});




