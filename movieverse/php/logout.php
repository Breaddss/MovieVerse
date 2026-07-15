<?php

require_once __DIR__ . '/../includes/auth.php';
start_secure_session();

if (!empty($_SESSION['jwt'])) {
    $payload = jwt_verify($_SESSION['jwt']);
    if ($payload && !empty($payload['jti'])) {
        jwt_blacklist($payload['jti'], $payload['exp']);
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: ' . BASE_URL . '/login.php');
exit;
