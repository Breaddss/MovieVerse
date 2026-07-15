<?php

require_once __DIR__ . '/includes/auth.php';
require_login();

$me = current_user();

$err = $_SESSION['flash_error']   ?? '';  unset($_SESSION['flash_error']);
$ok  = $_SESSION['flash_success'] ?? '';  unset($_SESSION['flash_success']);

$hasPic = !empty($me['profile_picture']);
$picUrl = $hasPic ? BASE_URL . '/uploads/' . rawurlencode($me['profile_picture']) : '';

$pageTitle = 'Profile';
require __DIR__ . '/includes/header.php';
?>

<div class="section-head"><h2>Your profile</h2></div>

<?php if ($ok):  ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

<div class="card">
  <div class="profile-head" style="margin-bottom:24px">
    <?php if ($hasPic): ?>
      <img class="avatar" src="<?= e($picUrl) ?>" alt="Profile picture" style="display:block">
    <?php else: ?>
      <div class="avatar"><?= e(strtoupper(substr($me['username'], 0, 1))) ?></div>
    <?php endif; ?>
    <div>
      <h2><?= e($me['username']) ?></h2>
      <p class="muted"><?= e($me['email']) ?></p>
      <p class="muted">Member since <?= e(date('M Y', strtotime($me['created_at']))) ?></p>
    </div>
  </div>

  <form method="post" action="<?= BASE_URL ?>/php/profile.php" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?= e($me['username']) ?>" required>
      <small>3-50 letters, numbers or underscores.</small>
    </div>

    <div class="field">
      <label for="bio">Bio</label>
      <textarea id="bio" name="bio" rows="4" maxlength="500"
        placeholder="Tell people about your taste in movies…"><?= e($me['bio']) ?></textarea>
      <small>Up to 500 characters.</small>
    </div>

    <div class="field">
      <label for="picture">Profile picture</label>
      <input type="file" id="picture" name="picture" accept="image/png,image/jpeg,image/webp">
      <small>JPG, PNG or WEBP, up to 2 MB.</small>
    </div>

    <button class="btn btn-primary" type="submit">Save changes</button>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
