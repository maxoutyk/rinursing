<?php
/**
 * Test Form Save Functionality
 * 
 * This script tests saving form data for an application.
 */

// Start session
session_start();

// Set content type to plain text for debugging
header('Content-Type: text/plain');

echo "Test Form Save Functionality\n";
echo "===========================\n\n";

// Include database connection
require_once 'includes/db_connect.php';

// Create a test user if not already exists
$user = createTestUser($conn);

// Create fake session
$_SESSION['user_id'] = $user['id'];
$_SESSION['logged_in'] = true;
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];

// Get or create application
$applicationId = getOrCreateApplication($conn, $user['id']);
echo "Application ID: $applicationId\n\n";

// Test saving basic information
echo "Step 1: Basic Information\n";
echo "-----------------------\n";
$step1Data = [
    'fullName' => 'Test Student',
    'dob' => '2000-01-15',
    'sex' => 'female'
];
$result = saveBasicInformation($conn, $applicationId, $step1Data);
echo "Basic Information saved: " . ($result ? "Success" : "Failed") . "\n\n";

// Test saving parent/guardian information
echo "Step 2: Parent/Guardian Details\n";
echo "----------------------------\n";
$step2Data = [
    'fatherName' => 'Test Father',
    'fatherOccupation' => 'Engineer',
    'fatherMobile' => '9876543210',
    'fatherEmail' => 'father@example.com',
    'motherName' => 'Test Mother',
    'motherOccupation' => 'Doctor',
    'motherMobile' => '9876543211',
    'motherEmail' => 'mother@example.com',
    'guardianName' => 'Test Guardian',
    'guardianOccupation' => 'Teacher',
    'guardianMobile' => '9876543212',
    'guardianEmail' => 'guardian@example.com',
    'guardianAddress' => 'Test Guardian Address'
];
$result = saveParentGuardian($conn, $applicationId, $step2Data);
echo "Parent/Guardian details saved: " . ($result ? "Success" : "Failed") . "\n\n";

// Test saving address information
echo "Step 3: Address Details\n";
echo "--------------------\n";
$step3Data = [
    'permanentAddress' => 'Test Permanent Address, Test City, Test State, 123456',
    'presentAddress' => 'Test Present Address, Test City, Test State, 123456'
];
$result = saveAddresses($conn, $applicationId, $step3Data);
echo "Address details saved: " . ($result ? "Success" : "Failed") . "\n\n";

// Test saving personal details
echo "Step 4: Personal Details\n";
echo "---------------------\n";
$step4Data = [
    'nationality' => 'Indian',
    'religion' => 'Hindu',
    'caste' => 'General',
    'maritalStatus' => 'single',
    'motherTongue' => 'Hindi',
    'annualIncome' => '500000'
];
$result = savePersonalDetails($conn, $applicationId, $step4Data);
echo "Personal details saved: " . ($result ? "Success" : "Failed") . "\n\n";

// Test saving education (10th)
echo "Step 5: Education (10th)\n";
echo "---------------------\n";
$step5Data = [
    'schoolName10th' => 'Test School',
    'board10th' => 'CBSE',
    'totalMarks10th' => '500',
    'marksObtained10th' => '450',
    'percentage10th' => '90',
    'yearOfPassing10th' => '2016',
    'mode10th' => 'Regular'
];
$result = saveEducation($conn, $applicationId, $step5Data, '10th');
echo "Education (10th) saved: " . ($result ? "Success" : "Failed") . "\n\n";

// Print application status
$applicationInfo = getApplicationInfo($conn, $user['id']);
echo "Application Status After Tests\n";
echo "--------------------------\n";
print_r($applicationInfo);
echo "\n";

// Print section completion status
$sectionStatus = getSectionStatus($conn, $applicationId);
echo "Section Completion Status\n";
echo "-----------------------\n";
print_r($sectionStatus);

// Close database connection
$conn->close();

// Helper Functions

/**
 * Create a test user
 */
function createTestUser($conn) {
    $email = 'testform@example.com';
    
    // Check if user exists
    $query = "SELECT id, first_name, last_name, email FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "Using existing test user: {$user['first_name']} {$user['last_name']} ({$user['email']})\n\n";
        return [
            'id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'email' => $user['email']
        ];
    }
    
    // Create new user
    $hashedPassword = password_hash('testpassword', PASSWORD_DEFAULT);
    $query = "INSERT INTO users (first_name, last_name, email, phone, password, status, verified_at, last_login) 
             VALUES (?, ?, ?, ?, ?, 'verified', NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    $firstName = 'TestForm';
    $lastName = 'User';
    $phone = '9876543200';
    
    $stmt->bind_param("sssss", $firstName, $lastName, $email, $phone, $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        echo "Created new test user: $firstName $lastName ($email)\n\n";
        return [
            'id' => $userId,
            'name' => $firstName . ' ' . $lastName,
            'email' => $email
        ];
    } else {
        echo "Error creating test user: " . $stmt->error . "\n\n";
        exit;
    }
}

/**
 * Get or create application
 */
function getOrCreateApplication($conn, $userId) {
    // Check if application exists
    $query = "SELECT id FROM applications WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "Using existing application\n";
        return $row['id'];
    }
    
    // Create new application
    $query = "INSERT INTO applications (user_id, application_id, status, progress, created_at, last_updated) 
              VALUES (?, ?, 'in_progress', 0, NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    $applicationId = 'RIN-' . date('Y') . '-' . str_pad($userId, 5, '0', STR_PAD_LEFT);
    
    $stmt->bind_param("is", $userId, $applicationId);
    
    if ($stmt->execute()) {
        $newAppId = $conn->insert_id;
        
        // Create default sections
        createDefaultSections($conn, $newAppId);
        
        echo "Created new application with ID: $applicationId\n";
        return $newAppId;
    }
    
    return false;
}

/**
 * Create default application sections
 */
function createDefaultSections($conn, $appId) {
    $sections = [
        ['section_id' => 1, 'section_name' => 'Basic Information'],
        ['section_id' => 2, 'section_name' => 'Parent/Guardian Details'],
        ['section_id' => 3, 'section_name' => 'Address Details'],
        ['section_id' => 4, 'section_name' => 'Personal Details'],
        ['section_id' => 5, 'section_name' => 'Academic Information (10th)'],
        ['section_id' => 6, 'section_name' => 'Academic Information (12th)'],
        ['section_id' => 7, 'section_name' => 'Other Qualifications'],
        ['section_id' => 8, 'section_name' => 'Documents Upload'],
        ['section_id' => 9, 'section_name' => 'Declaration']
    ];
    
    $query = "INSERT INTO application_sections (application_id, section_id, section_name, is_completed) 
              VALUES (?, ?, ?, 0)";
    
    $stmt = $conn->prepare($query);
    
    foreach ($sections as $section) {
        $stmt->bind_param("iis", $appId, $section['section_id'], $section['section_name']);
        $stmt->execute();
    }
}

/**
 * Get application info
 */
function getApplicationInfo($conn, $userId) {
    $query = "SELECT * FROM applications WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get section status
 */
function getSectionStatus($conn, $applicationId) {
    $query = "SELECT section_id, section_name, is_completed, updated_at FROM application_sections WHERE application_id = ? ORDER BY section_id";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
    
    return $sections;
}

/**
 * Update section status
 */
function updateSectionStatus($conn, $applicationId, $sectionId) {
    $query = "UPDATE application_sections 
              SET is_completed = 1, updated_at = NOW() 
              WHERE application_id = ? AND section_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $applicationId, $sectionId);
    return $stmt->execute();
}

/**
 * Update application progress
 */
function updateApplicationProgress($conn, $applicationId) {
    // Count total sections
    $query = "SELECT COUNT(*) as total FROM application_sections WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    
    // Count completed sections
    $query = "SELECT COUNT(*) as completed FROM application_sections WHERE application_id = ? AND is_completed = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $completed = $result->fetch_assoc()['completed'];
    
    // Calculate progress percentage
    $progress = ($total > 0) ? round(($completed / $total) * 100) : 0;
    
    // Update application progress
    $query = "UPDATE applications SET progress = ?, last_updated = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $progress, $applicationId);
    $stmt->execute();
    
    return $progress;
}

/**
 * Save basic information
 */
function saveBasicInformation($conn, $applicationId, $data) {
    // Validate required fields
    $required = ['dob', 'sex'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo "Missing required field: $field\n";
            return false;
        }
    }
    
    // Check if personal_details record exists
    $query = "SELECT id FROM personal_details WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        // Update existing record
        $query = "UPDATE personal_details SET gender = ?, dob = ? WHERE application_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $data['sex'], $data['dob'], $applicationId);
    } else {
        // Insert new record
        $query = "INSERT INTO personal_details (application_id, gender, dob) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $applicationId, $data['sex'], $data['dob']);
    }
    
    if (!$stmt->execute()) {
        echo "Database error: " . $stmt->error . "\n";
        return false;
    }
    
    // Mark section as completed
    updateSectionStatus($conn, $applicationId, 1);
    
    // Update application progress
    updateApplicationProgress($conn, $applicationId);
    
    return true;
}

/**
 * Save parent/guardian details
 */
function saveParentGuardian($conn, $applicationId, $data) {
    // Validate required fields
    $required = ['fatherName', 'fatherOccupation', 'fatherMobile', 'motherName', 'motherOccupation', 'motherMobile'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo "Missing required field: $field\n";
            return false;
        }
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete existing guardian records for this application
        $query = "DELETE FROM guardians WHERE application_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        
        // Insert father's details
        $query = "INSERT INTO guardians (application_id, relationship, name, occupation, phone, email) 
                  VALUES (?, 'father', ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issss", $applicationId, $data['fatherName'], $data['fatherOccupation'], 
                         $data['fatherMobile'], $data['fatherEmail']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save father details: ' . $stmt->error);
        }
        
        // Insert mother's details
        $query = "INSERT INTO guardians (application_id, relationship, name, occupation, phone, email) 
                  VALUES (?, 'mother', ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issss", $applicationId, $data['motherName'], $data['motherOccupation'], 
                         $data['motherMobile'], $data['motherEmail']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save mother details: ' . $stmt->error);
        }
        
        // Insert guardian's details if provided
        if (isset($data['guardianName']) && !empty($data['guardianName'])) {
            $query = "INSERT INTO guardians (application_id, relationship, name, occupation, phone, email) 
                      VALUES (?, 'guardian', ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $guardianOccupation = $data['guardianOccupation'] ?? '';
            $guardianMobile = $data['guardianMobile'] ?? '';
            $guardianEmail = $data['guardianEmail'] ?? '';
            
            $stmt->bind_param("issss", $applicationId, $data['guardianName'], $guardianOccupation, 
                             $guardianMobile, $guardianEmail);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to save guardian details: ' . $stmt->error);
            }
        }
        
        // Mark section as completed
        updateSectionStatus($conn, $applicationId, 2);
        
        // Update application progress
        updateApplicationProgress($conn, $applicationId);
        
        // Commit transaction
        $conn->commit();
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Save addresses
 */
function saveAddresses($conn, $applicationId, $data) {
    // Validate required fields
    $required = ['permanentAddress', 'presentAddress'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo "Missing required field: $field\n";
            return false;
        }
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete existing addresses for this application
        $query = "DELETE FROM addresses WHERE application_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        
        // Insert permanent address
        $query = "INSERT INTO addresses (application_id, type, address_line1) VALUES (?, 'permanent', ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $applicationId, $data['permanentAddress']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save permanent address: ' . $stmt->error);
        }
        
        // Insert present address
        $query = "INSERT INTO addresses (application_id, type, address_line1) VALUES (?, 'present', ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $applicationId, $data['presentAddress']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save present address: ' . $stmt->error);
        }
        
        // Mark section as completed
        updateSectionStatus($conn, $applicationId, 3);
        
        // Update application progress
        updateApplicationProgress($conn, $applicationId);
        
        // Commit transaction
        $conn->commit();
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Save personal details
 */
function savePersonalDetails($conn, $applicationId, $data) {
    // Validate required fields
    $required = ['nationality', 'religion', 'caste', 'maritalStatus'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo "Missing required field: $field\n";
            return false;
        }
    }
    
    // Check if personal_details record exists
    $query = "SELECT id FROM personal_details WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        // Update existing record
        $query = "UPDATE personal_details 
                  SET nationality = ?, religion = ?, category = ?, marital_status = ?
                  WHERE application_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $data['nationality'], $data['religion'], $data['caste'], 
                         $data['maritalStatus'], $applicationId);
    } else {
        // Insert new record
        $query = "INSERT INTO personal_details (application_id, nationality, religion, category, marital_status) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issss", $applicationId, $data['nationality'], $data['religion'], 
                         $data['caste'], $data['maritalStatus']);
    }
    
    if (!$stmt->execute()) {
        echo "Database error: " . $stmt->error . "\n";
        return false;
    }
    
    // Mark section as completed
    updateSectionStatus($conn, $applicationId, 4);
    
    // Update application progress
    updateApplicationProgress($conn, $applicationId);
    
    return true;
}

/**
 * Save education details
 */
function saveEducation($conn, $applicationId, $data, $level) {
    // Set required fields based on level
    $requiredFields = [];
    if ($level === '10th') {
        $requiredFields = ['schoolName10th', 'board10th', 'percentage10th', 'yearOfPassing10th'];
        $school = $data['schoolName10th'];
        $board = $data['board10th'];
        $percentage = $data['percentage10th'];
        $yearOfPassing = $data['yearOfPassing10th'];
        $subjects = '';
        $sectionId = 5;
    } elseif ($level === '12th') {
        $requiredFields = ['schoolName12th', 'board12th', 'subjects12th', 'percentage12th', 'yearOfPassing12th'];
        $school = $data['schoolName12th'];
        $board = $data['board12th'];
        $percentage = $data['percentage12th'];
        $yearOfPassing = $data['yearOfPassing12th'];
        $subjects = $data['subjects12th'];
        $sectionId = 6;
    } else { // other
        $requiredFields = ['schoolNameOther', 'boardOther', 'percentageOther', 'yearOfPassingOther'];
        $school = $data['schoolNameOther'];
        $board = $data['boardOther'];
        $percentage = $data['percentageOther'];
        $yearOfPassing = $data['yearOfPassingOther'];
        $subjects = $data['subjectsOther'] ?? '';
        $sectionId = 7;
    }
    
    // Validate required fields
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo "Missing required field: $field\n";
            return false;
        }
    }
    
    // Check if education record exists
    $query = "SELECT id FROM education WHERE application_id = ? AND level = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $applicationId, $level);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        // Update existing record
        $query = "UPDATE education 
                  SET board_university = ?, school_college = ?, year_of_passing = ?, percentage = ?, subjects = ?
                  WHERE application_id = ? AND level = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssdssi", $board, $school, $yearOfPassing, $percentage, $subjects, 
                         $applicationId, $level);
    } else {
        // Insert new record
        $query = "INSERT INTO education (application_id, level, board_university, school_college, 
                                       year_of_passing, percentage, subjects) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssdds", $applicationId, $level, $board, $school, 
                         $yearOfPassing, $percentage, $subjects);
    }
    
    if (!$stmt->execute()) {
        echo "Database error: " . $stmt->error . "\n";
        return false;
    }
    
    // Mark section as completed
    updateSectionStatus($conn, $applicationId, $sectionId);
    
    // Update application progress
    updateApplicationProgress($conn, $applicationId);
    
    return true;
} 
 
 
 
 
 
 