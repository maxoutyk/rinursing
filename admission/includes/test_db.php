<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connect.php';

echo "<h2>Database Connection Test</h2>";

if (isset($conn) && $conn instanceof mysqli) {
    echo "<p style='color:green'>Database connection successful!</p>";
    
    // Test query to check if we can read from the database
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM users");
        $row = $result->fetch_assoc();
        echo "<p>Total users in database: " . $row['total'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error reading from database: " . $e->getMessage() . "</p>";
    }
    
    // Test if we can insert a record
    try {
        // Generate a test email with timestamp to ensure uniqueness
        $testEmail = "test_" . time() . "@example.com";
        
        echo "<p>Attempting to insert test user with email: $testEmail</p>";
        
        // Generate verification token
        $verificationToken = bin2hex(random_bytes(32));
        
        // Hash password
        $hashedPassword = password_hash("TestPassword123!", PASSWORD_DEFAULT);
        
        // Insert user into database
        $insertQuery = "INSERT INTO users (first_name, last_name, email, phone, password, verification_token, status) 
                       VALUES (?, ?, ?, ?, ?, ?, 'unverified')";
        
        $stmt = $conn->prepare($insertQuery);
        $firstName = "Test";
        $lastName = "User";
        $phone = "1234567890";
        
        $stmt->bind_param("ssssss", $firstName, $lastName, $testEmail, $phone, $hashedPassword, $verificationToken);
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            echo "<p style='color:green'>Test user inserted successfully! User ID: $userId</p>";
            
            // Clean up - delete the test user
            $conn->query("DELETE FROM users WHERE id = $userId");
            echo "<p>Test user deleted for cleanup.</p>";
        } else {
            echo "<p style='color:red'>Failed to insert test user: " . $stmt->error . "</p>";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo "<p style='color:red'>Error testing database insertion: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>Database connection failed!</p>";
}

// Check database permissions for the user
try {
    $username = $db_user ?? 'rinursing_user';
    echo "<h3>Checking database permissions for user: $username</h3>";
    
    $permResult = $conn->query("SHOW GRANTS FOR CURRENT_USER()");
    echo "<ul>";
    while ($perm = $permResult->fetch_array()) {
        echo "<li>" . htmlspecialchars($perm[0]) . "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error checking permissions: " . $e->getMessage() . "</p>";
}

// Close the connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
    echo "<p>Database connection closed.</p>";
}
?> 
 
 
 
 
 
 