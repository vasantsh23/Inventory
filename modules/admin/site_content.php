<?php
/**
 * Website -> Page Content
 *   - every text, image and link on each page (shared by all templates)
 *   - which sections the chosen template shows on the page, and their order
 */
declare(strict_types=1);
require_once __DIR__ . '/../../site/admin_helpers.php';

$ready = site_admin_ready();
$pages = $ready ? Site::defaults() : [];
$pageKey = (string) ($_GET['page'] ?? $_POST['page'] ?? 'home');
if (!isset($pages[$pageKey])) {
    $pageKey = 'home';
}
if ($ready) {
    Site::ensureBuiltins();
    Site::syncContent();
}
$tplKey = (string) ($_GET['template'] ?? $_POST['template'] ?? '');
if (!$ready || !Site::isTemplate($tplKey)) {
    $tplKey = $ready ? Site::liveTemplate() : 'atelier';
}
$self = fn (array $q = []) => site_admin_url('site_content.php', $q + ['page' => $pageKey, 'template' => $tplKey]);

const SC_TEXT_MAX = 500;
const SC_AREA_MAX = 10000;

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    theme_csrf_check();
    $def = $pages[$pageKey];
    $db = get_db();

    if (isset($_POST['save_layout']) && $pageKey !== 'global') {
        $st = $db->prepare('INSERT INTO site_blocks (template_key, page_key, block_key, is_enabled, sort_order) VALUES (?,?,?,?,?)
                            ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled), sort_order = VALUES(sort_order)');
        foreach (array_keys($def['blocks']) as $blockKey) {
            $row = (array) ($_POST['layout'][$blockKey] ?? []);
            $st->execute([$tplKey, $pageKey, $blockKey, !empty($row['enabled']) ? 1 : 0, max(0, min(9999, (int) ($row['sort'] ?? 0)))]);
        }
        theme_flash('success', 'Saved the sections shown on ' . $def['label'] . ' for “' . Site::get($tplKey)['name'] . '”.');
        theme_redirect('site_content.php?' . http_build_query(['page' => $pageKey, 'template' => $tplKey]) . '#layout');
    }

    if (isset($_POST['reset_layout']) && $pageKey !== 'global') {
        $db->prepare('DELETE FROM site_blocks WHERE template_key = ? AND page_key = ?')->execute([$tplKey, $pageKey]);
        theme_flash('success', 'Sections on ' . $def['label'] . ' are back to the template’s default arrangement.');
        theme_redirect('site_content.php?' . http_build_query(['page' => $pageKey, 'template' => $tplKey]) . '#layout');
    }

    if (($reset = (string) ($_POST['reset_block'] ?? '')) !== '' && isset($def['blocks'][$reset])) {
        $db->prepare('UPDATE site_content SET content_value = NULL WHERE page_key = ? AND block_key = ?')->execute([$pageKey, $reset]);
        theme_flash('success', '“' . $def['blocks'][$reset]['label'] . '” is back to its default text and images.');
        theme_redirect('site_content.php?' . http_build_query(['page' => $pageKey, 'template' => $tplKey]) . '#blk-' . $reset);
    }

    if (isset($_POST['save_content'])) {
        $upd = $db->prepare('UPDATE site_content SET content_value = ? WHERE page_key = ? AND block_key = ? AND field_key = ?');
        $saved = 0;
        $errors = [];
        foreach ($def['blocks'] as $blockKey => $block) {
            foreach ($block['fields'] as $fieldKey => $f) {
                if (!isset($_POST['c'][$blockKey]) || !array_key_exists($fieldKey, (array) $_POST['c'][$blockKey])) {
                    continue;
                }
                $v = str_replace("\r\n", "\n", (string) $_POST['c'][$blockKey][$fieldKey]);
                $v = $f['type'] === 'textarea' ? trim($v) : trim(preg_replace('/\s+/', ' ', $v));
                $where = $block['label'] . ' → ' . $f['label'];

                if ($f['type'] === 'image' && ($file = site_uploaded_file('up', $blockKey, $fieldKey))) {
                    try {
                        $v = site_store_upload($file);
                    } catch (RuntimeException $e) {
                        $errors[] = $where . ': ' . $e->getMessage();
                        continue;
                    }
                }
                $ok = match ($f['type']) {
                    'image'    => Site::isValidImage($v),
                    'url'      => Site::isValidUrl($v),
                    'select'   => isset($f['options'][$v]),
                    'textarea' => mb_strlen($v) <= SC_AREA_MAX,
                    default    => mb_strlen($v) <= SC_TEXT_MAX,
                };
                if (!$ok) {
                    $errors[] = $where . ': ' . match ($f['type']) {
                        'image'  => 'choose an image from the list or upload one.',
                        'url'    => 'use a page (page:about), inventory, a path starting with /, or a full https:// link.',
                        'select' => 'choose one of the options.',
                        default  => 'the text is too long.',
                    };
                    continue;
                }
                if ($v !== Site::raw($pageKey, $blockKey, $fieldKey)) {
                    $upd->execute([$v, $pageKey, $blockKey, $fieldKey]);
                    $saved++;
                }
            }
        }
        if ($saved) {
            theme_flash('success', $saved === 1 ? 'Saved 1 change.' : "Saved $saved changes.");
        }
        foreach ($errors as $err) {
            theme_flash('error', 'Not saved — ' . $err);
        }
        if (!$saved && !$errors) {
            theme_flash('info', 'Nothing was changed.');
        }
        theme_redirect('site_content.php?' . http_build_query(['page' => $pageKey, 'template' => $tplKey]));
    }
    theme_redirect('site_content.php?' . http_build_query(['page' => $pageKey, 'template' => $tplKey]));
}

$def = $pages[$pageKey] ?? null;
$layout = ($ready && $pageKey !== 'global') ? Site::layout($tplKey, $pageKey, true) : [];
$shown = [];
foreach ($layout as $r) {
    $shown[$r['key']] = $r['enabled'];
}
$library = $ready ? site_image_library() : [];
$tplName = $ready ? Site::get($tplKey)['name'] : '';
$linkHints = ['inventory' => 'Inventory'] + array_map(fn ($p) => $p['label'], array_filter($pages, fn ($k) => $k !== 'global', ARRAY_FILTER_USE_KEY));

$pageTitle = 'Page Content';
$pageSubtitle = 'Text, images and links for every page of the website. They are shared by all templates. Use {company}, {phone}, {email}, {address} or {year} to insert details from Site Setup.';
$activeNav = 'site_content';
$dashActionsHtml = ($def && !empty($def['file']))
    ? '<a class="btn" href="' . e(asset_url('/' . $def['file']) . ($tplKey !== Site::liveTemplate() ? '?preview=' . urlencode($tplKey) : '')) . '" target="_blank" rel="noopener">View this page</a>' : '';

require_once __DIR__ . '/../../includes/admin_header.php';
echo site_admin_css();
?>
    <?= theme_render_flashes() ?>
    <?php if (!$ready): ?>
        <?= site_admin_missing_notice() ?>
    <?php else: ?>

    <nav class="ts-chips" aria-label="Pages">
        <?php foreach ($pages as $k => $p): ?>
            <a class="ts-chip<?= $k === $pageKey ? ' is-active' : '' ?>" href="<?= e(site_admin_url('site_content.php', ['page' => $k, 'template' => $tplKey])) ?>"<?= $k === $pageKey ? ' aria-current="page"' : '' ?>><?= e($p['label']) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($pageKey !== 'global'): ?>
        <section class="panel" id="layout">
            <header class="sc-head">
                <div>
                    <h2>Sections on this page</h2>
                    <p class="panel-desc">Which sections <strong><?= e($tplName) ?></strong> shows on <?= e($def['label']) ?>, and in what order (lower numbers first). Each template keeps its own arrangement.</p>
                </div>
                <form method="get" class="sc-tpl">
                    <input type="hidden" name="page" value="<?= e($pageKey) ?>">
                    <label for="sc-tpl">Template</label>
                    <select id="sc-tpl" name="template" data-autosubmit>
                        <?php foreach (Site::templates() as $k => $t): ?>
                            <option value="<?= e($k) ?>" <?= $k === $tplKey ? 'selected' : '' ?>><?= e($t['name']) ?><?= $t['active'] ? ' (live)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button class="btn btn-sm" type="submit">Show</button></noscript>
                </form>
            </header>
            <form method="post" action="<?= e($self()) ?>">
                <?= theme_csrf_field() ?>
                <input type="hidden" name="page" value="<?= e($pageKey) ?>"><input type="hidden" name="template" value="<?= e($tplKey) ?>">
                <div class="table-wrap">
                    <table class="data-table sc-layout">
                        <thead><tr><th scope="col">Show</th><th scope="col">Order</th><th scope="col">Section</th><th scope="col">Style</th></tr></thead>
                        <tbody>
                        <?php foreach ($layout as $r):
                            $b = $def['blocks'][$r['key']];
                            $fam = Site::family($tplKey, $b['type']); ?>
                            <tr class="<?= $r['enabled'] ? '' : 'is-off' ?>">
                                <td><input type="checkbox" name="layout[<?= e($r['key']) ?>][enabled]" value="1" <?= $r['enabled'] ? 'checked' : '' ?> aria-label="Show <?= e($b['label']) ?>"></td>
                                <td><input class="sc-order" type="number" min="0" max="9999" step="1" name="layout[<?= e($r['key']) ?>][sort]" value="<?= (int) $r['sort'] ?>" aria-label="Order of <?= e($b['label']) ?>"></td>
                                <td><a href="#blk-<?= e($r['key']) ?>"><?= e($b['label']) ?></a></td>
                                <td><?= e(Site::FAMILIES[$fam]['name']) ?> <span class="hint"><?= e(Site::SECTION_TYPES[$b['type']] ?? 'Banner') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-actions">
                    <button class="btn btn-accent" type="submit" name="save_layout" value="1">Save sections</button>
                    <button class="btn btn-ghost" type="submit" name="reset_layout" value="1" data-confirm="Go back to the default sections for <?= e($tplName) ?> on this page?">Use template default</button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="<?= e($self()) ?>" id="content-form" autocomplete="off">
        <?= theme_csrf_field() ?>
        <input type="hidden" name="page" value="<?= e($pageKey) ?>"><input type="hidden" name="template" value="<?= e($tplKey) ?>">
        <input type="hidden" name="save_content" value="1">

        <?php foreach ($def['blocks'] as $blockKey => $block):
            $off = $pageKey !== 'global' && empty($shown[$blockKey]);
            $items = [];
            $plain = [];
            foreach ($block['fields'] as $fk => $f) {
                if (preg_match('/^item(\d+)_/', $fk, $m)) {
                    $items[(int) $m[1]][$fk] = $f;
                } else {
                    $plain[$fk] = $f;
                }
            } ?>
            <details class="panel sc-block" id="blk-<?= e($blockKey) ?>" <?= $off ? '' : 'open' ?>>
                <summary>
                    <span class="sc-block__title"><?= e($block['label']) ?></span>
                    <?php if ($off): ?><span class="pill pill-neutral">Not shown in <?= e($tplName) ?></span><?php endif; ?>
                </summary>
                <div class="sc-fields">
                    <?php
                    $render = function (string $fk, array $f) use ($blockKey, $pageKey, $library, $linkHints): void {
                        $id = 'f-' . $blockKey . '-' . $fk;
                        $name = 'c[' . $blockKey . '][' . $fk . ']';
                        $v = Site::raw($pageKey, $blockKey, $fk);
                        $changed = $v !== (string) $f['default'];
                        $label = preg_replace('/^Item \d+ — /', '', $f['label']);
                        echo '<div class="form-group sc-field sc-field--' . e($f['type']) . '">';
                        echo '<label for="' . e($id) . '">' . e($label) . ($changed ? ' <span class="pill pill-warning" title="Default: ' . e(mb_substr((string) $f['default'], 0, 120)) . '">Changed</span>' : '') . '</label>';
                        switch ($f['type']) {
                            case 'textarea':
                                echo '<textarea id="' . e($id) . '" name="' . e($name) . '" rows="' . (mb_strlen($v) > 220 ? 6 : 3) . '" maxlength="' . SC_AREA_MAX . '">' . e($v) . '</textarea>';
                                echo '<p class="hint">Leave an empty line between paragraphs.</p>';
                                break;
                            case 'select':
                                echo '<select id="' . e($id) . '" name="' . e($name) . '">';
                                foreach ((array) $f['options'] as $ok => $ol) {
                                    echo '<option value="' . e((string) $ok) . '"' . ((string) $ok === $v ? ' selected' : '') . '>' . e($ol) . '</option>';
                                }
                                echo '</select>';
                                break;
                            case 'url':
                                echo '<input id="' . e($id) . '" name="' . e($name) . '" value="' . e($v) . '" list="sc-links" maxlength="' . SC_TEXT_MAX . '" placeholder="page:contact">';
                                echo '<p class="hint">A page (page:about), inventory, a path like /login.php, or a full https:// link.</p>';
                                break;
                            case 'image':
                                $opts = ['' => 'No image'] + $library;
                                if ($v !== '' && !isset($opts[$v])) {
                                    $opts = [$v => 'Current — ' . basename($v)] + $opts;
                                }
                                echo '<div class="sc-image">';
                                echo $v !== '' && Site::imageUrl($v) !== '' ? '<img src="' . e(Site::imageUrl($v)) . '" alt="" class="sc-image__pv">' : '<span class="sc-image__pv sc-image__pv--none">No image</span>';
                                echo '<div class="sc-image__ctl"><select id="' . e($id) . '" name="' . e($name) . '" data-root="' . e(BASE_URL) . '">';
                                foreach ($opts as $path => $l) {
                                    echo '<option value="' . e((string) $path) . '"' . ((string) $path === $v ? ' selected' : '') . '>' . e($l) . '</option>';
                                }
                                echo '</select><label class="sf-upload">Upload a new image <input type="file" name="up[' . e($blockKey) . '][' . e($fk) . ']" accept="image/jpeg,image/png,image/webp,image/gif"></label></div></div>';
                                break;
                            default:
                                echo '<input id="' . e($id) . '" name="' . e($name) . '" value="' . e($v) . '" maxlength="' . SC_TEXT_MAX . '">';
                        }
                        echo '</div>';
                    };
                    foreach ($plain as $fk => $f) {
                        $render($fk, $f);
                    }
                    ?>
                </div>
                <?php if ($items): ?>
                    <div class="sc-items">
                        <?php foreach ($items as $n => $fields): ?>
                            <fieldset class="sc-item">
                                <legend>Item <?= $n ?></legend>
                                <div class="sc-fields"><?php foreach ($fields as $fk => $f) { $render($fk, $f); } ?></div>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>
                    <p class="hint">An item is hidden on the website when its first field is empty.</p>
                <?php endif; ?>
                <div class="sc-block__foot">
                    <button type="submit" class="btn btn-sm btn-ghost" name="reset_block" value="<?= e($blockKey) ?>" formnovalidate data-confirm="Put every text and image in “<?= e($block['label']) ?>” back to its default?">Reset section to defaults</button>
                </div>
            </details>
        <?php endforeach; ?>

        <datalist id="sc-links">
            <?php foreach ($linkHints as $k => $l): ?><option value="<?= e($k === 'inventory' ? 'inventory' : 'page:' . $k) ?>"><?= e($l) ?></option><?php endforeach; ?>
        </datalist>

        <div class="sc-savebar">
            <span>Changes to <?= e($def['label']) ?> are saved together.</span>
            <button class="btn btn-accent" type="submit">Save <?= e($def['label']) ?> content</button>
        </div>
    </form>
    <?php endif; ?>
    <script src="<?= e(asset_url_versioned('/assets/site/js/admin-site.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
