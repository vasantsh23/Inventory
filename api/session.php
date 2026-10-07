<?php
/**
 * GET /api/session.php
 * App start-up info: whether sign-in is required, whether the cart is
 * enabled, who (if anyone) is signed in, and branding.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/api.php';

api_require_method('GET');

$setup = get_setup() ?? [];
$user = current_user();
$guestMode = is_guest_browsing_enabled();

api_json([
    'apiVersion'    => API_VERSION,
    'loginRequired' => !$guestMode,
    'cartEnabled'   => !$guestMode,
    'signedIn'      => $user !== null,
    'user'          => $user === null ? null : [
        'username' => (string)$user['username'],
        'role'     => (string)($user['usertype_label'] ?? ''),
        'level'    => (int)($user['level'] ?? 0),
    ],
    'company' => [
        'name'    => trim((string)($setup['company'] ?? '')) ?: APP_NAME,
        'logoUrl' => api_absolute_url(asset_url(get_logo_path())),
        'phone'   => primary_phone($setup) ?: null,
        'email'   => primary_email($setup) ?: null,
    ],
]);
