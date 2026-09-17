<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crud_engine.php';

require_memo_level();

$table = 'customer';
$meta = get_table_meta($table);

$id = isset($_GET['id']) ? (string)$_GET['id'] : (isset($_POST['id']) ? (string)$_POST['id'] : null);
$isNew = $id === null || $id === '';
$error = '';
$row = [];

// Where to send the user back to after saving — Results wants this
// so it can immediately select the newly-added customer.
$returnTo = (string)($_GET['return'] ?? $_POST['return'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired — please try again.';
    } else {
        $input = crud_extract_form_input($meta, $_POST);
        try {
            $savedId = crud_save($table, $isNew ? null : $id, $input);
            if ($returnTo === 'results') {
                header('Location: ' . asset_url('/modules/user/results.php?new_customer=' . urlencode((string)$savedId)));
            } else {
                header('Location: ' . asset_url('/modules/user/customer_list.php?saved=1'));
            }
            exit;
        } catch (Throwable $e) {
            $error = 'Could not save this record: ' . $e->getMessage();
        }
    }
    $row = $input ?? [];
} elseif (!$isNew) {
    $row = crud_get($table, $id) ?? [];
    if ($row === []) {
        http_response_code(404);
        exit('Customer not found.');
    }
}

$pageTitle = ($isNew ? 'Add Customer' : 'Edit Customer');
$pageSubtitle = '';
$activeNav = 'diamond_search';
$applyPublicTheme = true;

require_once __DIR__ . '/../../includes/header.php';
?>
    <section class="ds-page">
        <div class="ds-hero">
            <h1 class="ds-title"><?= e($pageTitle) ?></h1>
            <div class="ds-actions">
                <a class="btn" href="<?= e(asset_url('/modules/user/customer_list.php')) ?>">&larr; Back to Customers</a>
            </div>
        </div>

        <div class="panel">
            <?php if ($error !== ''): ?>
                <div class="alert alert-error" style="margin-bottom:18px;"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(asset_url('/modules/user/customer_form.php' . ($isNew ? '' : '?id=' . urlencode($id)))) ?>" class="crud-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php if (!$isNew): ?><input type="hidden" name="id" value="<?= e($id) ?>"><?php endif; ?>
                <?php if ($returnTo !== ''): ?><input type="hidden" name="return" value="<?= e($returnTo) ?>"><?php endif; ?>

                <?php foreach ($meta['columns'] as $colName => $col):
                    if ($col['type'] === 'hidden' || ($col['auto'] && $isNew)) {
                        continue;
                    }
                    $value = (string)($row[$colName] ?? '');
                    $fieldId = crud_wire_name($colName);
                ?>
                    <div class="form-group">
                        <label for="<?= e($fieldId) ?>"><?= e($col['label']) ?></label>
                        <?php if ($col['type'] === 'readonly'): ?>
                            <div class="readonly-value"><?= e($value !== '' ? $value : '—') ?></div>
                        <?php else: ?>
                            <input type="text" id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>" value="<?= e($value) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Save Customer</button>
                    <a class="btn btn-ghost" href="<?= e(asset_url('/modules/user/customer_list.php')) ?>">Cancel</a>
                </div>
            </form>
        </div>
    </section>
<?php
require_once __DIR__ . '/../../includes/footer.php';
