<?php
/**
 * POST /api/login.php   {"username": "...", "password": "..."}
 * Signs in with the same rules as the website (lockout, approval,
 * audit log) and always issues the 30-day "remember me" token, so the
 * app stays signed in across restarts.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/api.php';

api_require_method('POST');
$body = api_body();
$username = trim((string)(is_scalar($body['username'] ?? null) ? $body['username'] : ''));
$password = (string)(is_scalar($body['password'] ?? null) ? $body['password'] : '');

if ($username === '' || $password === '' || strlen($username) > 150 || strlen($password) > 1024) {
    api_error(400, 'Please enter your login ID and password.', 'bad_request');
}

if (!attempt_login($username, $password, true)) {
    // Same generic message as the website — never reveal which part was wrong.
    api_error(401, 'Invalid login ID or password.', 'invalid_credentials');
}

$user = current_user();
$allowed = modules_for_level((int)($user['level'] ?? 0));
if (!in_array('user', $allowed, true)) {
    logout();
    api_error(403, 'Your account does not have access to diamond search.', 'forbidden');
}

api_json([
    'signedIn' => true,
    'user'     => [
        'username' => (string)$user['username'],
        'role'     => (string)($user['usertype_label'] ?? ''),
        'level'    => (int)($user['level'] ?? 0),
    ],
]);
