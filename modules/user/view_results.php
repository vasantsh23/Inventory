<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/diamond_search_query.php';
require_once __DIR__ . '/../../includes/xlsx_lite.php';

require_module_access('user');

$emailid = (string)(current_user()['emailid'] ?? '');

// ---- Clear cart ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_cart') {
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

$columns = get_results_columns();
$rows = [];
$total = 0;

if ($emailid !== '' && $columns !== []) {
    $stockStmt = get_db()->prepare('SELECT DISTINCT stockno FROM selection WHERE emailid = :e');
    $stockStmt->execute([':e' => $emailid]);
    $stockNos = array_column($stockStmt->fetchAll(), 'stockno');

    if ($stockNos !== []) {
        $fieldList = implode(', ', array_map(fn($c) => "`{$c['field']}`", $columns));
        // Always fetch avail/notforweb (background color) and
        // Lab/CertificateNo/StockNo (certificate hyperlink) too, even
        // if not shown as visible columns.
        $validColsForBg = get_maindata_columns();
        $extraBgFields = array_diff(
            array_intersect(['avail', 'notforweb', 'Lab', 'CertificateNo', 'StockNo'], $validColsForBg),
            array_column($columns, 'field')
        );
        if ($extraBgFields !== []) {
            $fieldList .= ', ' . implode(', ', array_map(fn($f) => "`$f`", $extraBgFields));
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
                // this user's own cart — a requested id outside that set
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
            XlsxWriter::download('cart-' . date('Ymd-His') . '.xlsx', $headers, $exportRows);
        }

        $stmt = get_db()->prepare("SELECT id, $fieldList FROM maindata WHERE StockNo IN ($placeholders) ORDER BY " . build_rsetup_order_by());
        $stmt->execute($stockNos);
        $rows = $stmt->fetchAll();
        $total = count($rows);
    }
}

$pageTitle = 'View Cart';
$pageSubtitle = '';
$activeNav = 'diamond_search';
$applyPublicTheme = true;
$wideContent = true; // this table can have many columns — use the full viewport width

$rsetup = get_rsetup();

require_once __DIR__ . '/../../includes/header.php';
?>
    <?php if ($rsetup && (!empty($rsetup['fontype']) || !empty($rsetup['fontsize']))): ?>
        <style>
            table.results-table { font-family: <?= json_encode((string)($rsetup['fontype'] ?: 'inherit')) ?>, sans-serif !important; font-size: <?= (int)($rsetup['fontsize'] ?: 14) ?>px !important; }
        </style>
    <?php endif; ?>
    <section class="ds-page">
        <div class="ds-hero">
            <h1 class="ds-title">View Cart</h1>
            <div class="ds-actions">
                <a class="btn" href="<?= e(asset_url('/modules/user/results.php')) ?>">&larr; Back to Results</a>
                <?php if ($total > 0): ?>
                    <a class="btn btn-accent" href="#" id="exportExcelBtn">Export to Excel</a>
                    <button type="button" class="btn" id="copyBtn">Copy</button>
                    <button type="button" class="btn" id="markupCopyBtn">Markup Copy</button>
                    <button type="button" class="btn" id="clearSelectionBtn">Clear Selection</button>
                    <form method="post" action="<?= e(asset_url('/modules/user/view_results.php')) ?>" style="display:inline;"
                          data-confirm="Clear your entire cart? This cannot be undone.">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="clear_cart">
                        <button type="submit" class="btn btn-danger">Clear Cart</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($total > 0): ?>
            <div class="copy-status" id="copyStatus" hidden></div>
            <div class="copy-markup-row" id="markupCopyRow" hidden>
                <label class="copy-markup-field">
                    Markup %
                    <input type="number" step="any" id="markupCopyInput" placeholder="e.g. 5" style="width:80px;">
                </label>
                <button type="button" class="btn btn-accent" id="markupCopyConfirmBtn">Copy</button>
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

        <?php if ($columns === []): ?>
            <div class="panel">
                <p class="panel-desc">No result columns are configured yet. An administrator can enable columns in the Results table (active = yes) to control what appears here.</p>
            </div>
        <?php elseif ($total === 0): ?>
            <div class="panel">
                <p class="panel-desc">Your cart is empty. Go to Results, select some rows, and click "Add to Cart".</p>
            </div>
        <?php else: ?>
            <p class="results-count"><?= number_format($total) ?> diamond<?= $total === 1 ? '' : 's' ?> in your cart</p>

            <div class="results-table-wrap">
                <table class="data-table results-table">
                    <thead>
                        <tr>
                            <th class="results-checkbox-col"><input type="checkbox" id="memoSelectAll" title="Select all"></th>
                            <?php foreach ($columns as $col): ?>
                                <th><?= e($col['label']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="results-checkbox-col" data-label="Select"><input type="checkbox" class="memo-row-select" value="<?= e((string)$row['id']) ?>"></td>
                                <?php
                                $stockNoBg = get_stockno_bg_color($row['avail'] ?? null, $row['notforweb'] ?? null);
                                $rowCertUrl = build_certificate_url($row['Lab'] ?? null, $row['CertificateNo'] ?? null, $row['StockNo'] ?? null);
                                ?>
                                <?php foreach ($columns as $col): ?>
                                    <?php if ($col['field'] === 'StockNo'): ?>
                                        <td data-label="<?= e($col['label']) ?>"<?= $stockNoBg !== null ? ' style="background-color:' . e($stockNoBg) . ';"' : '' ?>>
                                            <a class="results-stockno-link" href="<?= e(asset_url('/modules/user/diamond_details.php?id=' . urlencode((string)$row['id']))) ?>"><?= e((string)($row[$col['field']] ?? '')) ?></a>
                                        </td>
                                    <?php elseif ($col['field'] === 'CertificateNo'):
                                        $certDisplay = format_certificate_no_display($row['CertificateNo'] ?? null);
                                    ?>
                                        <td data-label="<?= e($col['label']) ?>">
                                            <?php if ($certDisplay !== '' && $rowCertUrl !== null): ?>
                                                <a href="<?= e($rowCertUrl) ?>" target="_blank" rel="noopener" title="View certificate"><?= e($certDisplay) ?></a>
                                            <?php else: ?>
                                                <?= e($certDisplay) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php elseif ($col['field'] === 'Measurements'): ?>
                                        <td data-label="<?= e($col['label']) ?>">
                                            <?= e(format_measurements_display($row['Measurements'] ?? null)) ?>
                                        </td>
                                    <?php elseif ($col['field'] === 'totamt'): ?>
                                        <td data-label="<?= e($col['label']) ?>">
                                            <?= e(number_format(ds_display_amount($row['totamt'] ?? 0), 2)) ?>
                                        </td>
                                    <?php else: ?>
                                        <td data-label="<?= e($col['label']) ?>">
                                            <?= e((string)($row[$col['field']] ?? '')) ?>
                                        </td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/confirm_submit.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/footer.php';
