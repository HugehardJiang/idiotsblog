<?php
require_once 'config.php';

// Generate state to prevent CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['oauth_state'] = bin2hex(random_bytes(16));

$params = [
    'client_id' => SSO_CLIENT_ID,
    'redirect_uri' => SSO_REDIRECT_URI,
    'state' => $_SESSION['oauth_state']
];

$loginUrl = SSO_AUTH_SERVER . '/login.php?' . http_build_query($params);

header('Location: ' . $loginUrl);
exit;
