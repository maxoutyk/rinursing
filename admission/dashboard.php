<?php
/**
 * Student Dashboard PHP Wrapper
 * 
 * This script ensures that:
 * 1. Only authenticated users can access the dashboard
 * 2. Session handling is consistent with the rest of the application
 * 3. Loads user and application data
 * 4. Handles the submission success parameter
 */

// Include authentication requirement check
require_once 'includes/required_auth.php';

// Define constant to indicate this file was included properly
define('INCLUDED', true);

// Get user ID from session
$userId = $_SESSION['user_id'];

// Debug user ID
error_log('Debug - User ID: ' . $userId);

// Database connection
require_once 'includes/db_connect.php';

// Initialize variables
$has_application = false;
$application_data = null;
$sections = array();
$userData = array(
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'last_login' => null
);

try {
    // First fetch user data
    $user_query = "SELECT first_name, last_name, email, last_login FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $userId);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result && $user_result->num_rows > 0) {
        $userData = $user_result->fetch_assoc();
        error_log('Debug - User data: ' . print_r($userData, true));
    }
    $user_stmt->close();

    // Then check if user has an application
    $check_application = $conn->prepare("SELECT id, progress, status, application_id FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $check_application->bind_param("i", $userId);
    $check_application->execute();
    $result = $check_application->get_result();
    
    error_log('Debug - Application check result rows: ' . $result->num_rows);
    
    if($result->num_rows > 0) {
        $has_application = true;
        $application_data = $result->fetch_assoc();
        error_log('Debug - Basic application data: ' . print_r($application_data, true));
        
        // Now fetch the additional details with joins
        $query = "SELECT 
                    p.full_name,
                    p.gender,
                    p.dob,
                    e.board_university as hsce_board,
                    e.percentage as hsce_percentage,
                    e.year_of_passing as hsce_year
                FROM applications a
                LEFT JOIN personal_details p ON a.id = p.application_id
                LEFT JOIN education e ON a.id = e.application_id AND e.level = '12th'
                WHERE a.id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $application_data['id']);
        $stmt->execute();
        $detail_result = $stmt->get_result();
        
        error_log('Debug - Detailed query result rows: ' . $detail_result->num_rows);
        
        if($detail_result && $detail_result->num_rows > 0) {
            // Merge the additional details with existing application data
            $additional_data = $detail_result->fetch_assoc();
            $application_data = array_merge($application_data, $additional_data);
            error_log('Debug - Merged application data: ' . print_r($application_data, true));
            
            // If application is submitted, set progress to 100%
            if ($application_data['status'] === 'submitted') {
                $application_data['progress'] = 100;
            }
            
            // Get sections data if not submitted
            if ($application_data['status'] !== 'submitted') {
                $sections_query = "SELECT section_id, section_name, is_completed 
                                 FROM application_sections 
                                 WHERE application_id = ? 
                                 ORDER BY section_id ASC";
                $sections_stmt = $conn->prepare($sections_query);
                $sections_stmt->bind_param("i", $application_data['id']);
                $sections_stmt->execute();
                $sections_result = $sections_stmt->get_result();
                
                while($section = $sections_result->fetch_assoc()) {
                    $sections[] = $section;
                }
                $sections_stmt->close();
            }
        }
        $stmt->close();
    }
    $check_application->close();
    
} catch (Exception $e) {
    error_log('Error in dashboard.php: ' . $e->getMessage());
}

// Debug final application data
error_log('Final application data: ' . print_r($application_data, true));

// Check for submission success parameter
$submissionSuccess = isset($_GET['submission']) && $_GET['submission'] === 'success';

// Add JavaScript variables for client-side use
echo "<script>window.__INCLUDED__ = true;</script>";
echo "<script>window.userData = " . json_encode($userData) . ";</script>";
echo "<script>window.applicationData = " . json_encode($application_data) . ";</script>";
echo "<script>window.submissionSuccess = " . ($submissionSuccess ? 'true' : 'false') . ";</script>";

// Include the dashboard HTML content
include 'dashboard.html';

// Close the database connection at the end of the script
$conn->close();
?> 
 
 
 
 
 
 