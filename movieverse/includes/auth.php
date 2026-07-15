<?php
require_once __DIR__ . '/functions.php';

function is_logged_in() {
    if (empty($_SESSION['user_id']) || empty($_SESSION['jwt'])) {
        return false;
    }
    $payload = jwt_verify($_SESSION['jwt']);
    return $payload && (int)$payload['sub'] === (int)$_SESSION['user_id'];
}


function require_login() {
    start_secure_session();
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_user() {
    static $user = null;
    if ($user === null && current_user_id()) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([current_user_id()]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}
