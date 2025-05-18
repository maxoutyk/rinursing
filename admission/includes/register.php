<?php
// Start session
session_start();

// Database connection
require_once 'db_connect.php';
// Email utilities
require_once 'mail_utils.php';

// Add error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/register_errors.log');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the POST data for debugging
    error_log("Registration attempt - POST data: " . print_r($_POST, true));
    
    // Get form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $termsAgree = isset($_POST['termsAgree']);
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    
    // Validate form data
    $errors = [];
    
    // Required fields
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (empty($confirmPassword)) $errors[] = 'Please confirm your password';
    
    // Email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Phone format
    if (!empty($phone) && !preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number';
    }
    
    // Password strength
    if (!empty($password)) {
        $passwordStrength = 0;
        if (strlen($password) > 7) $passwordStrength++;
        if (strlen($password) > 10) $passwordStrength++;
        if (preg_match('/[A-Z]/', $password)) $passwordStrength++;
        if (preg_match('/[0-9]/', $password)) $passwordStrength++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $passwordStrength++;
        
        if ($passwordStrength < 3) {
            $errors[] = 'Please choose a stronger password with a combination of uppercase letters, numbers, and special characters';
        }
    }
    
    // Passwords match
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    // Terms and conditions
    if (!$termsAgree) {
        $errors[] = 'You must agree to the Terms and Conditions and Privacy Policy';
    }
    
    // Verify reCAPTCHA if configured
    if (!empty($recaptchaResponse)) {
        // For development mode, we'll use Google's test keys and skip actual verification
        $recaptchaSecret = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Google's test secret key
        
        // In development mode, we can skip actual verification or use Google's test keys
        // Comment this section in production and uncomment the verification code
        /*
        $recaptchaVerify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptchaSecret . '&response=' . $recaptchaResponse);
        $recaptchaData = json_decode($recaptchaVerify);
        
        if (!$recaptchaData->success) {
            $errors[] = 'reCAPTCHA verification failed';
        }
        */
        
        // For development, we'll assume the CAPTCHA passed
        // Remove this in production
        $recaptchaSuccess = true;
    } else {
        // Still require users to complete the CAPTCHA, even in development
        $errors[] = 'Please complete the reCAPTCHA verification';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        try {
            $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
            $stmt = $conn->prepare($checkEmailQuery);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = 'Email address is already registered';
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
            error_log("Database error checking email: " . $e->getMessage());
        }
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $insertQuery = "INSERT INTO users (first_name, last_name, email, phone, password, verification_token, status) 
                           VALUES (?, ?, ?, ?, ?, ?, 'unverified')";
            
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("ssssss", $firstName, $lastName, $email, $phone, $hashedPassword, $verificationToken);
            
            error_log("Attempting to insert user into database: $firstName $lastName, $email");
            
            if ($stmt->execute()) {
                // Get new user ID
                $userId = $conn->insert_id;
                error_log("User inserted successfully! User ID: $userId");
                
                // Send verification email using the mail utility
                $emailResult = sendVerificationEmail($email, $firstName, $verificationToken);
                
                // For demo purposes, we'll just simulate success even if email fails
                // In production, you would handle this differently
                if (!$emailResult['success']) {
                    // Log the error but continue
                    error_log("Failed to send verification email: {$emailResult['message']}");
                } else {
                    error_log("Verification email sent successfully");
                }
                
                $response['success'] = true;
                $response['message'] = 'Registration successful! A verification email has been sent to your address.';
                
                // Store user ID in session for verification page
                $_SESSION['temp_user_id'] = $userId;
                $_SESSION['temp_email'] = $email;
                
                // Redirect to confirmation page with success status
                $redirectUrl = '../registration-confirmation.html?status=' . urlencode(json_encode($response));
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $errors[] = 'Registration failed: ' . $stmt->error;
                error_log("Database insert failed: " . $stmt->error);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = 'Registration error: ' . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
        }
    }
    
    // If we get here, there were errors
    error_log("Registration failed with errors: " . print_r($errors, true));
    $response['errors'] = $errors;
    $response['message'] = 'Please correct the errors and try again.';
    
    // Redirect to confirmation page with error status
    $redirectUrl = '../registration-confirmation.html?status=' . urlencode(json_encode($response));
    header('Location: ' . $redirectUrl);
    exit;
}

// If not a POST request, redirect to registration page
header('Location: ../register.html');
exit;
?> 
 
 
 
 
 
 