<?php
// Database configuration
define('DB_HOST', 'YOUR_DB_HOST');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASS');
define('DB_NAME', 'YOUR_DB_NAME');

// Site configuration
define('SITE_NAME', 'YOUR_SITE_NAME');
define('SITE_DOMAIN', 'YOUR_SITE_DOMAIN');
define('SITE_URL', 'YOUR_SITE_URL'); // e.g. https://www.example.com
define('ENABLE_ABOUT_PAGE', true); // Set to false to disable the about page

// Error reporting (turn off for production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Wildhens Passport SSO Configuration (Optional)
define('SSO_CLIENT_ID', 'YOUR_SSO_CLIENT_ID');
define('SSO_CLIENT_SECRET', 'YOUR_SSO_CLIENT_SECRET');
define('SSO_REDIRECT_URI', SITE_URL . '/sso_callback.php');
define('SSO_AUTH_SERVER', 'https://www.wildhens.com');

// Start session
session_start();
?>