<?php
// Start session
session_start();

// Database connection
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

// Get user ID from session
$userId = $_SESSION['user_id'];

// Function to get user profile data
function getUserProfile($userId, $conn) {
    $profile = [
        'id' => $userId,
        'name' => '',
        'email' => '',
        'phone' => '',
        'created_at' => '',
        'status' => ''
    ];
    
    try {
        $query = "SELECT first_name, last_name, email, phone, created_at, status FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            $profile['name'] = $user['first_name'] . ' ' . $user['last_name'];
            $profile['email'] = $user['email'];
            $profile['phone'] = $user['phone'];
            $profile['created_at'] = $user['created_at'];
            $profile['status'] = $user['status'];
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Log error
    }
    
    return $profile;
}

// Function to get application data
function getApplicationData($userId, $conn) {
    $application = [
        'id' => null,
        'progress' => 0,
        'application_status' => 'not_started',
        'last_updated' => null,
        'form_sections' => []
    ];
    
    try {
        // Check if application exists
        $query = "SELECT id, application_id, status, progress, last_updated FROM applications WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $app = $result->fetch_assoc();
            
            $application['id'] = $app['id'];
            $application['application_id'] = $app['application_id'];
            $application['application_status'] = $app['status'];
            $application['progress'] = $app['progress'];
            $application['last_updated'] = $app['last_updated'];
            
            // Get form section statuses
            $sectionsQuery = "SELECT section_id, section_name, is_completed, updated_at 
                             FROM application_sections 
                             WHERE application_id = ? 
                             ORDER BY section_id";
                             
            $sectionsStmt = $conn->prepare($sectionsQuery);
            $sectionsStmt->bind_param("i", $app['id']);
            $sectionsStmt->execute();
            $sectionsResult = $sectionsStmt->get_result();
            
            while ($section = $sectionsResult->fetch_assoc()) {
                $application['form_sections'][] = [
                    'id' => $section['section_id'],
                    'name' => $section['section_name'],
                    'completed' => (bool)$section['is_completed'],
                    'updated_at' => $section['updated_at']
                ];
            }
            
            $sectionsStmt->close();
        } else {
            // No application yet, create default form sections
            $application['form_sections'] = [
                ['id' => 1, 'name' => 'Basic Information', 'completed' => false, 'updated_at' => null],
                ['id' => 2, 'name' => 'Parent/Guardian Details', 'completed' => false, 'updated_at' => null],
                ['id' => 3, 'name' => 'Address Details', 'completed' => false, 'updated_at' => null],
                ['id' => 4, 'name' => 'Personal Details', 'completed' => false, 'updated_at' => null],
                ['id' => 5, 'name' => 'Academic Information (10th)', 'completed' => false, 'updated_at' => null],
                ['id' => 6, 'name' => 'Academic Information (12th)', 'completed' => false, 'updated_at' => null],
                ['id' => 7, 'name' => 'Other Qualifications', 'completed' => false, 'updated_at' => null],
                ['id' => 8, 'name' => 'Documents Upload', 'completed' => false, 'updated_at' => null],
                ['id' => 9, 'name' => 'Declaration', 'completed' => false, 'updated_at' => null]
            ];
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Log error
    }
    
    return $application;
}

// Function to get notifications
function getNotifications($userId, $conn) {
    $notifications = [];
    
    try {
        $query = "SELECT id, title, message, created_at, is_read 
                 FROM notifications 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 10";
                 
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($notification = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'date' => $notification['created_at'],
                'read' => (bool)$notification['is_read']
            ];
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Log error
    }
    
    return $notifications;
}

// Function to get important dates
function getImportantDates($conn) {
    $dates = [];
    
    try {
        $query = "SELECT id, title, event_date, description 
                 FROM important_dates 
                 WHERE event_date >= CURDATE() 
                 ORDER BY event_date ASC 
                 LIMIT 5";
                 
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($date = $result->fetch_assoc()) {
            $dates[] = [
                'id' => $date['id'],
                'title' => $date['title'],
                'date' => $date['event_date'],
                'description' => $date['description']
            ];
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Log error
    }
    
    // If no dates found in database, return some default dates
    if (empty($dates)) {
        $dates = [
            [
                'id' => 1,
                'title' => 'Application Deadline',
                'date' => '2023-07-15',
                'description' => 'Last date to submit applications'
            ],
            [
                'id' => 2,
                'title' => 'Document Verification',
                'date' => '2023-07-20',
                'description' => 'Verification of submitted documents'
            ],
            [
                'id' => 3,
                'title' => 'Entrance Examination',
                'date' => '2023-07-30',
                'description' => 'Entrance test for all applicants'
            ],
            [
                'id' => 4,
                'title' => 'Results Declaration',
                'date' => '2023-08-10',
                'description' => 'Announcement of selected candidates'
            ],
            [
                'id' => 5,
                'title' => 'Commencement of Session',
                'date' => '2023-09-01',
                'description' => 'Start of the academic session'
            ]
        ];
    }
    
    return $dates;
}

// Get all dashboard data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userData = getUserProfile($userId, $conn);
    $applicationData = getApplicationData($userId, $conn);
    $notificationsData = getNotifications($userId, $conn);
    $importantDates = getImportantDates($conn);
    
    $response['success'] = true;
    $response['data'] = [
        'user' => $userData,
        'application' => $applicationData,
        'notifications' => $notificationsData,
        'important_dates' => $importantDates
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Update application section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_section') {
    $sectionId = $_POST['section_id'] ?? 0;
    $isCompleted = isset($_POST['is_completed']) ? (bool)$_POST['is_completed'] : false;
    
    if ($sectionId > 0) {
        try {
            // Check if application exists
            $checkQuery = "SELECT id FROM applications WHERE user_id = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("i", $userId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            $applicationId = null;
            
            if ($checkResult->num_rows === 0) {
                // Create new application
                $createQuery = "INSERT INTO applications (user_id, status, progress, last_updated) 
                               VALUES (?, 'in_progress', 0, NOW())";
                $createStmt = $conn->prepare($createQuery);
                $createStmt->bind_param("i", $userId);
                
                if ($createStmt->execute()) {
                    $applicationId = $conn->insert_id;
                    
                    // Create application ID (format: RIN-2023-00001)
                    $year = date('Y');
                    $paddedId = str_pad($applicationId, 5, '0', STR_PAD_LEFT);
                    $applicationIdString = "RIN-{$year}-{$paddedId}";
                    
                    // Update application with application_id
                    $updateAppIdQuery = "UPDATE applications SET application_id = ? WHERE id = ?";
                    $updateAppIdStmt = $conn->prepare($updateAppIdQuery);
                    $updateAppIdStmt->bind_param("si", $applicationIdString, $applicationId);
                    $updateAppIdStmt->execute();
                    $updateAppIdStmt->close();
                    
                    // Create default sections
                    $sectionNames = [
                        1 => 'Basic Information',
                        2 => 'Parent/Guardian Details',
                        3 => 'Address Details',
                        4 => 'Personal Details',
                        5 => 'Academic Information (10th)',
                        6 => 'Academic Information (12th)',
                        7 => 'Other Qualifications',
                        8 => 'Documents Upload',
                        9 => 'Declaration'
                    ];
                    
                    foreach ($sectionNames as $secId => $secName) {
                        $insertSectionQuery = "INSERT INTO application_sections 
                                              (application_id, section_id, section_name, is_completed) 
                                              VALUES (?, ?, ?, 0)";
                        $insertSectionStmt = $conn->prepare($insertSectionQuery);
                        $insertSectionStmt->bind_param("iis", $applicationId, $secId, $secName);
                        $insertSectionStmt->execute();
                        $insertSectionStmt->close();
                    }
                }
                
                $createStmt->close();
            } else {
                // Get existing application ID
                $applicationId = $checkResult->fetch_assoc()['id'];
            }
            
            $checkStmt->close();
            
            if ($applicationId) {
                // Update section status
                $updateQuery = "UPDATE application_sections 
                               SET is_completed = ?, updated_at = NOW() 
                               WHERE application_id = ? AND section_id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $completed = $isCompleted ? 1 : 0;
                $updateStmt->bind_param("iii", $completed, $applicationId, $sectionId);
                
                if ($updateStmt->execute()) {
                    // Update application progress
                    $progressQuery = "SELECT COUNT(*) as completed, 
                                     (SELECT COUNT(*) FROM application_sections WHERE application_id = ?) as total 
                                     FROM application_sections 
                                     WHERE application_id = ? AND is_completed = 1";
                    $progressStmt = $conn->prepare($progressQuery);
                    $progressStmt->bind_param("ii", $applicationId, $applicationId);
                    $progressStmt->execute();
                    $progressResult = $progressStmt->get_result();
                    $progressData = $progressResult->fetch_assoc();
                    
                    $completed = $progressData['completed'];
                    $total = $progressData['total'];
                    $progressPercentage = ($total > 0) ? round(($completed / $total) * 100) : 0;
                    
                    $updateAppQuery = "UPDATE applications 
                                      SET progress = ?, last_updated = NOW() 
                                      WHERE id = ?";
                    $updateAppStmt = $conn->prepare($updateAppQuery);
                    $updateAppStmt->bind_param("ii", $progressPercentage, $applicationId);
                    $updateAppStmt->execute();
                    $updateAppStmt->close();
                    
                    $progressStmt->close();
                    
                    $response['success'] = true;
                    $response['message'] = 'Section updated successfully';
                }
                
                $updateStmt->close();
            }
        } catch (Exception $e) {
            $response['message'] = 'Error updating section: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid section ID';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Default response for other requests
header('Content-Type: application/json');
echo json_encode($response);
exit;
?> 
 
 
 
 