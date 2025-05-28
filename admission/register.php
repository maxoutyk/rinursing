<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constant to indicate this file was included properly
define('INCLUDED', true);

// Set JavaScript flag
echo "<script>window.__INCLUDED__ = true;</script>";

// Strict check for logged-in users - redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Include the register page content
include 'register.html';
?> 
 
 
 
 
 
 