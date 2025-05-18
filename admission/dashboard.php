<?php
/**
 * Dashboard PHP Wrapper
 * 
 * This script ensures that:
 * 1. Only authenticated users can access the dashboard
 * 2. Session handling is consistent with the rest of the application
 * 3. Retrieves all necessary data for the dashboard
 */

// Include authentication requirement check
require_once 'includes/required_auth.php';

// Define constant to indicate this file was included properly
define('INCLUDED', true);

// Any additional dashboard-specific session variables or initialization can go here
$_SESSION['last_dashboard_access'] = date('Y-m-d H:i:s');

// Get all dashboard data
$dashboardData = require_once 'dashboard_data.php';

// Set JavaScript variable to indicate proper inclusion
echo "<script>window.__INCLUDED__ = true;</script>";

// Set dashboard data as JavaScript variable
echo "<script>window.dashboardData = " . json_encode($dashboardData) . ";</script>";

// Include the dashboard HTML content
include 'dashboard.html';
?> 
 
 
 
 
 
 