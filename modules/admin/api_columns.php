<?php
/**
 * api_columns.php
 * Admin > API > CSV Columns: choose which columns go into the customer
 * stock CSV (api/stock_csv.php) — the results.extract field (yes/no) of
 * the CSV's user type (APIKEY_CSV_USERTYPE_ID, Standard User by default).
 *
 * Opens as a read-only list; "Edit" switches to a form with a Yes/No
 * choice per column. Logic: includes/apikey.php.
 */

declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/apikey.php';

require_module_access('admin');

$self = asset_url('/modules/admin/api_columns.php');
$editing = isset($_GET['edit']);
$flash = null;

try {
    $ready = apikey_has_extract_column();
} catch (Throwable $e) {
    $ready = false;
}

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $flash = ['type' => 'error', 'message' => 'Your session expired — please try again.'];
        $editing = true;
    } else {
        try {
            $bulk = (string)($_POST['bulk'] ?? '');
            if ($bulk === 'yes' || $bulk === 'no') {
                $values = [];
                foreach (apikey_extract_rows() as $row) {
                    $values[(int)$row['id']] = $bulk;
                }
            } else {
                $values = is_array($_POST['extract'] ?? null) ? $_POST['extract'] : [];
            }
            $changed = apikey_save_extract($values);
            $_SESSION['api_columns_flash'] = [
                'type'    => 'success',
                'message' => $changed === 0 ? 'No changes to save.' : $changed . ' column' . ($changed === 1 ? '' : 's') . ' updated.',
            ];
            header('Location: ' . $self, true, 303);
            exit;
        } catch (Throwable $e) {
            error_log('api_columns save failed: ' . $e->getMessage());
            $flash = ['type' => 'error', 'message' => 'The changes could not be saved. Please try again.'];
            $editing = true;
        }
    }
}

if ($flash === null && isset($_SESSION['api_columns_flash'])) {
    $flash = $_SESSION['api_columns_flash'];
    unset($_SESSION['api_columns_flash']);
}

$rows = $ready ? apikey_extract_rows() : [];
$inCsv = count(array_filter($rows, fn($r) => $r['extract'] === 'yes' && $r['valid']));
$usertypeLabel = $ready ? apikey_csv_usertype_label() : '';

$pageTitle = 'API — CSV Columns';
$pageSubtitle = 'Choose which columns customers get in the stock CSV';
$activeNav = 'api_keys';
require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <div class="api-tabs">
        <a href="<?= e(asset_url('/modules/admin/api_keys.php')) ?>">API keys</a>
        <a href="<?= e($self) ?>" class="active" aria-current="page">CSV columns</a>
    </div>

    <?php if (!$ready): ?>
        <div class="alert alert-error">The <code>extract</code> field doesn’t exist in the results table yet. Please run <code>sql/migration_results_extract.sql</code> in phpMyAdmin.</div>
    <?php else: ?>

    <?php if ($flash !== null): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="status" style="margin-bottom:18px;"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($inCsv === 0 && $rows !== []): ?>
        <div class="alert alert-warning" style="margin-bottom:18px;">No columns are set to Extract = Yes, so customers can’t download the stock list until at least one is.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="table-toolbar">
            <div>
                <h2 style="margin:0;">CSV columns</h2>
                <p class="panel-desc" style="margin:4px 0 0;">
                    <strong><?= $inCsv ?></strong> of <?= count($rows) ?> columns go into the CSV, in this order.
                    These are the <?= e($usertypeLabel) ?> rows of the Results table; <em>On Results page</em> is shown for reference only.
                </p>
            </div>
            <?php if (!$editing && $rows !== []): ?>
                <a class="btn btn-accent" href="<?= e($self . '?edit=1') ?>">Edit</a>
            <?php endif; ?>
        </div>

        <?php if ($rows === []): ?>
            <p>The Results table has no rows for <?= e($usertypeLabel) ?>.</p>
        <?php elseif ($editing): ?>
            <form method="post" action="<?= e($self) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>#</th><th>Column name</th><th>Extract</th><th>On Results page</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $i => $r): ?>
                                <tr>
                                    <td data-label="#"><?= $i + 1 ?></td>
                                    <td data-label="Column name">
                                        <label for="extract-<?= (int)$r['id'] ?>"><?= e((string)$r['colname']) ?></label>
                                        <?php if ($r['fldname'] !== $r['colname']): ?><br><small style="color:var(--text-muted);">field: <?= e((string)$r['fldname']) ?></small><?php endif; ?>
                                        <?php if (!$r['valid']): ?><br><small class="text-danger">field doesn’t exist in maindata — skipped</small><?php endif; ?>
                                    </td>
                                    <td data-label="Extract">
                                        <select id="extract-<?= (int)$r['id'] ?>" name="extract[<?= (int)$r['id'] ?>]">
                                            <option value="yes" <?= $r['extract'] === 'yes' ? 'selected' : '' ?>>Yes</option>
                                            <option value="no" <?= $r['extract'] === 'no' ? 'selected' : '' ?>>No</option>
                                        </select>
                                    </td>
                                    <td data-label="On Results page"><?= strcasecmp(trim((string)$r['active']), 'yes') === 0 ? 'yes' : 'no' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="api-form-actions">
                    <button type="submit" class="btn btn-accent">Save</button>
                    <a class="btn" href="<?= e($self) ?>">Cancel</a>
                </div>
            </form>
            <form method="post" action="<?= e($self) ?>" class="api-form-actions" data-confirm="Set Extract = Yes for ALL <?= count($rows) ?> columns and save?">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" name="bulk" value="yes" class="btn btn-sm">Set all to Yes</button>
            </form>
            <form method="post" action="<?= e($self) ?>" class="api-form-actions" data-confirm="Set Extract = No for ALL <?= count($rows) ?> columns and save? Customers won’t be able to download until you set some back to Yes.">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" name="bulk" value="no" class="btn btn-sm">Set all to No</button>
            </form>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>#</th><th>Column name</th><th>Extract</th><th>On Results page</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $i => $r): ?>
                            <tr>
                                <td data-label="#"><?= $i + 1 ?></td>
                                <td data-label="Column name">
                                    <?= e((string)$r['colname']) ?>
                                    <?php if ($r['fldname'] !== $r['colname']): ?><br><small style="color:var(--text-muted);">field: <?= e((string)$r['fldname']) ?></small><?php endif; ?>
                                    <?php if (!$r['valid']): ?><br><small class="text-danger">field doesn’t exist in maindata — skipped</small><?php endif; ?>
                                </td>
                                <td data-label="Extract"><strong style="color:<?= $r['extract'] === 'yes' ? 'var(--success-text)' : 'var(--text-muted)' ?>;"><?= $r['extract'] === 'yes' ? 'Yes' : 'No' ?></strong></td>
                                <td data-label="On Results page"><?= strcasecmp(trim((string)$r['active']), 'yes') === 0 ? 'yes' : 'no' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="api-form-actions"><a class="btn btn-accent" href="<?= e($self . '?edit=1') ?>">Edit</a></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
