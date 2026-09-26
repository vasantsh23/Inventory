<?php
declare(strict_types=1);

// ---- Force HTTPS in production -------------------------------------------
if ((getenv('APP_ENV') ?: 'production') === 'production') {
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect, true, 301);
        exit;
    }
}

// ---- Secure session configuration (must run before session_start) --------
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_secure', (getenv('APP_ENV') ?: 'production') === 'production' ? '1' : '0');
session_name('IMS_SESSID');

// ---- Security headers ------------------------------------------------------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://v3601425.v360.in; frame-src 'self' https://v3601425.v360.in https://veeradimon.be; media-src 'self' https://onlinemediafiles.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'");

// ---- Database credentials --------------------------------------------------
// Read from dbconn.php rather than kept here, so the actual
// credentials live in exactly one file, are never in this repo/zip,
// and can be placed somewhere the webserver won't ever serve (see
// the "Where to put dbconn.php" note below and the delivered
// dbconn.example.php for the exact format it must follow).
//
// Checked in this order — first one found wins:
//   1. Two levels above this app's root folder (i.e. OUTSIDE the web
//      root, assuming this app's root is your hosting account's
//      public_html/ or equivalent) — the strongest option, since a
//      file outside the web root can never be requested over HTTP no
//      matter how the server is configured.
//   2. One level above this app's root (still outside web root on
//      most cPanel-style layouts, just one directory shallower).
//   3. Inside config/ itself, as a last resort for hosting that only
//      grants access to a single directory — that specific file is
//      also blocked at the webserver level by the .htaccess shipped
//      alongside it (see the root .htaccess this app now includes).
$dbConnCandidates = [
    dirname(__DIR__, 2) . '/dbconn.php',
    dirname(__DIR__, 1) . '/dbconn.php', // one level above config/, i.e. the app root's parent
    __DIR__ . '/dbconn.php',
    dirname(__DIR__, 1) . '/includes/dbconn.php', // this server keeps it in includes/
];
$dbConnFile = null;
foreach ($dbConnCandidates as $candidate) {
    if (is_file($candidate)) {
        $dbConnFile = $candidate;
        break;
    }
}
if ($dbConnFile === null) {
    error_log('FATAL: dbconn.php not found in any of: ' . implode(', ', $dbConnCandidates));
    http_response_code(500);
    exit('Server configuration error.');
}

// dbconn.php must define exactly these four variables and nothing
// that produces output (it runs in this same request):
//   $servernm  - DB host, e.g. "localhost" or "yourdb.host.example.com"
//   $dbname    - database name
//   $username  - database user
//   $pwd       - database password
require $dbConnFile;

// getenv() still wins when actually set, for any host that does
// support real environment variables — dbconn.php's values are the
// fallback used everywhere else.
define('DB_HOST', getenv('DB_HOST') ?: ($servernm ?? ''));
define('DB_NAME', getenv('DB_NAME') ?: ($dbname ?? ''));
define('DB_USER', getenv('DB_USER') ?: ($username ?? ''));
define('DB_PASS', getenv('DB_PASS') ?: ($pwd ?? ''));

if (DB_HOST === '' || DB_NAME === '' || DB_USER === '') {
    error_log('FATAL: dbconn.php was found but did not set $servernm / $dbname / $username.');
    http_response_code(500);
    exit('Server configuration error.');
}

// ---- Encryption keys --------------------------------------------------------
// Hardcoded here because this shared-hosting plan has no environment-
// variable mechanism for PHP. These were generated with:
//   php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
// Do NOT regenerate these once real data has been encrypted with them —
// doing so makes existing encrypted rows undecryptable.
define('APP_ENCRYPTION_KEY', getenv('APP_ENCRYPTION_KEY') ?: 'k32Sphgdzkl31XmMIMS+V4v4pknrCvmW0BAvGbmX/Dk=');
define('APP_HMAC_KEY', getenv('APP_HMAC_KEY') ?: 'A2GBjplnESvyvCNVbVmYoy+iQZJkTcAjzNJh7vKWwTM=');

if (APP_ENCRYPTION_KEY === '' || APP_HMAC_KEY === '') {
    // Fail loudly rather than silently running with weak/no encryption.
    error_log('FATAL: APP_ENCRYPTION_KEY / APP_HMAC_KEY are not configured.');
    http_response_code(500);
    exit('Server configuration error.');
}

define('APP_NAME', 'Inventory Management System');
// Bump this on each deploy — shown in the footer of every page so
// support can quickly confirm which build is currently live.
define('APP_VERSION', '22-Sep-26V1.18');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

// ---- Base URL (auto-detected) -----------------------------------------
// The app can be deployed at the domain root OR in a subfolder
// (e.g. labtest13.site/inventory-admin-module/). All internal links,
// asset paths and redirects use BASE_URL so they work either way
// without editing code when you rename/move the folder.
$appRoot = str_replace('\\', '/', dirname(__DIR__));               // .../inventory-admin-module
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
$basePath = $docRoot !== '' && str_starts_with($appRoot, $docRoot)
    ? substr($appRoot, strlen($docRoot))
    : '';
define('BASE_URL', rtrim($basePath, '/')); // '' at domain root, or e.g. '/inventory-admin-module'

/** Build a URL to a file inside this app, respecting BASE_URL. */
function asset_url(string $path): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Same as asset_url(), but a full absolute URL (scheme + host)
 * rather than a path relative to the current page. Use this for any
 * link meant to leave the browser context — clipboard content,
 * emails, WhatsApp shares, etc. — where a relative path resolves to
 * nothing meaningful once pasted somewhere else.
 */
function full_url(string $path): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $scheme . $host . asset_url($path);
}

/**
 * Same as asset_url(), but appends a "?v=<file mtime>" query string so
 * browsers fetch a fresh copy whenever the file changes on disk —
 * without this, updated CSS/JS can sit in a visitor's browser cache
 * under the old, unversioned URL and never get re-downloaded.
 */
function asset_url_versioned(string $path): string
{
    $fsPath = dirname(__DIR__) . '/' . ltrim($path, '/');
    $version = @filemtime($fsPath);
    $url = asset_url($path);
    return $version !== false ? $url . '?v=' . $version : $url;
}

