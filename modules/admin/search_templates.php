<?php
/**
 * Admin → Search Templates
 * Templates for the Diamond Search, Results and View Cart / View
 * (Selected) pages (see includes/search_templates.php):
 *   - list every template, with which one is live
 *   - preview a template yourself, make one live
 *   - create a new template from an existing one, edit or delete your own
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme_ui.php';
require_once __DIR__ . '/../../includes/search_templates.php';

require_module_access('admin');

$ready = search_templates_ready();
$self = 'search_templates.php';

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    theme_csrf_check();
    $action = (string)($_POST['action'] ?? '');
    $key = (string)($_POST['key'] ?? '');
    try {
        if ($action === 'activate' && search_template_activate($key)) {
            unset($_SESSION['search_tpl_preview']);
            theme_flash('success', '“' . search_template_get($key)['name'] . '” is now live on Diamond Search, Results and View Cart.');
        } elseif ($action === 'create') {
            $new = search_template_create($key, (string)($_POST['name'] ?? ''), (string)($_POST['description'] ?? ''));
            theme_flash('success', 'Template created. Adjust its colours below, preview it, then make it live.');
            theme_redirect($self . '?edit=' . urlencode($new));
        } elseif ($action === 'save') {
            search_template_update($key, (string)($_POST['name'] ?? ''), (string)($_POST['description'] ?? ''), (array)($_POST['settings'] ?? []));
            theme_flash('success', 'Template saved.');
            theme_redirect($self . '?edit=' . urlencode($key));
        } elseif ($action === 'delete') {
            $name = search_template_get($key)['name'] ?? $key;
            search_template_delete($key);
            theme_flash('success', 'Deleted the template “' . $name . '”.');
        }
    } catch (InvalidArgumentException $e) {
        theme_flash('error', $e->getMessage());
        if ($action === 'save') {
            theme_redirect($self . '?edit=' . urlencode($key));
        }
        if ($action === 'create') {
            theme_redirect($self . '?new=' . urlencode($key));
        }
    } catch (Throwable $e) {
        error_log('search_templates: ' . $e->getMessage());
        theme_flash('error', 'That did not work: ' . $e->getMessage());
    }
    theme_redirect($self);
}

$templates = $ready ? search_templates_all() : [];
$layouts = search_template_layouts();
$editKey = (string)($_GET['edit'] ?? '');
$editing = $editKey !== '' ? ($templates[$editKey] ?? null) : null;
$newFrom = (string)($_GET['new'] ?? '');
$creatingFrom = $newFrom !== '' ? ($templates[$newFrom] ?? null) : null;
$searchUrl = asset_url('/modules/user/diamond_search.php');

$pageTitle = 'Search Templates';
$pageSubtitle = 'Choose how Diamond Search, Results and View Cart look. Preview any template yourself first — users keep seeing the live one until you switch.';
$activeNav = 'search_templates';

require_once __DIR__ . '/../../includes/admin_header.php';
?>
<style>
    .stp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
    .stp-card { display: flex; flex-direction: column; gap: 10px; }
    .stp-card.is-live { outline: 2px solid var(--accent, #2468d8); }
    .stp-card h2 { margin: 0; font-size: 18px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .stp-badge { font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; padding: 2px 8px; border-radius: 10px; background: #e6e9ef; color: #333; }
    .stp-badge--live { background: #1e8e3e; color: #fff; }
    .stp-swatches { display: flex; gap: 6px; flex-wrap: wrap; }
    .stp-swatch { width: 26px; height: 26px; border-radius: 4px; border: 1px solid rgba(0,0,0,.2); }
    .stp-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: auto; }
    .stp-actions form { margin: 0; }
    .stp-form { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px 20px; }
    .stp-form label { display: flex; flex-direction: column; gap: 4px; font-weight: 600; }
    .stp-color { display: flex; gap: 8px; align-items: center; }
    .stp-color input[type=color] { width: 44px; height: 32px; padding: 0; border: 1px solid #bbb; border-radius: 4px; }
    .stp-note { color: #555; font-size: 14px; margin: 0; }
</style>

<?= theme_render_flashes() ?>

<?php if (!$ready): ?>
    <div class="panel">
        <p class="panel-desc">The Search Templates table has not been created yet. Run <code>sql/migration_search_templates.sql</code> in phpMyAdmin (SQL tab), then reload this page. Until then the pages use the Solitaire Station design.</p>
    </div>
<?php else: ?>

<?php if ($creatingFrom !== null): ?>
    <section class="panel">
        <h2 class="panel-title">New template from “<?= e($creatingFrom['name']) ?>”</h2>
        <p class="stp-note">The new template starts as a copy of “<?= e($creatingFrom['name']) ?>” (<?= e($layouts[$creatingFrom['layout']]['name']) ?> layout). You can change its colours on the next screen.</p>
        <form method="post" action="<?= e($self) ?>" class="stp-form" style="margin-top:12px;">
            <?= theme_csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="key" value="<?= e($creatingFrom['key']) ?>">
            <label>Name
                <input type="text" name="name" required maxlength="80" value="<?= e('Copy of ' . $creatingFrom['name']) ?>">
            </label>
            <label>Description
                <input type="text" name="description" maxlength="255" value="<?= e($creatingFrom['description']) ?>">
            </label>
            <div class="stp-actions" style="grid-column:1/-1;">
                <button type="submit" class="btn btn-accent">Create template</button>
                <a class="btn" href="<?= e($self) ?>">Cancel</a>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php if ($editing !== null): ?>
    <section class="panel">
        <h2 class="panel-title"><?= $editing['is_builtin'] ? 'View' : 'Edit' ?> “<?= e($editing['name']) ?>”</h2>
        <?php if ($editing['is_builtin']): ?>
            <p class="stp-note">Built-in templates can’t be changed, so the original designs are always kept. Use “New template from this” to make your own version.</p>
        <?php else: ?>
            <form method="post" action="<?= e($self) ?>" class="stp-form" style="margin-top:12px;">
                <?= theme_csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="key" value="<?= e($editing['key']) ?>">
                <label>Name
                    <input type="text" name="name" required maxlength="80" value="<?= e($editing['name']) ?>">
                </label>
                <label>Description
                    <input type="text" name="description" maxlength="255" value="<?= e($editing['description']) ?>">
                </label>
                <?php foreach ($layouts[$editing['layout']]['settings'] as $sk => [$slabel, $sdefault]): ?>
                    <?php if ($sk === 'font'): ?>
                        <label><?= e($slabel) ?>
                            <input type="text" name="settings[font]" maxlength="120" value="<?= e($editing['settings']['font']) ?>">
                            <span class="stp-note">e.g. Arial, Helvetica, sans-serif</span>
                        </label>
                    <?php else: ?>
                        <label><?= e($slabel) ?>
                            <span class="stp-color">
                                <input type="color" value="<?= e($editing['settings'][$sk]) ?>" aria-label="<?= e($slabel) ?> colour picker"
                                       oninput="this.nextElementSibling.value=this.value">
                                <input type="text" name="settings[<?= e($sk) ?>]" value="<?= e($editing['settings'][$sk]) ?>" pattern="#[0-9a-fA-F]{6}" maxlength="7" size="9"
                                       oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))this.previousElementSibling.value=this.value">
                            </span>
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($layouts[$editing['layout']]['settings'] === []): ?>
                    <p class="stp-note" style="grid-column:1/-1;">This layout takes its colours and fonts from Appearance → Theme Settings.</p>
                <?php endif; ?>
                <div class="stp-actions" style="grid-column:1/-1;">
                    <button type="submit" class="btn btn-accent">Save</button>
                    <a class="btn" href="<?= e($searchUrl . '?tpl_preview=' . urlencode($editing['key'])) ?>" target="_blank" rel="noopener">Preview</a>
                    <a class="btn" href="<?= e($self) ?>">Back to templates</a>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<div class="stp-grid">
    <?php foreach ($templates as $t): ?>
        <article class="panel stp-card<?= $t['is_active'] ? ' is-live' : '' ?>">
            <h2>
                <?= e($t['name']) ?>
                <?php if ($t['is_active']): ?><span class="stp-badge stp-badge--live">Live</span><?php endif; ?>
                <span class="stp-badge"><?= $t['is_builtin'] ? 'Built-in' : 'Custom' ?></span>
            </h2>
            <p class="stp-note"><strong>Layout:</strong> <?= e($layouts[$t['layout']]['name']) ?></p>
            <?php if ($t['description'] !== ''): ?>
                <p class="stp-note"><?= e($t['description']) ?></p>
            <?php endif; ?>
            <?php $colours = array_filter($t['settings'], fn($v, $k) => $k !== 'font', ARRAY_FILTER_USE_BOTH); ?>
            <?php if ($colours !== []): ?>
                <div class="stp-swatches" aria-label="Colours">
                    <?php foreach ($colours as $ck => $cv): ?>
                        <span class="stp-swatch" style="background:<?= e($cv) ?>" title="<?= e($layouts[$t['layout']]['settings'][$ck][0] . ': ' . $cv) ?>"></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="stp-note">Colours from Theme Settings.</p>
            <?php endif; ?>
            <div class="stp-actions">
                <?php if (!$t['is_active']): ?>
                    <form method="post" action="<?= e($self) ?>">
                        <?= theme_csrf_field() ?>
                        <input type="hidden" name="action" value="activate">
                        <input type="hidden" name="key" value="<?= e($t['key']) ?>">
                        <button type="submit" class="btn btn-accent">Make live</button>
                    </form>
                <?php endif; ?>
                <a class="btn" href="<?= e($searchUrl . '?tpl_preview=' . urlencode($t['key'])) ?>" target="_blank" rel="noopener">Preview</a>
                <a class="btn" href="<?= e($self . '?new=' . urlencode($t['key'])) ?>">New template from this</a>
                <?php if (!$t['is_builtin']): ?>
                    <a class="btn" href="<?= e($self . '?edit=' . urlencode($t['key'])) ?>">Edit</a>
                    <?php if (!$t['is_active']): ?>
                        <form method="post" action="<?= e($self) ?>" data-confirm="Delete the template “<?= e($t['name']) ?>”?">
                            <?= theme_csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="key" value="<?= e($t['key']) ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<p class="stp-note" style="margin-top:16px;">Preview opens Diamond Search with that template for you only; Results and View Cart follow while you browse. A yellow bar at the top lets you exit the preview.</p>
<?php endif; ?>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
