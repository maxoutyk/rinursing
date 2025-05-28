<?php
/**
 * Database connection configuration
 * 
 * This file handles the connection to the MySQL database for the Regional
 * Institute of Nursing admission system.
 */

// Database credentials
$db_host = 'localhost';
$db_name = 'rinursing_admission';
$db_user = 'rinursing_user';
$db_pass = 'password123'; // Using the simple password we set for development

// Create connection
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // For production, log error instead of displaying
    // error_log("Database connection error: " . $e->getMessage());
    
    // For development, you may want to see the error
    die("Database connection error: " . $e->getMessage());
}

// Don't register a shutdown function as each script should manage its own connection closure
// This prevents issues with multiple inclusions and premature closures
?> 
 
 
 
 
 
 