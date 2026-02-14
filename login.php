<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role, avatar FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'user'; // Default to user if null
            $_SESSION['avatar'] = $user['avatar'];

            // Redirect based on role
            if ($_SESSION['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}

$pageTitle = 'Login - Idiots';
require_once 'includes/header.php';
?>

<main class="container">
    <div class="auth-container">
        <h1>欢迎回来</h1>

        <?php if ($error): ?>
            <div class="msg error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit">登录</button>
        </form>

        <div style="margin-top: 24px; text-align: center;">
            <a href="sso_login.php" class="btn-sso">🚀 使用 Wildhens 账号登录</a>
        </div>

        <p class="auth-footer">还没有账号? <a href="register.php">立即注册</a>.</p>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>