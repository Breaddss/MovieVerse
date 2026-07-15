<?php

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'About';
require __DIR__ . '/includes/header.php';
?>

<section class="hero" style="padding:48px 40px">
  <span class="eyebrow" style="color:#8fe0b3">About the project</span>
  <h1>MovieVerse</h1>
  <p>A clean, modern movie companion built as a university project to demonstrate
     secure authentication, database design, API integration and responsive UI —
     using only HTML, CSS, JavaScript, PHP and MySQL.</p>
</section>

<div class="stat-grid" style="margin-top:30px">
  <div class="card">
    <h3>🔐 Secure auth</h3>
    <p class="muted">Triple-factor login: password, a time-limited email OTP, and an
       OpenSSL cryptographic token — followed by a signed JWT and a secure session.</p>
  </div>
  <div class="card">
    <h3>🗄️ MySQL</h3>
    <p class="muted">Normalised tables for users, movies, reviews and watchlists, with
       every query run through prepared statements.</p>
  </div>
  <div class="card">
    <h3>🎬 OMDb API</h3>
    <p class="muted">Live movie data — posters, cast, genres and ratings — fetched and
       displayed with AJAX, no page reloads.</p>
  </div>
</div>

<div class="card" style="margin-top:22px">
  <h2 style="margin-bottom:10px">How login works</h2>
  <p class="muted">
    Factor 1 verifies your password with <code>password_verify()</code>.
    Factor 2 emails a six-digit one-time code that expires after two minutes.
    Factor 3 generates a random token with PHP's OpenSSL functions and verifies it by
    decrypting a stored value with AES-256. Only when all three succeed are you issued a
    JSON Web Token and signed in. Logging out blacklists that token so it can't be reused.
  </p>
</div>

<div class="card" style="margin-top:22px">
  <h2 style="margin-bottom:10px">Tech stack</h2>
  <div class="meta-row">
    <span class="chip">HTML5</span>
    <span class="chip">CSS3 (Flexbox + Grid)</span>
    <span class="chip">Vanilla JavaScript</span>
    <span class="chip">PHP</span>
    <span class="chip">MySQL</span>
    <span class="chip orange">OMDb API</span>
  </div>
  <p class="muted" style="margin-top:12px">No frameworks. No libraries. Just the fundamentals, done carefully.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
