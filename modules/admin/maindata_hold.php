<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_module_access('admin');

$validCols = get_maindata_columns();

// The columns shown here, in order: [maindata column => header label].
// Every one is validated against the real, current maindata columns
// below (get_maindata_columns()) before ever being used in SQL.
$displayColumns = [
    'StockNo'               => 'Stock No',
    'Weight'                => 'Size',
    'Shape'                 => 'Shape',
    'Color'                 => 'Color',
    'Clarity'               => 'Clarity',
    'CutGrade'              => 'Cut Grade',
    'Polish'                => 'Polish',
    'Symmetry'              => 'Symmetry',
    'FluorescenceIntensity' => 'Fluor',
    'Lab'                   => 'Lab',
    'CertificateNo'         => 'Certificate No',
    'Price'                 => 'Price',
    'totamt'                => 'Amount',
];
$displayColumns = array_filter($displayColumns, fn($label, $col) => in_array($col, $validCols, true), ARRAY_FILTER_USE_BOTH);

$hasHoldCol = in_array('hold', $validCols, true);

$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = resolve_per_page('maindata_hold_per_page', 100);

// Server-side search across the columns actually shown — a fresh
// query every time, so it works across the whole table, not just
// whatever happens to be on the current page.
$where = '1=1';
$params = [];
if ($search !== '' && $displayColumns !== []) {
    $likeParts = [];
    $i = 0;
    foreach (array_keys($displayColumns) as $col) {
        $key = ':s' . $i++;
        $likeParts[] = "`$col` LIKE $key";
        $params[$key] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
    }
    $where = '(' . implode(' OR ', $likeParts) . ')';
}

$db = get_db();
$countStmt = $db->prepare("SELECT COUNT(*) FROM `maindata` WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$selectCols = array_keys($displayColumns);
$selectCols[] = 'id';
if ($hasHoldCol) {
    $selectCols[] = 'hold';
}
$selectCols = array_values(array_unique($selectCols));
$fieldList = implode(', ', array_map(fn($c) => "`$c`", $selectCols));

$stmt = $db->prepare("SELECT $fieldList FROM `maindata` WHERE $where ORDER BY `id` DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$pageTitle = 'Hold Selection';
$pageSubtitle = number_format($total) . ' record' . ($total === 1 ? '' : 's');
$activeNav = 'maindata_hold';

$flash = (string)($_GET['flash'] ?? '');

require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <?php if ($flash === 'saved'): ?>
        <div class="alert alert-success" style="margin-bottom:18px;">Hold selection saved for this page's rows.</div>
    <?php elseif ($flash === 'save_failed'): ?>
        <div class="alert alert-error" style="margin-bottom:18px;">Something went wrong saving — please try again.</div>
    <?php elseif (!$hasHoldCol): ?>
        <div class="alert alert-error" style="margin-bottom:18px;">The maindata table has no <code>hold</code> column — nothing can be saved here until that's added.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="table-toolbar">
            <form method="get" action="<?= e(asset_url('/modules/admin/maindata_hold.php')) ?>">
                <input type="text" name="q" class="search-input" placeholder="Search stock no, certificate no, shape, color…" value="<?= e($search) ?>">
            </form>
        </div>

        <form method="post" action="<?= e(asset_url('/modules/admin/maindata_hold_save.php')) ?>" id="holdForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="q" value="<?= e($search) ?>">
            <input type="hidden" name="page" value="<?= (int)$page ?>">
            <input type="hidden" name="per_page" value="<?= (int)$perPage ?>">

            <div class="table-toolbar" style="justify-content:flex-end;">
                <button type="button" class="btn" id="holdClearBtn">Clear Selection</button>
                <button type="submit" class="btn btn-accent" <?= $hasHoldCol ? '' : 'disabled' ?>>Save</button>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="holdTable">
                    <thead>
                        <tr>
                            <th style="width:36px;"><span class="hint">Hold</span></th>
                            <?php foreach ($displayColumns as $label): ?>
                                <th><?= e($label) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($rows === []): ?>
                        <tr><td colspan="<?= count($displayColumns) + 1 ?>" style="color:var(--text-low);">No records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $isHeld = $hasHoldCol && strcasecmp((string)($row['hold'] ?? ''), 'yes') === 0; ?>
                        <tr>
                            <td data-label="Hold">
                                <input type="checkbox" name="hold_ids[]" value="<?= (int)$row['id'] ?>" <?= $isHeld ? 'checked' : '' ?> <?= $hasHoldCol ? '' : 'disabled' ?>>
                                <input type="hidden" name="all_ids[]" value="<?= (int)$row['id'] ?>">
                            </td>
                            <?php foreach (array_keys($displayColumns) as $col):
                                $val = $row[$col] ?? '';
                                $display = ($col === 'Price' || $col === 'totamt') && $val !== '' && $val !== null
                                    ? number_format((float)$val, 2)
                                    : (string)$val;
                            ?>
                                <td data-label="<?= e($displayColumns[$col]) ?>"><?= e($display) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-toolbar" style="justify-content:flex-end;">
                <button type="button" class="btn" id="holdClearBtnBottom">Clear Selection</button>
                <button type="submit" class="btn btn-accent" <?= $hasHoldCol ? '' : 'disabled' ?>>Save</button>
            </div>
        </form>

        <div class="results-pagination-row">
            <form class="rows-per-page" method="get" action="<?= e(asset_url('/modules/admin/maindata_hold.php')) ?>">
                <label for="perPageSelect">Rows per page:</label>
                <select id="perPageSelect" name="per_page">
                    <?php foreach (rows_per_page_choices() as $opt): ?>
                        <option value="<?= (int)$opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= (int)$opt ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="q" value="<?= e($search) ?>">
                <input type="hidden" name="page" value="1">
            </form>
            <?php render_pagination($page, $totalPages, fn($p) => '?q=' . urlencode($search) . '&per_page=' . $perPage . '&page=' . $p); ?>
        </div>

        <p class="hint" style="margin-top:14px;">
            Checking or unchecking a box only takes effect after you click <strong>Save</strong>, and only affects the
            rows currently shown on this page — navigating to another page or a new search first discards any
            unsaved changes here. A held stone (<code>hold = yes</code>) is excluded from Diamond Search Results
            and View Cart for everyone.
        </p>
    </div>

    <script src="<?= e(asset_url_versioned('/assets/js/maindata_hold.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
