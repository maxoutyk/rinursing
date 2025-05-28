<?php
// Find and replace the session_start() call at the beginning of the file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type header - IMPORTANT for AJAX requests
header('Content-Type: application/json');

// Suppress warnings and notices that might break JSON output
error_reporting(E_ERROR);
ini_set('display_errors', 0);

// Capture any output buffering to prevent it from breaking JSON
ob_start();

try {
    // Database connection
    require_once 'db_connect.php';
    // Email utilities
    require_once 'mail_utils.php';
    
    // Initialize response array
    $response = [
        'success' => false,
        'message' => '',
        'errors' => [],
        'require2FA' => false
    ];

    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Enable debugging
        error_log("POST received in login.php: " . print_r($_POST, true));
        
        // Handle resend code request
        if (isset($_POST['resendCode']) && $_POST['resendCode'] === 'true' && isset($_POST['email'])) {
            $email = trim($_POST['email']);
            
            try {
                // Find user by email
                $query = "SELECT id, first_name, last_name, email, status FROM users WHERE email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Check if user is verified
                    if ($user['status'] === 'verified') {
                        // Generate new 2FA code
                        $twoFactorCode = sprintf("%06d", mt_rand(100000, 999999));
                        $_SESSION['2fa_pending'] = true;
                        $_SESSION['2fa_code'] = $twoFactorCode;
                        $_SESSION['2fa_user_id'] = $user['id'];
                        
                        // Send verification code
                        $emailResult = sendVerificationCode($user['email'], $user['first_name'], $twoFactorCode);
                        
                        if ($emailResult['success']) {
                            $response['success'] = true;
                            $response['message'] = 'New verification code sent. Please check your email.';
                            error_log("New verification code sent to: {$user['email']}");
                        } else {
                            $response['success'] = false;
                            $response['message'] = 'Failed to send verification code. Please try again.';
                            error_log("Failed to send new verification code: {$emailResult['message']}");
                        }
                    } else {
                        $response['success'] = false;
                        $response['message'] = 'Your account is not verified. Please check your email for the verification link.';
                        error_log("Resend code attempt for unverified account: $email");
                    }
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Email address not found.';
                    error_log("Resend code attempt for unknown email: $email");
                }
                
                $stmt->close();
            } catch (Exception $e) {
                $response['success'] = false;
                $response['message'] = 'An error occurred. Please try again.';
                error_log("Error during code resend: " . $e->getMessage());
            }
            
            echo json_encode($response);
            exit;
        }
        
        // Handle resend verification email request
        if (isset($_POST['resendVerification']) && $_POST['resendVerification'] === 'true' && isset($_POST['email'])) {
            $email = trim($_POST['email']);
            
            try {
                // Find user by email
                $query = "SELECT id, first_name, last_name, email, status, verification_token FROM users WHERE email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Check if user is unverified
                    if ($user['status'] === 'unverified') {
                        // Generate new verification token if needed
                        if (empty($user['verification_token'])) {
                            $verificationToken = bin2hex(random_bytes(32));
                            
                            // Update verification token
                            $updateQuery = "UPDATE users SET verification_token = ? WHERE id = ?";
                            $updateStmt = $conn->prepare($updateQuery);
                            $updateStmt->bind_param("si", $verificationToken, $user['id']);
                            $updateStmt->execute();
                            $updateStmt->close();
                        } else {
                            $verificationToken = $user['verification_token'];
                        }
                        
                        // Send verification email
                        $emailResult = sendVerificationEmail($user['email'], $user['first_name'], $verificationToken);
                        
                        if ($emailResult['success']) {
                            $response['success'] = true;
                            $response['message'] = 'Verification email has been resent. Please check your inbox.';
                            error_log("Verification email resent to: {$user['email']}");
                        } else {
                            $response['success'] = false;
                            $response['message'] = 'Failed to send verification email. Please try again.';
                            error_log("Failed to resend verification email: {$emailResult['message']}");
                        }
                    } else {
                        $response['success'] = false;
                        $response['message'] = 'This account is already verified.';
                        error_log("Resend verification attempt for verified account: $email");
                    }
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Email address not found.';
                    error_log("Resend verification attempt for unknown email: $email");
                }
                
                $stmt->close();
            } catch (Exception $e) {
                $response['success'] = false;
                $response['message'] = 'An error occurred. Please try again.';
                error_log("Error during verification resend: " . $e->getMessage());
            }
            
            echo json_encode($response);
            exit;
        }
        
        // Check if this is a verification code submission
        if (isset($_POST['verificationCode']) && !empty($_POST['verificationCode'])) {
            // Verify 2FA code
            $verificationCode = trim($_POST['verificationCode']);
            
            // If email is provided, try to retrieve user info
            if (isset($_POST['email']) && !empty($_POST['email']) && 
                (!isset($_SESSION['2fa_pending']) || !isset($_SESSION['2fa_code']) || !isset($_SESSION['2fa_user_id']))) {
                
                $email = trim($_POST['email']);
                // Find user by email
                try {
                    $query = "SELECT id FROM users WHERE email = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        
                        // Check if we need to regenerate code
                        if (!isset($_SESSION['2fa_pending']) || !isset($_SESSION['2fa_code'])) {
                            $response['success'] = false;
                            $response['message'] = 'Your verification session has expired. Please log in again.';
                            echo json_encode($response);
                            exit;
                        }
                    }
                    
                    $stmt->close();
                } catch (Exception $e) {
                    $response['errors'][] = 'Database error: ' . $e->getMessage();
                    error_log("Database error during verification: " . $e->getMessage());
                    echo json_encode($response);
                    exit;
                }
            }
            
            // Check if there's a pending 2FA session
            if (isset($_SESSION['2fa_pending']) && isset($_SESSION['2fa_code']) && isset($_SESSION['2fa_user_id'])) {
                // Verify the code
                if ($_SESSION['2fa_code'] === $verificationCode) {
                    // Code is valid, complete login
                    $userId = $_SESSION['2fa_user_id'];
                    
                    // Fetch user data
                    try {
                        $query = "SELECT id, first_name, last_name, email, phone, status FROM users WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $userId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows === 1) {
                            $user = $result->fetch_assoc();
                            
                            // Check if user is verified
                            if ($user['status'] === 'verified') {
                                // Set logged in session
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                                $_SESSION['user_email'] = $user['email'];
                                $_SESSION['logged_in'] = true;
                                
                                // Update last login timestamp
                                $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = ?";
                                $updateStmt = $conn->prepare($updateQuery);
                                $updateStmt->bind_param("i", $userId);
                                $updateStmt->execute();
                                $updateStmt->close();
                                
                                // Clear 2FA sessions
                                unset($_SESSION['2fa_pending']);
                                unset($_SESSION['2fa_code']);
                                unset($_SESSION['2fa_user_id']);
                                
                                // Set response
                                $response['success'] = true;
                                $response['message'] = 'Login successful! Redirecting to your dashboard...';
                                
                                // Check if there's a specific page the user was trying to access
                                if (isset($_SESSION['requested_page'])) {
                                    $response['redirect'] = $_SESSION['requested_page'];
                                    unset($_SESSION['requested_page']);
                                } else {
                                    $response['redirect'] = 'dashboard.html';
                                }
                                
                                // Remember me functionality
                                if (isset($_POST['rememberMe']) && $_POST['rememberMe']) {
                                    $token = bin2hex(random_bytes(32));
                                    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                                    
                                    // Store token in database
                                    $tokenQuery = "INSERT INTO remember_tokens (user_id, token, expiry) VALUES (?, ?, ?)";
                                    $tokenStmt = $conn->prepare($tokenQuery);
                                    $tokenStmt->bind_param("iss", $userId, $token, $expiry);
                                    $tokenStmt->execute();
                                    $tokenStmt->close();
                                    
                                    // Set cookie
                                    setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
                                }
                                
                                echo json_encode($response);
                                exit;
                            } else {
                                $response['errors'][] = 'Your account is not verified. Please check your email for the verification link.';
                            }
                        } else {
                            $response['errors'][] = 'User not found.';
                        }
                        
                        $stmt->close();
                    } catch (Exception $e) {
                        $response['errors'][] = 'Database error: ' . $e->getMessage();
                    }
                } else {
                    $response['errors'][] = 'Invalid verification code. Please try again.';
                    $response['require2FA'] = true;
                    error_log("Invalid verification code provided: $verificationCode vs {$_SESSION['2fa_code']}");
                }
            } else {
                $response['errors'][] = 'Your verification session has expired. Please log in again.';
                error_log("No active 2FA session found in PHP session.");
            }
            
            echo json_encode($response);
            exit;
        }
        
        // Regular login process
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['rememberMe']);
        
        // Validate input
        $errors = [];
        
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($password)) $errors[] = 'Password is required';
        
        if (empty($errors)) {
            try {
                // Find user by email
                $query = "SELECT id, first_name, last_name, email, phone, password, status FROM users WHERE email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Check if user is verified
                        if ($user['status'] === 'verified') {
                            // Generate 2FA code
                            $twoFactorCode = sprintf("%06d", mt_rand(100000, 999999));
                            $_SESSION['2fa_pending'] = true;
                            $_SESSION['2fa_code'] = $twoFactorCode;
                            $_SESSION['2fa_user_id'] = $user['id'];
                            
                            // Send verification code using the mail utility
                            $emailResult = sendVerificationCode($user['email'], $user['first_name'], $twoFactorCode);
                            
                            // Log the email sending attempt
                            if (!$emailResult['success']) {
                                // Log the error but continue
                                error_log("Failed to send verification code email: {$emailResult['message']}");
                            } else {
                                error_log("Verification code email sent successfully to: {$user['email']}");
                            }
                            
                            // Return 2FA required response
                            $response['require2FA'] = true;
                            $response['message'] = 'Verification code sent. Please check your email.';
                            $response['success'] = true;
                            
                            error_log("Login successful for $email - 2FA required. Code: $twoFactorCode");
                            
                            // Clear any buffered output
                            ob_end_clean();
                            // Send JSON response
                            echo json_encode($response);
                            exit;
                        } else {
                            $errors[] = 'Your account is not verified. Please check your email for the verification link.';
                            error_log("Login attempt with unverified account: $email");
                        }
                    } else {
                        $errors[] = 'Invalid email or password.';
                        error_log("Login attempt with invalid password for: $email");
                    }
                } else {
                    $errors[] = 'Invalid email or password.';
                    error_log("Login attempt with invalid email: $email");
                }
                
                $stmt->close();
            } catch (Exception $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
                error_log("Database error during login: " . $e->getMessage());
            }
        }
        
        // If we get here, there were errors
        $response['errors'] = $errors;
        $response['message'] = 'Login failed. Please check your credentials.';
        error_log("Login failed with errors: " . print_r($errors, true));
        
        // Clear any buffered output
        ob_end_clean();
        // Send JSON response
        echo json_encode($response);
        exit;
    }
    
    // Check for remember me token
    if (!isset($_SESSION['logged_in']) && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        try {
            $query = "SELECT u.id, u.first_name, u.last_name, u.email, u.status 
                     FROM users u 
                     JOIN remember_tokens t ON u.id = t.user_id 
                     WHERE t.token = ? AND t.expiry > NOW()";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Set logged in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                
                // Redirect to dashboard
                header('Location: ../dashboard.html');
                exit;
            }
            
            $stmt->close();
        } catch (Exception $e) {
            // Token error, just continue to login page
            error_log("Remember me token error: " . $e->getMessage());
        }
    }
    
    // If not a POST request or remember me failed, return JSON error
    $response['success'] = false;
    $response['message'] = 'Invalid request method';
    
    // Clear any buffered output
    ob_end_clean();
    // Send JSON response
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    // Log the error
    error_log("Critical error in login.php: " . $e->getMessage());
    
    // Prepare error response
    $response = [
        'success' => false,
        'message' => 'A system error occurred. Please try again later.',
        'errors' => []
    ];
    
    // Clear any buffered output
    ob_end_clean();
    // Send JSON response
    echo json_encode($response);
    exit;
}

// Final fallback - should never reach here
ob_end_clean();
echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
?> 
 
 
 
 
 
 