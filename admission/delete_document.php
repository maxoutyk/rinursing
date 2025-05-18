<?php
/**
 * Document Deletion Handler
 * 
 * This script handles document deletion requests for the admission application.
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
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No application found']);
    exit;
}

$applicationId = $result->fetch_assoc()['id'];

// Get document information
$query = "SELECT id, file_path FROM documents WHERE application_id = ? AND document_type = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $applicationId, $documentType);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Document not found']);
    exit;
}

$document = $result->fetch_assoc();

// Delete file from filesystem
if (file_exists($document['file_path']) && is_file($document['file_path'])) {
    unlink($document['file_path']);
}

// Delete record from database
$query = "DELETE FROM documents WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $document['id']);

if ($stmt->execute()) {
    // Check if there are any remaining documents
    $query = "SELECT COUNT(*) as doc_count FROM documents WHERE application_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // If no documents left, mark section as incomplete
    if ($row['doc_count'] === 0) {
        $query = "UPDATE application_sections 
                  SET is_completed = 0, updated_at = NOW() 
                  WHERE application_id = ? AND section_id = 8";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        
        // Update application progress
        updateApplicationProgress($conn, $applicationId);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Document deleted successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete document']);
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
 
 
 
 
 
 