<?php
/**
 * Run Education Table Migration Script
 * 
 * This script runs the database migration to modify the education table structure
 * by adding total_marks, marks_obtained, mode, remarks columns while removing division column.
 */

// Database connection
require_once 'includes/db_connect.php';

echo "Starting education table migration...\n";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/includes/migrations/modify_education_table.sql';
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
    
    echo "Education table migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
} 