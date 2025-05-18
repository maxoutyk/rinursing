<?php
// Start session if not already started
session_start();

// Set JSON content type header
header('Content-Type: application/json');

// Initialize response
$response = [
    'logged_in' => false,
    'user_id' => null,
    'user_name' => null,
    'user_email' => null,
    'application_progress' => 0,
    'last_login' => null
];

// Check if user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $response['logged_in'] = true;
    $response['user_id'] = $_SESSION['user_id'] ?? null;
    $response['user_name'] = $_SESSION['user_name'] ?? null;
    $response['user_email'] = $_SESSION['user_email'] ?? null;
    
    // Get additional user data if needed
    if (isset($_SESSION['user_id'])) {
        try {
            // Include database connection
            require_once 'db_connect.php';
            
            // Get user's application progress and last login
            $query = "SELECT last_login, 
                     (SELECT progress FROM applications WHERE user_id = ?) as application_progress 
                     FROM users WHERE id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows === 1) {
                $data = $result->fetch_assoc();
                $response['last_login'] = $data['last_login'];
                $response['application_progress'] = $data['application_progress'] ?? 40; // Default to 40% if null
            }
            
            $stmt->close();
        } catch (Exception $e) {
            // Just log the error, don't expose it to the response
            error_log("Error fetching user data: " . $e->getMessage());
        }
    }
}

// Return the response
echo json_encode($response);
exit; 
 
 
 
 
 
 