<?php
// Prevent direct access to this file
if (!defined('INCLUDED')) {
    http_response_code(403);
    die('Direct access to this file is not allowed.');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin
function isAdminLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['is_admin']) && 
           $_SESSION['is_admin'] === true;
}

// If not logged in as admin, redirect to login page
if (!isAdminLoggedIn()) {
    // Store the requested URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to admin login page
    header("Location: /admin/login.php");
    exit;
}

// Get admin user details
require_once __DIR__ . '/db_connect.php';

$admin_query = "SELECT first_name, last_name, email FROM users WHERE id = ? AND is_admin = 1";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // If user is not found or not an admin, destroy session and redirect
    session_destroy();
    
    // Close database connections before exit
    $stmt->close();
    $conn->close();
    
    header("Location: /admin/login.php");
    exit;
}

// Store admin details in session if not already stored
if (!isset($_SESSION['admin_name'])) {
    $admin = $result->fetch_assoc();
    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
    $_SESSION['admin_email'] = $admin['email'];
}

// Close the statement but keep connection open for the main script
$stmt->close();
?> 