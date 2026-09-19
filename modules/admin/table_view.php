<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crud_engine.php';

require_module_access('admin');

$table = (string)($_GET['table'] ?? '');
crud_assert_table($table);
$meta = get_table_meta($table);

$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$result = crud_list($table, $search, $page);

$listColumns = crud_list_columns($table, $meta);

$pageTitle = CRUD_TABLES[$table];
$pageSubtitle = number_format($result['total']) . ' record' . ($result['total'] === 1 ? '' : 's');
$activeNav = 'table:' . $table;

$dashActionsHtml =
    '<a class="btn" href="' . e(asset_url('/modules/admin/table_export.php?table=' . urlencode($table))) . '">Export to Excel</a>'
    . '<a class="btn" href="#import-panel">Import from Excel</a>'
    . '<a class="btn btn-accent" href="' . e(asset_url('/modules/admin/table_form.php?table=' . urlencode($table))) . '">+ Add New</a>';

require_once __DIR__ . '/../../includes/admin_header.php';

$errorMessages = [
    'self_delete'      => 'You can\'t delete your own logged-in account.',
    'still_referenced' => 'This record can\'t be deleted because other records still depend on it (e.g. a user type still assigned to one or more accounts).',
    'delete_failed'    => 'This record could not be deleted.',
];
$errorCode = (string)($_GET['error'] ?? '');
?>
    <?php if ($errorCode !== '' && isset($errorMessages[$errorCode])): ?>
        <div class="alert alert-error" style="margin-bottom:18px;"><?= e($errorMessages[$errorCode]) ?></div>
    <?php endif; ?>
    <div class="panel">
        <div class="table-toolbar">
            <form method="get" action="<?= e(asset_url('/modules/admin/table_view.php')) ?>">
                <input type="hidden" name="table" value="<?= e($table) ?>">
                <input type="text" name="q" class="search-input" placeholder="Search..." value="<?= e($search) ?>">
            </form>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <?php foreach ($listColumns as $col): ?>
                            <th><?= e($meta['columns'][$col]['label']) ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result['rows'] === []): ?>
                    <tr><td colspan="<?= count($listColumns) + 1 ?>" style="color:var(--text-low);">No records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($result['rows'] as $row): ?>
                    <tr>
                        <?php foreach ($listColumns as $col):
                            $colType = $meta['columns'][$col]['type'] ?? 'text';
                            $rawVal = (string)($row[$col] ?? '');
                            $val = $colType === 'lookup'
                                ? ($meta['columns'][$col]['options'][$rawVal] ?? $rawVal)
                                : $rawVal;
                            $display = mb_strlen($val) > 60 ? mb_substr($val, 0, 60) . '…' : $val;
                        ?>
                            <td title="<?= e($val) ?>" data-label="<?= e($meta['columns'][$col]['label'] ?? $col) ?>">
                                <?php if ($col === 'usertype' && $colType === 'lookup'): ?>
                                    <span class="pill <?= strcasecmp($val, 'Super Admin') === 0 ? 'pill-danger' : (strcasecmp($val, 'Admin') === 0 ? 'pill-warning' : 'pill-neutral') ?>"><?= e($val) ?></span>
                                <?php elseif ($col === 'approval'): ?>
                                    <span class="pill <?= $val === 'approved' ? 'pill-success' : ($val === 'pending' ? 'pill-warning' : 'pill-danger') ?>"><?= e($val) ?></span>
                                <?php else: ?>
                                    <?= e($display) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="row-actions" data-label="Actions">
                            <a class="btn btn-sm" href="<?= e(asset_url('/modules/admin/table_form.php?table=' . urlencode($table) . '&id=' . urlencode((string)$row[$meta['pk']]))) ?>">Edit</a>
                            <form method="post" action="<?= e(asset_url('/modules/admin/table_delete.php')) ?>"
                                  data-confirm="Delete this record? This cannot be undone." style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="table" value="<?= e($table) ?>">
                                <input type="hidden" name="id" value="<?= e((string)$row[$meta['pk']]) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <div class="pagination">
                <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                    <?php if ($p === $result['page']): ?>
                        <span class="current"><?= $p ?></span>
                    <?php else: ?>
                        <a href="<?= e(asset_url('/modules/admin/table_view.php?table=' . urlencode($table) . '&q=' . urlencode($search) . '&page=' . $p)) ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel" id="import-panel">
        <h2>Import from Excel</h2>
        <p class="panel-desc">
            Upload an .xlsx file with a header row matching the column names shown in the edit form
            (e.g. "<?= e(array_values($meta['columns'])[1]['label'] ?? 'Column') ?>"). If a row's
            "<?= e($meta['pk']) ?>" matches an existing record, that record is updated; otherwise a new one is created.
        </p>
        <form method="post" action="<?= e(asset_url('/modules/admin/table_import.php')) ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="table" value="<?= e($table) ?>">
            <div class="dropzone">
                <input type="file" name="import_file" accept=".xlsx" required>
                <p style="margin:10px 0 0;"><strong>Choose an .xlsx file</strong> to import into <?= e($pageTitle) ?>.</p>
            </div>
            <div style="margin-top:14px;">
                <button type="submit" class="btn btn-accent">Upload &amp; Import</button>
            </div>
        </form>
    </div>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
