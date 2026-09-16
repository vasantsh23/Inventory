<?php
/**
 * config.php
 * Central configuration.
 *
 * NOTE on this shared-hosting (BigRock/cPanel) version:
 * cPanel shared hosting typically has no clean way to set real server
 * environment variables for PHP, so this file falls back to hardcoded
 * values below when getenv() returns nothing. That is a deliberate
 * trade-off for this hosting environment — see the warnings inline.
 *
 * IMPORTANT — DO THIS BEFORE DEPLOYING:
 *   1. Change the MySQL user's password in cPanel > MySQL Databases,
 *      then put the NEW password in DB_PASS below. The old password
 *      that was previously in this file must be treated as compromised
 *      and must not be reused.
 *   2. Keep this file OUTSIDE the public web root if your hosting plan
 *      allows it, or at minimum block direct HTTP access to it via
 *      .htaccess (see note at bottom of this file).
 *   3. Do not commit this file (with real values) to a public repo.
 */

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
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://v3601425.v360.in; frame-src 'self' https://v3601425.v360.in https://veeradimon.be; media-src 'self' https://onlinemediafiles.com; style-src 'self' 'unsafe-inline'; script-src 'self'");

// ---- Database credentials --------------------------------------------------
// On BigRock/cPanel shared hosting, the DB host is almost always
// "localhost" (the database runs on the same server as PHP).
// Do NOT append the port to the hostname — that made the earlier
// value ("localhost3306") invalid. If you ever do need a non-default
// port, use "localhost;port=3306" is NOT valid either — instead pass
// it separately in config/db.php's DSN as ";port=3306".
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'atest8a6_inventorydb');
define('DB_USER', getenv('DB_USER') ?: 'atest8a6_inv001');

// !! Replace with the NEW password you set in cPanel after rotating it.
// The previous password must be considered leaked and not reused.
define('DB_PASS', getenv('DB_PASS') ?: 'D({7uSLiEuW0.UnK');

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

// ---- Protecting this file on shared hosting --------------------------------
// Add this to a .htaccess file in the SAME folder as config.php so it
// can never be requested directly over HTTP:
//
//   <Files "config.php">
//       Require all denied
//   </Files>
//
// (Apache 2.4+ / cPanel default). This matters more on shared hosting
// because config.php can't be moved above the web root the way it
// could on a VPS.
