<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_module_access('admin');

$validCols = get_maindata_columns();
// The exact columns requested, in order — id and hold are always
// fetched too (id for the checkbox value/updates, hold to pre-check
// it), whether or not they're in this display list.
$displayCols = array_values(array_filter(
    ['StockNo', 'Weight', 'Shape', 'Color', 'Clarity', 'CutGrade', 'Polish', 'Symmetry', 'FluorescenceIntensity', 'Lab', 'CertificateNo', 'Price', 'totamt'],
    fn($c) => in_array($c, $validCols, true)
));

$successMessage = '';
$fatalError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $fatalError = 'Your session expired — please reload this page and try again.';
    } elseif (!in_array('hold', $validCols, true)) {
        $fatalError = 'The hold column doesn\'t exist on this database yet — run migration_fancy.sql first.';
    } else {
        // Every row that was on the page when Save was clicked, and
        // which of those are checked — so unchecked rows (including
        // ones that were previously on hold) correctly get hold set
        // back to 'no', not just left alone.
        $allIds = array_values(array_filter(array_map('intval', (array)($_POST['all_ids'] ?? []))));
        $checkedIds = array_values(array_filter(array_map('intval', (array)($_POST['checked_ids'] ?? []))));

        $db = get_db();
        $db->beginTransaction();
        try {
            if ($checkedIds !== []) {
                $inKeys = [];
                $params = [];
                foreach ($checkedIds as $i => $id) {
                    $key = ":c$i";
                    $inKeys[] = $key;
                    $params[$key] = $id;
                }
                $stmt = $db->prepare('UPDATE maindata SET hold = \'yes\' WHERE id IN (' . implode(', ', $inKeys) . ')');
                $stmt->execute($params);
            }
            $uncheckedIds = array_values(array_diff($allIds, $checkedIds));
            if ($uncheckedIds !== []) {
                $inKeys = [];
                $params = [];
                foreach ($uncheckedIds as $i => $id) {
                    $key = ":u$i";
                    $inKeys[] = $key;
                    $params[$key] = $id;
                }
                $stmt = $db->prepare('UPDATE maindata SET hold = \'no\' WHERE id IN (' . implode(', ', $inKeys) . ')');
                $stmt->execute($params);
            }
            $db->commit();
            $successMessage = 'Saved — ' . count($checkedIds) . ' row(s) on hold, ' . count($uncheckedIds) . ' row(s) released.';
        } catch (Throwable $e) {
            $db->rollBack();
            $fatalError = 'Save failed: ' . $e->getMessage();
        }
    }
}

$rows = [];
if ($displayCols !== []) {
    $fieldList = implode(', ', array_map(fn($c) => "`$c`", $displayCols));
    $holdSelect = in_array('hold', $validCols, true) ? ', `hold`' : '';
    $stmt = get_db()->query("SELECT id, $fieldList$holdSelect FROM maindata ORDER BY id DESC");
    $rows = $stmt->fetchAll();
}

$pageTitle = 'Hold Management';
$pageSubtitle = '';
$activeNav = 'hold_management';

require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <?php if ($fatalError !== ''): ?>
        <div class="alert alert-error"><?= e($fatalError) ?></div>
    <?php endif; ?>
    <?php if ($successMessage !== ''): ?>
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <div class="panel">
        <p class="panel-desc">
            Check a row to put that diamond on hold, uncheck to release it, then click Save. Diamonds on hold
            are excluded from the public Results page. The search box below filters what's currently shown on
            this page — it doesn't change what gets saved.
        </p>

        <div class="hold-mgmt-toolbar">
            <input type="text" id="holdSearchBox" class="ds-range-input" placeholder="Search…" style="max-width:280px;">
            <button type="button" class="btn" id="holdClearSelectionBtn">Clear Selection</button>
            <button type="submit" form="holdManagementForm" class="btn btn-accent">Save</button>
        </div>

        <form method="post" id="holdManagementForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php foreach ($rows as $row): ?>
                <input type="hidden" name="all_ids[]" value="<?= (int)$row['id'] ?>">
            <?php endforeach; ?>

            <div class="table-wrap">
                <table class="data-table" id="holdManagementTable">
                    <thead>
                        <tr>
                            <th class="results-checkbox-col"></th>
                            <?php foreach ($displayCols as $col): ?>
                                <th><?= e($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="<?= count($displayCols) + 1 ?>" class="results-empty">No data found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td class="results-checkbox-col">
                                        <input type="checkbox" name="checked_ids[]" value="<?= (int)$row['id'] ?>"
                                               <?= (($row['hold'] ?? 'no') === 'yes') ? 'checked' : '' ?>>
                                    </td>
                                    <?php foreach ($displayCols as $col): ?>
                                        <td data-label="<?= e($col) ?>"><?= e((string)($row[$col] ?? '')) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <script src="<?= e(asset_url_versioned('/assets/js/hold_management.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
