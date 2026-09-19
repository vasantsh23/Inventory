<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crud_engine.php';

require_memo_level();

$table = 'customer';
$meta = get_table_meta($table);

$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$result = crud_list($table, $search, $page);
$listColumns = crud_list_columns($table, $meta);

$pageTitle = 'Customers';
$pageSubtitle = '';
$activeNav = 'diamond_search';
$applyPublicTheme = true;

$errorMessages = [
    'still_referenced' => 'This customer can\'t be deleted because they\'re referenced elsewhere.',
    'delete_failed'    => 'This record could not be deleted.',
];
$errorCode = (string)($_GET['error'] ?? '');

require_once __DIR__ . '/../../includes/header.php';
?>
    <section class="ds-page">
        <div class="ds-hero">
            <h1 class="ds-title">Customers</h1>
            <div class="ds-actions">
                <a class="btn" href="<?= e(asset_url('/modules/user/results.php')) ?>">&larr; Back to Results</a>
                <a class="btn btn-accent" href="<?= e(asset_url('/modules/user/customer_form.php')) ?>">+ Add Customer</a>
            </div>
        </div>

        <?php if ($errorCode !== '' && isset($errorMessages[$errorCode])): ?>
            <div class="alert alert-error" style="margin-bottom:18px;"><?= e($errorMessages[$errorCode]) ?></div>
        <?php endif; ?>

        <div class="panel">
            <div class="table-toolbar">
                <form method="get" action="<?= e(asset_url('/modules/user/customer_list.php')) ?>">
                    <input type="text" name="q" class="search-input" placeholder="Search customers…" value="<?= e($search) ?>">
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
                        <tr><td colspan="<?= count($listColumns) + 1 ?>" style="color:var(--text-low);">No customers found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($result['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($listColumns as $col): ?>
                                <td data-label="<?= e($meta['columns'][$col]['label']) ?>"><?= e((string)($row[$col] ?? '')) ?></td>
                            <?php endforeach; ?>
                            <td class="row-actions" data-label="Actions">
                                <a class="btn btn-sm" href="<?= e(asset_url('/modules/user/customer_form.php?id=' . urlencode((string)$row[$meta['pk']]))) ?>">Edit</a>
                                <form method="post" action="<?= e(asset_url('/modules/user/customer_delete.php')) ?>"
                                      data-confirm="Delete this customer? This cannot be undone." style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
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
                            <a href="<?= e(asset_url('/modules/user/customer_list.php?q=' . urlencode($search) . '&page=' . $p)) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/confirm_submit.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/footer.php';
