# MovieVerse 🎬

A full-stack movie review website built with **only** HTML5, CSS3, vanilla
JavaScript, PHP and MySQL — no frameworks. Made as a university project to
demonstrate secure authentication, database design, API integration and modern
responsive UI.

---

## Features

- **Pages:** Home, Register, Login, Dashboard, Search, Movie Details, Watchlist, Profile, About
- **Triple Factor Authentication** on login:
  1. Password (`password_verify()`)
  2. Six-digit email OTP (expires in 2 minutes)
  3. OpenSSL cryptographic token (AES-256 generate + verify)
- **JWT** issued after login (HS256), validated on protected pages alongside a secure PHP session; logout **blacklists** the token
- **OMDb API** integration with **AJAX live search** (no page reloads)
- Add/remove **watchlist**, mark **watched**, **rate 1–10**, write/edit/delete **reviews**, see **average ratings**
- Dashboard with watchlist count, review count, recently viewed and recent activity
- Editable profile (username, picture upload, bio)
- Security: prepared statements, CSRF tokens, output escaping (XSS), input validation, secure cookies, session regeneration, login rate limiting, safe file uploads

---

## Folder structure

```
movieverse/
├── config/      config.php  (DB creds, API key, secrets)
├── includes/    db.php, functions.php, auth.php, header.php, footer.php
├── php/         AJAX + form handlers (search, watchlist, review, profile, logout)
├── css/         style.css
├── js/          main.js, search.js
├── images/      static images
├── uploads/     profile pictures + dev OTP log
├── sql/         movieverse.sql (database schema)
├── index.php register.php login.php dashboard.php
├── search.php movie.php watchlist.php profile.php about.php
```

---

## Setup (XAMPP / WAMP / MAMP or any PHP + MySQL host)

1. **Copy** the `movieverse` folder into your web root
   (e.g. `htdocs/movieverse` for XAMPP).

2. **Create the database.** Open phpMyAdmin and import `sql/movieverse.sql`,
   or run on the command line:
   ```bash
   mysql -u root -p < sql/movieverse.sql
   ```

3. **Get a free OMDb API key** at <https://www.omdbapi.com/apikey.aspx>.

4. **Edit `config/config.php`:**
   - `DB_USER` / `DB_PASS` — your MySQL login
   - `OMDB_API_KEY` — your OMDb key
   - `JWT_SECRET` and `APP_SECRET` — change to long random strings
   - `BASE_URL` — set to `/movieverse` (or `` if served at the domain root)

5. **Open** <http://localhost/movieverse/> in your browser.

---

## Reading the OTP during development

By default `MAIL_MODE` is `'log'`, so OTP codes are **not emailed** — they are
written to `uploads/otp_log.txt`. Open that file to read the latest code while
testing. On a real server with mail configured, set `MAIL_MODE` to `'mail'`.

---

## Notes for graders / students

- Every database call uses **PDO prepared statements** (`includes/functions.php`,
  `includes/db.php`).
- The JWT is implemented by hand in `functions.php` (`jwt_create` / `jwt_verify`)
  so the HS256 sign-and-verify steps are easy to read.
- The third login factor lives in `crypto_factor_generate()` /
  `crypto_factor_verify()` and uses `openssl_encrypt` / `openssl_decrypt`.
- Turn off `display_errors` in `config/config.php` before deploying publicly.
