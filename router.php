<?php
/**
 * Router for PHP's built-in server
 * Run with: php -S localhost:8000 router.php
 */

// Get the requested URI
$uri = $_SERVER['REQUEST_URI'];

// Define HTML to PHP redirects
$redirects = [
    '/admission/login.html' => '/admission/login.php',
    '/admission/register.html' => '/admission/register.php',
    '/admission/verify-login.html' => '/admission/verify-login.php',
    '/admission/forgot-password.html' => '/admission/forgot-password.php',
    '/admission/dashboard.html' => '/admission/dashboard.php',
    '/admission/admission-form.html' => '/admission/admission-form.php'
];

// Check if we need to redirect
if (isset($redirects[$uri])) {
    header('Location: ' . $redirects[$uri]);
    exit;
}

// For static files, let the built-in server handle them
$path = __DIR__ . $uri;
if (is_file($path) && !preg_match('/\.php$/', $uri)) {
    return false;
}

// For PHP files, let the server handle them
if (is_file($path) && preg_match('/\.php$/', $uri)) {
    return false;
}

// Handle 404s
if (!is_file($path)) {
    http_response_code(404);
    echo "404 - File not found";
    exit;
}
?> 
 
 
 
 
 
 