<?php
/**
 * Run Education Table Migration Script
 * 
 * This script adds a qualification_name column to the education table
 * and safely converts the level column back to an ENUM.
 */

// Database connection
require_once 'includes/db_connect.php';

echo "Starting education table migration...\n";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/../education_migration.sql';
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
    
    // Now verify the changes
    $result = $conn->query("DESCRIBE education");
    if ($result) {
        $columnFound = false;
        $levelIsEnum = false;
        
        while ($row = $result->fetch_assoc()) {
            if ($row['Field'] == 'qualification_name') {
                $columnFound = true;
                echo "Verified: qualification_name column exists.\n";
            }
            if ($row['Field'] == 'level' && strpos($row['Type'], 'enum') === 0) {
                $levelIsEnum = true;
                echo "Verified: level column is now an ENUM type.\n";
            }
        }
        
        if (!$columnFound) {
            echo "Warning: qualification_name column was not found!\n";
        }
        
        if (!$levelIsEnum) {
            echo "Warning: level column is not an ENUM type!\n";
        }
    }
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
} 