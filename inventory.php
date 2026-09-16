<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Selecting "Inventory" from the home page requires login first,
// unless the site is configured for guest browsing (setup.loginscrn
// = 'no'), in which case it goes straight into the user module.
if (!is_logged_in()) {
    if (is_guest_browsing_enabled()) {
        header('Location: ' . asset_url('/modules/user/diamond_search.php'));
        exit;
    }
    $_SESSION['redirect_after_login'] = asset_url('/inventory.php');
    header('Location: ' . asset_url('/login.php'));
    exit;
}

// Route the authenticated user straight into the module for their access level.
$level = (int)(current_user()['level'] ?? 0);

if ($level >= 9) {
    header('Location: ' . asset_url('/modules/superadmin/index.php'));
} elseif ($level >= 8) {
    header('Location: ' . asset_url('/modules/admin/index.php'));
} else {
    header('Location: ' . asset_url('/modules/user/diamond_search.php'));
}
exit;
