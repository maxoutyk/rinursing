<?php
/**
 * Get Document Information
 * 
 * This script retrieves information about a document for a specific application.
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

// Check if document type is provided
if (!isset($_GET['type']) || empty($_GET['type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Document type is required']);
    exit;
}

$documentType = $_GET['type'];

// Get application ID for this user
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

// Get document information
$query = "SELECT document_type, file_name, file_path, mime_type, created_at 
          FROM documents 
          WHERE application_id = ? AND document_type = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $applicationId, $documentType);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Document not found']);
    exit;
}

$document = $result->fetch_assoc();

// Return document information
echo json_encode([
    'status' => 'success',
    'document' => [
        'type' => $document['document_type'],
        'name' => $document['file_name'],
        'path' => $document['file_path'],
        'mime_type' => $document['mime_type'],
        'uploaded_at' => $document['created_at']
    ]
]);
?> 
 
 
 
 
 
 