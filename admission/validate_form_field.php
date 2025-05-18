<?php
/**
 * Validate Form Field
 * 
 * This script handles validation of individual form fields via AJAX.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

// Check if field data is provided
if (!isset($_POST['field_name']) || !isset($_POST['field_value'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

$fieldName = $_POST['field_name'];
$fieldValue = $_POST['field_value'];

// Validate field
$result = validateField($fieldName, $fieldValue);

// Return validation result
echo json_encode($result);

/**
 * Validate field based on its name
 */
function validateField($fieldName, $fieldValue) {
    switch ($fieldName) {
        case 'fullName':
            return validateFullName($fieldValue);
        
        case 'email':
            return validateEmail($fieldValue);
        
        case 'phone':
        case 'fatherMobile':
        case 'motherMobile':
        case 'guardianMobile':
            return validatePhone($fieldValue);
        
        case 'dob':
            return validateDateOfBirth($fieldValue);
        
        case 'totalMarks10th':
        case 'marksObtained10th':
        case 'totalMarks12th': 
        case 'marksObtained12th':
            return validateMarks($fieldValue);
        
        case 'percentage10th':
        case 'percentage12th':
            return validatePercentage($fieldValue);
        
        case 'yearOfPassing10th':
        case 'yearOfPassing12th':
            return validateYear($fieldValue);
        
        default:
            // For other fields, do a simple required check
            if (empty($fieldValue)) {
                return ['status' => 'error', 'message' => 'This field is required'];
            }
            return ['status' => 'success'];
    }
}

/**
 * Validate full name
 */
function validateFullName($value) {
    if (empty($value)) {
        return ['status' => 'error', 'message' => 'Full name is required'];
    }
    
    if (strlen($value) < 3) {
        return ['status' => 'error', 'message' => 'Name must be at least 3 characters'];
    }
    
    if (!preg_match('/^[a-zA-Z\s]+$/', $value)) {
        return ['status' => 'error', 'message' => 'Name should contain only letters and spaces'];
    }
    
    return ['status' => 'success'];
}

/**
 * Validate email
 */
function validateEmail($value) {
    if (empty($value)) {
        return ['status' => 'success']; // Email is not always required
    }
    
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'message' => 'Please enter a valid email address'];
    }
    
    return ['status' => 'success'];
}

/**
 * Validate phone number
 */
function validatePhone($value) {
    if (empty($value)) {
        return ['status' => 'error', 'message' => 'Phone number is required'];
    }
    
    // Allow only digits, spaces, dashes, and parentheses
    if (!preg_match('/^[0-9\s\-\(\)]+$/', $value)) {
        return ['status' => 'error', 'message' => 'Please enter a valid phone number'];
    }
    
    // Remove any non-digit characters for length check
    $digits = preg_replace('/\D/', '', $value);
    
    if (strlen($digits) < 10 || strlen($digits) > 15) {
        return ['status' => 'error', 'message' => 'Phone number should be 10-15 digits'];
    }
    
    return ['status' => 'success'];
}

/**
 * Validate date of birth
 */
function validateDateOfBirth($value) {
    if (empty($value)) {
        return ['status' => 'error', 'message' => 'Date of birth is required'];
    }
    
    // Check if valid date
    $date = date_create($value);
    if (!$date) {
        return ['status' => 'error', 'message' => 'Please enter a valid date'];
    }
    
    // Check if in the past
    $now = new DateTime();
    if ($date > $now) {
        return ['status' => 'error', 'message' => 'Date of birth must be in the past'];
    }
    
    // Check age (must be at least 16 years old)
    $age = $now->diff($date)->y;
    if ($age < 16) {
        return ['status' => 'error', 'message' => 'You must be at least 16 years old'];
    }
    
    // Check if not too old (reasonable limit)
    if ($age > 100) {
        return ['status' => 'error', 'message' => 'Please enter a valid date of birth'];
    }
    
    return ['status' => 'success'];
}

/**
 * Validate marks
 */
function validateMarks($value) {
    if (empty($value)) {
        return ['status' => 'error', 'message' => 'Marks are required'];
    }
    
    if (!is_numeric($value)) {
        return ['status' => 'error', 'message' => 'Please enter a valid number'];
    }
    
    $marks = floatval($value);
    
    if ($marks < 0) {
        return ['status' => 'error', 'message' => 'Marks cannot be negative'];
    }
    
    return ['status' => 'success'];
}

/**
 * Validate percentage
 */
function validatePercentage($value) {
    if (empty($value)) {
        return ['status' => 'error', 'message' => 'Percentage is required'];
    }
    
    if (!is_numeric($value)) {
        return ['status' => 'error', 'message' => 'Please enter a valid number'];
    }
    
    $percentage = floatval($value);
    
    if ($percentage < 0 || $percentage > 100) {
        return ['status' => 'error', 'message' => 'Percentage must be between 0 and 100'];
    }
    
    return ['status' => 'success'];
}

/**
 * Validate year
 */
function validateYear($value) {
    if (empty($value)) {
        return ['status' => 'error', 'message' => 'Year is required'];
    }
    
    if (!is_numeric($value) || !preg_match('/^\d{4}$/', $value)) {
        return ['status' => 'error', 'message' => 'Please enter a valid 4-digit year'];
    }
    
    $year = intval($value);
    $currentYear = intval(date('Y'));
    
    if ($year < ($currentYear - 50) || $year > $currentYear) {
        return ['status' => 'error', 'message' => 'Please enter a reasonable year'];
    }
    
    return ['status' => 'success'];
} 
 
 
 
 
 
 