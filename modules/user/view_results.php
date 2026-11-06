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
        $stmt = get_db()->prepare("SELECT $fieldList FROM maindata WHERE id IN ($verifiedPlaceholders) ORDER BY " . build_rsetup_order_by());
        $stmt->execute($verifiedIds);
        $headers = array_map(fn($c) => $c['label'], $columns);
        $exportRows = [];
        foreach ($stmt->fetchAll() as $row) {
            $exportRows[] = array_map(
                fn($c) => $c['field'] === 'totamt'
                    ? number_format(ds_display_amount($row['totamt'] ?? 0), 2, '.', '')
                    : (string)($row[$c['field']] ?? ''),
                $columns
            );
        }
        XlsxWriter::download(($showSelected ? 'selected-' : 'cart-') . date('Ymd-His') . '.xlsx', $headers, $exportRows);
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
$bodyClass = 'compact-footer no-footer-gap rs-v2'; // tight footer; rs-v2 = bordered blue-header table design
$copyLabel = (strtok(trim((string)((get_setup() ?? [])['company'] ?? '')), ' ') ?: 'Copy') . '-Copy';

$rsetup = get_rsetup();

require_once __DIR__ . '/../../includes/partials/results_table.php';
$mediaLinks = build_result_media_links_for_rows($rows);
require_once __DIR__ . '/../../includes/header.php';
?>
    <section class="ds-page rs-page">
        <div class="rs-toolbar">
            <h1 class="rs-title"><?= e($viewTitle) ?></h1>
            <div class="rs-buttons">
                <div class="rs-btn-row">
                    <a class="rs-btn" href="<?= e(asset_url('/modules/user/results.php')) ?>"><?= rs_icon('back') ?>Back to Results</a>
                    <?php if ($total > 0): ?>
                        <button type="button" class="rs-btn" id="exportExcelBtn"><?= rs_icon('xls') ?>Excel-Select</button>
                        <button type="button" class="rs-btn" id="copyBtn"><?= rs_icon('copy') ?><?= e($copyLabel) ?></button>
                        <button type="button" class="rs-btn" id="markupCopyBtn"><?= rs_icon('copy') ?>Markup Copy</button>
                        <?php if (!$showSelected): ?>
                            <form method="post" action="<?= e(asset_url('/modules/user/view_results.php')) ?>" class="rs-inline-form"
                                  data-confirm="Clear your entire cart? This cannot be undone.">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="rs-btn rs-btn--danger"><?= rs_icon('trash') ?>Clear Cart</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($cartEnabled && $showSelected): ?>
                        <a class="rs-btn" href="<?= e(asset_url('/modules/user/view_results.php')) ?>"><?= rs_icon('cart') ?>View cart</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($total > 0): ?>
            <div class="copy-status" id="copyStatus" hidden></div>
            <div class="copy-markup-row" id="markupCopyRow" hidden>
                <label class="copy-markup-field">
                    Markup %
                    <input type="number" step="any" id="markupCopyInput" placeholder="e.g. 5" style="width:80px;">
                </label>
                <button type="button" class="rs-btn" id="markupCopyConfirmBtn">Copy</button>
            </div>
            <form method="post" action="<?= e(asset_url('/modules/user/copy_generate.php')) ?>" id="copyForm" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="ids" id="copyFormIds">
                <input type="hidden" name="mk" id="copyFormMk">
                <input type="hidden" name="mv" id="copyFormMv">
            </form>
        <?php endif; ?>

        <?php if (($_GET['cleared'] ?? '') === '1'): ?>
            <div class="alert alert-success" style="margin-bottom:18px;">Your cart has been cleared.</div>
        <?php endif; ?>

        <?php if ($resultsError !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($resultsError) ?></div>
        <?php elseif ($columns === []): ?>
            <div class="panel">
                <p class="panel-desc">No result columns are configured yet. An administrator can enable columns in the Results table (active = yes) to control what appears here.</p>
            </div>
        <?php elseif ($total === 0): ?>
            <div class="panel">
                <?php if (!$showSelected): ?>
                    <p class="panel-desc">Your cart is empty. Go to Results, select some rows, and click "Add to cart".</p>
                <?php else: ?>
                    <p class="panel-desc">No diamonds selected. Go to Results, tick some rows, and click "View (Selected)".</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php render_results_table($columns, $rows, $mediaLinks, [
                'total' => $total,
                'toolbarExtra' => '<button type="button" class="rs-link-btn" id="clearSelectionBtn">Clear selection</button>',
            ]); ?>
        <?php endif; ?>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_fit.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_toolbar.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/confirm_submit.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/footer.php';
