<?php
// Start session
session_start();

// Set a fake login session
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'test@example.com';

// Redirect to login.php to test if it redirects properly to dashboard
echo "Setting logged_in session and redirecting to login.php in 2 seconds...";
?>
<script>
    setTimeout(function() {
        window.location.href = 'login.php';
    }, 2000);
</script> 
 
 
 
 
 
 