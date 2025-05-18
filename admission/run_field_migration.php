<?php
/**
 * Run Field Migration Script
 * 
 * This script runs the database migration to add mother_tongue and annual_income fields
 * to the personal_details table.
 */

// Database connection
require_once 'includes/db_connect.php';

echo "Starting field migration...\n";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/includes/migrations/add_personal_details_fields.sql';
    $migrationContent = file_get_contents($migrationFile);
    
    if ($migrationContent === false) {
        throw new Exception("Could not read migration file: $migrationFile");
    }
    
    echo "Migration file loaded successfully.\n";
    
    // Split SQL statements by semicolon and execute them one by one
    $statements = explode(';', $migrationContent);
    $statements = array_filter($statements, function($statement) {
        return trim($statement) !== '';
    });
    
    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if (!empty($trimmed)) {
            echo "Executing: " . substr($trimmed, 0, 80) . (strlen($trimmed) > 80 ? '...' : '') . "\n";
            
            if ($conn->query($trimmed) === false) {
                throw new Exception("Error executing statement: " . $conn->error);
            }
        }
    }
    
    echo "Field migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
} 