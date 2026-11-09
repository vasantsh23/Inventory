<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/diamond_search_query.php';
require_once __DIR__ . '/../../includes/media_check.php';
require_once __DIR__ . '/../../includes/xlsx_lite.php';

require_module_access('user');

$emailid = (string)(current_user()['emailid'] ?? '');

// setup.loginscrn = 'yes' → "View Cart": rows come from this user's
//   entries in the `selection` (cart) table.
// setup.loginscrn = 'no'  → "View Selected": the cart table is not used
//   at all. The Results page posts the StockNos of the ticked rows here;
//   they are kept in the session (so reloads and Excel export keep
//   working) and the details are fetched straight from maindata.
$cartEnabled = !is_guest_browsing_enabled();

// ---- View (Selected): receive the ticked StockNos from Results ----
// Without a cart (loginscrn = 'no') this page is always "View
// Selected". With a cart, Results' floating "View (Selected)" button
// opens it as ?selected=1, showing just the ticked rows instead of the
// cart.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'view_selected') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Your session expired — please reload the page and try again.');
    }
    $postedStockNos = array_values(array_unique(array_filter(
        array_map('trim', explode(',', (string)($_POST['stocknos'] ?? ''))),
        fn($v) => $v !== '' && strlen($v) <= 255
    )));
    $_SESSION['view_selected_stocknos'] = array_slice($postedStockNos, 0, 1000);
    header('Location: ' . asset_url('/modules/user/view_results.php' . ($cartEnabled ? '?selected=1' : '')));
    exit;
}
$showSelected = !$cartEnabled || !empty($_GET['selected']);

// ---- Clear cart (loginscrn = 'yes' only) ----
if ($cartEnabled && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_cart') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Your session expired — please reload the page and try again.');
    }
    if ($emailid !== '') {
        $delStmt = get_db()->prepare('DELETE FROM selection WHERE emailid = :e');
        $delStmt->execute([':e' => $emailid]);
    }
    header('Location: ' . asset_url('/modules/user/view_results.php?cleared=1'));
    exit;
}

// Columns come from the `results` rows for this viewer's user type. If
// that user type has no rows, show an error instead of the table.
$resultsError = '';
try {
    $columns = get_results_columns();
} catch (ResultsConfigException $e) {
    $columns = [];
    $resultsError = $e->getMessage();
    if (($_GET['export'] ?? '') === 'xlsx') {
        http_response_code(400);
        exit($resultsError);
    }
}
$rows = [];
$total = 0;

$stockNos = [];
if ($columns !== []) {
    if (!$showSelected) {
        if ($emailid !== '') {
            $stockStmt = get_db()->prepare('SELECT DISTINCT stockno FROM selection WHERE emailid = :e');
            $stockStmt->execute([':e' => $emailid]);
            $stockNos = array_column($stockStmt->fetchAll(), 'stockno');
        }
    } else {
        $sessionStockNos = $_SESSION['view_selected_stocknos'] ?? [];
        $stockNos = is_array($sessionStockNos) ? array_values(array_map('strval', $sessionStockNos)) : [];
    }
}

if ($stockNos !== []) {
    $fieldList = implode(', ', array_map(fn($c) => "`{$c['field']}`", $columns));
    // Also fetch the fields the table needs but may not show (StockNo
    // colour, certificate link, media paths, Weight/Amount totals).
    require_once __DIR__ . '/../../includes/partials/results_table.php';
    $extraFields = results_table_extra_fields($columns);
    if ($extraFields !== []) {
        $fieldList .= ', ' . implode(', ', array_map(fn($f) => "`$f`", $extraFields));
    }
    $placeholders = implode(',', array_fill(0, count($stockNos), '?'));

    // ---- Excel export: only the checked rows (falls back to
    //      the whole cart if nothing was selected — e.g. a
    //      bookmarked export link with no selection state) ----
    if (($_GET['export'] ?? '') === 'xlsx') {
        $exportIdsRaw = (string)($_GET['ids'] ?? '');
        $exportIds = array_values(array_unique(array_filter(
            array_map('trim', explode(',', $exportIdsRaw)),
            fn($v) => $v !== '' && ctype_digit($v)
        )));

        if ($exportIds !== []) {
            // Security: only export rows whose StockNo is actually in
            // this user's own cart / selection — a requested id outside that set
            // (however it got there) is silently dropped, not exported.
            $idPlaceholders = implode(',', array_fill(0, count($exportIds), '?'));
            $verifyStmt = get_db()->prepare("SELECT id FROM maindata WHERE id IN ($idPlaceholders) AND StockNo IN ($placeholders)");
            $verifyStmt->execute(array_merge($exportIds, $stockNos));
            $verifiedIds = array_column($verifyStmt->fetchAll(), 'id');
        } else {
            $verifiedIds = [];
        }

        if ($verifiedIds === []) {
            http_response_code(400);
            exit('Please select at least one row to export.');
        }

        $verifiedPlaceholders = implode(',', array_fill(0, count($verifiedIds), '?'));
        $stmt = get_db()->prepare('SELECT ' . results_export_select_list($columns) . " FROM maindata WHERE id IN ($verifiedPlaceholders) ORDER BY " . build_rsetup_order_by());
        $stmt->execute($verifiedIds);
        // Stock no. -> Diamond Details link, Cert no. -> certificate link, both as text.
        [$headers, $exportRows, $xlsxOpts] = results_export_sheet($columns, $stmt->fetchAll());
        XlsxWriter::download(($showSelected ? 'selected-' : 'cart-') . date('Ymd-His') . '.xlsx', $headers, $exportRows, $xlsxOpts);
    }

    $stmt = get_db()->prepare("SELECT id, $fieldList FROM maindata WHERE StockNo IN ($placeholders) ORDER BY " . build_rsetup_order_by());
    $stmt->execute($stockNos);
    $rows = $stmt->fetchAll();
    $total = count($rows);
}

$viewTitle = $showSelected ? 'View (Selected)' : 'View Cart';
$pageTitle = $viewTitle;
$pageSubtitle = '';
$activeNav = 'diamond_search';
$wideContent = true; // this table can have many columns — use the full viewport width

$rsetup = get_rsetup();
require_once __DIR__ . '/../../includes/partials/results_table.php';
$mediaLinks = build_result_media_links_for_rows($rows);

// ---- Page markup comes from the live template (Admin → Search Templates) ----
require_once __DIR__ . '/../../includes/search_templates.php';
require search_template_view('view_results');
