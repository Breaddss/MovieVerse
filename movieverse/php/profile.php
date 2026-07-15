<?php

require_once __DIR__ . '/../includes/auth.php';
require_login();

$userId = current_user_id();

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid request token.';
    header('Location: ' . BASE_URL . '/profile.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$bio      = trim($_POST['bio'] ?? '');

if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    $_SESSION['flash_error'] = 'Username must be 3-50 letters, numbers or underscores.';
    header('Location: ' . BASE_URL . '/profile.php');
    exit;
}

$stmt = db()->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
$stmt->execute([$username, $userId]);
if ($stmt->fetch()) {
    $_SESSION['flash_error'] = 'That username is already taken.';
    header('Location: ' . BASE_URL . '/profile.php');
    exit;
}

if (mb_strlen($bio) > 500) {
    $_SESSION['flash_error'] = 'Bio must be 500 characters or fewer.';
    header('Location: ' . BASE_URL . '/profile.php');
    exit;
}

$pictureFilename = null;
if (!empty($_FILES['picture']['name']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['picture']['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        $_SESSION['flash_error'] = 'Profile picture must be JPG, PNG or WEBP.';
        header('Location: ' . BASE_URL . '/profile.php');
        exit;
    }
    if ($_FILES['picture']['size'] > 2 * 1024 * 1024) { // 2 MB cap
        $_SESSION['flash_error'] = 'Profile picture must be under 2 MB.';
        header('Location: ' . BASE_URL . '/profile.php');
        exit;
    }

    $pictureFilename = 'pfp_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    move_uploaded_file($_FILES['picture']['tmp_name'], UPLOAD_DIR . $pictureFilename);
}

if ($pictureFilename) {
    $stmt = db()->prepare('UPDATE users SET username = ?, bio = ?, profile_picture = ? WHERE id = ?');
    $stmt->execute([$username, $bio, $pictureFilename, $userId]);
} else {
    $stmt = db()->prepare('UPDATE users SET username = ?, bio = ? WHERE id = ?');
    $stmt->execute([$username, $bio, $userId]);
}

$_SESSION['flash_success'] = 'Profile updated.';
header('Location: ' . BASE_URL . '/profile.php');
exit;
