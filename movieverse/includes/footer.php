</main>

<footer class="footer">
  <div class="container footer-inner">
    <div>
      <a class="brand" href="<?= BASE_URL ?>/index.php">
        <span class="brand-mark">▣</span> Movie<span class="brand-accent">Verse</span>
      </a>
      <p class="muted">Discover, rate and track the films you love.</p>
    </div>

    <div class="footer-cols">
      <div>
        <h4>Explore</h4>
        <a href="<?= BASE_URL ?>/index.php">Home</a>
        <a href="<?= BASE_URL ?>/search.php">Search</a>
        <a href="<?= BASE_URL ?>/about.php">About</a>
      </div>
      <div>
        <h4>Account</h4>
        <a href="<?= BASE_URL ?>/login.php">Log in</a>
        <a href="<?= BASE_URL ?>/register.php">Sign up</a>
        <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p class="muted">© <?= date('Y') ?> MovieVerse · Built with HTML, CSS, JS, PHP &amp; MySQL · Data from OMDb</p>
  </div>
</footer>

<script>
  window.BASE_URL   = "<?= BASE_URL ?>";
  window.CSRF_TOKEN = "<?= e(csrf_token()) ?>";
</script>
<script src="<?= BASE_URL ?>/js/main.js"></script>
</body>
</html>
