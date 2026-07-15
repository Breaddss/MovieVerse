<?php
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';

$featured = omdb_search('Avengers');
?>

<section class="hero">
  <span class="eyebrow" style="color:#8fe0b3">Your film companion</span>
  <h1>Discover, rate and track the movies you love.</h1>
  <p>Search thousands of films, build a personal watchlist, and share honest reviews — all in one clean, modern place.</p>

  <form class="hero-search" action="<?= BASE_URL ?>/search.php" method="get">
    <div class="search-bar">
      <input type="text" name="q" placeholder="Search for a movie…" aria-label="Search movies">
      <button class="btn btn-primary" type="submit">Search</button>
    </div>
  </form>
</section>

<div class="section-head">
  <div>
    <span class="eyebrow">Popular right now</span>
    <h2>Trending movies</h2>
  </div>
  <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/search.php">Browse all</a>
</div>

<?php if (!$featured): ?>
  <div class="card">
    <p class="muted">
      No movies to show yet. Add your free OMDb API key in
      <code>config/config.php</code> to load posters, or use the search bar above.
    </p>
  </div>
<?php else: ?>
  <div class="movie-grid">
    <?php foreach (array_slice($featured, 0, 10) as $m):
        $hasPoster = ($m['Poster'] ?? 'N/A') !== 'N/A'; ?>
      <a class="movie-card" href="<?= BASE_URL ?>/movie.php?id=<?= e($m['imdbID']) ?>">
        <div class="poster-wrap">
          <?php if ($hasPoster): ?>
            <img src="<?= e($m['Poster']) ?>" alt="<?= e($m['Title']) ?> poster" loading="lazy">
          <?php else: ?>
            <div class="poster-fallback"><?= e($m['Title']) ?></div>
          <?php endif; ?>
        </div>
        <div class="movie-card-body">
          <h3><?= e($m['Title']) ?></h3>
          <div class="year"><?= e($m['Year']) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="section-head"><h2>Why MovieVerse?</h2></div>
<div class="stat-grid">
  <div class="card">
    <h3>🔎 Smart search</h3>
    <p class="muted">Instant results as you type, powered by the OMDb database.</p>
  </div>
  <div class="card">
    <h3>📌 Watchlists</h3>
    <p class="muted">Save films for later and tick them off as you watch.</p>
  </div>
  <div class="card">
    <h3>⭐ Honest reviews</h3>
    <p class="muted">Rate from 1 to 10 and see the community average.</p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
