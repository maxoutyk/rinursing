<?php
/**
 * Save Form Step
 * 
 * This script handles AJAX requests to save form data for each step of the admission application.
 * It validates the data, saves it to the database, and returns success/error responses.
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
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Check if step data is provided
if (!isset($_POST['step']) || !isset($_POST['data'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

$step = $_POST['step'];
$data = $_POST['data'];

// Parse JSON data
$parsedData = json_decode($data, true);
if ($parsedData === null && json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg() . " - Raw data: " . $data);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

// Log received data for debugging
error_log("Form data received for step $step: " . print_r($parsedData, true));

// Get or create application record
$applicationId = getOrCreateApplication($conn, $userId);

if (!$applicationId) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to create or retrieve application']);
    exit;
}

// Process the step data
$response = processStepData($conn, $applicationId, $step, $parsedData);

// Return response
header('Content-Type: application/json');
echo json_encode($response);
exit;

/**
 * Get existing application or create a new one for the user
 */
function getOrCreateApplication($conn, $userId) {
    // First check if user already has an application
    $query = "SELECT id, application_id FROM applications WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        error_log("Found existing application for user $userId: ID " . $row['id'] . ", Application ID: " . $row['application_id']);
        return $row['id'];
    }
    
    // No existing application, create a new one
    error_log("No existing application found for user $userId. Creating new application.");
    
    // Generate a unique application ID
    $year = date('Y');
    $query = "SELECT COUNT(*) as count FROM applications WHERE application_id LIKE ?";
    $stmt = $conn->prepare($query);
    $pattern = "RIN-$year-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'] + 1;
    $applicationId = 'RIN-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    
    // Create new application
    $query = "INSERT INTO applications (user_id, application_id, status, progress, created_at, last_updated) 
              VALUES (?, ?, 'in_progress', 0, NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $userId, $applicationId);
    
    if ($stmt->execute()) {
        $newAppId = $conn->insert_id;
        error_log("Created new application for user $userId: ID $newAppId, Application ID: $applicationId");
        
        // Create default sections
        createDefaultSections($conn, $newAppId);
        
        return $newAppId;
    } else {
        error_log("Failed to create application for user $userId: " . $stmt->error);
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
 * Process form data based on step
 */
function processStepData($conn, $applicationId, $step, $data) {
    error_log("Processing step $step with application ID: $applicationId");
    
    switch ($step) {
        case '1': // Basic Information
            error_log("Calling saveBasicInformation");
            error_log("Form data received for step $step: " . print_r($data, true));

            return saveBasicInformation($conn, $applicationId, $data);
        case '2': // Parent/Guardian Details
            error_log("Calling saveParentGuardian");
            return saveParentGuardian($conn, $applicationId, $data);
        case '3': // Address Details
            error_log("Calling saveAddresses");
            return saveAddresses($conn, $applicationId, $data);
        case '4': // Personal Details
            error_log("Calling savePersonalDetails");
            return savePersonalDetails($conn, $applicationId, $data);
        case '5': // Academic Information (10th)
            return saveEducation($conn, $applicationId, $data, '10th');
        case '6': // Academic Information (12th)
            return saveEducation($conn, $applicationId, $data, '12th');
        case '7': // Other Qualifications
            return saveEducation($conn, $applicationId, $data, 'other');
        case '8': // Documents Upload
            return saveDocuments($conn, $applicationId, $data);
        case '9': // Declaration
            return saveDeclaration($conn, $applicationId, $data);
        default:
            return ['status' => 'error', 'message' => 'Invalid step'];
    }
}

/**
 * Validate and save basic information
 */
function saveBasicInformation($conn, $applicationId, $data) {
    // Validate required fields
    $required = ['dob', 'sex'];
    error_log("Form data received for basic information: " . print_r($data, true));

    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            error_log("Basic Info validation failed: Field '$field' is missing or empty.");
            return ['status' => 'error', 'message' => 'Missing required field: ' . $field];
        }
    }
    
    // Log the data being processed
    error_log("Processing basic information: " . print_r($data, true));
    
    // Get user ID from application
    $query = "SELECT user_id FROM applications WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_id = $result->fetch_assoc()['user_id'];
    
    // Prepare full name
    $fullName = '';
    if (isset($data['fullName']) && !empty($data['fullName'])) {
        $fullName = $data['fullName'];
        
        // Also update the users table for consistency
        $nameParts = explode(' ', $data['fullName'], 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        
        $query = "UPDATE users SET first_name = ?, last_name = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $firstName, $lastName, $user_id);
        $stmt->execute();
    }
    
    // Check if personal details record already exists
    $query = "SELECT id FROM personal_details WHERE application_id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        // Update existing record
        error_log("Updating existing personal details for application ID: $applicationId");
        $query = "UPDATE personal_details SET full_name = ?, gender = ?, dob = ? WHERE application_id = ?";
        $stmt = $conn->prepare($query);
        $gender = strtolower($data['sex']); // Converting to lowercase to match enum values
        $dob = $data['dob'];
        
        $stmt->bind_param("sssi", $fullName, $gender, $dob, $applicationId);
    } else {
        // Insert new record
        error_log("Creating new personal details for application ID: $applicationId");
        $query = "INSERT INTO personal_details (application_id, full_name, gender, dob) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $gender = strtolower($data['sex']); // Converting to lowercase to match enum values
        $dob = $data['dob'];
        
        $stmt->bind_param("isss", $applicationId, $fullName, $gender, $dob);
    }
    
    if (!$stmt->execute()) {
        return ['status' => 'error', 'message' => 'Failed to save basic information: ' . $stmt->error];
    }
    
    // Mark this section as completed
    updateSectionStatus($conn, $applicationId, 1);
    
    // Update application progress
    updateApplicationProgress($conn, $applicationId);
    
    return ['status' => 'success', 'message' => 'Basic information saved successfully'];
}

/**
 * Validate and save parent/guardian details
 */
function saveParentGuardian($conn, $applicationId, $data) {
    // Validate required fields for parents
    $requiredParents = ['fatherName', 'fatherOccupation', 'fatherMobile', 'motherName', 'motherOccupation', 'motherMobile'];
    foreach ($requiredParents as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return ['status' => 'error', 'message' => 'Missing required field: ' . $field];
        }
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Save father's details
        $query = "INSERT INTO guardians (application_id, relationship, name, occupation, phone, email) 
                  VALUES (?, 'father', ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE name = VALUES(name), occupation = VALUES(occupation), 
                                          phone = VALUES(phone), email = VALUES(email)";
        
        $stmt = $conn->prepare($query);
        $fatherEmail = $data['fatherEmail'] ?? '';
        
        $stmt->bind_param("issss", $applicationId, $data['fatherName'], $data['fatherOccupation'], 
                         $data['fatherMobile'], $fatherEmail);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save father details: ' . $stmt->error);
        }
        
        // Save mother's details
        $query = "INSERT INTO guardians (application_id, relationship, name, occupation, phone, email) 
                  VALUES (?, 'mother', ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE name = VALUES(name), occupation = VALUES(occupation), 
                                          phone = VALUES(phone), email = VALUES(email)";
        
        $stmt = $conn->prepare($query);
        $motherEmail = $data['motherEmail'] ?? '';
        
        $stmt->bind_param("issss", $applicationId, $data['motherName'], $data['motherOccupation'], 
                         $data['motherMobile'], $motherEmail);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save mother details: ' . $stmt->error);
        }
        
        // Save guardian's details if provided
        if (isset($data['guardianName']) && !empty($data['guardianName'])) {
            $query = "INSERT INTO guardians (application_id, relationship, name, occupation, phone, email) 
                      VALUES (?, 'guardian', ?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE name = VALUES(name), occupation = VALUES(occupation), 
                                              phone = VALUES(phone), email = VALUES(email)";
            
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
        
        // Mark this section as completed
        updateSectionStatus($conn, $applicationId, 2);
        
        // Update application progress
        updateApplicationProgress($conn, $applicationId);
        
        // Commit transaction
        $conn->commit();
        
        return ['status' => 'success', 'message' => 'Parent/Guardian details saved successfully'];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Validate and save address details
 */
function saveAddresses($conn, $applicationId, $data) {
    // Validate required fields
    $required = ['permanentAddress', 'presentAddress'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return ['status' => 'error', 'message' => 'Missing required field: ' . $field];
        }
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Save permanent address
        $query = "INSERT INTO addresses (application_id, type, address_line1) 
                  VALUES (?, 'permanent', ?) 
                  ON DUPLICATE KEY UPDATE address_line1 = VALUES(address_line1)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $applicationId, $data['permanentAddress']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save permanent address: ' . $stmt->error);
        }
        
        // Save present address
        $query = "INSERT INTO addresses (application_id, type, address_line1) 
                  VALUES (?, 'present', ?) 
                  ON DUPLICATE KEY UPDATE address_line1 = VALUES(address_line1)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $applicationId, $data['presentAddress']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save present address: ' . $stmt->error);
        }
        
        // Mark this section as completed
        updateSectionStatus($conn, $applicationId, 3);
        
        // Update application progress
        updateApplicationProgress($conn, $applicationId);
        
        // Commit transaction
        $conn->commit();
        
        return ['status' => 'success', 'message' => 'Address details saved successfully'];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Validate and save personal details
 */
function savePersonalDetails($conn, $applicationId, $data) {
    // Validate required fields
    $required = ['nationality', 'religion', 'caste', 'maritalStatus', 'motherTongue', 'annualIncome'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return ['status' => 'error', 'message' => 'Missing required field: ' . $field];
        }
    }
    
    // Update personal_details table
    $query = "UPDATE personal_details 
              SET nationality = ?, religion = ?, category = ?, marital_status = ?, 
                  mother_tongue = ?, annual_income = ?
              WHERE application_id = ?";
    
    $stmt = $conn->prepare($query);
    $maritalStatus = strtolower($data['maritalStatus']); // Convert to lowercase to match enum values
    $motherTongue = $data['motherTongue'];
    $annualIncome = floatval(str_replace(['₹', ','], '', $data['annualIncome'])); // Remove currency symbol and commas
    
    $stmt->bind_param("sssssdi", $data['nationality'], $data['religion'], $data['caste'], 
                     $maritalStatus, $motherTongue, $annualIncome, $applicationId);
    
    if (!$stmt->execute()) {
        return ['status' => 'error', 'message' => 'Failed to save personal details: ' . $stmt->error];
    }
    
    // Mark this section as completed
    updateSectionStatus($conn, $applicationId, 4);
    
    // Update application progress
    updateApplicationProgress($conn, $applicationId);
    
    return ['status' => 'success', 'message' => 'Personal details saved successfully'];
}

/**
 * Validate and save education details
 */
function saveEducation($conn, $applicationId, $data, $level) {
    $requiredFields = [];
    
    if ($level === '10th') {
        $requiredFields = ['schoolName10th', 'board10th', 'totalMarks10th', 'marksObtained10th', 
                          'percentage10th', 'yearOfPassing10th', 'mode10th'];
    } elseif ($level === '12th') {
        $requiredFields = ['schoolName12th', 'board12th', 'totalMarks12th', 
                          'marksObtained12th', 'percentage12th', 'yearOfPassing12th', 'mode12th'];
    } elseif ($level === 'other') {
        $requiredFields = ['otherQualification', 'schoolNameOther', 'otherQualificationBoard', 
                          'totalMarksOther', 'marksObtainedOther', 
                          'percentageOther', 'otherQualificationYear', 'modeOther'];
    }
    
    // Validate required fields
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return ['status' => 'error', 'message' => 'Missing required field: ' . $field];
        }
    }
    
    // Prepare data
    if ($level === '10th') {
        $school = $data['schoolName10th'];
        $board = $data['board10th'];
        $percentage = $data['percentage10th'];
        $yearOfPassing = $data['yearOfPassing10th'];
        $totalMarks = intval($data['totalMarks10th']);
        $marksObtained = intval($data['marksObtained10th']);
        $mode = $data['mode10th'];
        $remarks = $data['remarks10th'] ?? '';
        $subjects = '';
        $sectionId = 5;
        $qualificationName = $level;
    } elseif ($level === '12th') {
        $school = $data['schoolName12th'];
        $board = $data['board12th'];
        $percentage = $data['percentage12th'];
        $yearOfPassing = $data['yearOfPassing12th'];
        $totalMarks = intval($data['totalMarks12th']);
        $marksObtained = intval($data['marksObtained12th']);
        $mode = $data['mode12th'];
        $remarks = $data['remarks12th'] ?? '';
        $subjects = $data['subjects12th'] ?? ''; // Store subjects field value
        $sectionId = 6;
        $qualificationName = $level;

    } elseif ($level === 'other') {
        // For other qualification, save the actual qualification name in the qualification_name field
        $actualQualificationName = $data['otherQualification'];
        $school = $data['schoolNameOther'] ?? '';
        $board = $data['otherQualificationBoard'] ?? ''; // Updated field name
        $percentage = $data['percentageOther'] ?? 0;
        $yearOfPassing = $data['otherQualificationYear'] ?? ''; // Updated field name
        $totalMarks = isset($data['totalMarksOther']) ? intval($data['totalMarksOther']) : 0;
        $marksObtained = isset($data['marksObtainedOther']) ? intval($data['marksObtainedOther']) : 0;
        $mode = $data['modeOther'] ?? '';
        $remarks = $data['remarksOther'] ?? '';
        $subjects = $data['subjectsOther'] ?? '';
        $sectionId = 7;
        
        // We'll use qualification_name to store the specific qualification
        $qualificationName = $actualQualificationName;
        // Keep level as 'other'
    }
    
    // Save to database
    $query = "INSERT INTO education (application_id, level, qualification_name, board_university, school_college, 
                                   year_of_passing, percentage, subjects, total_marks, 
                                   marks_obtained, mode, remarks) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
              ON DUPLICATE KEY UPDATE qualification_name = VALUES(qualification_name),
                                     board_university = VALUES(board_university), 
                                     school_college = VALUES(school_college),
                                     year_of_passing = VALUES(year_of_passing),
                                     percentage = VALUES(percentage),
                                     subjects = VALUES(subjects),
                                     total_marks = VALUES(total_marks),
                                     marks_obtained = VALUES(marks_obtained),
                                     mode = VALUES(mode),
                                     remarks = VALUES(remarks)";
    
    $stmt = $conn->prepare($query);
    
    $stmt->bind_param("issssdssiiss", $applicationId, $level, $qualificationName, $board, $school, 
                    $yearOfPassing, $percentage, $subjects, $totalMarks,
                    $marksObtained, $mode, $remarks);

    if (!$stmt->execute()) {
        return ['status' => 'error', 'message' => 'Failed to save education details: ' . $stmt->error];
    }
    
    // Get the education ID
    $educationId = $stmt->insert_id;
    
    // If education record already existed, get its ID
    if ($educationId == 0) {
        $query = "SELECT id FROM education WHERE application_id = ? AND level = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $applicationId, $level);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $educationId = $row['id'];
        }
    }
    
    // Save subject-wise marks for 12th
    if ($level === '12th') {
        // Check if we have subject data in the form
        $hasSubjectData = false;
        
        // Look for subject-wise marks in form data structure
        if (isset($data['subject12th']) && is_array($data['subject12th'])) {
            $hasSubjectData = true;
        } else {
            // Extract subject data from form array keys
            $subjectNames = [];
            $subjectTotals = [];
            $subjectObtained = [];
            
            foreach ($data as $key => $value) {
                // Check for subject names
                if (preg_match('/^subject12th\[(\d+)\]$/', $key, $matches)) {
                    $index = $matches[1];
                    $subjectNames[$index] = $value;
                }
                
                // Check for subject total marks
                if (preg_match('/^subjectTotal12th\[(\d+)\]$/', $key, $matches)) {
                    $index = $matches[1];
                    $subjectTotals[$index] = $value;
                }
                
                // Check for subject obtained marks
                if (preg_match('/^subjectObtained12th\[(\d+)\]$/', $key, $matches)) {
                    $index = $matches[1];
                    $subjectObtained[$index] = $value;
                }
            }
            
            // If we found any subject data, process it
            if (!empty($subjectNames)) {
                $hasSubjectData = true;
                
                // Restructure the data for processing
                $data['subject12th'] = $subjectNames;
                $data['subjectTotal12th'] = $subjectTotals;
                $data['subjectObtained12th'] = $subjectObtained;
            }
        }
        
        if ($hasSubjectData) {
            // Get existing subject records to map by index position
            $existingSubjects = [];
            $getExistingQuery = "SELECT id, subject_name FROM subject_marks WHERE education_id = ? ORDER BY id ASC";
            $getExistingStmt = $conn->prepare($getExistingQuery);
            $getExistingStmt->bind_param("i", $educationId);
            $getExistingStmt->execute();
            $existingResult = $getExistingStmt->get_result();
            
            // Store existing subject IDs by name for lookup
            $subjectIdMap = [];
            $index = 0;
            while ($row = $existingResult->fetch_assoc()) {
                $existingSubjects[$index] = $row;
                $subjectIdMap[$row['subject_name']] = $row['id'];
                $index++;
            }
            
            // Now insert or update subject records
            $subjectCount = count($data['subject12th']);
            
            for ($i = 0; $i < $subjectCount; $i++) {
                // Skip empty subjects
                if (empty($data['subject12th'][$i])) {
                    continue;
                }
                
                $subjectName = $data['subject12th'][$i];
                $subjectTotal = isset($data['subjectTotal12th'][$i]) ? intval($data['subjectTotal12th'][$i]) : 0;
                $subjectObtained = isset($data['subjectObtained12th'][$i]) ? intval($data['subjectObtained12th'][$i]) : 0;
                $subjectPercentage = 0;
                
                if ($subjectTotal > 0) {
                    $subjectPercentage = round(($subjectObtained / $subjectTotal) * 100, 2);
                }
                
                // Check if we have an existing record for this subject
                if (isset($subjectIdMap[$subjectName])) {
                    // Update existing record
                    $updateQuery = "UPDATE subject_marks SET 
                        total_marks = ?, 
                        marks_obtained = ?, 
                        percentage = ? 
                        WHERE id = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $subjectId = $subjectIdMap[$subjectName];
                    $updateStmt->bind_param("iiid", $subjectTotal, $subjectObtained, $subjectPercentage, $subjectId);
                    $updateStmt->execute();
                } else if (isset($existingSubjects[$i])) {
                    // Update record at this position with new subject name
                    $updateQuery = "UPDATE subject_marks SET 
                        subject_name = ?,
                        total_marks = ?, 
                        marks_obtained = ?, 
                        percentage = ? 
                        WHERE id = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $subjectId = $existingSubjects[$i]['id'];
                    $updateStmt->bind_param("siiid", $subjectName, $subjectTotal, $subjectObtained, $subjectPercentage, $subjectId);
                    $updateStmt->execute();
                } else {
                    // Insert new record
                    $insertQuery = "INSERT INTO subject_marks (education_id, subject_name, total_marks, marks_obtained, percentage) 
                                  VALUES (?, ?, ?, ?, ?)";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->bind_param("isiid", $educationId, $subjectName, $subjectTotal, $subjectObtained, $subjectPercentage);
                    $insertStmt->execute();
                }
            }
            
            // If we have more existing records than new ones, delete the extras
            if (count($existingSubjects) > $subjectCount) {
                $keepIds = [];
                foreach ($existingSubjects as $index => $subject) {
                    if ($index < $subjectCount) {
                        $keepIds[] = $subject['id'];
                    }
                }
                
                if (!empty($keepIds)) {
                    $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
                    $deleteExtraQuery = "DELETE FROM subject_marks WHERE education_id = ? AND id NOT IN ($placeholders)";
                    $deleteExtraStmt = $conn->prepare($deleteExtraQuery);
                    
                    // Create parameter binding
                    $bindParams = [$educationId];
                    foreach ($keepIds as $id) {
                        $bindParams[] = $id;
                    }
                    
                    // Create the type string (i for educationId, i for each keepId)
                    $typeString = 'i' . str_repeat('i', count($keepIds));
                    $deleteExtraStmt->bind_param($typeString, ...$bindParams);
                    $deleteExtraStmt->execute();
                }
            }
        } else {
            error_log("No subject-wise marks found in form data for 12th grade");
        }
    }
    
    // Mark this section as completed
    updateSectionStatus($conn, $applicationId, $sectionId);
    
    // Update application progress
    updateApplicationProgress($conn, $applicationId);
    
    return ['status' => 'success', 'message' => 'Education details saved successfully'];
}

/**
 * Save document upload information
 */
function saveDocuments($conn, $applicationId, $data) {
    // Document uploads should be handled separately via file upload
    // Here we just mark the section as potentially completed if at least one document exists
    
    $query = "SELECT COUNT(*) as doc_count FROM documents WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['doc_count'] > 0) {
        // Mark this section as completed
        updateSectionStatus($conn, $applicationId, 8);
        
        // Update application progress
        updateApplicationProgress($conn, $applicationId);
        
        return ['status' => 'success', 'message' => 'Documents section saved'];
    }
    
    return ['status' => 'error', 'message' => 'No documents uploaded yet'];
}

/**
 * Save declaration information
 */
function saveDeclaration($conn, $applicationId, $data) {
    // Validate agreement
    if (!isset($data['agreeTerms']) || $data['agreeTerms'] !== 'yes') {
        return ['status' => 'error', 'message' => 'You must agree to the terms and conditions'];
    }
    
    $query = "INSERT INTO declarations (application_id, agreed_terms, agreed_authenticity, agreed_rules, place, date) 
              VALUES (?, 1, 1, 1, ?, CURDATE()) 
              ON DUPLICATE KEY UPDATE agreed_terms = 1, agreed_authenticity = 1, agreed_rules = 1, 
                                      place = VALUES(place), date = CURDATE()";
    
    $stmt = $conn->prepare($query);
    $place = $data['place'] ?? '';
    
    $stmt->bind_param("is", $applicationId, $place);
    
    if (!$stmt->execute()) {
        return ['status' => 'error', 'message' => 'Failed to save declaration: ' . $stmt->error];
    }
    
    // Mark this section as completed
    updateSectionStatus($conn, $applicationId, 9);
    
    // Update application progress
    updateApplicationProgress($conn, $applicationId);
    
    // If all sections are complete, mark application as submitted
    checkAndUpdateApplicationStatus($conn, $applicationId);
    
    return ['status' => 'success', 'message' => 'Declaration saved successfully'];
}

/**
 * Update the section status
 */
function updateSectionStatus($conn, $applicationId, $sectionId) {
    $query = "UPDATE application_sections 
              SET is_completed = 1, updated_at = NOW() 
              WHERE application_id = ? AND section_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $applicationId, $sectionId);
    $stmt->execute();
}

/**
 * Calculate and update application progress
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
 * Check if all sections are complete and update application status accordingly
 */
function checkAndUpdateApplicationStatus($conn, $applicationId) {
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
    
    // If all sections are complete, mark application as submitted
    if ($total === $completed && $total > 0) {
        $query = "UPDATE applications 
                  SET status = 'submitted', submitted_at = NOW(), last_updated = NOW() 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        
        // Also create a notification for user
        createSubmissionNotification($conn, $applicationId);
    }
}

/**
 * Create notification for application submission
 */
function createSubmissionNotification($conn, $applicationId) {
    // Get user ID from application
    $query = "SELECT user_id FROM applications WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $userId = $result->fetch_assoc()['user_id'];
        
        // Create notification
        $query = "INSERT INTO notifications (user_id, title, message, is_read, created_at) 
                  VALUES (?, 'Application Submitted', 'Your application has been successfully submitted. You will be notified once it is reviewed.', 0, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
} 