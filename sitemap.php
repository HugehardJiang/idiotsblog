<?php
require_once 'config.php';
require_once 'includes/db.php';

// Set header for XML
header("Content-Type: application/xml; charset=utf-8");

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// 1. Static Pages
$staticPages = [
    '' => 'daily',      // Homepage
    'about' => 'monthly', // About page
    'archive' => 'weekly' // Archive page
];

foreach ($staticPages as $path => $freq) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . SITE_URL . ($path ? '/' . $path : '') . '</loc>' . PHP_EOL;
    echo '    <changefreq>' . $freq . '</changefreq>' . PHP_EOL;
    echo '    <priority>' . ($path == '' ? '1.0' : '0.8') . '</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

// 2. Articles (Pseudo-static: /article/{id})
try {
    $stmt = $pdo->query("SELECT id, updated_at, created_at FROM articles WHERE is_published = 1 ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Use updated_at if available, otherwise created_at
        $date = !empty($row['updated_at']) ? $row['updated_at'] : $row['created_at'];
        // Format date to ISO 8601 (YYYY-MM-DD)
        $dateStr = date('Y-m-d', strtotime($date));

        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . SITE_URL . '/article/' . $row['id'] . '</loc>' . PHP_EOL;
        echo '    <lastmod>' . $dateStr . '</lastmod>' . PHP_EOL;
        echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
        echo '    <priority>0.8</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;
    }
} catch (PDOException $e) {
    // Handle error quietly or log it
}

// 3. Categories (Pseudo-static: /category/{slug})
try {
    $stmt = $pdo->query("SELECT slug FROM categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . SITE_URL . '/category/' . $row['slug'] . '</loc>' . PHP_EOL;
        echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
        echo '    <priority>0.6</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;
    }
} catch (PDOException $e) {
}

// 4. Tags (Pseudo-static: /tag/{slug})
try {
    $stmt = $pdo->query("SELECT slug FROM tags");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . SITE_URL . '/tag/' . $row['slug'] . '</loc>' . PHP_EOL;
        echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
        echo '    <priority>0.5</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;
    }
} catch (PDOException $e) {
}

echo '</urlset>';
?>