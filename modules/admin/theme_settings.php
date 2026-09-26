<?php
/**
 * Theme Settings — every colour, font, size and spacing used on the
 * public site AND this admin dashboard, grouped by section. Values can be
 * edited in place (quick edit) and saved together.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme_ui.php';

require_module_access('admin');

// Quick-edit: save changed values from the list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['values'])) {
    theme_csrf_check();
    [$saved, $errors] = ThemeSettings::updateValues(array_map('strval', (array) $_POST['values']));
    if ($saved) {
        theme_flash('success', $saved === 1 ? 'Saved 1 setting.' : "Saved $saved settings.");
    }
    foreach ($errors as $err) {
        theme_flash('error', 'Not saved — ' . $err);
    }
    if (!$saved && !$errors) {
        theme_flash('info', 'No values were changed.');
    }
    theme_redirect('theme_settings.php?' . http_build_query(array_filter([
        'section' => $_POST['section'] ?? '', 'type' => $_POST['type'] ?? '', 'q' => $_POST['q'] ?? '',
    ])));
}

$sectionId = (int) ($_GET['section'] ?? 0) ?: null;
$type      = (string) ($_GET['type'] ?? '');
$q         = trim((string) ($_GET['q'] ?? ''));

$sections = ThemeSettings::sections();
$rows     = ThemeSettings::all($sectionId, $q, $type);
$total    = array_sum(array_map('intval', array_column($sections, 'setting_count')));

$grouped = [];
foreach ($rows as $r) {
    $grouped[$r['section_id']]['name'] = $r['section_name'];
    $grouped[$r['section_id']]['rows'][] = $r;
}

$pageTitle    = 'Theme Settings';
$pageSubtitle = $total . ' settings control every colour, font and size on the website and this dashboard. Changes go live as soon as you save.';
$activeNav    = 'theme_settings';
$loadAllThemeFonts = true; // so the font dropdown previews render
$dashActionsHtml =
    '<a class="btn" href="' . e(asset_url('/modules/admin/theme_sections.php')) . '">Sections</a>'
    . '<a class="btn btn-accent" href="' . e(asset_url('/modules/admin/theme_setting_form.php' . ($sectionId ? '?section=' . $sectionId : ''))) . '">+ Add Setting</a>';

require_once __DIR__ . '/../../includes/admin_header.php';
$self = asset_url('/modules/admin/theme_settings.php');
?>
    <?= theme_render_flashes() ?>

    <nav class="ts-chips" aria-label="Filter by section">
        <a href="<?= e($self) ?>" class="ts-chip<?= !$sectionId ? ' is-active' : '' ?>">All sections <span><?= $total ?></span></a>
        <?php foreach ($sections as $s): ?>
            <a href="<?= e($self . '?section=' . (int) $s['id']) ?>" class="ts-chip<?= $sectionId === (int) $s['id'] ? ' is-active' : '' ?>">
                <?= e($s['name']) ?> <span><?= (int) $s['setting_count'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <form class="ts-filters" method="get" action="<?= e($self) ?>">
        <?php if ($sectionId): ?><input type="hidden" name="section" value="<?= $sectionId ?>"><?php endif; ?>
        <label class="ts-visually-hidden" for="f-q">Search</label>
        <input id="f-q" class="search-input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search by label, key or value">
        <label class="ts-visually-hidden" for="f-type">Property type</label>
        <select id="f-type" class="search-input" name="type">
            <option value="">All property types</option>
            <?php foreach (ThemeSettings::TYPES as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Filter</button>
        <?php if ($q !== '' || $type !== ''): ?>
            <a class="btn btn-ghost" href="<?= e($self . ($sectionId ? '?section=' . $sectionId : '')) ?>">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!$rows): ?>
        <div class="panel ts-empty">
            <p><strong>No settings match these filters.</strong></p>
            <p class="panel-desc">Clear the search, or add a new setting for this part of the website.</p>
            <a class="btn btn-accent" href="<?= e(asset_url('/modules/admin/theme_setting_form.php' . ($sectionId ? '?section=' . $sectionId : ''))) ?>">+ Add Setting</a>
        </div>
    <?php else: ?>

    <form method="post" id="quick-edit" action="<?= e($self) ?>">
        <?= theme_csrf_field() ?>
        <input type="hidden" name="section" value="<?= e((string) $sectionId) ?>">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="hidden" name="q" value="<?= e($q) ?>">

        <?php foreach ($grouped as $sid => $g): ?>
            <section class="panel ts-group">
                <header class="ts-group-head">
                    <h2><?= e($g['name']) ?></h2>
                    <button type="submit" form="reset-section-<?= (int) $sid ?>" class="btn btn-sm btn-ghost"
                            data-confirm="Reset every <?= e($g['name']) ?> setting to its default value?">Reset section to defaults</button>
                </header>
                <div class="table-wrap">
                <table class="data-table ts-table">
                    <thead>
                        <tr><th scope="col">Setting</th><th scope="col">Value</th><th scope="col">Preview</th><th scope="col"><span class="ts-visually-hidden">Actions</span></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($g['rows'] as $r):
                        $fid = 'v' . $r['id'];
                        $changed = $r['setting_value'] !== $r['default_value']; ?>
                        <tr>
                            <td data-label="Setting">
                                <label for="<?= $fid ?>" class="ts-label"><?= e($r['label']) ?></label>
                                <code class="ts-key">--<?= e($r['setting_key']) ?></code>
                                <span class="pill pill-neutral"><?= e(ThemeSettings::TYPES[$r['property_type']] ?? $r['property_type']) ?></span>
                                <?php if ($changed): ?><span class="pill pill-warning" title="Default: <?= e($r['default_value']) ?>">Changed</span><?php endif; ?>
                                <?php if (!empty($r['description'])): ?><span class="hint ts-desc"><?= e($r['description']) ?></span><?php endif; ?>
                            </td>
                            <td data-label="Value"><?= value_control('values[' . $r['id'] . ']', $r['property_type'], (string) $r['setting_value'], $fid) ?></td>
                            <td data-label="Preview"><?= value_preview($r['property_type'], (string) $r['setting_value'], $fid) ?></td>
                            <td class="ts-actions-cell"><div class="row-actions">
                                <a class="btn btn-sm" href="<?= e(asset_url('/modules/admin/theme_setting_form.php?id=' . (int) $r['id'])) ?>">Edit</a>
                                <?php if ($changed): ?>
                                    <button type="submit" form="action-form" name="reset" value="<?= (int) $r['id'] ?>" class="btn btn-sm">Reset</button>
                                <?php endif; ?>
                                <button type="submit" form="action-form" name="delete" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-danger"
                                        data-confirm="Delete “<?= e($r['label']) ?>”? Parts of the website that use --<?= e($r['setting_key']) ?> will lose this style.">Delete</button>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="ts-savebar" data-savebar hidden>
            <span><strong data-dirty-count>0</strong> unsaved changes</span>
            <a class="btn btn-ghost" href="">Discard</a>
            <button class="btn btn-accent" type="submit">Save changes</button>
        </div>
    </form>

    <?php foreach (array_keys($grouped) as $sid): ?>
        <form id="reset-section-<?= (int) $sid ?>" method="post" action="<?= e(asset_url('/modules/admin/theme_actions.php')) ?>" hidden>
            <?= theme_csrf_field() ?><input type="hidden" name="reset_section" value="<?= (int) $sid ?>">
        </form>
    <?php endforeach; ?>
    <form id="action-form" method="post" action="<?= e(asset_url('/modules/admin/theme_actions.php')) ?>" hidden><?= theme_csrf_field() ?></form>

    <form class="panel ts-danger-zone" method="post" action="<?= e(asset_url('/modules/admin/theme_actions.php')) ?>">
        <?= theme_csrf_field() ?>
        <div>
            <strong>Reset the whole theme</strong>
            <p class="panel-desc">Puts every setting back to its default value. Settings you added stay, with their own defaults.</p>
        </div>
        <button class="btn btn-danger" name="reset_all" value="1" data-confirm="Reset ALL theme settings to their default values?">Reset all to defaults</button>
    </form>

    <?php endif; ?>

    <script src="<?= e(asset_url_versioned('/assets/js/theme_admin.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
