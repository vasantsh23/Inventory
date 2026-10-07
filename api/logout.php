<?php
/**
 * POST /api/logout.php
 * Ends the session and revokes this account's "remember me" tokens.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/api.php';

api_require_method('POST');
logout(true);
api_json(['signedIn' => false]);
