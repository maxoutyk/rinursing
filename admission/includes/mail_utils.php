<?php
/**
 * Email Utility Functions
 * 
 * This file contains functions for sending emails using PHPMailer with Office 365 SMTP.
 */

// Load Composer's autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Define environment constant if not already defined
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

/**
 * Send an email using Office 365 SMTP server
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML format)
 * @param string $altBody Plain text alternative (optional)
 * @param array $attachments Array of file paths to attach (optional)
 * @return array Status array with success flag and message
 */
function sendEmail($to, $subject, $body, $altBody = '', $attachments = []) {
    // Email configuration
    $mail = new PHPMailer(true); // Enable exceptions
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.office365.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'support@incitegravity.com'; // Office 365 email
        $mail->Password   = 'Init@123##'; // Your actual password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // For development environment only - allows self-signed certificates
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Enable verbose debug output in development
        if (ENVIRONMENT === 'development') {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Output debug info to logs
        }
        
        // Recipients - IMPORTANT: setFrom must match Username for Office 365
        $mail->setFrom('support@incitegravity.com', 'RIN Admissions');
        $mail->addAddress($to);
        $mail->addReplyTo('support@incitegravity.com', 'RIN Admissions Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);
        
        // Attachments
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
        }
        
        // Send the email
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
    } catch (Exception $e) {
        // Log the error
        error_log("Email sending failed: {$mail->ErrorInfo}");
        
        return [
            'success' => false,
            'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"
        ];
    }
}

/**
 * Send a verification email to a newly registered user
 * 
 * @param string $to User's email address
 * @param string $firstName User's first name
 * @param string $verificationToken The verification token for the link
 * @return array Status array with success flag and message
 */
function sendVerificationEmail($to, $firstName, $verificationToken) {
    // Generate the verification URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $verificationLink = "$protocol://$host/admission/verify.php?token=$verificationToken";
    
    // Email subject
    $subject = "Regional Institute of Nursing - Verify Your Email";
    
    // Email body in HTML
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Verify Your Email</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
            }
            .container {
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .header {
                background-color: #06bbcc;
                padding: 15px;
                color: white;
                text-align: center;
                border-radius: 5px 5px 0 0;
                margin-bottom: 20px;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #06bbcc;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }
            .footer {
                margin-top: 30px;
                font-size: 12px;
                color: #777;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Welcome to Regional Institute of Nursing!</h2>
            </div>
            
            <p>Dear $firstName,</p>
            
            <p>Thank you for registering with the Regional Institute of Nursing's online admission portal. To activate your account and continue with the admission process, please verify your email address by clicking the button below:</p>
            
            <p style='text-align: center;'>
                <a href='$verificationLink' class='button'>Verify Email Address</a>
            </p>
            
            <p>If the button doesn't work, copy and paste the following link into your browser:</p>
            <p style='word-break: break-all;'>$verificationLink</p>
            
            <p>This verification link will expire in 24 hours for security reasons.</p>
            
            <p>If you did not create an account on our admission portal, you can safely ignore this email.</p>
            
            <p>For any queries regarding the admission process, please contact our admissions team at:</p>
            <p>Email: admissions@rinursing.edu</p>
            <p>Phone: +91 9862245330</p>
            
            <p>Regards,<br>
            Admissions Team<br>
            Regional Institute of Nursing</p>
            
            <div class='footer'>
                <p>This is an automated message, please do not reply directly to this email.</p>
                <p>&copy; 2025 Regional Institute of Nursing. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Text alternative
    $altBody = "
    Welcome to Regional Institute of Nursing!
    
    Dear $firstName,
    
    Thank you for registering with the Regional Institute of Nursing's online admission portal. To activate your account and continue with the admission process, please verify your email address by visiting the link below:
    
    $verificationLink
    
    This verification link will expire in 24 hours for security reasons.
    
    If you did not create an account on our admission portal, you can safely ignore this email.
    
    For any queries regarding the admission process, please contact our admissions team at:
    Email: admissions@rinursing.edu
    Phone: +91 9862245330
    
    Regards,
    Admissions Team
    Regional Institute of Nursing
    
    This is an automated message, please do not reply directly to this email.
    © 2025 Regional Institute of Nursing. All rights reserved.
    ";
    
    // Send the email
    return sendEmail($to, $subject, $body, $altBody);
}

/**
 * Send a verification code (OTP) to a user for login
 * 
 * @param string $to User's email address
 * @param string $firstName User's first name
 * @param string $verificationCode The verification code
 * @return array Status array with success flag and message
 */
function sendVerificationCode($to, $firstName, $verificationCode) {
    // Email subject
    $subject = "Regional Institute of Nursing - Your Login Verification Code";
    
    // Email body in HTML
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Your Login Verification Code</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
            }
            .container {
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .header {
                background-color: #06bbcc;
                padding: 15px;
                color: white;
                text-align: center;
                border-radius: 5px 5px 0 0;
                margin-bottom: 20px;
            }
            .verification-code {
                font-size: 24px;
                font-weight: bold;
                text-align: center;
                padding: 15px;
                background-color: #f8f9fa;
                border-radius: 5px;
                margin: 20px 0;
                letter-spacing: 5px;
            }
            .footer {
                margin-top: 30px;
                font-size: 12px;
                color: #777;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Login Verification Code</h2>
            </div>
            
            <p>Dear $firstName,</p>
            
            <p>You are receiving this email because you are attempting to log in to the Regional Institute of Nursing's online admission portal. Please use the verification code below to complete your login:</p>
            
            <div class='verification-code'>$verificationCode</div>
            
            <p>This verification code will expire in 10 minutes for security reasons.</p>
            
            <p>If you did not attempt to log in, please ignore this email and consider changing your password.</p>
            
            <p>For any queries regarding the admission process, please contact our admissions team at:</p>
            <p>Email: admissions@rinursing.edu</p>
            <p>Phone: +91 9862245330</p>
            
            <p>Regards,<br>
            Admissions Team<br>
            Regional Institute of Nursing</p>
            
            <div class='footer'>
                <p>This is an automated message, please do not reply directly to this email.</p>
                <p>&copy; 2025 Regional Institute of Nursing. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Text alternative
    $altBody = "
    Login Verification Code
    
    Dear $firstName,
    
    You are receiving this email because you are attempting to log in to the Regional Institute of Nursing's online admission portal. Please use the verification code below to complete your login:
    
    $verificationCode
    
    This verification code will expire in 10 minutes for security reasons.
    
    If you did not attempt to log in, please ignore this email and consider changing your password.
    
    For any queries regarding the admission process, please contact our admissions team at:
    Email: admissions@rinursing.edu
    Phone: +91 9862245330
    
    Regards,
    Admissions Team
    Regional Institute of Nursing
    
    This is an automated message, please do not reply directly to this email.
    © 2025 Regional Institute of Nursing. All rights reserved.
    ";
    
    // Send the email
    return sendEmail($to, $subject, $body, $altBody);
}
?> 
 
 
 
 
 
 