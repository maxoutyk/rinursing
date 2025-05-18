<?php
// Include authentication check to prevent logged-in users from accessing forgot password page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Strict check for logged-in users - redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Include the forgot password page content
include 'forgot-password.html';
?> 
 
 
 
 
 
 