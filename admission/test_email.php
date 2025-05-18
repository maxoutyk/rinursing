<?php
// Email Test Script
// Include mail utilities
require_once 'includes/mail_utils.php';

// Show all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Email Test</h1>";

// Get test details
$toEmail = isset($_GET['email']) ? $_GET['email'] : '';
$debugMode = isset($_GET['debug']) && $_GET['debug'] == 1;

// Only proceed if email is provided
if (!empty($toEmail)) {
    echo "<h2>Testing email to: " . htmlspecialchars($toEmail) . "</h2>";
    
    // Test SMTP connection and send a test email
    try {
        // Create test email
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Debug output
        if ($debugMode) {
            echo "<h3>Debug Mode Enabled</h3>";
            $mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
        } else {
            $mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_CLIENT;
        }
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.office365.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'support@incitegravity.com'; // Your Office 365 email
        $mail->Password   = 'Init@123##'; // Your password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // SSL options
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Recipients - IMPORTANT: setFrom must use the same email as Username
        $mail->setFrom('support@incitegravity.com', 'RIN Admissions');
        $mail->addAddress($toEmail);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from RIN Admissions';
        $mail->Body    = '<h1>Test Email</h1><p>This is a test email from the RIN Admissions portal.</p>';
        $mail->AltBody = 'This is a test email from the RIN Admissions portal.';
        
        // Send
        echo "<div class='log'><pre>";
        $mail->send();
        echo "</pre></div>";
        
        echo "<div style='color: green; font-weight: bold;'>Message sent successfully to " . htmlspecialchars($toEmail) . "</div>";
        
    } catch (Exception $e) {
        echo "<div style='color: red; font-weight: bold;'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
        
        if ($debugMode) {
            echo "<div class='error-detail'><pre>" . print_r($e, true) . "</pre></div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Test Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .form-container { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .log { background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto; }
        .error-detail { background-color: #fff0f0; padding: 10px; border: 1px solid #ffcccc; margin-top: 10px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"] { width: 300px; padding: 8px; margin-bottom: 10px; }
        button { padding: 8px 16px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .checkbox-container { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="form-container">
        <form method="GET">
            <label for="email">Enter your email to test:</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($toEmail); ?>">
            
            <div class="checkbox-container">
                <input type="checkbox" id="debug" name="debug" value="1" <?php echo $debugMode ? 'checked' : ''; ?>>
                <label for="debug" style="display: inline;">Enable Detailed Debug Mode</label>
            </div>
            
            <button type="submit">Send Test Email</button>
        </form>
    </div>
    
    <div>
        <h3>Troubleshooting Tips:</h3>
        <ul>
            <li>Make sure the SMTP settings are correct</li>
            <li>Verify that the From email address matches the SMTP username</li>
            <li>Check if your Office 365 account allows SMTP authentication</li>
            <li>Try enabling debug mode for detailed error information</li>
        </ul>
    </div>
</body>
</html> 
 
 
 
 
 
 