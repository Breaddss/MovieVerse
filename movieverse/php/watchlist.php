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
    if (!$details) {
        json_response(['ok' => false, 'error' => 'Movie not found.'], 404);
    }
    $movieId = movie_upsert_from_omdb($details);
}

switch ($action) {
    case 'add':
        $stmt = db()->prepare(
            'INSERT IGNORE INTO watchlists (user_id, movie_id) VALUES (?, ?)'
        );
        $stmt->execute([$userId, $movieId]);
        json_response(['ok' => true]);
        break;

    case 'remove':
        $stmt = db()->prepare('DELETE FROM watchlists WHERE user_id = ? AND movie_id = ?');
        $stmt->execute([$userId, $movieId]);
        json_response(['ok' => true]);
        break;

    case 'toggle_watched':
        // Ensure it is in the watchlist first.
        db()->prepare('INSERT IGNORE INTO watchlists (user_id, movie_id) VALUES (?, ?)')
            ->execute([$userId, $movieId]);
        // Flip the watched flag.
        $stmt = db()->prepare(
            'UPDATE watchlists SET watched = 1 - watched WHERE user_id = ? AND movie_id = ?'
        );
        $stmt->execute([$userId, $movieId]);

        $s = db()->prepare('SELECT watched FROM watchlists WHERE user_id = ? AND movie_id = ?');
        $s->execute([$userId, $movieId]);
        json_response(['ok' => true, 'watched' => (bool)$s->fetchColumn()]);
        break;

    default:
        json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
}
