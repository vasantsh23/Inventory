<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/diamond_search_query.php';
require_once __DIR__ . '/../../includes/xlsx_lite.php';

require_module_access('user');

// A fresh search submits here via POST (so the filter criteria never
// appear in the browser's address bar) — store it in the session,
// then redirect to a clean GET URL. Every subsequent request on this
// page (pagination, export, a plain reload) reads the filters back
// from the session rather than the URL.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedFilters = $_POST['f'] ?? [];
    $_SESSION['ds_last_filters'] = is_array($postedFilters) ? $postedFilters : [];
    header('Location: ' . asset_url('/modules/user/results.php'));
    exit;
}

$memoLevel = (int)(current_user()['level'] ?? 0);
$canMemo = in_array($memoLevel, [4, 5], true);
$customers = [];
$newCustomerId = '';
if ($canMemo) {
    $customers = get_db()->query("SELECT custid, custnm FROM customer ORDER BY custnm ASC")->fetchAll();
    $newCustomerId = (string)($_GET['new_customer'] ?? '');
}

$emailid = (string)(current_user()['emailid'] ?? '');
$cartCount = 0;
if ($emailid !== '') {
    $cartCountStmt = get_db()->prepare('SELECT COUNT(DISTINCT stockno) AS c FROM selection WHERE emailid = :e');
    $cartCountStmt->execute([':e' => $emailid]);
    $cartCount = (int)$cartCountStmt->fetch()['c'];
}

$filters = $_SESSION['ds_last_filters'] ?? [];
if (!is_array($filters)) {
    $filters = [];
}
[$where, $params] = build_maindata_search_where($filters);
// Diamonds on hold are never shown in search results — checked
// against the real column list first since older databases may not
// have run the hold-related migration yet.
if (in_array('hold', get_maindata_columns(), true)) {
    $where = "($where) AND (`hold` IS NULL OR `hold` != 'yes')";
}

// TEMPORARY DIAGNOSTIC — remove once the Stock No search issue is
// resolved. Visit this page with ?debug_stockno=1 appended to see
// exactly what's stored in the session and what SQL actually runs.
if (($_GET['debug_stockno'] ?? '') === '1') {
    echo '<pre style="background:#111;color:#0f0;padding:16px;font-size:13px;white-space:pre-wrap;">';
    echo "SESSION filters:\n" . htmlspecialchars(print_r($filters, true)) . "\n";
    echo "Generated WHERE:\n" . htmlspecialchars($where) . "\n\n";
    echo "Params:\n" . htmlspecialchars(print_r($params, true)) . "\n";
    $debugStmt = get_db()->prepare("SELECT COUNT(*) AS c FROM maindata WHERE $where");
    $debugStmt->execute($params);
    echo "Row count with this WHERE+params: " . (int)$debugStmt->fetch()['c'] . "\n";
    echo '</pre>';
    exit;
}

$columns = get_results_columns();
$appliedFilters = build_applied_filters_summary($filters);

// ---- Excel export: same filters, no pagination ----
if (($_GET['export'] ?? '') === 'xlsx') {
    if ($columns === []) {
        http_response_code(400);
        exit('No result columns are configured.');
    }
    $fieldList = implode(', ', array_map(fn($c) => "`{$c['field']}`", $columns));
    $stmt = get_db()->prepare("SELECT $fieldList FROM maindata WHERE $where ORDER BY " . build_rsetup_order_by());
    $stmt->execute($params);
    $headers = array_map(fn($c) => $c['label'], $columns);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[] = array_map(fn($c) => (string)($row[$c['field']] ?? ''), $columns);
    }
    XlsxWriter::download('diamond-search-results-' . date('Ymd-His') . '.xlsx', $headers, $rows);
}

// ---- Excel-select: only the checked rows (ids from the Excel-select
//      button's JS), ignoring the current search filters entirely —
//      whatever the person selected is exactly what gets exported. ----
if (($_GET['export'] ?? '') === 'xlsx_selected') {
    if ($columns === []) {
        http_response_code(400);
        exit('No result columns are configured.');
    }
    $selectedIdsRaw = (string)($_GET['ids'] ?? '');
    $selectedIds = array_values(array_unique(array_filter(
        array_map('trim', explode(',', $selectedIdsRaw)),
        fn($v) => $v !== '' && ctype_digit($v)
    )));
    if ($selectedIds === []) {
        http_response_code(400);
        exit('Please select at least one row to export.');
    }
    $fieldList = implode(', ', array_map(fn($c) => "`{$c['field']}`", $columns));
    $selPlaceholders = implode(',', array_fill(0, count($selectedIds), '?'));
    $stmt = get_db()->prepare("SELECT $fieldList FROM maindata WHERE id IN ($selPlaceholders) ORDER BY " . build_rsetup_order_by());
    $stmt->execute($selectedIds);
    $headers = array_map(fn($c) => $c['label'], $columns);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[] = array_map(fn($c) => (string)($row[$c['field']] ?? ''), $columns);
    }
    XlsxWriter::download('diamond-search-selected-' . date('Ymd-His') . '.xlsx', $headers, $rows);
}

// ---- Normal paginated HTML view ----
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$total = 0;
$rows = [];
if ($columns !== []) {
    $countStmt = get_db()->prepare("SELECT COUNT(*) AS c FROM maindata WHERE $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetch()['c'];

    $fieldList = implode(', ', array_map(fn($c) => "`{$c['field']}`", $columns));
    // Always fetch avail/notforweb (background color) and
    // Lab/CertificateNo/StockNo (certificate hyperlink) too, even if
    // not shown as visible columns.
    $validColsForBg = get_maindata_columns();
    $extraBgFields = array_diff(
        array_intersect(['avail', 'notforweb', 'Lab', 'CertificateNo', 'StockNo'], $validColsForBg),
        array_column($columns, 'field')
    );
    if ($extraBgFields !== []) {
        $fieldList .= ', ' . implode(', ', array_map(fn($f) => "`$f`", $extraBgFields));
    }
    $sql = "SELECT id, $fieldList FROM maindata WHERE $where ORDER BY " . build_rsetup_order_by() . " LIMIT :lim OFFSET :off";
    $stmt = get_db()->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
}
$totalPages = max(1, (int)ceil($total / $perPage));

$pageTitle = 'Results';
$pageSubtitle = '';
$activeNav = 'diamond_search';
$applyPublicTheme = true;
$wideContent = true; // this table has many columns — use the full viewport width

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
            <h1 class="ds-title">Results</h1>
            <div class="ds-actions">
                <a class="btn" href="<?= e(asset_url('/modules/user/diamond_search.php') . '?restore=1') ?>">&larr; Back to Search</a>
                <?php if ($columns !== [] && $total > 0): ?>
                    <a class="btn btn-accent" href="?export=xlsx">Export to Excel</a>
                    <button type="button" class="btn" id="excelSelectBtn">Excel-select</button>
                    <button type="button" class="btn" id="copyBtn">Copy</button>
                    <button type="button" class="btn" id="addToCartBtn">Add to Cart (<span id="cartCountLabel"><?= (int)$cartCount ?></span>)</button>
                <?php endif; ?>
                <a class="btn" href="<?= e(asset_url('/modules/user/view_results.php')) ?>">View Cart</a>
            </div>
        </div>

        <?php if ($columns !== [] && $total > 0): ?>
            <div class="copy-status" id="copyStatus" hidden></div>
            <div class="cart-status" id="cartStatus" hidden></div>
            <form method="post" action="<?= e(asset_url('/modules/user/copy_generate.php')) ?>" id="copyForm" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="ids" id="copyFormIds">
            </form>
            <form method="post" action="<?= e(asset_url('/modules/user/cart_add.php')) ?>" id="cartForm" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="ids" id="cartFormIds">
            </form>
        <?php endif; ?>

        <?php if ($canMemo): ?>
            <div class="memo-bar">
                <div class="memo-error" id="memoError" hidden></div>
                <div class="memo-bar-row">
                    <div class="memo-field">
                        <label for="memoCustomerSelect">Customer</label>
                        <select id="memoCustomerSelect" data-add-url="<?= e(asset_url('/modules/user/customer_form.php?return=results')) ?>">
                            <option value="">— Select Customer —</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= e((string)$c['custid']) ?>" <?= $newCustomerId === (string)$c['custid'] ? 'selected' : '' ?>><?= e($c['custnm']) ?></option>
                            <?php endforeach; ?>
                            <option value="__new__">+ Add New Customer…</option>
                        </select>
                    </div>
                    <a class="btn" href="<?= e(asset_url('/modules/user/customer_list.php')) ?>">Customer</a>
                    <div class="memo-bar-spacer"></div>
                    <button type="button" class="btn btn-accent" id="memo1Btn">Memo-1</button>
                    <button type="button" class="btn btn-accent" id="memo3Btn">Memo-3</button>
                </div>
            </div>
            <form method="post" action="<?= e(asset_url('/modules/user/memo_print.php')) ?>" id="memoForm" target="_blank">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="customer_id" id="memoFormCustomerId">
                <input type="hidden" name="copyopt" id="memoFormCopyopt">
                <input type="hidden" name="ids" id="memoFormIds">
            </form>
        <?php endif; ?>

        <?php if ($columns === []): ?>
            <div class="panel">
                <p class="panel-desc">No result columns are configured yet. An administrator can enable columns in the Results table (active = yes) to control what appears here.</p>
            </div>
        <?php else: ?>
            <div class="results-summary-row">
                <p class="results-count"><?= number_format($total) ?> diamond<?= $total === 1 ? '' : 's' ?> found</p>
                <?php if ($appliedFilters !== []): ?>
                    <div class="results-applied-filters">
                        <?php foreach ($appliedFilters as $af): ?>
                            <span class="filter-tag"><strong><?= e($af['label']) ?>:</strong> <?= e($af['value']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

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
                        <?php if ($rows === []): ?>
                            <tr><td colspan="<?= count($columns) + 1 ?>" class="results-empty">No diamonds match your criteria.</td></tr>
                        <?php endif; ?>
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

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php if ($p === $page): ?>
                            <span class="current"><?= $p ?></span>
                        <?php else: ?>
                            <a href="?page=<?= e((string)$p) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/footer.php';
