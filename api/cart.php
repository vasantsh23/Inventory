<?php
/**
 * The signed-in user's cart (the `selection` table, keyed by email —
 * the same cart as "View Cart" on the website).
 *
 * GET  /api/cart.php                              → cart contents
 * POST /api/cart.php {"action":"add","ids":[..]}  → add stones
 * POST /api/cart.php {"action":"remove","stockNos":[..]}
 * POST /api/cart.php {"action":"clear"}
 *
 * Only available when setup.loginscrn = 'yes'; in guest mode the app
 * keeps its selection on the device and uses /api/selected.php.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/api.php';

api_require_method('GET', 'POST');
api_require_user();

if (is_guest_browsing_enabled()) {
    api_error(403, 'The cart is not available on this site.', 'cart_disabled');
}
$emailid = api_cart_email();
if ($emailid === '') {
    api_error(400, "No email address is on file for your account, so items can't be added to a cart.", 'no_email');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = api_body();
    $action = (string)(is_scalar($body['action'] ?? null) ? $body['action'] : '');

    if ($action === 'add') {
        $ids = api_ids($body['ids'] ?? []);
        if ($ids === []) {
            api_error(400, 'Please select at least one stone to add to the cart.', 'bad_request');
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = get_db()->prepare("SELECT StockNo FROM maindata WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $stockNos = array_column($stmt->fetchAll(), 'StockNo');

        $existingStmt = get_db()->prepare('SELECT stockno FROM selection WHERE emailid = :e');
        $existingStmt->execute([':e' => $emailid]);
        $existing = array_flip(array_column($existingStmt->fetchAll(), 'stockno'));

        $insert = get_db()->prepare('INSERT INTO selection (emailid, stockno) VALUES (:e, :s)');
        $added = 0;
        foreach ($stockNos as $stockNo) {
            if ($stockNo === null || isset($existing[$stockNo])) {
                continue;
            }
            $insert->execute([':e' => $emailid, ':s' => $stockNo]);
            $existing[$stockNo] = true;
            $added++;
        }
        api_json(['added' => $added, 'requested' => count($stockNos), 'cartCount' => api_cart_count($emailid)]);
    }

    if ($action === 'remove') {
        $raw = $body['stockNos'] ?? [];
        $stockNos = is_array($raw)
            ? array_slice(array_values(array_unique(array_filter(
                array_map(fn($v) => is_scalar($v) ? trim((string)$v) : '', $raw),
                fn($v) => $v !== '' && strlen($v) <= 255
            ))), 0, API_MAX_IDS)
            : [];
        if ($stockNos !== []) {
            $placeholders = implode(',', array_fill(0, count($stockNos), '?'));
            $del = get_db()->prepare("DELETE FROM selection WHERE emailid = ? AND stockno IN ($placeholders)");
            $del->execute(array_merge([$emailid], $stockNos));
        }
        api_json(['cartCount' => api_cart_count($emailid)]);
    }

    if ($action === 'clear') {
        get_db()->prepare('DELETE FROM selection WHERE emailid = :e')->execute([':e' => $emailid]);
        api_json(['cartCount' => 0]);
    }

    api_error(400, 'Unknown cart action.', 'bad_request');
}

// GET: cart contents.
$columns = api_results_columns();
$stockStmt = get_db()->prepare('SELECT DISTINCT stockno FROM selection WHERE emailid = :e');
$stockStmt->execute([':e' => $emailid]);
$stockNos = array_column($stockStmt->fetchAll(), 'stockno');

$rows = [];
if ($stockNos !== [] && $columns !== []) {
    $placeholders = implode(',', array_fill(0, count($stockNos), '?'));
    $stmt = get_db()->prepare(
        'SELECT ' . api_results_field_list($columns) . " FROM maindata WHERE StockNo IN ($placeholders) ORDER BY " . build_rsetup_order_by()
    );
    $stmt->execute($stockNos);
    $rows = array_map(fn($r) => api_result_row($r, $columns), $stmt->fetchAll());
}

api_json([
    'columns'   => $columns,
    'rows'      => $rows,
    // Stones still in the cart but no longer in stock (e.g. sold since).
    'unavailableCount' => max(0, count($stockNos) - count($rows)),
    'cartCount' => count($stockNos),
]);
