<?php

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Search';
require __DIR__ . '/includes/header.php';
?>

<div class="section-head">
  <div>
    <span class="eyebrow">Find something to watch</span>
    <h2>Search movies</h2>
  </div>
</div>

<div class="card" style="margin-bottom:24px">
  <div class="search-bar">
    <input type="text" id="search-input" placeholder="Start typing a movie title…" autofocus
           style="border:2px solid var(--gray-200)">
  </div>
  <p id="search-status" class="muted" style="margin-top:12px"></p>
</div>

<div id="search-results" class="movie-grid"></div>

<script src="<?= BASE_URL ?>/js/search.js"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
