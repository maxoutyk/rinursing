<?php
// Include database connection
require_once 'db_connect.php';

try {
    // Drop existing table to ensure clean slate
    $conn->query("DROP TABLE IF EXISTS `important_dates`");
    
    // Create important_dates table
    $createTable = "CREATE TABLE IF NOT EXISTS `important_dates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `event_date` DATE NOT NULL,
        `status` ENUM('upcoming', 'today', 'passed') DEFAULT 'upcoming',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_title_date` (`title`, `event_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createTable)) {
        echo "✓ Created important_dates table\n";
        
        // Insert default dates
        $currentYear = date('Y');
        $insertDates = "INSERT INTO `important_dates` (`title`, `event_date`) VALUES
            ('Application Start', '$currentYear-06-01'),
            ('Application Deadline', '$currentYear-07-31'),
            ('Application Payment', '$currentYear-08-07'),
            ('Document Verification', '$currentYear-08-15'),
            ('Entrance Examination', '$currentYear-08-30'),
            ('Result Declaration', '$currentYear-09-15'),
            ('Admission Confirmation', '$currentYear-09-30')";
        
        if ($conn->query($insertDates)) {
            echo "✓ Inserted default important dates\n";
        } else {
            echo "✗ Error inserting default dates: " . $conn->error . "\n";
        }
    } else {
        echo "✗ Error creating table: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Close the database connection
$conn->close();
?> 