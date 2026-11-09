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
    $fieldList = results_export_select_list($columns);
    $stmt = get_db()->prepare("SELECT $fieldList FROM maindata WHERE $where ORDER BY " . build_rsetup_order_by());
    $stmt->execute($params);
    // Cert no. -> certificate link; stock and cert numbers written as text.
    [$headers, $rows, $xlsxOpts] = results_export_sheet($columns, $stmt->fetchAll());
    XlsxWriter::download('diamond-search-results-' . date('Ymd-His') . '.xlsx', $headers, $rows, $xlsxOpts);
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
    $fieldList = results_export_select_list($columns);
    $stmt = get_db()->prepare("SELECT $fieldList FROM maindata WHERE $allWhere ORDER BY " . build_rsetup_order_by());
    $stmt->execute($allParams);
    // Cert no. -> certificate link; stock and cert numbers written as text.
    [$headers, $rows, $xlsxOpts] = results_export_sheet($columns, $stmt->fetchAll());
    XlsxWriter::download('complete-stock-' . date('Ymd-His') . '.xlsx', $headers, $rows, $xlsxOpts);
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
    $fieldList = results_export_select_list($columns);
    $selPlaceholders = implode(',', array_fill(0, count($selectedIds), '?'));
    $stmt = get_db()->prepare("SELECT $fieldList FROM maindata WHERE id IN ($selPlaceholders) ORDER BY " . build_rsetup_order_by());
    $stmt->execute($selectedIds);
    // Cert no. -> certificate link; stock and cert numbers written as text.
    [$headers, $rows, $xlsxOpts] = results_export_sheet($columns, $stmt->fetchAll());
    XlsxWriter::download('diamond-search-selected-' . date('Ymd-His') . '.xlsx', $headers, $rows, $xlsxOpts);
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

$rsetup = get_rsetup();
require_once __DIR__ . '/../../includes/partials/results_table.php';
$mediaLinks = build_result_media_links_for_rows($rows);

// ---- Page markup comes from the live template (Admin → Search Templates) ----
require_once __DIR__ . '/../../includes/search_templates.php';
require search_template_view('results');
