<?php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'movieverse');
define('DB_USER', 'root');         
define('DB_PASS', '');             
define('DB_CHARSET', 'utf8mb4');


define('OMDB_API_KEY', '3d85192e');
define('OMDB_API_URL', 'https://www.omdbapi.com/');


define('JWT_SECRET', 'change-this-to-a-long-random-jwt-secret-string');
define('APP_SECRET', 'change-this-to-a-different-long-random-secret');


define('JWT_TTL', 3600);


define('OTP_TTL_SECONDS', 120);    


define('MAX_LOGIN_ATTEMPTS', 5);  
define('LOGIN_WINDOW_SECONDS', 900); 


define('MAIL_MODE', 'log');       
define('MAIL_FROM', 'no-reply@movieverse.local');


define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('BASE_URL', '/movieverse');  


error_reporting(E_ALL);
ini_set('display_errors', '1');
