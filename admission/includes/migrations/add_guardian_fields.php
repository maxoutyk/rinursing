<?php
// Include database connection
require_once dirname(__FILE__) . '/../db_connect.php';

try {
    // Add new fields to guardians table
    $alterTable = "ALTER TABLE `guardians`
        MODIFY COLUMN `relationship` ENUM('father', 'mother', 'guardian', 'other') NOT NULL,
        ADD COLUMN `relation_to_applicant` VARCHAR(100) NULL AFTER `relationship`,
        ADD COLUMN `address` TEXT NULL AFTER `email`";
    
    if ($conn->query($alterTable)) {
        echo "✓ Added new guardian fields successfully\n";
    } else {
        echo "✗ Error adding guardian fields: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Close the database connection
$conn->close();
?> 