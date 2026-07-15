<?php

require_once __DIR__ . '/includes/auth.php';
start_secure_session();

$imdbId = trim($_GET['id'] ?? '');
if (!preg_match('/^tt\d+$/', $imdbId)) {
    http_response_code(400);
    $pageTitle = 'Movie';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty"><div class="big">🎬</div><p>Invalid movie id.</p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$movie = omdb_details($imdbId);
if (!$movie) {
    $pageTitle = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty"><div class="big">😕</div><p>We couldn\'t find that movie.</p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$movieId = movie_upsert_from_omdb($movie);

$loggedIn = is_logged_in();
$userId   = current_user_id();

if ($loggedIn) {
    $stmt = db()->prepare(
        'INSERT INTO recently_viewed (user_id, movie_id) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE viewed_at = NOW()'
    );
    $stmt->execute([$userId, $movieId]);
}

$inWatchlist = false; $watched = false; $myReview = null;
if ($loggedIn) {
    $stmt = db()->prepare('SELECT watched FROM watchlists WHERE user_id = ? AND movie_id = ?');
    $stmt->execute([$userId, $movieId]);
    if (($row = $stmt->fetch()) !== false) { $inWatchlist = true; $watched = (bool)$row['watched']; }

    $stmt = db()->prepare('SELECT rating, review_text FROM reviews WHERE user_id = ? AND movie_id = ?');
    $stmt->execute([$userId, $movieId]);
    $myReview = $stmt->fetch() ?: null;
}

$stmt = db()->prepare('SELECT AVG(rating) avg_r, COUNT(*) cnt FROM reviews WHERE movie_id = ?');
$stmt->execute([$movieId]);
$agg = $stmt->fetch();
$avg = $agg['avg_r'] ? round($agg['avg_r'], 1) : null;

$stmt = db()->prepare(
    'SELECT r.rating, r.review_text, r.updated_at, u.username
     FROM reviews r JOIN users u ON u.id = r.user_id
     WHERE r.movie_id = ? ORDER BY r.updated_at DESC'
);
$stmt->execute([$movieId]);
$reviews = $stmt->fetchAll();

$hasPoster = ($movie['Poster'] ?? 'N/A') !== 'N/A';
$pageTitle = $movie['Title'];
require __DIR__ . '/includes/header.php';
?>

<div class="detail">
  <div class="poster-wrap">
    <?php if ($hasPoster): ?>
      <img src="<?= e($movie['Poster']) ?>" alt="<?= e($movie['Title']) ?> poster">
    <?php else: ?>
      <div class="poster-fallback"><?= e($movie['Title']) ?></div>
    <?php endif; ?>
  </div>

  <div>
    <h1><?= e($movie['Title']) ?></h1>

    <div class="meta-row">
      <?php if (!empty($movie['Year'])): ?><span class="chip"><?= e($movie['Year']) ?></span><?php endif; ?>
      <?php if (!empty($movie['Rated']) && $movie['Rated'] !== 'N/A'): ?><span class="chip"><?= e($movie['Rated']) ?></span><?php endif; ?>
      <?php if (!empty($movie['Runtime']) && $movie['Runtime'] !== 'N/A'): ?><span class="chip"><?= e($movie['Runtime']) ?></span><?php endif; ?>
      <?php if (!empty($movie['imdbRating']) && $movie['imdbRating'] !== 'N/A'): ?>
        <span class="chip orange">★ IMDb <?= e($movie['imdbRating']) ?></span>
      <?php endif; ?>
      <?php if ($avg !== null): ?>
        <span class="chip orange">⭐ MovieVerse <?= e($avg) ?>/10 (<?= (int)$agg['cnt'] ?>)</span>
      <?php endif; ?>
    </div>

    <?php if (!empty($movie['Genre']) && $movie['Genre'] !== 'N/A'): ?>
      <p><strong>Genre:</strong> <?= e($movie['Genre']) ?></p>
    <?php endif; ?>
    <?php if (!empty($movie['Actors']) && $movie['Actors'] !== 'N/A'): ?>
      <p><strong>Cast:</strong> <?= e($movie['Actors']) ?></p>
    <?php endif; ?>
    <?php if (!empty($movie['Director']) && $movie['Director'] !== 'N/A'): ?>
      <p><strong>Director:</strong> <?= e($movie['Director']) ?></p>
    <?php endif; ?>

    <?php if (!empty($movie['Plot']) && $movie['Plot'] !== 'N/A'): ?>
      <p style="margin-top:14px"><?= e($movie['Plot']) ?></p>
    <?php endif; ?>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:22px">
      <?php if ($loggedIn): ?>
        <button class="btn <?= $inWatchlist ? 'btn-outline' : 'btn-primary' ?>"
                data-action="<?= $inWatchlist ? 'remove-watchlist' : 'add-watchlist' ?>"
                data-toggle="1" data-imdb="<?= e($imdbId) ?>">
          <?= $inWatchlist ? '✓ In watchlist' : '+ Watchlist' ?>
        </button>
        <button class="btn <?= $watched ? 'btn-primary' : 'btn-outline' ?>"
                data-action="toggle-watched" data-imdb="<?= e($imdbId) ?>">
          <?= $watched ? '✓ Watched' : 'Mark watched' ?>
        </button>
      <?php else: ?>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/login.php">Log in to rate &amp; save</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($loggedIn): ?>
<div class="card" style="margin-top:30px">
  <h2 style="margin-bottom:14px"><?= $myReview ? 'Edit your review' : 'Write a review' ?></h2>
  <form id="review-form" data-imdb="<?= e($imdbId) ?>">
    <div class="field">
      <label>Your rating (1-10)</label>
      <div class="rate-input">
        <?php for ($i = 1; $i <= 10; $i++): ?>
          <button type="button" data-val="<?= $i ?>"
            class="<?= ($myReview && $myReview['rating'] >= $i) ? 'active' : '' ?>"><?= $i ?></button>
        <?php endfor; ?>
      </div>
      <input type="hidden" name="rating" value="<?= $myReview ? (int)$myReview['rating'] : '' ?>">
    </div>
    <div class="field">
      <label for="review_text">Your thoughts (optional)</label>
      <textarea id="review_text" name="review_text" rows="4" maxlength="2000"
        placeholder="What did you think?"><?= $myReview ? e($myReview['review_text']) : '' ?></textarea>
    </div>
    <div style="display:flex;gap:10px">
      <button class="btn btn-primary" type="submit"><?= $myReview ? 'Update review' : 'Post review' ?></button>
      <?php if ($myReview): ?>
        <button class="btn btn-danger" type="button" data-action="delete-review" data-imdb="<?= e($imdbId) ?>">Delete</button>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card" style="margin-top:22px">
  <h2 style="margin-bottom:6px">Reviews <span class="muted">(<?= count($reviews) ?>)</span></h2>
  <?php if (!$reviews): ?>
    <p class="muted">No reviews yet. Be the first!</p>
  <?php else: ?>
    <?php foreach ($reviews as $r): ?>
      <div class="review">
        <div class="head">
          <span class="who"><?= e($r['username']) ?></span>
          <span class="stars">⭐ <?= (int)$r['rating'] ?>/10</span>
        </div>
        <?php if (!empty($r['review_text'])): ?>
          <p style="margin-top:6px"><?= nl2br(e($r['review_text'])) ?></p>
        <?php endif; ?>
        <small class="muted"><?= e(date('M j, Y', strtotime($r['updated_at']))) ?></small>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
