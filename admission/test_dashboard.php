<?php
/**
 * Test Dashboard Data Insertion
 * 
 * This script creates sample data in the database for testing the dashboard.
 * It also creates a fake session to simulate a logged-in user.
 */

// Start session
session_start();

// Include database connection
require_once 'includes/db_connect.php';

// Set content type to plain text for debugging
header('Content-Type: text/plain');

echo "Test Dashboard Data Insertion\n";
echo "=============================\n\n";

// Function to create a test user if it doesn't exist
function createTestUser($conn) {
    $email = 'test@example.com';
    
    // Check if user exists
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "Test user already exists with ID: {$user['id']}\n";
        return $user['id'];
    }
    
    // Create new user
    $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
    $query = "INSERT INTO users (first_name, last_name, email, phone, password, status, verified_at, last_login) 
             VALUES (?, ?, ?, ?, ?, 'verified', NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    $firstName = 'Test';
    $lastName = 'User';
    $phone = '9876543210';
    
    $stmt->bind_param("sssss", $firstName, $lastName, $email, $phone, $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        echo "Created test user with ID: $userId\n";
        return $userId;
    } else {
        echo "Error creating test user: " . $stmt->error . "\n";
        return false;
    }
}

// Function to create test application for user
function createTestApplication($conn, $userId) {
    // Check if application exists
    $query = "SELECT id FROM applications WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $app = $result->fetch_assoc();
        echo "Test application already exists with ID: {$app['id']}\n";
        return $app['id'];
    }
    
    // Create new application
    $query = "INSERT INTO applications (user_id, application_id, status, progress, created_at, last_updated) 
             VALUES (?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    $applicationId = 'RIN-2023-' . str_pad($userId, 5, '0', STR_PAD_LEFT);
    $status = 'in_progress';
    $progress = 40;
    
    $stmt->bind_param("issi", $userId, $applicationId, $status, $progress);
    
    if ($stmt->execute()) {
        $appId = $conn->insert_id;
        echo "Created test application with ID: $appId\n";
        return $appId;
    } else {
        echo "Error creating test application: " . $stmt->error . "\n";
        return false;
    }
}

// Function to create test application sections
function createTestSections($conn, $appId) {
    // Check if sections exist
    $query = "SELECT id FROM application_sections WHERE application_id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "Test sections already exist for application ID: $appId\n";
        return true;
    }
    
    // Define sections
    $sections = [
        ['section_id' => 1, 'section_name' => 'Basic Information', 'is_completed' => 1],
        ['section_id' => 2, 'section_name' => 'Parent/Guardian Details', 'is_completed' => 1],
        ['section_id' => 3, 'section_name' => 'Address Details', 'is_completed' => 1],
        ['section_id' => 4, 'section_name' => 'Personal Details', 'is_completed' => 0],
        ['section_id' => 5, 'section_name' => 'Academic Information (10th)', 'is_completed' => 0],
        ['section_id' => 6, 'section_name' => 'Academic Information (12th)', 'is_completed' => 0],
        ['section_id' => 7, 'section_name' => 'Other Qualifications', 'is_completed' => 0],
        ['section_id' => 8, 'section_name' => 'Documents Upload', 'is_completed' => 0],
        ['section_id' => 9, 'section_name' => 'Declaration', 'is_completed' => 0]
    ];
    
    // Insert sections
    $query = "INSERT INTO application_sections (application_id, section_id, section_name, is_completed, updated_at) 
             VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    
    foreach ($sections as $section) {
        $updatedAt = $section['is_completed'] ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param("iisss", $appId, $section['section_id'], $section['section_name'], $section['is_completed'], $updatedAt);
        
        if (!$stmt->execute()) {
            echo "Error creating section {$section['section_name']}: " . $stmt->error . "\n";
        }
    }
    
    echo "Created test sections for application ID: $appId\n";
    return true;
}

// Function to create test important dates
function createTestDates($conn) {
    // Check if dates exist
    $query = "SELECT id FROM important_dates LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "Test important dates already exist\n";
        return true;
    }
    
    // Define dates
    $dates = [
        ['title' => 'Application Deadline', 'event_date' => '2023-07-15', 'description' => 'Last date to submit applications'],
        ['title' => 'Document Verification', 'event_date' => '2023-07-20', 'description' => 'Verification of submitted documents'],
        ['title' => 'Entrance Examination', 'event_date' => '2023-07-30', 'description' => 'Entrance test for all applicants'],
        ['title' => 'Results Declaration', 'event_date' => '2023-08-10', 'description' => 'Announcement of selected candidates'],
        ['title' => 'Commencement of Session', 'event_date' => '2023-09-01', 'description' => 'Start of the academic session']
    ];
    
    // Insert dates
    $query = "INSERT INTO important_dates (title, event_date, description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    foreach ($dates as $date) {
        $stmt->bind_param("sss", $date['title'], $date['event_date'], $date['description']);
        
        if (!$stmt->execute()) {
            echo "Error creating date {$date['title']}: " . $stmt->error . "\n";
        }
    }
    
    echo "Created test important dates\n";
    return true;
}

// Function to create test notifications for user
function createTestNotifications($conn, $userId) {
    // Check if notifications exist
    $query = "SELECT id FROM notifications WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "Test notifications already exist for user ID: $userId\n";
        return true;
    }
    
    // Define notifications
    $notifications = [
        [
            'title' => 'Welcome to RIN Admission Portal',
            'message' => 'Welcome to the Regional Institute of Nursing Admission Portal. This is your dashboard where you can track your application status, view important dates, and receive notifications.',
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ],
        [
            'title' => 'Application Started',
            'message' => 'You have successfully started your application. Please complete all sections to submit your application.',
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'title' => 'Document Upload Reminder',
            'message' => 'Please remember to upload all required documents for your application. This is an important step in the admission process.',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
    
    // Insert notifications
    $query = "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    foreach ($notifications as $notification) {
        $stmt->bind_param("issis", $userId, $notification['title'], $notification['message'], $notification['is_read'], $notification['created_at']);
        
        if (!$stmt->execute()) {
            echo "Error creating notification {$notification['title']}: " . $stmt->error . "\n";
        }
    }
    
    echo "Created test notifications for user ID: $userId\n";
    return true;
}

// Execute all test data functions
try {
    // Create test user
    $userId = createTestUser($conn);
    
    if ($userId) {
        // Create application
        $appId = createTestApplication($conn, $userId);
        
        if ($appId) {
            // Create application sections
            createTestSections($conn, $appId);
        }
        
        // Create important dates
        createTestDates($conn);
        
        // Create notifications
        createTestNotifications($conn, $userId);
        
        // Create fake session for testing
        $_SESSION['user_id'] = $userId;
        $_SESSION['logged_in'] = true;
        $_SESSION['user_name'] = 'Test User';
        $_SESSION['user_email'] = 'test@example.com';
        
        echo "\nCreated test session with user ID: $userId\n";
        echo "You can now visit the dashboard: <a href='dashboard.php'>Go to Dashboard</a>\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Close database connection
$conn->close();
?> 
 
 
 
 
 
 