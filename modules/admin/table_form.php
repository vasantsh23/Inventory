<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crud_engine.php';

require_module_access('admin');

$table = (string)($_GET['table'] ?? $_POST['table'] ?? '');
crud_assert_table($table);
$meta = get_table_meta($table);

$id = isset($_GET['id']) ? (string)$_GET['id'] : (isset($_POST['id']) ? (string)$_POST['id'] : null);
$isNew = $id === null || $id === '';
$error = '';
$success = '';
$row = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired — please try again.';
    } else {
        $input = crud_extract_form_input($meta, $_POST);
        // Guard: a 'password' column is required when creating a new user-table row.
        if ($isNew && $table === 'user' && trim((string)($input['password'] ?? '')) === '') {
            $error = 'Please set a password for the new user.';
        } else {
            try {
                $savedId = crud_save($table, $isNew ? null : $id, $input);
                header('Location: ' . asset_url('/modules/admin/table_view.php?table=' . urlencode($table) . '&saved=1'));
                exit;
            } catch (Throwable $e) {
                $error = 'Could not save this record: ' . $e->getMessage();
            }
        }
    }
    $row = $input ?? [];
} elseif (!$isNew) {
    $row = crud_get($table, $id) ?? [];
    if ($row === []) {
        http_response_code(404);
        exit('Record not found.');
    }
}

$pageTitle = ($isNew ? 'Add new — ' : 'Edit — ') . CRUD_TABLES[$table];
$pageSubtitle = '';
$activeNav = 'table:' . $table;
$dashActionsHtml = '<a class="btn" href="' . e(asset_url('/modules/admin/table_view.php?table=' . urlencode($table))) . '">&larr; Back to list</a>';

require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-error" style="margin-bottom:18px;"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="panel">
        <form method="post" action="<?= e(asset_url('/modules/admin/table_form.php')) ?>" class="crud-form" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="table" value="<?= e($table) ?>">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?= e($id) ?>">
            <?php endif; ?>

            <?php $renderedDatalists = []; ?>
            <?php foreach ($meta['columns'] as $colName => $col):
                if ($col['type'] === 'hidden' || ($col['auto'] && $isNew)) {
                    continue;
                }
                $value = (string)($row[$colName] ?? '');
                $fieldId = crud_wire_name($colName);
            ?>
                <div class="form-group<?= $col['type'] === 'textarea' ? ' full-width' : '' ?>">
                    <label for="<?= e($fieldId) ?>"><?= e($col['label']) ?></label>

                    <?php if ($col['type'] === 'readonly'): ?>
                        <div class="readonly-value"><?= e($value !== '' ? $value : '—') ?></div>

                    <?php elseif ($col['type'] === 'textarea'): ?>
                        <textarea id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>" rows="4"><?= e($value) ?></textarea>

                    <?php elseif ($col['type'] === 'font_select'):
                        // Multiple fields (e.g. all 5 "font type-N" columns) share the
                        // exact same 1600+ option list — embed that list as data once
                        // and have every matching field's combo widget reference it,
                        // instead of repeating it per field.
                        $comboKey = 'combo_' . substr(md5(serialize($col['options'])), 0, 12);
                        $isFirstUse = !isset($renderedDatalists[$comboKey]);
                        $renderedDatalists[$comboKey] = true;
                    ?>
                        <div class="combo-wrap" data-combo-source="<?= e($comboKey) ?>">
                            <input type="text" id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>" value="<?= e($value) ?>"
                                   class="combo-input" autocomplete="off" placeholder="Click to browse, or type to search…">
                            <div class="combo-panel" hidden></div>
                        </div>
                        <?php if ($isFirstUse): ?>
                            <script type="application/json" id="<?= e($comboKey) ?>"><?= json_encode(array_values((array)$col['options']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
                        <?php endif; ?>
                        <p class="hint">Click the box to browse the full list — typing filters it live. Custom names are allowed too.</p>

                    <?php elseif ($col['type'] === 'maindata_column_select'): ?>
                        <select id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>">
                            <option value="">—</option>
                            <?php foreach (get_maindata_columns() as $mdCol): ?>
                                <option value="<?= e($mdCol) ?>" <?= $value === $mdCol ? 'selected' : '' ?>><?= e($mdCol) ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($col['type'] === 'select'): ?>
                        <select id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>">
                            <option value="">—</option>
                            <?php foreach ((array)$col['options'] as $optKey => $opt):
                                // Associative options (e.g. ['A' => 'Ascending']) use the key as
                                // the stored value and the array value as the display label;
                                // plain indexed lists (e.g. ['yes', 'no']) keep the original
                                // behaviour of using the same string for both.
                                $optValue = is_int($optKey) ? $opt : (string)$optKey;
                                $optLabel = $opt;
                                if ($table === 'font_and_color' && str_starts_with($colName, 'selected_')
                                    && array_key_exists($optValue, $row) && trim((string)$row[$optValue]) !== '') {
                                    $optLabel = $optValue . ' — ' . $row[$optValue];
                                }
                            ?>
                                <option value="<?= e($optValue) ?>" <?= $value === $optValue ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($col['type'] === 'lookup'): ?>
                        <select id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>">
                            <option value="">—</option>
                            <?php foreach ((array)$col['options'] as $optId => $optLabel): ?>
                                <option value="<?= e((string)$optId) ?>" <?= (string)$value === (string)$optId ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($col['type'] === 'color'): ?>
                        <div class="color-field-wrap">
                            <input type="color" class="color-swatch" tabindex="-1" aria-hidden="true"
                                   data-target="<?= e($fieldId) ?>"
                                   value="<?= e(preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : '#000000') ?>">
                            <input type="text" id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>" value="<?= e($value) ?>"
                                   placeholder="#rrggbb" pattern="^#[0-9a-fA-F]{6}$" maxlength="7" class="color-hex-input">
                        </div>
                        <p class="hint">Leave blank for no color set. Click the swatch to pick, or type a hex code directly.</p>

                    <?php elseif ($col['type'] === 'email'): ?>
                        <input type="email" id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>" value="<?= e($value) ?>">

                    <?php elseif ($col['type'] === 'password'): ?>
                        <input type="password" id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>" value="" autocomplete="new-password">
                        <p class="hint"><?= $isNew ? 'Required for a new account.' : 'Leave blank to keep the current password.' ?></p>

                    <?php elseif ($col['type'] === 'secret'): ?>
                        <input type="text" id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>" value="" placeholder="••••••••">
                        <p class="hint">Leave blank to keep the current value.</p>

                    <?php else: ?>
                        <input type="text" id="<?= e($fieldId) ?>" name="<?= e($fieldId) ?>" value="<?= e($value) ?>">
                    <?php endif; ?>

                    <?php if (!empty($col['hint']) && !in_array($col['type'], ['password', 'secret'], true)): ?>
                        <p class="hint"><?= e($col['hint']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Record</button>
                <a class="btn btn-ghost" href="<?= e(asset_url('/modules/admin/table_view.php?table=' . urlencode($table))) ?>">Cancel</a>
            </div>
        </form>
    </div>

    <script src="<?= e(asset_url_versioned('/assets/js/color_picker_sync.js')) ?>"></script>
    <?php if (!empty($renderedDatalists)): ?>
        <script src="<?= e(asset_url_versioned('/assets/js/font_combo.js')) ?>"></script>
    <?php endif; ?>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
