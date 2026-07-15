<?php

require_once __DIR__ . '/includes/auth.php';
start_secure_session();

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$errors = [];
$notice = '';
$stage  = $_SESSION['login_stage'] ?? 'credentials';
$cryptoToken = $_SESSION['crypto_display_token'] ?? '';

if (!empty($_SESSION['flash_success'])) {
    $notice = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

function reset_login_flow() {
    unset(
        $_SESSION['login_stage'], $_SESSION['login_user_id'], $_SESSION['login_email'],
        $_SESSION['crypto_cipher'], $_SESSION['crypto_iv'], $_SESSION['crypto_display_token']
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please try again.';
    } else {
        $step = $_POST['stage'] ?? 'credentials';

        if ($step === 'credentials') {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (login_too_many_attempts($email)) {
                $errors[] = 'Too many login attempts. Please wait a few minutes and try again.';
            } else {
                $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['login_user_id'] = $user['id'];
                    $_SESSION['login_email']   = $user['email'];
                    $_SESSION['login_stage']   = 'otp';
                    clear_login_attempts($email);

                    otp_generate_and_send($user['id'], $user['email']);
                    $stage  = 'otp';
                    $notice = 'We sent a six-digit code to your email. It expires in 2 minutes.';
                } else {
                    record_login_attempt($email);
                    $errors[] = 'Incorrect email or password.';
                }
            }
        }

        elseif ($step === 'otp') {
            $code   = trim($_POST['otp'] ?? '');
            $userId = $_SESSION['login_user_id'] ?? null;

            if (!$userId) {
                reset_login_flow();
                $errors[] = 'Session expired. Please start again.';
            } elseif (!preg_match('/^\d{6}$/', $code)) {
                $errors[] = 'Enter the six-digit code.';
                $stage = 'otp';
            } elseif (otp_verify($userId, $code)) {
                $token = crypto_factor_generate();
                $_SESSION['crypto_display_token'] = $token; 
                $_SESSION['login_stage'] = 'crypto';
                $stage = 'crypto';
                $cryptoToken = $token;
                $notice = 'Final step: confirm your security token to finish signing in.';
            } else {
                $errors[] = 'That code is invalid or has expired.';
                $stage = 'otp';
            }
        }

        elseif ($step === 'crypto') {
            $token  = trim($_POST['crypto_token'] ?? '');
            $userId = $_SESSION['login_user_id'] ?? null;

            if (!$userId) {
                reset_login_flow();
                $errors[] = 'Session expired. Please start again.';
            } elseif (crypto_factor_verify($token)) {

                $stmt = db()->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $username = $stmt->fetchColumn();

                session_regenerate_id(true);

                $_SESSION['user_id'] = $userId;
                $_SESSION['jwt']     = jwt_create($userId, $username);

                reset_login_flow();
                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;
            } else {
                reset_login_flow();
                $errors[] = 'Security token verification failed. Please log in again.';
                $stage = 'credentials';
            }
        }
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';

$dot = function ($name) use ($stage) {
    $order = ['credentials' => 1, 'otp' => 2, 'crypto' => 3];
    return $order[$stage] >= $order[$name] ? 'on' : '';
};
?>

<div class="auth-wrap">
  <div class="card auth-card">
    <h1>Log in</h1>
    <p class="sub">Triple-factor authentication keeps your account safe.</p>

    <div class="step-dots">
      <span class="<?= $dot('credentials') ?>"></span>
      <span class="<?= $dot('otp') ?>"></span>
      <span class="<?= $dot('crypto') ?>"></span>
    </div>

    <?php if ($notice): ?><div class="alert alert-info"><?= e($notice) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <?php if ($stage === 'credentials'): ?>
      <form method="post" action="<?= BASE_URL ?>/login.php">
        <?= csrf_field() ?>
        <input type="hidden" name="stage" value="credentials">
        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required autofocus>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Continue</button>
      </form>
      <p class="center muted" style="margin-top:16px">
        New here? <a href="<?= BASE_URL ?>/register.php" style="color:var(--green);font-weight:600">Create an account</a>
      </p>

    <?php elseif ($stage === 'otp'): ?>
      <form method="post" action="<?= BASE_URL ?>/login.php">
        <?= csrf_field() ?>
        <input type="hidden" name="stage" value="otp">
        <div class="field">
          <label for="otp">Six-digit code</label>
          <input type="text" id="otp" name="otp" inputmode="numeric" maxlength="6"
                 pattern="\d{6}" placeholder="••••••" required autofocus
                 style="letter-spacing:.4em;text-align:center;font-size:1.3rem">
          <small>Check your email (or in dev mode, <code>uploads/otp_log.txt</code>).</small>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Verify code</button>
      </form>

    <?php elseif ($stage === 'crypto'): ?>
      <form method="post" action="<?= BASE_URL ?>/login.php">
        <?= csrf_field() ?>
        <input type="hidden" name="stage" value="crypto">
        <div class="field">
          <label>Security token</label>
          <input type="text" value="<?= e($cryptoToken) ?>" readonly
                 style="font-family:monospace;font-size:.8rem;background:var(--gray-50)">
          <small>
            This one-time token was generated with OpenSSL. The server verifies it by
            decrypting a stored value with AES-256. In a real product it would come from
            an authenticator app or hardware key.
          </small>
        </div>
        <input type="hidden" name="crypto_token" value="<?= e($cryptoToken) ?>">
        <button class="btn btn-primary btn-block" type="submit">Verify &amp; finish</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
