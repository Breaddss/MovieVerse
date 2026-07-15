<?php

require_once __DIR__ . '/includes/auth.php';
start_secure_session();

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$errors = [];
$old    = ['username' => '', 'email' => '']; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) CSRF protection.
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please try again.';
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $old['username'] = $username;
    $old['email']    = $email;

    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3-50 letters, numbers or underscores.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that username or email already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$username, $email, $hash]);

        $_SESSION['flash_success'] = 'Account created! Please log in.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="card auth-card">
    <h1>Create your account</h1>
    <p class="sub">Join MovieVerse to rate films and build your watchlist.</p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= BASE_URL ?>/register.php" autocomplete="off">
      <?= csrf_field() ?>

      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= e($old['username']) ?>" required>
        <small>3-50 characters. Letters, numbers and underscores.</small>
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <small>At least 8 characters.</small>
      </div>

      <div class="field">
        <label for="confirm">Confirm password</label>
        <input type="password" id="confirm" name="confirm" required>
      </div>

      <button class="btn btn-primary btn-block" type="submit">Create account</button>
    </form>

    <p class="center muted" style="margin-top:16px">
      Already have an account? <a href="<?= BASE_URL ?>/login.php" style="color:var(--green);font-weight:600">Log in</a>
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
