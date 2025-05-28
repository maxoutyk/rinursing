<?php
// Include direct access protection
define('INCLUDED', true);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include authentication check for admin
require_once '../../includes/admin_auth.php';
require_once '../../includes/db_connect.php';

// Set header to return JSON
header('Content-Type: application/json');

// Initialize statements as null
$section_stmt = null;
$details_stmt = null;

try {
    // Check if section ID is provided
    if (!isset($_GET['section_id'])) {
        throw new Exception('Section ID is required');
    }

    $section_id = intval($_GET['section_id']);
    
    // Validate section ID
    if ($section_id <= 0) {
        throw new Exception('Invalid section ID');
    }

    // Begin transaction
    if (!$conn->begin_transaction()) {
        throw new Exception("Failed to start transaction: " . $conn->error);
    }

    // Get section details
    $section_query = "SELECT s.*, a.user_id, a.id as application_id
                     FROM application_sections s
                     JOIN applications a ON s.application_id = a.id
                     WHERE s.id = ?";
    
    $section_stmt = $conn->prepare($section_query);
    if (!$section_stmt) {
        throw new Exception("Error preparing section query: " . $conn->error);
    }

    if (!$section_stmt->bind_param("i", $section_id)) {
        throw new Exception("Error binding section parameters: " . $section_stmt->error);
    }

    if (!$section_stmt->execute()) {
        throw new Exception("Error executing section query: " . $section_stmt->error);
    }

    $section_result = $section_stmt->get_result();
    if (!$section_result) {
        throw new Exception("Error getting section result: " . $section_stmt->error);
    }

    if ($section_result->num_rows === 0) {
        throw new Exception("Section not found for ID: " . $section_id);
    }
    
    $section = $section_result->fetch_assoc();
    if (!$section) {
        throw new Exception("Error fetching section data");
    }

    // Get section-specific details based on section ID
    $fields = [];
    switch ($section['section_id']) {
        case 1: // Personal Details
            $details_query = "SELECT * FROM personal_details WHERE application_id = ?";
            $fields_mapping = [
                'full_name' => 'Full Name',
                'gender' => 'Gender',
                'dob' => 'Date of Birth',
                'nationality' => 'Nationality',
                'aadhaar_number' => 'Aadhaar Number',
                'category' => 'Category',
                'religion' => 'Religion',
                'blood_group' => 'Blood Group',
                'marital_status' => 'Marital Status',
                'mother_tongue' => 'Mother Tongue',
                'annual_income' => 'Annual Income'
            ];
            break;

        case 2: // Guardian Details
            $details_query = "SELECT * FROM guardians WHERE application_id = ?";
            $fields_mapping = [
                'relationship' => 'Relationship',
                'name' => 'Name',
                'occupation' => 'Occupation',
                'annual_income' => 'Annual Income',
                'phone' => 'Phone Number',
                'email' => 'Email'
            ];
            break;

        case 3: // Education Details
            $details_query = "SELECT * FROM education WHERE application_id = ?";
            $fields_mapping = [
                'level' => 'Education Level',
                'board_university' => 'Board/University',
                'school_college' => 'School/College',
                'year_of_passing' => 'Year of Passing',
                'roll_number' => 'Roll Number',
                'registration_number' => 'Registration Number',
                'percentage' => 'Percentage',
                'total_marks' => 'Total Marks',
                'marks_obtained' => 'Marks Obtained',
                'mode' => 'Mode',
                'subjects' => 'Subjects'
            ];
            break;

        case 4: // Address Details
            $details_query = "SELECT * FROM addresses WHERE application_id = ?";
            $fields_mapping = [
                'type' => 'Address Type',
                'address_line1' => 'Address Line 1',
                'address_line2' => 'Address Line 2',
                'city' => 'City',
                'district' => 'District',
                'state' => 'State',
                'pincode' => 'Pincode',
                'country' => 'Country'
            ];
            break;

        case 5: // Documents
            $details_query = "SELECT * FROM documents WHERE application_id = ?";
            $fields_mapping = [
                'document_type' => 'Document Type',
                'file_name' => 'File Name',
                'mime_type' => 'File Type',
                'file_size' => 'File Size',
                'is_verified' => 'Verification Status',
                'verification_note' => 'Verification Note'
            ];
            break;

        case 6: // Declaration
            $details_query = "SELECT * FROM declarations WHERE application_id = ?";
            $fields_mapping = [
                'agreed_terms' => 'Agreed to Terms',
                'agreed_authenticity' => 'Agreed to Authenticity',
                'agreed_rules' => 'Agreed to Rules',
                'place' => 'Place',
                'date' => 'Date'
            ];
            break;

        default:
            throw new Exception("Unknown section ID: " . $section['section_id']);
    }

    // Fetch section-specific details
    $details_stmt = $conn->prepare($details_query);
    if (!$details_stmt) {
        throw new Exception("Error preparing details query: " . $conn->error);
    }

    if (!$details_stmt->bind_param("i", $section['application_id'])) {
        throw new Exception("Error binding details parameters: " . $details_stmt->error);
    }

    if (!$details_stmt->execute()) {
        throw new Exception("Error executing details query: " . $details_stmt->error);
    }

    $details_result = $details_stmt->get_result();
    if (!$details_result) {
        throw new Exception("Error getting details result: " . $details_stmt->error);
    }

    $details = $details_result->fetch_assoc();
    
    // Format fields for display
    if ($details) {
        foreach ($fields_mapping as $key => $label) {
            if (isset($details[$key])) {
                $value = $details[$key];
                // Format special values
                if (in_array($key, ['dob', 'date'])) {
                    $value = date('M d, Y', strtotime($value));
                } elseif (in_array($key, ['agreed_terms', 'agreed_authenticity', 'agreed_rules', 'is_verified'])) {
                    $value = $value ? 'Yes' : 'No';
                } elseif ($key === 'annual_income') {
                    $value = number_format($value, 2);
                } elseif ($key === 'file_size') {
                    $value = round($value / 1024, 2) . ' KB';
                } elseif ($key === 'gender') {
                    $value = ucfirst($value);
                } elseif ($key === 'marital_status') {
                    $value = ucfirst($value);
                }
                
                $fields[] = [
                    'label' => $label,
                    'value' => $value,
                    'type' => 'text'
                ];
            }
        }
    }
    
    // Commit transaction
    if (!$conn->commit()) {
        throw new Exception("Failed to commit transaction: " . $conn->error);
    }
    
    // Prepare response data
    $response = [
        'success' => true,
        'data' => [
            'section_name' => $section['section_name'],
            'section_id' => $section['section_id'],
            'is_completed' => (bool)$section['is_completed'],
            'updated_at' => date('M d, Y H:i:s', strtotime($section['updated_at'])),
            'fields' => $fields
        ]
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    // Log the error
    error_log("Section Details Error: " . $e->getMessage());
    
    // Rollback transaction on error
    try {
        $conn->rollback();
    } catch (Exception $rollbackError) {
        error_log("Rollback Error: " . $rollbackError->getMessage());
    }
    
    // Return detailed error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'section_id' => isset($section_id) ? $section_id : null,
            'error_type' => get_class($e),
            'error_line' => $e->getLine()
        ]
    ]);
} finally {
    // Close statements if they exist
    if ($section_stmt instanceof mysqli_stmt) {
        $section_stmt->close();
    }
    if ($details_stmt instanceof mysqli_stmt) {
        $details_stmt->close();
    }
    // Close connection
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
?> 