<?php
/**
 * auth.php
 * Handles login, logout and role-based module access.
 *
 * Roles are no longer a fixed 3-value set. Each account's `usertype`
 * column stores the id of a row in `user_types`, whose `level`
 * (0-9) determines module access:
 *   0-7  -> user module only (any number of custom role names)
 *   8    -> user + admin modules ("Admin")
 *   9    -> user + admin + superadmin modules ("Super Admin")
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php'; // get_setup(), for the loginscrn guest-mode check

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Which modules a given access level can enter. */
function modules_for_level(int $level): array
{
    if ($level >= 9) {
        return ['user', 'admin', 'superadmin'];
    }
    if ($level >= 8) {
        return ['user', 'admin'];
    }
    return ['user'];
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}
function is_logged_in(): bool
{
    return current_user() !== null;
}

/** Cookie name used for the persistent "remember me" login token. */
const REMEMBER_COOKIE = 'ims_remember';
const REMEMBER_DAYS    = 30;

/**
 * If no active session exists, check for a valid "remember me" cookie
 * and transparently log the user back in. Uses the selector/validator
 * pattern and rotates the token on every use (mitigates cookie theft
 * / replay). Called once, early, on every page load.
 */
function attempt_remember_me_login(): void
{
    if (is_logged_in() || empty($_COOKIE[REMEMBER_COOKIE])) {
        return;
    }

    $parts = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
    if (count($parts) !== 2) {
        clear_remember_cookie();
        return;
    }
    [$selector, $validator] = $parts;

    $db = get_db();
    $stmt = $db->prepare(
        'SELECT rt.id, rt.user_id, rt.validator_hash, rt.expires_at,
                u.username, u.emailid, u.usertype AS usertype_id, u.approval,
                t.usertype AS usertype_label, t.level AS level
         FROM remember_tokens rt
         JOIN user u ON u.id = rt.user_id
         JOIN user_types t ON t.id = u.usertype
         WHERE rt.selector = :s
         LIMIT 1'
    );
    $stmt->execute([':s' => $selector]);
    $row = $stmt->fetch();

    if (!$row || strtotime($row['expires_at']) < time() || $row['approval'] !== 'approved') {
        clear_remember_cookie();
        return;
    }

    if (!hash_equals($row['validator_hash'], hash('sha256', $validator))) {
        // Validator mismatch: possible token theft. Revoke the whole
        // selector rather than silently failing.
        $db->prepare('DELETE FROM remember_tokens WHERE id = :id')->execute([':id' => $row['id']]);
        clear_remember_cookie();
        return;
    }

    // Valid: log the user in and rotate the token.
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'             => $row['user_id'],
        'username'       => $row['username'],
        'emailid'        => (string)(decrypt_value($row['emailid']) ?? ''),
        'usertype_id'    => (int)$row['usertype_id'],
        'usertype_label' => $row['usertype_label'],
        'level'          => (int)$row['level'],
    ];

    $db->prepare('DELETE FROM remember_tokens WHERE id = :id')->execute([':id' => $row['id']]);
    issue_remember_token((int)$row['user_id']);
}

/** Create a new remember-me token for $userId and set the cookie. */
function issue_remember_token(int $userId): void
{
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $expires   = (new DateTime('+' . REMEMBER_DAYS . ' days'))->format('Y-m-d H:i:s');

    $stmt = get_db()->prepare(
        'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at)
         VALUES (:uid, :sel, :vh, :exp)'
    );
    $stmt->execute([
        ':uid' => $userId,
        ':sel' => $selector,
        ':vh'  => hash('sha256', $validator),
        ':exp' => $expires,
    ]);

    setcookie(REMEMBER_COOKIE, $selector . ':' . $validator, [
        'expires'  => time() + REMEMBER_DAYS * 86400,
        'path'     => BASE_URL !== '' ? BASE_URL . '/' : '/',
        'secure'   => (getenv('APP_ENV') ?: 'production') === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_remember_cookie(): void
{
    setcookie(REMEMBER_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => BASE_URL !== '' ? BASE_URL . '/' : '/',
        'secure'   => (getenv('APP_ENV') ?: 'production') === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** Revoke all remember-me tokens for a user (call on logout / password change). */
function revoke_remember_tokens(int $userId): void
{
    get_db()->prepare('DELETE FROM remember_tokens WHERE user_id = :uid')->execute([':uid' => $userId]);
}

/** Redirect to the login screen, remembering where the user wanted to go. */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? asset_url('/index.php');
        header('Location: ' . asset_url('/login.php'));
        exit;
    }
}

/** Require that the logged-in user's access level can reach $module ('user'|'admin'|'superadmin'). */
/**
 * Whether the site is configured to skip the login requirement for
 * the public "Inventory" entry point (setup.loginscrn = 'no'). Only
 * ever affects the 'user' module — admin/superadmin always require a
 * real login regardless of this setting.
 */
function is_guest_browsing_enabled(): bool
{
    $setup = get_setup();
    return strtolower(trim((string)($setup['loginscrn'] ?? 'yes'))) === 'no';
}

function require_module_access(string $module): void
{
    if ($module === 'user' && !is_logged_in() && is_guest_browsing_enabled()) {
        return; // guest browsing: no login required for the user module
    }
    require_login();
    $user = current_user();
    $allowed = modules_for_level((int)($user['level'] ?? 0));
    if (!in_array($module, $allowed, true)) {
        http_response_code(403);
        exit('Access denied: your account does not have permission to view this module.');
    }
}

/**
 * The Memo feature (Memo-1/Memo-3 printing, the Customer list, and
 * the customer picker on Results) is only available to user levels
 * 4 and 5 — level 4 uses the `dmemo` table for the footer text,
 * level 5 uses `memo`. Call this at the top of any memo-related page;
 * it also requires a logged-in session with 'user' module access.
 */
function require_memo_level(): int
{
    require_module_access('user');
    $level = (int)(current_user()['level'] ?? 0);
    if (!in_array($level, [4, 5], true)) {
        http_response_code(403);
        exit('Access denied: this feature is only available to memo-enabled accounts.');
    }
    return $level;
}

/**
 * Attempt to authenticate a username/password.
 * Returns true on success, false on failure. Applies lockout after
 * repeated failures and records every attempt in login_audit.
 */
function attempt_login(string $username, string $password, bool $remember = false): bool
{
    $db = get_db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $stmt = $db->prepare(
        'SELECT u.id, u.username, u.emailid, u.password, u.approval, u.failed_attempts, u.locked_until,
                u.usertype AS usertype_id, t.usertype AS usertype_label, t.level AS level
         FROM user u
         JOIN user_types t ON t.id = u.usertype
         WHERE u.username = :u LIMIT 1'
    );
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();

    $log = function (bool $success) use ($db, $username, $ip): void {
        $log = $db->prepare(
            'INSERT INTO login_audit (username, ipadd, success) VALUES (:u, :ip, :s)'
        );
        $log->execute([':u' => $username, ':ip' => $ip, ':s' => $success ? 1 : 0]);
    };

    if (!$row) {
        $log(false);
        // Constant-time-ish: still hash something so timing doesn't reveal
        // whether the username exists.
        password_verify($password, '$2y$10$invalidsaltinvalidsaltinvalidsal');
        return false;
    }

    if ($row['locked_until'] !== null && strtotime($row['locked_until']) > time()) {
        $log(false);
        return false; // account temporarily locked
    }

    if ($row['approval'] !== 'approved') {
        $log(false);
        return false; // pending / rejected / disabled accounts cannot log in
    }

    if (!password_verify($password, $row['password'])) {
        $attempts = (int)$row['failed_attempts'] + 1;
        $lockUntil = $attempts >= MAX_LOGIN_ATTEMPTS
            ? (new DateTime("+" . LOCKOUT_MINUTES . " minutes"))->format('Y-m-d H:i:s')
            : null;

        $upd = $db->prepare(
            'UPDATE user SET failed_attempts = :a, locked_until = :l WHERE id = :id'
        );
        $upd->execute([':a' => $attempts, ':l' => $lockUntil, ':id' => $row['id']]);
        $log(false);
        return false;
    }

    // Success: reset failed attempts, record login, rotate the session ID
    // to prevent session fixation.
    $upd = $db->prepare(
        'UPDATE user
         SET failed_attempts = 0, locked_until = NULL, last_login = NOW(), ipadd = :ip
         WHERE id = :id'
    );
    $upd->execute([':ip' => $ip, ':id' => $row['id']]);
    $log(true);

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'             => $row['id'],
        'username'       => $row['username'],
        'emailid'        => (string)(decrypt_value($row['emailid']) ?? ''),
        'usertype_id'    => (int)$row['usertype_id'],
        'usertype_label' => $row['usertype_label'],
        'level'          => (int)$row['level'],
    ];

    if ($remember) {
        issue_remember_token((int)$row['id']);
    }

    return true;
}

/**
 * End the current session. When $forgetDevice is true (the default —
 * a full, standard logout), any "remember me" token for this user is
 * also revoked and the cookie cleared. When false, the session ends
 * but the remember-me token is left intact, so the user is
 * transparently signed back in the next time they visit on this
 * device/browser — used by the "keep me signed in on this device"
 * logout option.
 */
function logout(bool $forgetDevice = true): void
{
    $user = current_user();
    if ($forgetDevice) {
        if ($user !== null) {
            revoke_remember_tokens((int)$user['id']);
        }
        clear_remember_cookie();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'],
            $params['secure'], $params['httponly']);
    }
    session_destroy();
}

// Transparently restore a session from a "remember me" cookie, if present.
attempt_remember_me_login();
