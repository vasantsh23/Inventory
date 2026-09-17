<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_module_access('user');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['error' => 'Your session expired — please reload the page and try again.']);
    exit;
}

$emailid = (string)(current_user()['emailid'] ?? '');
if ($emailid === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No email address is on file for your account, so items can\'t be added to a cart.']);
    exit;
}

$idsRaw = (string)($_POST['ids'] ?? '');
$ids = array_values(array_unique(array_filter(
    array_map('trim', explode(',', $idsRaw)),
    fn($v) => $v !== '' && ctype_digit($v)
)));

if ($ids === []) {
    http_response_code(400);
    echo json_encode(['error' => 'Please select at least one row to add to the cart.']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = get_db()->prepare("SELECT StockNo FROM maindata WHERE id IN ($placeholders)");
$stmt->execute($ids);
$stockNos = array_column($stmt->fetchAll(), 'StockNo');

// Skip anything already in this user's cart, so re-adding the same
// stone doesn't create duplicate rows.
$existingStmt = get_db()->prepare('SELECT stockno FROM selection WHERE emailid = :e');
$existingStmt->execute([':e' => $emailid]);
$existing = array_column($existingStmt->fetchAll(), 'stockno');

$insertStmt = get_db()->prepare('INSERT INTO selection (emailid, stockno) VALUES (:e, :s)');
$added = 0;
foreach ($stockNos as $stockNo) {
    if (in_array($stockNo, $existing, true)) {
        continue;
    }
    $insertStmt->execute([':e' => $emailid, ':s' => $stockNo]);
    $existing[] = $stockNo; // guard against duplicate StockNo within the same selection too
    $added++;
}

$cartTotalStmt = get_db()->prepare('SELECT COUNT(DISTINCT stockno) AS c FROM selection WHERE emailid = :e');
$cartTotalStmt->execute([':e' => $emailid]);
$cartTotal = (int)$cartTotalStmt->fetch()['c'];

echo json_encode(['added' => $added, 'total' => count($stockNos), 'cartTotal' => $cartTotal]);
