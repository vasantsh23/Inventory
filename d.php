<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_module_access('user');

$id = '';
$a = (string)($_GET['a'] ?? '');
if ($a !== '') {
    $decoded = base64_decode($a, true);
    if ($decoded !== false && ctype_digit($decoded)) {
        $id = $decoded;
    }
}

if ($id === '') {
    http_response_code(404);
    exit('This link is invalid or has expired.');
}

header('Location: ' . asset_url('/modules/user/diamond_details.php?id=' . urlencode($id)));
exit;
