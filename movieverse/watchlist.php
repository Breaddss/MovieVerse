<?php

require_once __DIR__ . '/includes/auth.php';
require_login();

$userId = current_user_id();

$stmt = db()->prepare(
    'SELECT m.imdb_id, m.title, m.year, m.poster, w.watched
     FROM watchlists w JOIN movies m ON m.id = w.movie_id
     WHERE w.user_id = ? ORDER BY w.added_at DESC'
);
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

$toWatch = array_filter($items, fn($i) => !$i['watched']);
$watched = array_filter($items, fn($i) => $i['watched']);

$pageTitle = 'Watchlist';
require __DIR__ . '/includes/header.php';

function watch_card($m) {
    $base = BASE_URL;
    $hasPoster = !empty($m['poster']);
    ob_start(); ?>
    <div class="movie-card" data-row>
      <a href="<?= $base ?>/movie.php?id=<?= e($m['imdb_id']) ?>">
        <div class="poster-wrap">
          <?php if ($hasPoster): ?>
            <img src="<?= e($m['poster']) ?>" alt="<?= e($m['title']) ?> poster" loading="lazy">
          <?php else: ?>
            <div class="poster-fallback"><?= e($m['title']) ?></div>
          <?php endif; ?>
          <?php if ($m['watched']): ?><span class="rating-badge">✓ Watched</span><?php endif; ?>
        </div>
      </a>
      <div class="movie-card-body">
        <h3><?= e($m['title']) ?></h3>
        <div class="year"><?= e($m['year']) ?></div>
        <div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap">
          <button class="btn btn-outline btn-sm" data-action="toggle-watched" data-imdb="<?= e($m['imdb_id']) ?>">
            <?= $m['watched'] ? 'Unwatch' : 'Mark watched' ?>
          </button>
          <button class="btn btn-danger btn-sm" data-action="remove-watchlist" data-remove-row="1" data-imdb="<?= e($m['imdb_id']) ?>">
            Remove
          </button>
        </div>
      </div>
    </div>
    <?php return ob_get_clean();
}
?>

<div class="section-head">
  <div>
    <span class="eyebrow">Saved for you</span>
    <h2>My watchlist</h2>
  </div>
  <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/search.php">+ Add more</a>
</div>

<?php if (!$items): ?>
  <div class="empty">
    <div class="big">📭</div>
    <p>Your watchlist is empty.</p>
    <a class="btn btn-primary" style="margin-top:14px" href="<?= BASE_URL ?>/search.php">Find movies to add</a>
  </div>
<?php else: ?>

  <h3 style="margin:10px 0 14px">To watch (<?= count($toWatch) ?>)</h3>
  <?php if (!$toWatch): ?>
    <p class="muted" style="margin-bottom:20px">Nothing left to watch — nice work!</p>
  <?php else: ?>
    <div class="movie-grid" style="margin-bottom:30px">
      <?php foreach ($toWatch as $m) echo watch_card($m); ?>
    </div>
  <?php endif; ?>

  <h3 style="margin:10px 0 14px">Watched (<?= count($watched) ?>)</h3>
  <?php if (!$watched): ?>
    <p class="muted">You haven't marked anything as watched yet.</p>
  <?php else: ?>
    <div class="movie-grid">
      <?php foreach ($watched as $m) echo watch_card($m); ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
