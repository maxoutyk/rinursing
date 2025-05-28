<?php
// Include direct access protection
define('INCLUDED', true);

// Include authentication check for admin
require_once '../../includes/admin_auth.php';
require_once '../../includes/db_connect.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if required parameters are set
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Get parameters
$application_id = intval($_POST['id']);
$status = $_POST['status'];

// Validate status
$allowed_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Update application status
    $update_query = $conn->prepare("UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?");
    $update_query->bind_param("si", $status, $application_id);
    $update_query->execute();

    if ($update_query->affected_rows === 0) {
        throw new Exception("Application not found or no changes made");
    }

    // Get application details for notification
    $app_query = $conn->prepare("SELECT a.*, u.email, u.first_name 
                                FROM applications a 
                                JOIN users u ON a.user_id = u.id 
                                WHERE a.id = ?");
    $app_query->bind_param("i", $application_id);
    $app_query->execute();
    $result = $app_query->get_result();
    $application = $result->fetch_assoc();

    // Insert notification
    $notification_message = match($status) {
        'approved' => 'Your application has been approved! Please check your email for further instructions.',
        'rejected' => 'Your application has been rejected. Please contact the administration for more information.',
        default => 'Your application status has been updated.'
    };

    $insert_notification = $conn->prepare("INSERT INTO notifications (user_id, type, message, created_at) VALUES (?, 'application_status', ?, NOW())");
    $insert_notification->bind_param("is", $application['user_id'], $notification_message);
    $insert_notification->execute();

    // If approved, send email notification
    if ($status === 'approved') {
        // Include email utility
        require_once '../../includes/mail_utils.php';

        $email_subject = "Application Approved - Regional Institute of Nursing";
        $email_body = "Dear {$application['first_name']},\n\n"
                   . "Congratulations! Your application to Regional Institute of Nursing has been approved.\n\n"
                   . "Application ID: {$application['application_id']}\n\n"
                   . "Please log in to your account to view further instructions and complete the admission process.\n\n"
                   . "Best regards,\nRegional Institute of Nursing";

        send_mail($application['email'], $email_subject, $email_body);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Application status updated successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error updating application status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating application status']);
}

// Close prepared statements
$update_query->close();
$app_query->close();
$insert_notification->close();

// Close database connection
$conn->close();
?> 