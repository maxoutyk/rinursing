<?php
/**
 * Logout Script - Destroys user session and redirects to login page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Delete remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    // Connect to database to clear the token
    require_once 'db_connect.php';
    
    $token = $_COOKIE['remember_token'];
    
    // Delete token from database
    try {
        $query = "DELETE FROM remember_tokens WHERE token = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Just log the error, don't affect logout process
        error_log("Error deleting remember token: " . $e->getMessage());
    }
    
    // Delete the cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ../login.php');
exit;
?> 
 
 
 
 
 
 