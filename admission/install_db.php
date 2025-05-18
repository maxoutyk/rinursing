<?php
/**
 * Database Installation Script
 * 
 * This script creates the necessary database tables for the RIN admission system.
 */

// Set content type to plain text for debugging
header('Content-Type: text/plain');

echo "Database Installation Script\n";
echo "===========================\n\n";

// Include database connection
require_once 'includes/db_connect.php';

// Read SQL from schema file
$sql = file_get_contents('includes/database_schema.sql');

// Split SQL into individual queries
$queries = preg_split('/;\s*$/m', $sql);

// Execute each query
$success = true;
foreach ($queries as $query) {
    // Skip empty queries
    $query = trim($query);
    if (empty($query)) continue;
    
    // Execute query
    try {
        if ($conn->query($query)) {
            // Extract table name from query for CREATE TABLE statements
            if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`(\w+)`/i', $query, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
            } else {
                echo "✓ Executed query: " . substr($query, 0, 50) . "...\n";
            }
        } else {
            echo "✗ Error executing query: " . $conn->error . "\n";
            $success = false;
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        $success = false;
    }
}

// Summary
echo "\n";
if ($success) {
    echo "✓ Database installation completed successfully!\n";
    echo "You can now run the test_dashboard.php script to create sample data.\n";
} else {
    echo "✗ Database installation completed with errors. Please check the output above.\n";
}

// Close database connection
$conn->close();
?> 
 
 
 
 
 
 