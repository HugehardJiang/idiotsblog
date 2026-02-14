<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$isSsoUser = !empty($user['wildhens_uid']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. [REMOVED] Avatar Upload

    // 2. Handle Password Change
    if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
        $oldPass = $_POST['old_password'] ?? '';
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];

        if ($newPass !== $confirmPass) {
            $error = "New passwords do not match.";
        } elseif (empty($oldPass)) {
            $error = "Please enter your old password.";
        } else {
            if (password_verify($oldPass, $user['password'])) {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $updateStmt->execute(['password' => $hash, 'id' => $userId]);
                $message = "Password changed successfully!";
            } else {
                $error = "Incorrect old password.";
            }
        }
    }
}

$pageTitle = 'Settings - Idiots';
require_once 'includes/header.php';
?>

<div class="container" style="padding-top: 40px; max-width: 600px;">
    <h1 style="font-family: var(--font-serif); margin-bottom: 30px;">User Settings (个人设置)</h1>

    <?php if ($message): ?>
        <div class="msg success">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="msg error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div
        style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: var(--shadow-sm); border: 1px solid #eee;">

        <!-- Avatar Section Removed -->

        <!-- Password Section -->
        <h3 style="margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Change Password</h3>
        <form method="POST">
            <div class="form-group">
                <label>Old Password</label>
                <input type="password" name="old_password" required placeholder="Current Password">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required placeholder="New Password">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required placeholder="Confirm New Password">
            </div>
            <button type="submit" class="btn-submit">Change Password</button>
        </form>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>