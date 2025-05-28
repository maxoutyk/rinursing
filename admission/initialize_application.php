<?php
/**
 * Application Initialization Endpoint
 * Creates a new application entry for the user
 */

// Include authentication requirement check
require_once 'includes/required_auth.php';

// Database connection
require_once 'includes/db_connect.php';

// Get user ID from session
$userId = $_SESSION['user_id'];

// Response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Check if user already has an application
    $query = "SELECT id FROM applications WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $response['message'] = 'Application already exists';
        echo json_encode($response);
        exit;
    }
    
    // Generate application ID (Year + Random 6 digits)
    $year = date('Y');
    $randomNum = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    $applicationId = $year . $randomNum;
    
    // Start transaction
    $conn->begin_transaction();
    
    // Create new application
    $query = "INSERT INTO applications (user_id, application_id, status, progress, created_at, last_updated) 
              VALUES (?, ?, 'draft', 0, NOW(), NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $userId, $applicationId);
    $stmt->execute();
    
    $applicationDbId = $conn->insert_id;
    
    // Initialize application sections
    $sections = [
        ['Basic Information', 0],
        ['Parent/Guardian Details', 0],
        ['Address Details', 0],
        ['Personal Details', 0],
        ['Educational Background', 0],
        ['Documents', 0],
        ['Declaration', 0]
    ];
    
    $query = "INSERT INTO application_sections (application_id, section_id, section_name, is_completed) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    foreach ($sections as $index => $section) {
        $sectionId = $index + 1;
        $stmt->bind_param("iisi", $applicationDbId, $sectionId, $section[0], $section[1]);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = 'Application initialized successfully';
    $response['data'] = [
        'id' => $applicationDbId,
        'application_id' => $applicationId
    ];
    
} catch (Exception $e) {
    // Rollback on error
    if ($conn) {
        $conn->rollback();
    }
    $response['message'] = 'Error initializing application';
    error_log('Error initializing application: ' . $e->getMessage());
}

// Send response
header('Content-Type: application/json');
echo json_encode($response); 