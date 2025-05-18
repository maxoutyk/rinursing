<?php
// Start session
session_start();

// Database connection
require_once 'db_connect.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get email from form
    $email = trim($_POST['email'] ?? '');
    
    // Validate input
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($errors)) {
        try {
            // Check if user exists
            $query = "SELECT id, first_name, last_name, email, status FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Store token in database
                $updateQuery = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("ssi", $token, $expiry, $user['id']);
                
                if ($updateStmt->execute()) {
                    // Send password reset email
                    $resetLink = "https://{$_SERVER['HTTP_HOST']}/admission/reset-password.php?token=$token";
                    $subject = "Regional Institute of Nursing - Password Reset";
                    $message = "
                    <html>
                    <head>
                        <title>Password Reset</title>
                    </head>
                    <body>
                        <h2>Password Reset Request</h2>
                        <p>Hello " . htmlspecialchars($user['first_name']) . ",</p>
                        <p>We received a request to reset your password. Click the link below to reset your password:</p>
                        <p><a href=\"$resetLink\">Reset Your Password</a></p>
                        <p>This link will expire in 24 hours.</p>
                        <p>If you did not request a password reset, you can ignore this email.</p>
                        <p>Regards,<br>Regional Institute of Nursing Admissions Team</p>
                    </body>
                    </html>
                    ";
                    
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= "From: admissions@rinursing.edu" . "\r\n";
                    
                    // In a production environment, actually send the email
                    // mail($user['email'], $subject, $message, $headers);
                    
                    // Log the action
                    $logQuery = "INSERT INTO activity_log (user_id, action, entity_type, entity_id, description, ip_address, user_agent) 
                                VALUES (?, 'password_reset_request', 'user', ?, 'Password reset requested', ?, ?)";
                    $logStmt = $conn->prepare($logQuery);
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    $logStmt->bind_param("iiss", $user['id'], $user['id'], $ipAddress, $userAgent);
                    $logStmt->execute();
                    $logStmt->close();
                    
                    $response['success'] = true;
                    $response['message'] = 'Password reset instructions have been sent to your email address.';
                }
                
                $updateStmt->close();
            } else {
                // For security reasons, don't reveal that the email doesn't exist
                // Instead, show a success message anyway
                $response['success'] = true;
                $response['message'] = 'If your email exists in our system, you will receive password reset instructions.';
            }
            
            $stmt->close();
        } catch (Exception $e) {
            // Log the error
            error_log("Password reset error: " . $e->getMessage());
            $errors[] = 'An error occurred while processing your request. Please try again later.';
        }
    }
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Please correct the errors and try again.';
    }
    
    // Return JSON response for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // For regular form submissions, set session variables and redirect
    if ($response['success']) {
        $_SESSION['password_reset_email'] = $email;
        $_SESSION['password_reset_message'] = $response['message'];
        $_SESSION['password_reset_status'] = 'success';
    } else {
        $_SESSION['password_reset_errors'] = $response['errors'];
        $_SESSION['password_reset_status'] = 'error';
    }
    
    // Redirect back to forgot password page
    header('Location: ../forgot-password.html');
    exit;
}

// If not a POST request, redirect to forgot password page
header('Location: ../forgot-password.html');
exit;
?> 
 
 
 
 
 
 