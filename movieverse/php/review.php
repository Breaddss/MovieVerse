<?php

require_once __DIR__ . '/../includes/auth.php';
start_secure_session();

$body = json_decode(file_get_contents('php://input'), true) ?: [];

if (!is_logged_in()) {
    json_response(['ok' => false, 'error' => 'Please log in first.'], 401);
}
if (!csrf_check($body['csrf_token'] ?? '')) {
    json_response(['ok' => false, 'error' => 'Invalid request token.'], 403);
}

$userId = current_user_id();
$action = $body['action']  ?? '';
$imdbId = $body['imdb_id'] ?? '';

if (!$imdbId) {
    json_response(['ok' => false, 'error' => 'Missing movie id.'], 400);
}

$stmt = db()->prepare('SELECT id FROM movies WHERE imdb_id = ? LIMIT 1');
$stmt->execute([$imdbId]);
$movieId = $stmt->fetchColumn();
if (!$movieId) {
    $details = omdb_details($imdbId);
    if (!$details) json_response(['ok' => false, 'error' => 'Movie not found.'], 404);
    $movieId = movie_upsert_from_omdb($details);
}

if ($action === 'save') {
    $rating = filter_var($body['rating'] ?? '', FILTER_VALIDATE_INT);
    if ($rating === false || $rating < 1 || $rating > 10) {
        json_response(['ok' => false, 'error' => 'Rating must be a number from 1 to 10.'], 400);
    }
    $text = trim((string)($body['review_text'] ?? ''));
    if (mb_strlen($text) > 2000) {
        json_response(['ok' => false, 'error' => 'Review is too long (max 2000 characters).'], 400);
    }

    $stmt = db()->prepare(
        'INSERT INTO reviews (user_id, movie_id, rating, review_text)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE rating = VALUES(rating), review_text = VALUES(review_text)'
    );
    $stmt->execute([$userId, $movieId, $rating, $text]);
    json_response(['ok' => true]);
}

if ($action === 'delete') {
    $stmt = db()->prepare('DELETE FROM reviews WHERE user_id = ? AND movie_id = ?');
    $stmt->execute([$userId, $movieId]);
    json_response(['ok' => true]);
}

json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
