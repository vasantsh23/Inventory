<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/diamond_search_query.php';

require_module_access('user');

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Your session expired — please reload the page and try again.');
}

$idsRaw = (string)($_POST['ids'] ?? '');
$ids = array_values(array_unique(array_filter(
    array_map('trim', explode(',', $idsRaw)),
    fn($v) => $v !== '' && ctype_digit($v)
)));

if ($ids === []) {
    http_response_code(400);
    exit('Please select at least one row to copy.');
}

require_once __DIR__ . '/../../includes/diamond_copy.php';

// Optional markup pricing: when the person supplies a markup % (mv),
// the copied text shows a recalculated rap%/price/total instead of
// the stone's own values (mk=markup + mv=<percentage>).
echo ds_generate_copy_text(
    $ids,
    (string)($_POST['mk'] ?? '') === 'markup',
    (float)($_POST['mv'] ?? 0)
);
