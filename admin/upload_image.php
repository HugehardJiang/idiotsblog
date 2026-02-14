<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
// session_start(); // Already started in config.php included via db.php

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file['error']]);
        exit;
    }

    // Validate size (e.g., max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 5MB).']);
        exit;
    }

    // Validate type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']);
        exit;
    }

    $uploadDir = '../assets/uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
            exit;
        }
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    // Generate unique name
    $filename = uniqid('img_', true) . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        // Return full URL
        // SITE_URL is defined in config.php (e.g. https://www.idiots.cn)
        // We need to ensure no double slash if SITE_URL ends with /
        $baseUrl = rtrim(SITE_URL, '/');
        $fullUrl = $baseUrl . '/assets/uploads/' . $filename;

        echo json_encode(['success' => true, 'url' => $fullUrl, 'filename' => $filename]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file. Check permissions.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or invalid request.']);
}
