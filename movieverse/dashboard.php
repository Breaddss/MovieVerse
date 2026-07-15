<?php

require_once __DIR__ . '/includes/auth.php';
require_login();                 

$userId = current_user_id();
$me     = current_user();

$watchCount  = db()->prepare('SELECT COUNT(*) FROM watchlists WHERE user_id = ?');
$watchCount->execute([$userId]);
$watchCount = (int)$watchCount->fetchColumn();

$reviewCount = db()->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ?');
$reviewCount->execute([$userId]);
$reviewCount = (int)$reviewCount->fetchColumn();

$watchedCount = db()->prepare('SELECT COUNT(*) FROM watchlists WHERE user_id = ? AND watched = 1');
$watchedCount->execute([$userId]);
$watchedCount = (int)$watchedCount->fetchColumn();

$rv = db()->prepare(
    'SELECT m.imdb_id, m.title, m.year, m.poster
     FROM recently_viewed v JOIN movies m ON m.id = v.movie_id
     WHERE v.user_id = ? ORDER BY v.viewed_at DESC LIMIT 6'
);
$rv->execute([$userId]);
$recent = $rv->fetchAll();

$act = db()->prepare(
    'SELECT r.rating, r.updated_at, m.title, m.imdb_id
     FROM reviews r JOIN movies m ON m.id = r.movie_id
     WHERE r.user_id = ? ORDER BY r.updated_at DESC LIMIT 5'
);
$act->execute([$userId]);
$activity = $act->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="section-head">
  <div>
    <span class="eyebrow">Welcome back</span>
    <h2><?= e($me['username']) ?>'s dashboard</h2>
  </div>
  <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/search.php">+ Find a movie</a>
</div>

<div class="stat-grid">
  <div class="stat"><div class="num"><?= $watchCount ?></div><div class="label">In watchlist</div></div>
  <div class="stat"><div class="num"><?= $reviewCount ?></div><div class="label">Reviews written</div></div>
  <div class="stat"><div class="num"><?= $watchedCount ?></div><div class="label">Movies watched</div></div>
</div>

<div class="section-head"><h2>Recently viewed</h2></div>
<?php if (!$recent): ?>
  <div class="card"><p class="muted">Nothing here yet — go explore some movies!</p></div>
<?php else: ?>
  <div class="movie-grid">
    <?php foreach ($recent as $m): $hasPoster = !empty($m['poster']); ?>
      <a class="movie-card" href="<?= BASE_URL ?>/movie.php?id=<?= e($m['imdb_id']) ?>">
        <div class="poster-wrap">
          <?php if ($hasPoster): ?>
            <img src="<?= e($m['poster']) ?>" alt="<?= e($m['title']) ?> poster" loading="lazy">
          <?php else: ?>
            <div class="poster-fallback"><?= e($m['title']) ?></div>
          <?php endif; ?>
        </div>
        <div class="movie-card-body">
          <h3><?= e($m['title']) ?></h3>
          <div class="year"><?= e($m['year']) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="section-head"><h2>Recent activity</h2></div>
<div class="card">
  <?php if (!$activity): ?>
    <p class="muted">You haven't reviewed anything yet.</p>
  <?php else: ?>
    <?php foreach ($activity as $a): ?>
      <div class="review">
        <div class="head">
          <span class="who">
            You rated
            <a href="<?= BASE_URL ?>/movie.php?id=<?= e($a['imdb_id']) ?>" style="color:var(--green)">
              <?= e($a['title']) ?>
            </a>
          </span>
          <span class="stars">⭐ <?= (int)$a['rating'] ?>/10</span>
        </div>
        <small class="muted"><?= e(date('M j, Y · g:i a', strtotime($a['updated_at']))) ?></small>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
