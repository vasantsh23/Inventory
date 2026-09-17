<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_module_access('user');

// The User Module landing page was removed — this now goes straight
// to Diamond Search. Kept as a redirect (not deleted outright) so any
// existing bookmarks/links to this URL still work.
header('Location: ' . asset_url('/modules/user/diamond_search.php'));
exit;
