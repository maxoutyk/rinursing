<?php
// First check if we have a session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<p>Started a new session</p>";
} else {
    echo "<p>Using an existing session</p>";
}

// Set session variables if not already set
if (!isset($_SESSION['test_var'])) {
    $_SESSION['test_var'] = 'Session test value - ' . time();
    echo "<p>Set test_var to: " . $_SESSION['test_var'] . "</p>";
} else {
    echo "<p>test_var already set to: " . $_SESSION['test_var'] . "</p>";
}

// Display session status
echo "<h2>Current Session Status:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if we should include the auth_check file
if (isset($_GET['test_auth_check'])) {
    echo "<h2>Testing auth_check.php inclusion:</h2>";
    require_once 'includes/auth_check.php';
    echo "<p>auth_check.php included successfully</p>";
}

// Check if we should test logout
if (isset($_GET['test_logout'])) {
    echo "<h2>Testing logout process:</h2>";
    echo "<p>Current session ID: " . session_id() . "</p>";
    echo "<p>Calling logout in 3 seconds...</p>";
    // Add JavaScript to redirect to logout after 3 seconds
    echo '<script>
        setTimeout(function() {
            window.location.href = "includes/logout.php";
        }, 3000);
    </script>';
}

// Navigation links for testing
echo '<div style="margin-top: 20px; padding: 10px; background: #f0f0f0;">';
echo '<p><a href="session_test.php">Refresh Test</a> | ';
echo '<a href="session_test.php?test_auth_check=1">Test auth_check.php</a> | ';
echo '<a href="session_test.php?test_logout=1">Test Logout</a></p>';

// Add links to test pages
echo '<h3>Test Navigation:</h3>';
echo '<p>';
echo '<a href="login.php">Login (PHP)</a> | ';
echo '<a href="login.html">Login (HTML)</a>';
echo '</p>';

echo '<p>';
echo '<a href="dashboard.php">Dashboard (PHP)</a> | ';
echo '<a href="dashboard.html">Dashboard (HTML)</a>';
echo '</p>';

// Add session status indicator and action links
echo '<h3>Session Status:</h3>';
echo '<p>';
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo '<span style="color: green; font-weight: bold;">✓ LOGGED IN</span> as ' . htmlspecialchars($_SESSION['user_name'] ?? 'Unknown User') . ' | ';
    echo '<a href="includes/logout.php">Logout</a>';
} else {
    echo '<span style="color: red; font-weight: bold;">✘ NOT LOGGED IN</span> | ';
    echo '<a href="login.php">Login</a>';
}
echo '</p>';

// Add test login/logout actions
echo '<h3>Test Actions:</h3>';
echo '<p>';
echo '<a href="?set_login=1">Set Fake Login Session</a> | ';
echo '<a href="?clear_login=1">Clear Login Session</a>';
echo '</p>';

// Process test actions
if (isset($_GET['set_login'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = 999;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['user_email'] = 'test@example.com';
    echo '<script>window.location.href = "session_test.php";</script>';
} elseif (isset($_GET['clear_login'])) {
    unset($_SESSION['logged_in']);
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    echo '<script>window.location.href = "session_test.php";</script>';
}

echo '</div>';
?> 
 
 
 
 
 
 