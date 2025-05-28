<?php
/**
 * Load Form Data
 * 
 * This script retrieves existing form data for a specific step via AJAX.
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

// Check if step parameter is provided
if (!isset($_GET['step'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing step parameter']);
    exit;
}

$step = intval($_GET['step']);

// Get application ID
$query = "SELECT id FROM applications WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No application found']);
    exit;
}

$applicationId = $result->fetch_assoc()['id'];

// Return different data based on step
switch ($step) {
    case 1: // Basic Information
        loadBasicInformation($conn, $applicationId);
        break;
    case 2: // Parent/Guardian Details
        loadParentGuardian($conn, $applicationId);
        break;
    case 3: // Address Details
        loadAddresses($conn, $applicationId);
        break;
    case 4: // Personal Details
        loadPersonalDetails($conn, $applicationId);
        break;
    case 5: // Academic Information (10th)
        loadEducation($conn, $applicationId, '10th');
        break;
    case 6: // Academic Information (12th)
        loadEducation($conn, $applicationId, '12th');
        break;
    case 7: // Other Qualifications
        loadEducation($conn, $applicationId, 'other');
        break;
    case 8: // Documents Upload
        loadDocuments($conn, $applicationId);
        break;
    case 9: // Declaration
        loadDeclaration($conn, $applicationId);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid step']);
}

/**
 * Load basic information data
 */
function loadBasicInformation($conn, $applicationId) {
    $query = "SELECT full_name, gender, dob FROM personal_details WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No data found']);
    }
}

/**
 * Load parent/guardian data
 */
function loadParentGuardian($conn, $applicationId) {
    $query = "SELECT relationship, name, occupation, phone, email, relation_to_applicant, address FROM guardians WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['relationship']] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No data found']);
    }
}

/**
 * Load address data
 */
function loadAddresses($conn, $applicationId) {
    $query = "SELECT type, address_line1 FROM addresses WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['type']] = $row['address_line1'];
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No data found']);
    }
}

/**
 * Load personal details data
 */
function loadPersonalDetails($conn, $applicationId) {
    $query = "SELECT nationality, religion, category, marital_status, mother_tongue, annual_income FROM personal_details WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No data found']);
    }
}

/**
 * Load education data for a specific level
 */
function loadEducation($conn, $applicationId, $level) {
    if ($level === 'other') {
        // For 'other' level, we need to find all education records that are not '10th' or '12th'
        $query = "SELECT id, level, qualification_name, board_university, school_college, year_of_passing, percentage, subjects, 
                  total_marks, marks_obtained, mode, remarks 
                  FROM education 
                  WHERE application_id = ? AND level = 'other' 
                  ORDER BY id DESC LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $applicationId);
    } else {
        // For specific levels (10th, 12th), query directly
        $query = "SELECT id, qualification_name, board_university, school_college, year_of_passing, percentage, subjects, 
                  total_marks, marks_obtained, mode, remarks 
                  FROM education WHERE application_id = ? AND level = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $applicationId, $level);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        // Add debug log for other qualification
        if ($level === 'other' && isset($data['qualification_name'])) {
            error_log("Loading other qualification with name: " . $data['qualification_name']);
        }
        
        // For 12th grade, also load subject-wise marks
        if ($level === '12th') {
            $educationId = $data['id'];
            $subjectQuery = "SELECT id, subject_name, total_marks, marks_obtained, percentage 
                            FROM subject_marks WHERE education_id = ? ORDER BY id ASC";
            $subjectStmt = $conn->prepare($subjectQuery);
            $subjectStmt->bind_param("i", $educationId);
            $subjectStmt->execute();
            $subjectResult = $subjectStmt->get_result();
            
            $subjectData = [];
            while ($subjectRow = $subjectResult->fetch_assoc()) {
                // Don't include the ID in the output to the client
                $subjectId = $subjectRow['id'];
                unset($subjectRow['id']);
                $subjectData[] = $subjectRow;
            }
            
            $data['subject_marks'] = $subjectData;
        }
        
        // Remove internal ID from the output
        unset($data['id']);
        
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No data found']);
    }
}

/**
 * Load documents data
 */
function loadDocuments($conn, $applicationId) {
    $query = "SELECT document_type, file_name, file_path FROM documents WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['document_type']] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No data found']);
    }
}

/**
 * Load declaration data
 */
function loadDeclaration($conn, $applicationId) {
    $query = "SELECT agreed_terms, agreed_authenticity, agreed_rules, place, date FROM declarations WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No data found']);
    }
} 
 