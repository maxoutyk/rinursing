<?php
// Database connection
require_once 'includes/db_connect.php';

// Tables to check
$tables = ['applications', 'personal_details', 'guardians', 'addresses'];

foreach ($tables as $table) {
    echo "Indexes for $table:\n";
    $result = $conn->query("SHOW INDEXES FROM $table");
    
    while ($row = $result->fetch_assoc()) {
        echo " - {$row['Key_name']}\n";
    }
    
    echo "\n";
}

// Close connection
$conn->close();
?> 