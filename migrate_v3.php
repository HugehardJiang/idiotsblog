<?php
require_once 'includes/db.php';

echo "Starting migration v3...\n";

try {
    // Add wildhens_uid column
    $pdo->exec("ALTER TABLE users ADD COLUMN wildhens_uid VARCHAR(100) UNIQUE DEFAULT NULL");
    echo "Added wildhens_uid column.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "wildhens_uid column already exists.\n";
    } else {
        echo "Error adding wildhens_uid: " . $e->getMessage() . "\n";
    }
}

try {
    // Add avatar column
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
    echo "Added avatar column.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "avatar column already exists.\n";
    } else {
        echo "Error adding avatar: " . $e->getMessage() . "\n";
    }
}

echo "Migration v3 completed.\n";
