<?php
require_once 'includes/db.php';

try {
    echo "Starting migration v4...\n";

    // Check if status column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM articles LIKE 'status'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Adding 'status' column...\n";
        $pdo->exec("ALTER TABLE articles ADD COLUMN status ENUM('draft', 'pending', 'published') DEFAULT 'draft'");
        
        echo "Updating existing rows...\n";
        // Migrate is_published to status
        $pdo->exec("UPDATE articles SET status = 'published' WHERE is_published = 1");
        $pdo->exec("UPDATE articles SET status = 'draft' WHERE is_published = 0");
        
        echo "Migration successful.\n";
    } else {
        echo "'status' column already exists. Skipping.\n";
    }

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
