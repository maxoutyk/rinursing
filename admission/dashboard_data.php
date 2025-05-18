<?php
/**
 * Dashboard Data Retrieval
 * 
 * This script handles fetching all data needed for the dashboard from the database.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Initialize response array
$data = [
    'user' => [],
    'application' => [],
    'sections' => [],
    'important_dates' => [],
    'notifications' => []
];

// Get user data
try {
    $query = "SELECT 
                u.id, 
                u.first_name, 
                u.last_name, 
                u.email, 
                u.phone, 
                u.status,
                u.created_at, 
                u.last_login
              FROM users u 
              WHERE u.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows === 1) {
        $data['user'] = $result->fetch_assoc();
        $data['user']['full_name'] = $data['user']['first_name'] . ' ' . $data['user']['last_name'];
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}

// Get application data
try {
    $query = "SELECT 
                id, 
                application_id, 
                status, 
                progress, 
                created_at, 
                submitted_at, 
                last_updated
              FROM applications 
              WHERE user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows === 1) {
        $data['application'] = $result->fetch_assoc();
    } else {
        // No application found, create a default structure
        $data['application'] = [
            'id' => null,
            'application_id' => 'Not Allocated',
            'status' => 'not_started',
            'progress' => 0,
            'created_at' => null,
            'submitted_at' => null,
            'last_updated' => null
        ];
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching application data: " . $e->getMessage());
}

// Get section completion status
try {
    if (!empty($data['application']['id'])) {
        $query = "SELECT 
                    section_id, 
                    section_name, 
                    is_completed, 
                    updated_at
                  FROM application_sections 
                  WHERE application_id = ? 
                  ORDER BY section_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $data['application']['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data['sections'][] = $row;
            }
        }
        
        $stmt->close();
    }
    
    // If no sections found or no application, create default sections
    if (empty($data['sections'])) {
        $defaultSections = [
            ['section_id' => 1, 'section_name' => 'Basic Information', 'is_completed' => 0, 'updated_at' => null],
            ['section_id' => 2, 'section_name' => 'Parent/Guardian Details', 'is_completed' => 0, 'updated_at' => null],
            ['section_id' => 3, 'section_name' => 'Address Details', 'is_completed' => 0, 'updated_at' => null],
            ['section_id' => 4, 'section_name' => 'Personal Details', 'is_completed' => 0, 'updated_at' => null],
            ['section_id' => 5, 'section_name' => 'Academic Information (10th)', 'is_completed' => 0, 'updated_at' => null],
            ['section_id' => 6, 'section_name' => 'Academic Information (12th)', 'is_completed' => 0, 'updated_at' => null],
            ['section_id' => 7, 'section_name' => 'Other Qualifications', 'is_completed' => 0, 'updated_at' => null],
            ['section_id' => 8, 'section_name' => 'Documents Upload', 'is_completed' => 0, 'updated_at' => null],
            ['section_id' => 9, 'section_name' => 'Declaration', 'is_completed' => 0, 'updated_at' => null]
        ];
        $data['sections'] = $defaultSections;
    }
} catch (Exception $e) {
    error_log("Error fetching section data: " . $e->getMessage());
}

// Get important dates
try {
    $query = "SELECT 
                id, 
                title, 
                event_date, 
                description
              FROM important_dates 
              WHERE event_date >= CURDATE() 
              ORDER BY event_date ASC 
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['important_dates'][] = $row;
        }
    }
    
    $stmt->close();
    
    // If no dates found, create default dates
    if (empty($data['important_dates'])) {
        $defaultDates = [
            ['id' => 1, 'title' => 'Application Deadline', 'event_date' => '2023-07-15', 'description' => 'Last date to submit applications'],
            ['id' => 2, 'title' => 'Document Verification', 'event_date' => '2023-07-20', 'description' => 'Verification of submitted documents'],
            ['id' => 3, 'title' => 'Entrance Examination', 'event_date' => '2023-07-30', 'description' => 'Entrance test for all applicants'],
            ['id' => 4, 'title' => 'Results Declaration', 'event_date' => '2023-08-10', 'description' => 'Announcement of selected candidates'],
            ['id' => 5, 'title' => 'Commencement of Session', 'event_date' => '2023-09-01', 'description' => 'Start of the academic session']
        ];
        $data['important_dates'] = $defaultDates;
    }
} catch (Exception $e) {
    error_log("Error fetching important dates: " . $e->getMessage());
}

// Get notifications
try {
    $query = "SELECT 
                id, 
                title, 
                message, 
                is_read, 
                created_at
              FROM notifications 
              WHERE user_id = ? 
              ORDER BY created_at DESC 
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['notifications'][] = $row;
        }
    }
    
    $stmt->close();
    
    // If no notifications found, create a default welcome notification
    if (empty($data['notifications'])) {
        $welcomeMsg = "Welcome to the Regional Institute of Nursing Admission Portal. This is your dashboard where you can track your application status, view important dates, and receive notifications.";
        $data['notifications'][] = [
            'id' => 0,
            'title' => 'Welcome to RIN Admission Portal',
            'message' => $welcomeMsg,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
}

// Return as PHP data, not JSON
return $data; 
 
 
 
 
 
 