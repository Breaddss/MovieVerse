<?php

require_once __DIR__ . '/auth.php';
start_secure_session();

$loggedIn = is_logged_in();
$me       = $loggedIn ? current_user() : null;
$title    = isset($pageTitle) ? $pageTitle . ' · MovieVerse' : 'MovieVerse';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>

<header class="navbar">
  <div class="container nav-inner">
    <a class="brand" href="<?= BASE_URL ?>/index.php">
      <span class="brand-mark">▣</span> Movie<span class="brand-accent">Verse</span>
    </a>

    <button class="nav-toggle" aria-label="Toggle menu" onclick="document.body.classList.toggle('nav-open')">
      <span></span><span></span><span></span>
    </button>

    <nav class="nav-links">
      <a href="<?= BASE_URL ?>/index.php">Home</a>
      <a href="<?= BASE_URL ?>/search.php">Search</a>
      <?php if ($loggedIn): ?>
        <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/watchlist.php">Watchlist</a>
        <a href="<?= BASE_URL ?>/profile.php">Profile</a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/about.php">About</a>

      <?php if ($loggedIn): ?>
        <span class="nav-user">Hi, <?= e($me['username']) ?></span>
        <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/php/logout.php">Log out</a>
      <?php else: ?>
        <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/login.php">Log in</a>
        <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/register.php">Sign up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container page">
