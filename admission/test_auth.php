<?php
// Start a session
session_start();

// Print current session data
echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo "<p style='color: green;'>User is logged in as: " . htmlspecialchars($_SESSION['user_name'] ?? 'Unknown User') . "</p>";
} else {
    echo "<p style='color: red;'>User is not logged in</p>";
}

// Provide login/logout links
echo "<hr>";
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo "<a href='includes/logout.php'>Logout</a> | ";
    echo "<a href='dashboard.html'>Go to Dashboard</a>";
} else {
    echo "<a href='login.html'>Login</a>";
}

// Display server information for debugging
echo "<h3>Server Information:</h3>";
echo "<pre>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current Page: " . $_SERVER['PHP_SELF'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "</pre>";
?> 
 
 
 
 
 
 