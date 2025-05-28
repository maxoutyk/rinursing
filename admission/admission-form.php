<?php
/**
 * Admission Form PHP Wrapper
 * 
 * This script ensures that:
 * 1. Only authenticated users can access the admission form
 * 2. Session handling is consistent with the rest of the application
 * 3. Loads any existing application data for the user
 */

// Include authentication requirement check
require_once 'includes/required_auth.php';

// Define constant to indicate this file was included properly
define('INCLUDED', true);

// Get user ID from session
$userId = $_SESSION['user_id'];

// Database connection
require_once 'includes/db_connect.php';

// Fetch existing application data if available
$applicationData = [];
$formData = [];

try {
    // Check if application exists
    $query = "SELECT id, application_id, status, progress FROM applications WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $applicationData = $result->fetch_assoc();
        
        // Fetch sections status
        $query = "SELECT section_id, is_completed FROM application_sections WHERE application_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $applicationData['id']);
        $stmt->execute();
        $sectionsResult = $stmt->get_result();
        
        $sections = [];
        while ($row = $sectionsResult->fetch_assoc()) {
            $sections[$row['section_id']] = $row['is_completed'];
        }
        
        $applicationData['sections'] = $sections;
        
        // Fetch form data based on completed sections
        if (!empty($sections)) {
            // Basic Information
            if ($sections[1] ?? false) {
                $query = "SELECT full_name, dob, gender FROM personal_details WHERE application_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $applicationData['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $formData['basic'] = $result->fetch_assoc();
                }
            }
            
            // Parent/Guardian Details
            if ($sections[2] ?? false) {
                $query = "SELECT relationship, name, occupation, phone, email FROM guardians WHERE application_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $applicationData['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $parents = [];
                while ($row = $result->fetch_assoc()) {
                    $parents[$row['relationship']] = $row;
                }
                $formData['parents'] = $parents;
            }
            
            // Address Details
            if ($sections[3] ?? false) {
                $query = "SELECT type, address_line1 FROM addresses WHERE application_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $applicationData['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $addresses = [];
                while ($row = $result->fetch_assoc()) {
                    $addresses[$row['type']] = $row['address_line1'];
                }
                $formData['addresses'] = $addresses;
            }
            
            // Personal Details
            if ($sections[4] ?? false) {
                $query = "SELECT nationality, religion, category, marital_status, mother_tongue, annual_income FROM personal_details WHERE application_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $applicationData['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $formData['personal'] = $result->fetch_assoc();
                }
            }
            
            // Education (10th & 12th)
            $query = "SELECT id, level, qualification_name, board_university, school_college, year_of_passing, percentage, subjects, 
                     total_marks, marks_obtained, mode, remarks 
                     FROM education WHERE application_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $applicationData['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $education = [];
            while ($row = $result->fetch_assoc()) {
                $level = $row['level'];
                $educationId = $row['id'];
                
                // Remove internal ID from the output
                unset($row['id']);
                
                $education[$level] = $row;
                
                // For 12th grade, fetch subject-wise marks
                if ($level === '12th') {
                    $subjectQuery = "SELECT subject_name, total_marks, marks_obtained, percentage 
                                    FROM subject_marks WHERE education_id = ? ORDER BY id ASC";
                    $subjectStmt = $conn->prepare($subjectQuery);
                    $subjectStmt->bind_param("i", $educationId);
                    $subjectStmt->execute();
                    $subjectResult = $subjectStmt->get_result();
                    
                    $subjectData = [];
                    while ($subjectRow = $subjectResult->fetch_assoc()) {
                        $subjectData[] = $subjectRow;
                    }
                    
                    $education[$level]['subject_marks'] = $subjectData;
                }
            }
            $formData['education'] = $education;
            
            // Documents
            $query = "SELECT document_type, file_name, file_path FROM documents WHERE application_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $applicationData['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $documents = [];
            while ($row = $result->fetch_assoc()) {
                $documents[$row['document_type']] = $row;
            }
            $formData['documents'] = $documents;
            
            // Declaration
            $query = "SELECT agreed_terms, agreed_authenticity, agreed_rules, place, date FROM declarations WHERE application_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $applicationData['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $formData['declaration'] = $result->fetch_assoc();
            }
        }
    }
} catch (Exception $e) {
    error_log('Error fetching application data: ' . $e->getMessage());
}

// Any additional form-specific session variables or initialization can go here
$_SESSION['last_form_access'] = date('Y-m-d H:i:s');

// Add JavaScript variable to indicate proper inclusion
echo "<script>window.__INCLUDED__ = true;</script>";

// Pass application data to JavaScript
echo "<script>window.applicationData = " . json_encode($applicationData) . ";</script>";
echo "<script>window.formData = " . json_encode($formData) . ";</script>";

// Include the admission form HTML content
include 'admission-form.html';
?> 
 