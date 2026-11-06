<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/diamond_search_query.php';
require_once __DIR__ . '/../../includes/media_check.php';
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
    // A brand-new search starts with nothing ticked — tells
    // memo_actions.js to forget the rows remembered from the last one.
    $_SESSION['ds_reset_selection'] = true;
    header('Location: ' . asset_url('/modules/user/results.php'));
    exit;
}

$memoLevel = (int)(current_user()['level'] ?? 0);
$memoFeatureOn = strcasecmp((string)((get_setup() ?? [])['Memo'] ?? 'yes'), 'yes') === 0;
$canMemo = $memoFeatureOn && in_array($memoLevel, [4, 5], true);
$customers = [];
$newCustomerId = '';
if ($canMemo) {
    $customers = get_db()->query("SELECT custid, custnm FROM customer ORDER BY custnm ASC")->fetchAll();
    $newCustomerId = (string)($_GET['new_customer'] ?? '');
}

// setup.loginscrn = 'yes' → the normal cart: "Add to Cart" saves rows
// to the `selection` table and "View Cart" lists them.
// setup.loginscrn = 'no'  → no cart at all: "Add to Cart" is hidden and
// "View Selected" just shows the rows ticked on this page, looked up
// straight from maindata by StockNo (see view_results.php).
$cartEnabled = !is_guest_browsing_enabled();

$emailid = (string)(current_user()['emailid'] ?? '');
$cartCount = 0;
if ($cartEnabled && $emailid !== '') {
    $cartCountStmt = get_db()->prepare('SELECT COUNT(DISTINCT stockno) AS c FROM selection WHERE emailid = :e');
    $cartCountStmt->execute([':e' => $emailid]);
    $cartCount = (int)$cartCountStmt->fetch()['c'];
}

$filters = $_SESSION['ds_last_filters'] ?? [];
if (!is_array($filters)) {
    $filters = [];
}
[$where, $params] = build_maindata_search_where($filters);
// Columns come from the `results` rows for this viewer's user type. If
// that user type has no rows, show an error instead of the table.
$resultsError = '';
try {
    $columns = get_results_columns();
} catch (ResultsConfigException $e) {
    $columns = [];
    $resultsError = $e->getMessage();
}
$appliedFilters = build_applied_filters_summary($filters);

// ---- Excel export: same filters, no pagination ----
if (($_GET['export'] ?? '') === 'xlsx') {
    if ($resultsError !== '') {
        http_response_code(400);
        exit($resultsError);
    }
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
        $rows[] = array_map(
            fn($c) => $c['field'] === 'totamt'
                ? number_format(ds_display_amount($row['totamt'] ?? 0), 2, '.', '')
                : (string)($row[$c['field']] ?? ''),
            $columns
        );
    }
    XlsxWriter::download('diamond-search-results-' . date('Ymd-His') . '.xlsx', $headers, $rows);
}

// ---- Complete Stock: every stone the viewer may see (no search
//      filters; held stones stay hidden for plain users, exactly as in
//      an unfiltered search), same columns as the table. ----
if (($_GET['export'] ?? '') === 'xlsx_all') {
    if ($resultsError !== '') {
        http_response_code(400);
        exit($resultsError);
    }
    if ($columns === []) {
        http_response_code(400);
        exit('No result columns are configured.');
    }
    [$allWhere, $allParams] = build_maindata_search_where([]);
    $fieldList = implode(', ', array_map(fn($c) => "`{$c['field']}`", $columns));
    $stmt = get_db()->prepare("SELECT $fieldList FROM maindata WHERE $allWhere ORDER BY " . build_rsetup_order_by());
    $stmt->execute($allParams);
    $headers = array_map(fn($c) => $c['label'], $columns);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[] = array_map(
            fn($c) => $c['field'] === 'totamt'
                ? number_format(ds_display_amount($row['totamt'] ?? 0), 2, '.', '')
                : (string)($row[$c['field']] ?? ''),
            $columns
        );
    }
    XlsxWriter::download('complete-stock-' . date('Ymd-His') . '.xlsx', $headers, $rows);
}

// ---- Excel-select: only the checked rows (ids from the Excel-select
//      button's JS), ignoring the current search filters entirely —
//      whatever the person selected is exactly what gets exported. ----
if (($_GET['export'] ?? '') === 'xlsx_selected') {
    if ($resultsError !== '') {
        http_response_code(400);
        exit($resultsError);
    }
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
        $rows[] = array_map(
            fn($c) => $c['field'] === 'totamt'
                ? number_format(ds_display_amount($row['totamt'] ?? 0), 2, '.', '')
                : (string)($row[$c['field']] ?? ''),
            $columns
        );
    }
    XlsxWriter::download('diamond-search-selected-' . date('Ymd-His') . '.xlsx', $headers, $rows);
}

// ---- Normal paginated HTML view ----
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = resolve_per_page('ds_results_per_page', 100);
$offset = ($page - 1) * $perPage;

$total = 0;
$rows = [];
if ($columns !== []) {
    $countStmt = get_db()->prepare("SELECT COUNT(*) AS c FROM maindata WHERE $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetch()['c'];

    $fieldList = implode(', ', array_map(fn($c) => "`{$c['field']}`", $columns));
    // Also fetch the fields the table needs but may not show (StockNo
    // colour, certificate link, media paths, Weight/Amount totals).
    require_once __DIR__ . '/../../includes/partials/results_table.php';
    $extraFields = results_table_extra_fields($columns);
    if ($extraFields !== []) {
        $fieldList .= ', ' . implode(', ', array_map(fn($f) => "`$f`", $extraFields));
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

$resetSelection = !empty($_SESSION['ds_reset_selection']);
unset($_SESSION['ds_reset_selection']);

$pageTitle = 'Results';
$pageSubtitle = '';
$activeNav = 'diamond_search';
$wideContent = true; // this table has many columns — use the full viewport width
$compactTop = true;  // less space between the site header and the Results buttons
$hideFooterBar = true; // the copyright line is printed in the page's own blue footer band
$bodyClass = 'compact-footer no-footer-gap rs-v2'; // tight footer; rs-v2 = bordered blue-header table design
$rsCompany = (string)((get_setup() ?? [])['company'] ?? '');
$copyLabel = (strtok(trim($rsCompany), ' ') ?: 'Copy') . '-Copy';  // e.g. "Veera-Copy"

$rsetup = get_rsetup();

require_once __DIR__ . '/../../includes/partials/results_table.php';
$mediaLinks = build_result_media_links_for_rows($rows);
require_once __DIR__ . '/../../includes/header.php';
?>
    <section class="ds-page rs-page">
        <div class="rs-toolbar">
            <?php if ($canMemo && $resultsError === ''): ?>
                <div class="rs-customer">
                    <div class="rs-customer-search">
                        <input type="search" id="memoCustomerSearch" placeholder="Search Customer" aria-label="Search customer" autocomplete="off">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M10.5 3a7.5 7.5 0 0 1 5.9 12.1l4.7 4.7-1.4 1.4-4.7-4.7A7.5 7.5 0 1 1 10.5 3zm0 2a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11z"/></svg>
                    </div>
                    <select id="memoCustomerSelect" aria-label="Select customer" data-add-url="<?= e(asset_url('/modules/user/customer_form.php?return=results')) ?>">
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= e((string)$c['custid']) ?>" <?= $newCustomerId === (string)$c['custid'] ? 'selected' : '' ?>><?= e($c['custnm']) ?></option>
                        <?php endforeach; ?>
                        <option value="__new__">+ Add New Customer…</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="rs-buttons">
                <div class="rs-btn-row">
                    <?php if ($columns !== [] && $total > 0): ?>
                        <button type="button" class="rs-btn" id="excelSelectBtn"><?= rs_icon('xls') ?>Excel-Select</button>
                    <?php endif; ?>
                    <a class="rs-btn" href="<?= e(asset_url('/modules/user/diamond_search.php')) ?>"><?= rs_icon('filter') ?>Blank Filter</a>
                    <a class="rs-btn" href="<?= e(asset_url('/modules/user/diamond_search.php') . '?restore=1') ?>"><?= rs_icon('filter') ?>Modify Filter</a>
                    <?php if ($canMemo): ?>
                        <a class="rs-btn" href="<?= e(asset_url('/modules/user/customer_form.php?return=results')) ?>"><?= rs_icon('filter') ?>Add Customer</a>
                    <?php endif; ?>
                    <?php if ($columns !== []): ?>
                        <a class="rs-btn" href="?export=xlsx_all"><?= rs_icon('xls') ?>Complete Stock</a>
                    <?php endif; ?>
                </div>
                <div class="rs-btn-row">
                    <?php if ($cartEnabled && $columns !== [] && $total > 0): ?>
                        <button type="button" class="rs-btn rs-btn--big" id="addToCartBtn"><?= rs_icon('cart') ?><span>Add to cart-<span id="cartCountLabel"><?= (int)$cartCount ?></span></span></button>
                    <?php endif; ?>
                    <?php if ($cartEnabled): ?>
                        <a class="rs-btn" href="<?= e(asset_url('/modules/user/view_results.php')) ?>"><?= rs_icon('cart') ?>View cart</a>
                    <?php endif; ?>
                    <?php if ($columns !== [] && $total > 0): ?>
                        <button type="button" class="rs-btn" id="copyBtn"><?= rs_icon('copy') ?><?= e($copyLabel) ?></button>
                    <?php endif; ?>
                    <?php if ($canMemo && $resultsError === ''): ?>
                        <button type="button" class="rs-btn" id="memo1Btn"><?= rs_icon('eye') ?>Memo-1</button>
                        <button type="button" class="rs-btn" id="memo3Btn"><?= rs_icon('eye') ?>Memo-3</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="memo-error" id="memoError" hidden></div>
        <?php if ($columns !== [] && $total > 0): ?>
            <div class="copy-status" id="copyStatus" hidden></div>
            <div class="cart-status" id="cartStatus" hidden></div>
            <form method="post" action="<?= e(asset_url('/modules/user/copy_generate.php')) ?>" id="copyForm" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="ids" id="copyFormIds">
            </form>
            <?php if ($cartEnabled): ?>
                <form method="post" action="<?= e(asset_url('/modules/user/cart_add.php')) ?>" id="cartForm" style="display:none;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="ids" id="cartFormIds">
                </form>
            <?php endif; ?>
            <form method="post" action="<?= e(asset_url('/modules/user/view_results.php')) ?>" id="viewSelectedForm" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="view_selected">
                <input type="hidden" name="stocknos" id="viewSelectedStocknos">
            </form>
        <?php endif; ?>

        <?php if ($canMemo && $resultsError === ''): ?>
            <form method="post" action="<?= e(asset_url('/modules/user/memo_print.php')) ?>" id="memoForm" target="_blank">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="customer_id" id="memoFormCustomerId">
                <input type="hidden" name="copyopt" id="memoFormCopyopt">
                <input type="hidden" name="ids" id="memoFormIds">
            </form>
        <?php endif; ?>

        <?php if ($resultsError !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($resultsError) ?></div>
        <?php elseif ($columns === []): ?>
            <div class="panel">
                <p class="panel-desc">No result columns are configured yet. An administrator can enable columns in the Results table (active = yes) to control what appears here.</p>
            </div>
        <?php else: ?>
            <?php
            $filterTags = '';
            if ($appliedFilters !== []) {
                $filterTags = '<div class="results-applied-filters">';
                foreach ($appliedFilters as $af) {
                    $filterTags .= '<span class="filter-tag"><strong>' . e($af['label']) . ':</strong> ' . e($af['value']) . '</span>';
                }
                $filterTags .= '</div>';
            }
            render_results_table($columns, $rows, $mediaLinks, [
                'total' => $total,
                'persist' => true,
                'reset' => $resetSelection,
                'toolbarExtra' => '<button type="button" class="rs-link-btn" id="clearSelectionBtn">Clear selection</button>' . $filterTags,
            ]);
            ?>

            <div class="results-pagination-row">
                <form class="rows-per-page" method="get" action="<?= e(asset_url('/modules/user/results.php')) ?>">
                    <label for="perPageSelect">Rows per page:</label>
                    <select id="perPageSelect" name="per_page">
                        <?php foreach (rows_per_page_choices() as $opt): ?>
                            <option value="<?= (int)$opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= (int)$opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="page" value="1">
                </form>
                <?php render_pagination($page, $totalPages, fn($p) => '?page=' . $p); ?>
            </div>

            <?php if ($total > 0): ?>
                <button type="button" class="rs-float-btn" id="viewSelectedBtn">View<br>(Selected)</button>
            <?php endif; ?>
        <?php endif; ?>
    </section>
    <?php render_rs_footer_band(); ?>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_fit.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_toolbar.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/footer.php';
