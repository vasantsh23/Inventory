<?php
/** Add / edit one theme setting. The value control changes with the property type. */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme_ui.php';

require_module_access('admin');

$id       = (int) ($_GET['id'] ?? 0);
$existing = $id ? ThemeSettings::find($id) : null;
if ($id && !$existing) {
    theme_flash('error', 'That setting no longer exists. It may have been deleted.');
    theme_redirect('theme_settings.php');
}

$data = $existing ?? [
    'section_id'    => (int) ($_GET['section'] ?? 0),
    'setting_key'   => '',
    'label'         => '',
    'property_type' => 'color',
    'setting_value' => '#4f8cff',
    'default_value' => '#4f8cff',
    'description'   => '',
    'sort_order'    => 0,
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    theme_csrf_check();
    $posted = array_intersect_key($_POST, $data);
    $data = array_merge($data, array_map(fn($v) => is_string($v) ? trim($v) : $v, $posted));
    if ((string) ($data['default_value'] ?? '') === '') {
        $data['default_value'] = $data['setting_value'];
    }
    $errors = ThemeSettings::validate($data, $existing ? (int) $existing['id'] : null);
    if (!$errors) {
        if ($existing) {
            ThemeSettings::update($id, $data);
            theme_flash('success', 'Saved “' . $data['label'] . '”.');
        } else {
            ThemeSettings::create($data);
            theme_flash('success', 'Added “' . $data['label'] . '”. Use it in assets/css/style.css as var(--' . $data['setting_key'] . ').');
        }
        theme_redirect(isset($_POST['save_and_new'])
            ? 'theme_setting_form.php?section=' . (int) $data['section_id']
            : 'theme_settings.php?section=' . (int) $data['section_id']);
    }
}

$sections     = ThemeSettings::sections();
$pageTitle    = $existing ? 'Edit “' . $existing['label'] . '”' : 'Add a Theme Setting';
$pageSubtitle = $existing
    ? 'Last changed ' . $existing['updated_at'] . '.'
    : 'A new setting becomes a CSS variable you can use anywhere in assets/css/style.css.';
$activeNav    = 'theme_settings';
$loadAllThemeFonts = true;
$dashActionsHtml = '<a class="btn" href="' . e(asset_url('/modules/admin/theme_settings.php')) . '">&larr; Back to Theme Settings</a>';

require_once __DIR__ . '/../../includes/admin_header.php';

$fieldError = fn(string $f): string => isset($errors[$f]) ? '<p class="ts-field-error" id="err-' . $f . '">' . e($errors[$f]) . '</p>' : '';
$invalid    = fn(string $f): string => isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="err-' . $f . '"' : '';
?>
    <?= theme_render_flashes() ?>
    <?php if ($errors): ?>
        <div class="alert alert-error ts-flash" role="alert">Check the <?= count($errors) === 1 ? 'highlighted field' : count($errors) . ' highlighted fields' ?> and save again.</div>
    <?php endif; ?>

    <div class="panel">
    <form method="post" class="crud-form" novalidate>
        <?= theme_csrf_field() ?>

        <div class="form-group">
            <label for="f-section">Section of the website</label>
            <select id="f-section" name="section_id"<?= $invalid('section_id') ?>>
                <option value="">Choose a section</option>
                <?php foreach ($sections as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= (int) $data['section_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError('section_id') ?>
        </div>

        <div class="form-group">
            <label for="f-label">Label</label>
            <input id="f-label" name="label" value="<?= e((string) $data['label']) ?>" placeholder="e.g. Header background" data-label-source<?= $invalid('label') ?>>
            <?= $fieldError('label') ?>
        </div>

        <div class="form-group">
            <label for="f-key">Key</label>
            <span class="ts-prefix"><span>--</span><input id="f-key" name="setting_key" value="<?= e((string) $data['setting_key']) ?>" placeholder="header-bg"
                spellcheck="false" autocomplete="off" <?= $existing ? '' : 'data-key-target' ?><?= $invalid('setting_key') ?>></span>
            <p class="hint">Used in CSS as <code>var(--<span data-key-echo><?= e((string) $data['setting_key'] ?: 'your-key') ?></span>)</code>. Changing the key of a setting already used in CSS breaks that style.</p>
            <?= $fieldError('setting_key') ?>
        </div>

        <div class="form-group">
            <label for="f-type">Property type</label>
            <select id="f-type" name="property_type" data-type-select<?= $invalid('property_type') ?>>
                <?php foreach (ThemeSettings::TYPES as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= $data['property_type'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError('property_type') ?>
        </div>

        <div class="form-group">
            <label for="f-value">Value</label>
            <div data-control="setting_value"><?= value_control('setting_value', (string) $data['property_type'], (string) $data['setting_value'], 'f-value') ?></div>
            <p class="hint" data-type-hint><?= e(ThemeSettings::hint((string) $data['property_type'])) ?></p>
            <?= $fieldError('setting_value') ?>
        </div>

        <div class="form-group">
            <label for="f-default">Default value</label>
            <div data-control="default_value"><?= value_control('default_value', (string) $data['property_type'], (string) $data['default_value'], 'f-default') ?></div>
            <p class="hint">“Reset” puts the value back to this. Leave empty to use the value above.</p>
            <?= $fieldError('default_value') ?>
        </div>

        <div class="form-group full-width">
            <label for="f-desc">Description <span class="hint">optional</span></label>
            <input id="f-desc" name="description" value="<?= e((string) ($data['description'] ?? '')) ?>" placeholder="Where this appears on the website">
        </div>

        <div class="form-group">
            <label for="f-order">Order in list</label>
            <input id="f-order" name="sort_order" type="number" value="<?= (int) $data['sort_order'] ?>">
        </div>

        <div class="form-group">
            <span class="ts-field-label">Preview</span>
            <div class="ts-big-preview" data-big-preview>
                <?= value_preview((string) $data['property_type'], (string) $data['setting_value'], 'f-value') ?>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-accent" type="submit"><?= $existing ? 'Save changes' : 'Add setting' ?></button>
            <?php if (!$existing): ?><button class="btn" type="submit" name="save_and_new" value="1">Add and create another</button><?php endif; ?>
            <a class="btn btn-ghost" href="<?= e(asset_url('/modules/admin/theme_settings.php')) ?>">Cancel</a>
            <?php if ($existing): ?>
                <button class="btn btn-danger ts-push-right" type="submit" form="delete-form"
                        data-confirm="Delete “<?= e($existing['label']) ?>”? Parts of the website that use --<?= e($existing['setting_key']) ?> will lose this style.">Delete setting</button>
            <?php endif; ?>
        </div>
    </form>
    </div>

    <?php if ($existing): ?>
        <form id="delete-form" method="post" action="<?= e(asset_url('/modules/admin/theme_actions.php')) ?>" hidden><?= theme_csrf_field() ?><input type="hidden" name="delete" value="<?= (int) $existing['id'] ?>"></form>
    <?php endif; ?>

    <?php // Control templates for switching property type without reloading ?>
    <?php foreach (array_keys(ThemeSettings::TYPES) as $t):
        $sample = ['color' => '#4f8cff', 'font_family' => "'Inter', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif", 'font_size' => '16px', 'font_weight' => '400',
                   'font_style' => 'normal', 'text_transform' => 'none', 'size' => '16px', 'number' => '1.5',
                   'color_scheme' => 'dark'][$t]; ?>
        <template data-type-template="<?= e($t) ?>" data-hint="<?= e(ThemeSettings::hint($t)) ?>" data-sample="<?= e($sample) ?>">
            <?= value_control('__NAME__', $t, $sample, '__ID__') ?>
        </template>
        <template data-preview-template="<?= e($t) ?>"><?= value_preview($t, $sample, 'f-value') ?></template>
    <?php endforeach; ?>

    <script src="<?= e(asset_url_versioned('/assets/js/theme_admin.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
