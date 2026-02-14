<?php
header('Content-Type: application/json');

// Helper to send JSON response
function sendResponse($success, $message, $data = [])
{
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// Check if locked
if (file_exists(__DIR__ . '/install.lock')) {
    sendResponse(false, '系统已安装，如需重装请删除 install/install.lock 文件');
}

// 1. Validate Input
$required_fields = ['db_host', 'db_user', 'db_name', 'site_name', 'site_url', 'admin_user', 'admin_pass'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        sendResponse(false, "缺少必要参数: $field");
    }
}

$db_host = $_POST['db_host'];
$db_user = $_POST['db_user'];
$db_pass = $_POST['db_pass'] ?? ''; // Can be empty
$db_name = $_POST['db_name'];
$site_name = $_POST['site_name'];
$site_url = rtrim($_POST['site_url'], '/');
$admin_user = $_POST['admin_user'];
$admin_pass = $_POST['admin_pass'];

// 2. Connect to Database
try {
    // Connect without DB name first to create it
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        throw new Exception("数据库连接失败: " . $conn->connect_error);
    }

    // Create Database
    $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query($sql)) {
        throw new Exception("创建数据库失败: " . $conn->error);
    }

    // Select Database
    $conn->select_db($db_name);

    // 3. Import SQL Schema
    $sqlFile = __DIR__ . '/../database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("无法找到 database.sql 文件");
    }

    $sqlContent = file_get_contents($sqlFile);
    // Remove comments
    $lines = explode("\n", $sqlContent);
    $cleanSql = "";
    foreach ($lines as $line) {
        if (substr(trim($line), 0, 2) == '--' || substr(trim($line), 0, 1) == '#')
            continue;
        $cleanSql .= $line . "\n";
    }

    // Split by semicolon (naive split, but works for simple dumps)
    $queries = explode(';', $cleanSql);

    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if (!$conn->query($query)) {
                // Ignore "Table already exists" or "Duplicate entry" warnings if we want to be robust,
                // but for fresh install, error is better.
                // However, user might be reinstalling on existing DB.
                // Let's suppress "Table exists" but throw on others?
                // Actually, standard is to fail or warn. Let's just catch and continue for now?
                // Better approach: database.sql uses CREATE TABLE IF NOT EXISTS.
                // But INSERTs might fail.
                // Let's just log and continue for now, or throw if critical.
                // For simplicity in this script:
                if (strpos($conn->error, "already exists") === false && strpos($conn->error, "Duplicate entry") === false) {
                    throw new Exception("SQL执行错误: " . $conn->error . " | Query: " . substr($query, 0, 50) . "...");
                }
            }
        }
    }

    // 4. Create/Update Admin User
    $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
    // Use prepared statement
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin') ON DUPLICATE KEY UPDATE password=?, role='admin'");
    if (!$stmt) {
        throw new Exception("准备管理员账户语句失败: " . $conn->error);
    }
    $stmt->bind_param("sss", $admin_user, $hashed_password, $hashed_password);
    if (!$stmt->execute()) {
        throw new Exception("创建管理员账户失败: " . $stmt->error);
    }
    $stmt->close();

    // 5. Generate config.php
    $configFile = __DIR__ . '/../config.php';
    $configSample = __DIR__ . '/../config.sample.php';

    if (file_exists($configSample)) {
        $configContent = file_get_contents($configSample);
    } else {
        // Fallback template
        $configContent = "<?php
define('DB_HOST', 'YOUR_DB_HOST');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASS');
define('DB_NAME', 'YOUR_DB_NAME');
define('SITE_NAME', 'YOUR_SITE_NAME');
define('SITE_url', 'YOUR_SITE_URL');
define('SITE_DOMAIN', 'YOUR_SITE_DOMAIN');
// ...
?>";
    }

    $replacements = [
        'YOUR_DB_HOST' => $db_host,
        'YOUR_DB_USER' => $db_user,
        'YOUR_DB_PASS' => $db_pass,
        'YOUR_DB_NAME' => $db_name,
        'YOUR_SITE_NAME' => $site_name,
        'YOUR_SITE_URL' => $site_url,
        // Parse domain from URL
        'YOUR_SITE_DOMAIN' => parse_url($site_url, PHP_URL_HOST)
    ];

    foreach ($replacements as $key => $val) {
        $configContent = str_replace($key, $val, $configContent);
    }

    if (file_put_contents($configFile, $configContent) === false) {
        throw new Exception("无法写入 config.php 文件，请检查权限");
    }

    // 6. Create Lock File
    file_put_contents(__DIR__ . '/install.lock', 'Installed on ' . date('Y-m-d H:i:s'));

    sendResponse(true, '安装成功');

} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
