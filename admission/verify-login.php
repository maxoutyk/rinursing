<?php
// Include authentication check to prevent logged-in users from accessing verification page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constant to indicate this file was included properly
define('INCLUDED', true);

// Strict check for logged-in users - redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Include the verification page content
include 'verify-login.html';
?> 
 
 
 
 
 
 