<?php
/**
 * Get Application Progress
 * 
 * This script retrieves the current progress of the user's application via AJAX.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once 'includes/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

try {
    // Get application progress
    $query = "SELECT id, application_id, status, progress FROM applications WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $applicationData = $result->fetch_assoc();
        
        // Get section completion status
        $query = "SELECT section_id, is_completed FROM application_sections WHERE application_id = ? ORDER BY section_id";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $applicationData['id']);
        $stmt->execute();
        $sectionsResult = $stmt->get_result();
        
        $sections = [];
        while ($row = $sectionsResult->fetch_assoc()) {
            $sections[$row['section_id']] = (bool)$row['is_completed'];
        }
        
        // Return application data with sections
        echo json_encode([
            'status' => 'success',
            'application_id' => $applicationData['application_id'],
            'application_status' => $applicationData['status'],
            'progress' => $applicationData['progress'],
            'sections' => $sections
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No application found']);
    }
} catch (Exception $e) {
    error_log('Error fetching application progress: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error fetching application progress']);
} 
 
 
 