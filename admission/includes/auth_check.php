<?php
/**
 * Authentication Check - Prevents authenticated users from accessing login/registration pages
 * 
 * This script should be included at the top of login pages, registration pages, and verification pages
 * to redirect logged-in users to the dashboard.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Extract the current page path
    $current_page = basename($_SERVER['PHP_SELF']);
    $restricted_pages = [
        'login.html', 'login.php', 
        'register.html', 'register.php', 
        'verify-login.html', 'verify-login.php', 
        'verify.php', 
        'forgot-password.html', 'forgot-password.php'
    ];
    
    // Check if the current page is in the restricted list
    if (in_array($current_page, $restricted_pages) || 
        (isset($_SERVER['HTTP_REFERER']) && str_contains($_SERVER['HTTP_REFERER'], 'verify.php'))) {
        // Redirect to dashboard if user is accessing login/registration pages
        header('Location: dashboard.php');
        exit;
    }
}

// For pages that should only be accessible to logged-in users, check for valid session
// (This section would be included in pages like dashboard.html, profile.html, etc.)
// $restrict_to_logged_in = false; // Set this to true in protected pages
// if (isset($restrict_to_logged_in) && $restrict_to_logged_in) {
//     if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
//         header('Location: login.html');
//         exit;
//     }
// }
?> 
 
 
 
 
 
 