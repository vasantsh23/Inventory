<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_module_access('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Your session expired — please go back and try again.');
}

$q = (string)($_POST['q'] ?? '');
$page = (string)($_POST['page'] ?? '1');
$perPage = (string)($_POST['per_page'] ?? '100');
$redirectBack = fn(string $flash) => asset_url(
    '/modules/admin/maindata_hold.php?' . http_build_query(['q' => $q, 'page' => $page, 'per_page' => $perPage, 'flash' => $flash])
);

$validCols = get_maindata_columns();
if (!in_array('hold', $validCols, true)) {
    header('Location: ' . $redirectBack('save_failed'));
    exit;
}

// `all_ids` is every row that was actually rendered on the page just
// submitted (a hidden field per row); `hold_ids` is whichever of
// those the person left checked. Intersecting hold_ids against
// all_ids means this can only ever update rows that were genuinely
// shown on screen — never anything from outside that page, even if
// the posted data were tampered with.
$allIds = array_values(array_unique(array_map('intval', (array)($_POST['all_ids'] ?? []))));
$checkedIds = array_values(array_unique(array_map('intval', (array)($_POST['hold_ids'] ?? []))));
$checkedIds = array_values(array_intersect($checkedIds, $allIds));
$uncheckedIds = array_values(array_diff($allIds, $checkedIds));

$db = get_db();
try {
    $db->beginTransaction();
    if ($checkedIds !== []) {
        $placeholders = implode(',', array_fill(0, count($checkedIds), '?'));
        $stmt = $db->prepare("UPDATE `maindata` SET `hold` = 'yes' WHERE `id` IN ($placeholders)");
        $stmt->execute($checkedIds);
    }
    if ($uncheckedIds !== []) {
        $placeholders = implode(',', array_fill(0, count($uncheckedIds), '?'));
        $stmt = $db->prepare("UPDATE `maindata` SET `hold` = 'no' WHERE `id` IN ($placeholders)");
        $stmt->execute($uncheckedIds);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    header('Location: ' . $redirectBack('save_failed'));
    exit;
}

header('Location: ' . $redirectBack('saved'));
exit;
