<?php
/**
 * Website -> Templates -> Add / Edit
 *   no ?key   : create a new template (copied from an existing one)
 *   built-in  : read-only details, with "Duplicate to customise"
 *   your own  : name, description, preview picture and the style of every part
 */
declare(strict_types=1);
require_once __DIR__ . '/../../site/admin_helpers.php';

if (!site_admin_ready()) {
    $pageTitle = 'Website Template';
    $activeNav = 'site_templates';
    require_once __DIR__ . '/../../includes/admin_header.php';
    echo site_admin_missing_notice();
    require_once __DIR__ . '/../../includes/admin_footer.php';
    exit;
}
Site::ensureBuiltins();

$key = (string) ($_GET['key'] ?? $_POST['key'] ?? '');
$tpl = $key !== '' ? Site::get($key) : null;
if ($key !== '' && !$tpl) {
    theme_flash('error', 'That template no longer exists.');
    theme_redirect('site_templates.php');
}
$errors = [];
$input = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    theme_csrf_check();
    $name = trim((string) ($_POST['name'] ?? ''));
    $desc = trim((string) ($_POST['description'] ?? ''));
    $input = $_POST;
    if ($name === '' || mb_strlen($name) > 80) {
        $errors['name'] = 'Enter a name of up to 80 characters.';
    }
    if (mb_strlen($desc) > 255) {
        $errors['description'] = 'Keep the description under 255 characters.';
    }

    if (!$tpl) { // ---- create
        $from = (string) ($_POST['from'] ?? '');
        if (!Site::isTemplate($from)) {
            $errors['from'] = 'Choose the template to start from.';
        }
        if (!$errors) {
            try {
                $new = Site::duplicate($from, $name, $desc);
                theme_flash('success', 'Created “' . $name . '”. Now choose the style of each part.');
                theme_redirect('site_template_form.php?key=' . urlencode($new));
            } catch (Throwable $e) {
                $errors['form'] = 'The template could not be created: ' . $e->getMessage();
            }
        }
    } elseif (!$tpl['builtin']) { // ---- edit
        $parts = ['header' => $_POST['header'] ?? '', 'hero' => $_POST['hero'] ?? '', 'footer' => $_POST['footer'] ?? '',
                  'sections' => (array) ($_POST['sections'] ?? [])];
        $thumb = trim((string) ($_POST['thumbnail'] ?? ''));
        if (!empty($_POST['thumbnail_clear'])) {
            $thumb = '';
        }
        if ($file = site_uploaded_file('thumbnail_file')) {
            try {
                $thumb = site_store_upload($file);
            } catch (RuntimeException $e) {
                $errors['thumbnail'] = $e->getMessage();
            }
        } elseif ($thumb !== '' && !Site::isValidImage($thumb)) {
            $errors['thumbnail'] = 'Choose an image from the list or upload one.';
        }
        if (!$errors) {
            try {
                Site::saveCustom($key, $name, $desc, $parts, $thumb);
                theme_flash('success', 'Saved “' . $name . '”.');
                theme_redirect('site_template_form.php?key=' . urlencode($key));
            } catch (Throwable $e) {
                $errors['form'] = 'Not saved: ' . $e->getMessage();
            }
        }
    }
}

$isNew = !$tpl;
$editable = $tpl && !$tpl['builtin'];
$parts = $tpl['parts'] ?? Site::familyParts(Site::DEFAULT_TEMPLATE);
if ($editable && $input && $errors) {
    $parts = Site::normaliseParts(['header' => $input['header'] ?? '', 'hero' => $input['hero'] ?? '', 'footer' => $input['footer'] ?? '', 'sections' => (array) ($input['sections'] ?? [])], $tpl['base']);
}
$val = fn (string $f, string $d = '') => e((string) ($input[$f] ?? $d));
$sid = $tpl ? Site::paletteSectionId($key) : null;

$pageTitle = $isNew ? 'Add a website template' : ($editable ? 'Edit template — ' . $tpl['name'] : $tpl['name'] . ' (built-in)');
$pageSubtitle = $isNew ? 'A new template starts as a copy of an existing one: same styles, colours and page layouts. You can then change every part.' : '';
$activeNav = 'site_templates';
$dashActionsHtml = '<a class="btn" href="' . e(site_admin_url('site_templates.php')) . '">&larr; All templates</a>'
    . ($tpl ? '<a class="btn" href="' . e(asset_url('/index.php?preview=' . urlencode($key))) . '" target="_blank" rel="noopener">Preview</a>' : '');

require_once __DIR__ . '/../../includes/admin_header.php';
echo site_admin_css();

/** One radio card group for header / hero / footer */
$partPicker = function (string $part, string $title, string $current) use ($editable): string {
    $h = '<fieldset class="sf-part"><legend>' . e($title) . '</legend><div class="sf-options">';
    foreach (Site::FAMILIES as $fam => $info) {
        $id = 'p-' . $part . '-' . $fam;
        $h .= '<label class="sf-option" for="' . $id . '"><input type="radio" id="' . $id . '" name="' . e($part) . '" value="' . e($fam) . '"'
            . ($current === $fam ? ' checked' : '') . ($editable ? '' : ' disabled') . '>'
            . '<span class="sf-option__name">' . e($info['name']) . '</span><span class="sf-option__desc">' . e($info[$part]) . '</span></label>';
    }
    return $h . '</div></fieldset>';
};
?>
    <?= theme_render_flashes() ?>
    <?php if (isset($errors['form'])): ?><div class="alert alert-error ts-flash"><?= e($errors['form']) ?></div><?php endif; ?>

    <?php if ($isNew): ?>
        <div class="panel">
            <form method="post" class="crud-form" autocomplete="off">
                <?= theme_csrf_field() ?>
                <div class="form-group">
                    <label for="f-name">Template name</label>
                    <input id="f-name" name="name" value="<?= $val('name') ?>" maxlength="80" required<?= isset($errors['name']) ? ' aria-invalid="true"' : '' ?>>
                    <?php if (isset($errors['name'])): ?><p class="hint sf-error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="f-from">Start from</label>
                    <select id="f-from" name="from">
                        <?php foreach (Site::templates() as $k => $t): ?>
                            <option value="<?= e($k) ?>" <?= ($input['from'] ?? 'atelier') === $k ? 'selected' : '' ?>><?= e($t['name']) ?><?= $t['builtin'] ? ' (built-in)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hint">Its styles, colours, fonts and page layouts are copied. Page text and images are shared by all templates.</p>
                </div>
                <div class="form-group full-width">
                    <label for="f-desc">Description (optional)</label>
                    <textarea id="f-desc" name="description" rows="2" maxlength="255"><?= $val('description') ?></textarea>
                </div>
                <div class="form-actions"><button type="submit" class="btn-primary">Create template</button></div>
            </form>
        </div>

    <?php else: ?>
        <?php if (!$editable): ?>
            <div class="panel sf-builtin">
                <p class="panel-desc">Built-in templates keep their original design so you can always go back to it. Their colours and fonts can still be changed in <?= $sid ? '<a href="' . e(site_admin_url('theme_settings.php', ['section' => $sid])) . '">Theme Settings</a>' : 'Theme Settings' ?>.
                    To change which styles it uses, make a copy:</p>
                <form method="post" action="<?= e(site_admin_url('site_templates.php')) ?>"><?= theme_csrf_field() ?><button class="btn btn-accent" name="duplicate" value="<?= e($key) ?>">Duplicate to customise</button></form>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" autocomplete="off">
            <?= theme_csrf_field() ?>
            <input type="hidden" name="key" value="<?= e($key) ?>">

            <div class="panel">
                <h2>Name and preview picture</h2>
                <div class="crud-form">
                    <div class="form-group">
                        <label for="f-name">Template name</label>
                        <input id="f-name" name="name" value="<?= $val('name', $tpl['name']) ?>" maxlength="80"<?= $editable ? '' : ' readonly' ?><?= isset($errors['name']) ? ' aria-invalid="true"' : '' ?>>
                        <?php if (isset($errors['name'])): ?><p class="hint sf-error"><?= e($errors['name']) ?></p><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="f-desc">Description</label>
                        <input id="f-desc" name="description" value="<?= $val('description', $tpl['description']) ?>" maxlength="255"<?= $editable ? '' : ' readonly' ?>>
                    </div>
                    <?php if ($editable):
                        $row = get_db()->prepare('SELECT thumbnail FROM website_templates WHERE template_key = ?');
                        $row->execute([$key]);
                        $ownThumb = (string) $row->fetchColumn(); ?>
                        <div class="form-group full-width sf-thumb">
                            <label>Preview picture (shown on the Templates screen)</label>
                            <div class="sf-thumb__row">
                                <img src="<?= e(asset_url($tpl['thumbnail'])) ?>" alt="" class="sf-thumb__img">
                                <div class="sf-thumb__controls">
                                    <select name="thumbnail" aria-label="Choose an existing image">
                                        <option value="">Same as <?= e(Site::FAMILIES[$tpl['base']]['name']) ?></option>
                                        <?php foreach (site_image_library() as $path => $label): ?>
                                            <option value="<?= e($path) ?>" <?= $ownThumb === $path ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label class="sf-upload">Or upload an image <input type="file" name="thumbnail_file" accept="image/jpeg,image/png,image/webp,image/gif"></label>
                                    <?php if (isset($errors['thumbnail'])): ?><p class="hint sf-error"><?= e($errors['thumbnail']) ?></p><?php endif; ?>
                                    <p class="hint">Tip: take a screenshot of the template's home page using Preview, about 1200 × 800.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <h2>Header, hero and footer</h2>
                <p class="panel-desc">Pick the style of each part. You can mix styles from different templates.</p>
                <?= $partPicker('header', 'Header', $parts['header']) ?>
                <?= $partPicker('hero', 'Hero banner', $parts['hero']) ?>
                <?= $partPicker('footer', 'Footer', $parts['footer']) ?>
            </div>

            <div class="panel">
                <h2>Section styles</h2>
                <p class="panel-desc">How each type of section looks. Which sections appear on each page, and in what order, is set in
                    <a href="<?= e(site_admin_url('site_content.php', ['template' => $key])) ?>">Page layouts</a>.</p>
                <div class="table-wrap">
                    <table class="data-table sf-sections">
                        <thead><tr><th scope="col">Section</th><?php foreach (Site::FAMILIES as $info): ?><th scope="col"><?= e($info['name']) ?></th><?php endforeach; ?></tr></thead>
                        <tbody>
                        <?php foreach (Site::SECTION_TYPES as $type => $label): ?>
                            <tr>
                                <th scope="row"><?= e($label) ?></th>
                                <?php foreach (Site::FAMILIES as $fam => $info): ?>
                                    <td><label class="sf-cell"><input type="radio" name="sections[<?= e($type) ?>]" value="<?= e($fam) ?>" <?= $parts['sections'][$type] === $fam ? 'checked' : '' ?><?= $editable ? '' : ' disabled' ?>><span class="ts-visually-hidden"><?= e($label . ': ' . $info['name']) ?></span></label></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel sf-links">
                <div>
                    <strong>Colours and fonts</strong>
                    <p class="panel-desc">Each template has its own palette<?= $editable ? ' — copied from the template you started from' : '' ?>.</p>
                </div>
                <?php if ($sid): ?><a class="btn" href="<?= e(site_admin_url('theme_settings.php', ['section' => $sid])) ?>">Edit colours &amp; fonts</a><?php endif; ?>
            </div>

            <?php if ($editable): ?>
                <div class="form-actions sf-save"><button type="submit" class="btn-primary">Save template</button>
                    <a class="btn btn-ghost" href="<?= e(site_admin_url('site_templates.php')) ?>">Cancel</a></div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
