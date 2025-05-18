<?php
/**
 * Document Upload Handler
 * 
 * This script handles document uploads for the admission application.
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

// Check if request is POST and has file
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['document'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or invalid request']);
    exit;
}

// Check if document type is provided
if (!isset($_POST['document_type']) || empty($_POST['document_type'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Document type is required']);
    exit;
}

$documentType = $_POST['document_type'];

// Get application ID for this user
$query = "SELECT id FROM applications WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    // Create application if it doesn't exist
    $query = "INSERT INTO applications (user_id, application_id, status, progress, created_at, last_updated) 
              VALUES (?, ?, 'in_progress', 0, NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    $applicationId = 'RIN-' . date('Y') . '-' . str_pad($userId, 5, '0', STR_PAD_LEFT);
    
    $stmt->bind_param("is", $userId, $applicationId);
    
    if (!$stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed to create application']);
        exit;
    }
    
    $applicationId = $conn->insert_id;
} else {
    $applicationId = $result->fetch_assoc()['id'];
}

// Handle file upload
$uploadedFile = $_FILES['document'];

// Validate file
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($uploadedFile['type'], $allowedTypes)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Allowed types: JPG, JPEG, PNG, PDF']);
    exit;
}

if ($uploadedFile['size'] > $maxSize) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'File size exceeds 5MB limit']);
    exit;
}

// Create upload directory if it doesn't exist
$uploadDir = 'uploads/' . $userId . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
$filename = $documentType . '_' . time() . '.' . $extension;
$targetPath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
    // Save file information to database
    $query = "INSERT INTO documents (application_id, document_type, file_name, file_path, mime_type, file_size) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssi", $applicationId, $documentType, $uploadedFile['name'], 
                     $targetPath, $uploadedFile['type'], $uploadedFile['size']);
    
    if ($stmt->execute()) {
        // Update progress for documents section
        $query = "UPDATE application_sections 
                  SET is_completed = 1, updated_at = NOW() 
                  WHERE application_id = ? AND section_id = 8";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        
        // Update application progress
        updateApplicationProgress($conn, $applicationId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success', 
            'message' => 'Document uploaded successfully',
            'file_path' => $targetPath,
            'file_name' => $uploadedFile['name']
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed to save document information']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to upload document']);
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
 
 
 
 
 
 