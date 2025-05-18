<?php
/**
 * Required Authentication Check - Ensures only authenticated users can access protected pages
 * 
 * This script should be included at the top of dashboard.html, profile.html, and other protected pages
 * to redirect non-logged-in users to the login page.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Store the requested page for redirect after login (optional)
    $_SESSION['requested_page'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header('Location: login.php');
    exit;
}
?> 
 
 
 
 
 
 