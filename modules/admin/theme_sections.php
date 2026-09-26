<?php
/** Theme Sections — groups of theme settings by the part of the website they style. */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme_ui.php';

require_module_access('admin');

$editId = (int) ($_GET['edit'] ?? 0);
$form   = ['section_key' => '', 'name' => '', 'description' => '', 'sort_order' => 0];
$errors = [];

if ($editId) {
    $st = get_db()->prepare('SELECT * FROM theme_sections WHERE id = ?');
    $st->execute([$editId]);
    $form = $st->fetch() ?: $form;
    if (empty($form['id'])) {
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    theme_csrf_check();

    if (!empty($_POST['delete'])) {
        $sid = (int) $_POST['delete'];
        $st = get_db()->prepare('SELECT COUNT(*) FROM theme_settings WHERE section_id = ?');
        $st->execute([$sid]);
        if ((int) $st->fetchColumn() > 0) {
            theme_flash('error', 'This section still has settings. Move or delete them first, then delete the section.');
        } else {
            get_db()->prepare('DELETE FROM theme_sections WHERE id = ?')->execute([$sid]);
            theme_flash('success', 'Section deleted.');
        }
        theme_redirect('theme_sections.php');
    }

    $form = [
        'section_key' => trim((string) ($_POST['section_key'] ?? '')),
        'name'        => trim((string) ($_POST['name'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
    ];
    $sid = (int) ($_POST['id'] ?? 0);

    if (!preg_match('/^[a-z][a-z0-9_-]{1,39}$/', $form['section_key'])) {
        $errors['section_key'] = 'Use lowercase letters, numbers, hyphens or underscores, e.g. footer.';
    } else {
        $st = get_db()->prepare('SELECT id FROM theme_sections WHERE section_key = ? AND id <> ?');
        $st->execute([$form['section_key'], $sid]);
        if ($st->fetch()) {
            $errors['section_key'] = 'Another section already uses this key.';
        }
    }
    if ($form['name'] === '') {
        $errors['name'] = 'Enter a name.';
    }

    if (!$errors) {
        if ($sid) {
            get_db()->prepare('UPDATE theme_sections SET section_key=?, name=?, description=?, sort_order=? WHERE id=?')
                ->execute([$form['section_key'], $form['name'], $form['description'] ?: null, $form['sort_order'], $sid]);
            theme_flash('success', 'Saved section “' . $form['name'] . '”.');
        } else {
            get_db()->prepare('INSERT INTO theme_sections (section_key, name, description, sort_order) VALUES (?,?,?,?)')
                ->execute([$form['section_key'], $form['name'], $form['description'] ?: null, $form['sort_order']]);
            theme_flash('success', 'Added section “' . $form['name'] . '”.');
        }
        theme_redirect('theme_sections.php');
    }
    $editId = $sid;
}

$sections     = ThemeSettings::sections();
$pageTitle    = 'Theme Sections';
$pageSubtitle = 'Sections group theme settings by the part of the website they style.';
$activeNav    = 'theme_sections';
$dashActionsHtml = '<a class="btn" href="' . e(asset_url('/modules/admin/theme_settings.php')) . '">&larr; Back to Theme Settings</a>';

require_once __DIR__ . '/../../includes/admin_header.php';
$self = asset_url('/modules/admin/theme_sections.php');
?>
    <?= theme_render_flashes() ?>

    <div class="ts-two-col">
        <div class="panel">
            <div class="table-wrap">
            <table class="data-table ts-table">
                <thead><tr><th scope="col">Order</th><th scope="col">Name</th><th scope="col">Key</th><th scope="col">Settings</th><th scope="col"><span class="ts-visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                <?php foreach ($sections as $s): ?>
                    <tr>
                        <td data-label="Order"><?= (int) $s['sort_order'] ?></td>
                        <td data-label="Name"><strong><?= e($s['name']) ?></strong><?php if ($s['description']): ?><br><span class="hint"><?= e($s['description']) ?></span><?php endif; ?></td>
                        <td data-label="Key"><code class="ts-key"><?= e($s['section_key']) ?></code></td>
                        <td data-label="Settings"><a href="<?= e(asset_url('/modules/admin/theme_settings.php?section=' . (int) $s['id'])) ?>"><?= (int) $s['setting_count'] ?></a></td>
                        <td class="ts-actions-cell"><div class="row-actions">
                            <a class="btn btn-sm" href="<?= e($self . '?edit=' . (int) $s['id']) ?>">Edit</a>
                            <form method="post" action="<?= e($self) ?>" class="ts-inline" data-confirm="Delete section “<?= e($s['name']) ?>”?"><?= theme_csrf_field() ?>
                                <button class="btn btn-sm btn-danger" name="delete" value="<?= (int) $s['id'] ?>">Delete</button>
                            </form>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <form method="post" action="<?= e($self) ?>" class="panel ts-stack" novalidate>
            <h2><?= $editId ? 'Edit section' : 'Add section' ?></h2>
            <?= theme_csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editId ?>">
            <div class="form-group">
                <label for="s-name">Name</label>
                <input id="s-name" name="name" value="<?= e((string) $form['name']) ?>" placeholder="e.g. Customer list"<?= isset($errors['name']) ? ' aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['name'])): ?><p class="ts-field-error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="s-key">Key</label>
                <input id="s-key" name="section_key" value="<?= e((string) $form['section_key']) ?>" placeholder="customers" spellcheck="false"<?= isset($errors['section_key']) ? ' aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['section_key'])): ?><p class="ts-field-error"><?= e($errors['section_key']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="s-desc">Description <span class="hint">optional</span></label>
                <input id="s-desc" name="description" value="<?= e((string) ($form['description'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="s-order">Order</label>
                <input id="s-order" type="number" name="sort_order" value="<?= (int) $form['sort_order'] ?>">
            </div>
            <div class="form-actions">
                <button class="btn btn-accent" type="submit"><?= $editId ? 'Save section' : 'Add section' ?></button>
                <?php if ($editId): ?><a class="btn btn-ghost" href="<?= e($self) ?>">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
