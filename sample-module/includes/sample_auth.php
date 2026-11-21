<?php
/**
 * sample_auth.php
 * Shared by the new home page (sample/index.php) and the Diamond
 * Jewellery pages (sample/cat/filter.php, product.php).
 *
 *   - sample_url()           : URL of a file inside the sample folder,
 *                              wherever that folder is deployed.
 *   - sample_current_user()  : the user signed in through
 *                              sample/diamond-filter/login.php, or null.
 *   - sample_require_login() : sends a visitor who is not signed in to
 *                              the login screen, then back here after.
 *
 * Reads the SAME session the diamond-filter app writes (cookie
 * IMS_SESSID — see diamond-filter/config/config.php), read-only, and
 * only when that cookie already exists, so a page view never creates
 * a second, conflicting session cookie.
 */

declare(strict_types=1);

/** Web path of the sample folder, e.g. "/sample" ('' at domain root). */
function sample_base(): string
{
    $root = str_replace('\\', '/', rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
    $dir  = str_replace('\\', '/', dirname(__DIR__)); // .../sample
    if ($root !== '' && str_starts_with($dir, $root)) {
        return rtrim(substr($dir, strlen($root)), '/');
    }
    return '/sample';
}

/** URL of a path inside the sample folder: sample_url('/cat/filter.php'). */
function sample_url(string $path = '/'): string
{
    return sample_base() . '/' . ltrim($path, '/');
}

/**
 * URL of a page of the MAIN inventory app — the folder this sample
 * folder sits in (e.g. sample-module/ inside the web root -> the app at
 * the web root). Inventory, View Cart, Login and Logout all go there, so
 * they use the same MySQL database and the same login as the rest of
 * the site.
 *   main_app_url('/inventory.php')  ->  /inventory.php
 */
function main_app_url(string $path = '/'): string
{
    $base = rtrim(str_replace('\\', '/', dirname(sample_base() === '' ? '/' : sample_base())), '/.');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Cookie domain for the login session: the same rule as the main app's
 * config/config.php, which sets a host-only cookie (no Domain attribute).
 * Using a different Domain here would create a SECOND IMS_SESSID cookie
 * and the browser could send the wrong one, so the user would look
 * signed out on one side.
 */
function sample_cookie_domain(): ?string
{
    return null;
}

function sample_current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;

    if (session_status() === PHP_SESSION_ACTIVE) {
        $u = $_SESSION['user'] ?? null;
        return $user = is_array($u) ? $u : null;
    }
    if (empty($_COOKIE['IMS_SESSID'])) {
        return null; // never signed in on this browser
    }

    session_name('IMS_SESSID');
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'path'     => '/',
        'domain'   => sample_cookie_domain() ?? '',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Strict', // same as the main app's config/config.php
    ]);
    // read_and_close: read the login, release the session lock at once.
    if (@session_start(['read_and_close' => true])) {
        $u = $_SESSION['user'] ?? null;
        $user = is_array($u) ? $u : null;
    }
    return $user;
}

/**
 * Open the login session for READING AND WRITING (e.g. to keep the
 * Diamond Jewellery inquiry selection). Same cookie/settings as
 * diamond-filter/config/config.php. Call before any output. Does
 * nothing when no login cookie exists (nobody is signed in).
 */
function sample_session_start(): bool
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    if (empty($_COOKIE['IMS_SESSID']) || headers_sent()) {
        return false;
    }
    ini_set('session.use_strict_mode', '1');
    session_name('IMS_SESSID');
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'path'     => '/',
        'domain'   => sample_cookie_domain() ?? '',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Strict', // same as the main app's config/config.php
    ]);
    return @session_start();
}

/** Login screen (the main app's) that returns the visitor to $next afterwards. */
function sample_login_url(string $next): string
{
    return main_app_url('/login.php') . '?next=' . rawurlencode($next);
}

/**
 * Link target for a members-only option on the home page: the page
 * itself when signed in, otherwise the login screen (which sends the
 * visitor on to that page after a successful login).
 */
function sample_member_link(string $path): string
{
    $target = sample_url($path);
    return sample_current_user() !== null ? $target : sample_login_url($target);
}

/** Stop here and redirect to the login screen unless someone is signed in. */
function sample_require_login(): void
{
    if (sample_current_user() !== null) {
        return;
    }
    $here = (string)($_SERVER['REQUEST_URI'] ?? sample_url('/'));
    header('Location: ' . sample_login_url($here));
    exit;
}
