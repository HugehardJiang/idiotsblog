<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validate State
$state = $_GET['state'] ?? '';
if (!isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
    die('Error: Invalid state. Possible CSRF attack.');
}

// 2. Get Code
$code = $_GET['code'] ?? '';
if (!$code) {
    die('Error: No authorization code provided.');
}

// 3. Exchange Code for Access Token
$tokenUrl = SSO_AUTH_SERVER . '/api.php?action=token';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => SSO_CLIENT_ID,
    'client_secret' => SSO_CLIENT_SECRET,
    'code' => $code
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Disable SSL verification for demo if needed, but for production should be enabled.
// Given instructions say https is used, but for now let's be permissive or default.
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
if (curl_errno($ch)) {
    die('Curl error: ' . curl_error($ch));
}
curl_close($ch);

$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    die('Error getting token: ' . $response);
}

$accessToken = $tokenData['access_token'];

// 4. Get User Info
$userUrl = SSO_AUTH_SERVER . '/api.php?action=userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userResponse, true);

if (!$userInfo || !isset($userInfo['sub'])) {
    die('Error getting user info: ' . $userResponse);
}

// 5. Login or Register Logic
$wildhensUid = $userInfo['sub'];
$name = $userInfo['name'];
$name = $userInfo['name'];
// $avatar = $userInfo['picture'] ?? null;
// $email = $userInfo['email'] ?? ''; 

// Check if user exists by wildhens_uid
$stmt = $pdo->prepare("SELECT * FROM users WHERE wildhens_uid = :uid");
$stmt->execute(['uid' => $wildhensUid]);
$user = $stmt->fetch();

if ($user) {
    // Determine if we need to update avatar or name
    // Optional: update avatar on every login to keep it fresh
    /* if ($user['avatar'] !== $avatar) {
        $updateStmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
        $updateStmt->execute(['avatar' => $avatar, 'id' => $user['id']]);
        $user['avatar'] = $avatar;
    } */
} else {
    // User doesn't exist, check if username is taken (fallback)
    // If username taken, append random numbers
    $baseUsername = $name;
    $finalUsername = $baseUsername;
    $counter = 1;

    while (true) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $checkStmt->execute(['username' => $finalUsername]);
        if ($checkStmt->fetchColumn() == 0) {
            break;
        }
        $finalUsername = $baseUsername . '_' . $counter++;
    }

    // Register new user
    // Generate a random password since they use SSO
    $randomPass = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);

    $insertStmt = $pdo->prepare("INSERT INTO users (username, password, role, wildhens_uid) VALUES (:username, :password, 'user', :uid)");
    $insertStmt->execute([
        'username' => $finalUsername,
        'password' => $randomPass,
        'uid' => $wildhensUid
    ]);

    $userId = $pdo->lastInsertId();

    // Fetch the new user record
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
}

// 6. Set Session
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['role'] = $user['role'];
// $_SESSION['avatar'] = $user['avatar'];

// 7. Redirect Home
header('Location: index.php');
exit;
