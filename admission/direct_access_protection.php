<?php
/**
 * Direct Access Protection
 * 
 * This script prevents direct access to HTML files.
 * It should be included at the top of HTML files that should
 * only be accessed through their corresponding PHP wrapper.
 */

// Check if the INCLUDED constant is defined (set by the PHP wrapper)
if (!defined('INCLUDED')) {
    // Determine the PHP wrapper filename based on the current HTML filename
    $currentFile = basename($_SERVER['SCRIPT_FILENAME']);
    $phpWrapper = str_replace('.html', '.php', $currentFile);
    
    // Redirect to the PHP wrapper
    header("Location: $phpWrapper");
    exit;
}
?> 
 
 
 
 
 
 