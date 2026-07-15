<?php

require_once __DIR__ . '/../includes/functions.php';
start_secure_session();

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    json_response(['ok' => false, 'error' => 'Please type at least two characters.']);
}

$results = omdb_search($q);

$clean = array_map(function ($m) {
    return [
        'imdbID' => $m['imdbID'] ?? '',
        'Title'  => $m['Title']  ?? '',
        'Year'   => $m['Year']   ?? '',
        'Poster' => $m['Poster'] ?? 'N/A',
    ];
}, $results);

json_response(['ok' => true, 'results' => $clean]);