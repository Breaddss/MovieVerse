<?php

require_once __DIR__ . '/db.php';

function start_secure_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,   
        'samesite' => 'Lax',  
        'secure'   => false,  
    ]);

    session_name('MOVIEVERSE_SESSID');
    session_start();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check($token) {
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}


function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_create($userId, $username) {
    $header  = ['alg' => 'HS256', 'typ' => 'JWT'];
    $now     = time();

    $payload = [
        'sub'      => $userId,                    
        'username' => $username,
        'iat'      => $now,                        
        'exp'      => $now + JWT_TTL,              
        'jti'      => bin2hex(random_bytes(16)),  
    ];

    $h = base64url_encode(json_encode($header));
    $p = base64url_encode(json_encode($payload));

    $signature = hash_hmac('sha256', "$h.$p", JWT_SECRET, true);
    $s = base64url_encode($signature);

    return "$h.$p.$s";
}

function jwt_verify($token) {
    if (!$token || substr_count($token, '.') !== 2) {
        return null;
    }

    list($h, $p, $s) = explode('.', $token);

    $expected = base64url_encode(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    if (!hash_equals($expected, $s)) {
        return null; 
    }

    $payload = json_decode(base64url_decode($p), true);
    if (!is_array($payload)) {
        return null;
    }

    if (empty($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }

    if (!empty($payload['jti']) && jwt_is_blacklisted($payload['jti'])) {
        return null;
    }

    return $payload;
}

function jwt_blacklist($jti, $exp) {
    $stmt = db()->prepare(
        'INSERT IGNORE INTO jwt_blacklist (jti, expires_at) VALUES (?, FROM_UNIXTIME(?))'
    );
    $stmt->execute([$jti, $exp]);
}

function jwt_is_blacklisted($jti) {
    $stmt = db()->prepare('SELECT 1 FROM jwt_blacklist WHERE jti = ? LIMIT 1');
    $stmt->execute([$jti]);
    return (bool)$stmt->fetchColumn();
}


function otp_generate_and_send($userId, $email) {
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $stmt = db()->prepare(
        'INSERT INTO otp_codes (user_id, otp_code, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))'
    );
    $stmt->execute([$userId, $code, OTP_TTL_SECONDS]);

    send_otp_email($email, $code);
    return $code;
}

function otp_verify($userId, $code) {
    $stmt = db()->prepare(
        'SELECT id FROM otp_codes
         WHERE user_id = ? AND otp_code = ? AND used = 0 AND expires_at >= NOW()
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$userId, $code]);
    $row = $stmt->fetch();

    if (!$row) {
        return false;
    }

    db()->prepare('UPDATE otp_codes SET used = 1 WHERE id = ?')->execute([$row['id']]);
    return true;
}

function send_otp_email($email, $code) {
    $message = "Your MovieVerse verification code is: $code (valid 2 minutes).";

    if (MAIL_MODE === 'mail') {
        $headers = 'From: ' . MAIL_FROM . "\r\n";
        @mail($email, 'Your MovieVerse OTP', $message, $headers);
    } else {
        $line = date('Y-m-d H:i:s') . " | $email | $code\n";
        @file_put_contents(UPLOAD_DIR . 'otp_log.txt', $line, FILE_APPEND);
    }
}


function crypto_factor_generate() {
    $keyHex = bin2hex(random_bytes(32));
    $key    = hex2bin($keyHex);

    $plaintext = 'MOVIEVERSE_FACTOR3';
    $iv        = random_bytes(16);

    $cipher = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

    $_SESSION['crypto_cipher'] = base64_encode($cipher);
    $_SESSION['crypto_iv']     = base64_encode($iv);

    return $keyHex; 
}

function crypto_factor_verify($keyHex) {
    if (empty($_SESSION['crypto_cipher']) || empty($_SESSION['crypto_iv'])) {
        return false;
    }
    if (!ctype_xdigit((string)$keyHex) || strlen($keyHex) !== 64) {
        return false;
    }

    $key    = hex2bin($keyHex);
    $cipher = base64_decode($_SESSION['crypto_cipher']);
    $iv     = base64_decode($_SESSION['crypto_iv']);

    $plaintext = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

    unset($_SESSION['crypto_cipher'], $_SESSION['crypto_iv']);

    return hash_equals('MOVIEVERSE_FACTOR3', (string)$plaintext);
}

function login_too_many_attempts($email) {
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE email = ? AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)'
    );
    $stmt->execute([$email, LOGIN_WINDOW_SECONDS]);
    return (int)$stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
}

function record_login_attempt($email) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = db()->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)');
    $stmt->execute([$email, $ip]);
}

function clear_login_attempts($email) {
    db()->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$email]);
}


function omdb_request($params) {
    $url = OMDB_API_URL . '?' . http_build_query(array_merge(
        ['apikey' => OMDB_API_KEY],
        $params
    ));

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
    } else {
        $body = @file_get_contents($url);
    }

    if (!$body) {
        return null;
    }
    return json_decode($body, true);
}

function omdb_search($query) {
    $data = omdb_request(['s' => $query, 'type' => 'movie']);
    if (!$data || ($data['Response'] ?? 'False') !== 'True') {
        return [];
    }
    return $data['Search'] ?? [];
}

function omdb_details($imdbId) {
    $data = omdb_request(['i' => $imdbId, 'plot' => 'full']);
    if (!$data || ($data['Response'] ?? 'False') !== 'True') {
        return null;
    }
    return $data;
}

function movie_upsert_from_omdb($m) {
    $imdbId = $m['imdbID'] ?? ($m['imdb_id'] ?? null);
    if (!$imdbId) {
        return null;
    }

    $stmt = db()->prepare(
        'INSERT INTO movies (imdb_id, title, year, genre, plot, poster, actors, imdb_rating)
         VALUES (:imdb, :title, :year, :genre, :plot, :poster, :actors, :rating)
         ON DUPLICATE KEY UPDATE
            title = VALUES(title), year = VALUES(year), genre = VALUES(genre),
            plot = VALUES(plot), poster = VALUES(poster), actors = VALUES(actors),
            imdb_rating = VALUES(imdb_rating)'
    );
    $stmt->execute([
        ':imdb'   => $imdbId,
        ':title'  => $m['Title']      ?? 'Untitled',
        ':year'   => $m['Year']       ?? null,
        ':genre'  => $m['Genre']      ?? null,
        ':plot'   => $m['Plot']       ?? null,
        ':poster' => ($m['Poster'] ?? 'N/A') !== 'N/A' ? $m['Poster'] : null,
        ':actors' => $m['Actors']     ?? null,
        ':rating' => $m['imdbRating'] ?? null,
    ]);

    $row = db()->prepare('SELECT id FROM movies WHERE imdb_id = ? LIMIT 1');
    $row->execute([$imdbId]);
    return (int)$row->fetchColumn();
}
