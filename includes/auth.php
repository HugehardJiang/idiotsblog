<?php
// Auth middleware
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// echo "DEBUG: AUTH CHECK <br>";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect to general login
    exit;
}

// Check role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: You do not have permission to view this page.");
}
?>