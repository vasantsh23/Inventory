<?php
/**
 * Website -> Templates
 * Shows every website template (3 built-in + any you created), lets you
 * preview one, make it live, duplicate it, edit or delete your own.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../site/admin_helpers.php';

$ready = site_admin_ready();

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    theme_csrf_check();
    try {
        Site::ensureBuiltins();
        if (($key = (string) ($_POST['activate'] ?? '')) !== '') {
            if (Site::activate($key)) {
                unset($_SESSION['site_preview_template']);
                theme_flash('success', '“' . Site::get($key)['name'] . '” is now live on the website.');
            }
            // Coming from the preview bar on the website: go back to that page
            $return = (string) ($_POST['return'] ?? '');
            if ($return !== '' && Site::pageDef($return) && $return !== 'global') {
                header('Location: ' . Site::pageUrl($return));
                exit;
            }
        } elseif (($key = (string) ($_POST['duplicate'] ?? '')) !== '' && ($src = Site::get($key))) {
            $new = Site::duplicate($key, mb_substr('Copy of ' . $src['name'], 0, 80), $src['description']);
            theme_flash('success', 'Created “Copy of ' . $src['name'] . '”. Choose its name and styles below.');
            theme_redirect('site_template_form.php?key=' . urlencode($new));
        } elseif (($key = (string) ($_POST['delete'] ?? '')) !== '') {
            $name = Site::get($key)['name'] ?? $key;
            Site::deleteCustom($key);
            theme_flash('success', 'Deleted the template “' . $name . '”.');
        }
    } catch (InvalidArgumentException $e) {
        theme_flash('error', $e->getMessage());
    } catch (Throwable $e) {
        error_log('site_templates: ' . $e->getMessage());
        theme_flash('error', 'That did not work: ' . $e->getMessage());
    }
    theme_redirect('site_templates.php');
}

if ($ready) {
    Site::ensureBuiltins();
}
$templates = $ready ? Site::templates() : [];
$live = $ready ? Site::liveTemplate() : '';
$previewing = $ready ? Site::previewTemplate() : null;

$pageTitle    = 'Website Templates';
$pageSubtitle = 'Choose how the public website looks. Preview any template first — visitors keep seeing the live one until you switch.';
$activeNav    = 'site_templates';
$dashActionsHtml = $ready
    ? '<a class="btn" href="' . e(asset_url('/index.php')) . '" target="_blank" rel="noopener">View website</a>'
      . '<a class="btn btn-accent" href="' . e(site_admin_url('site_template_form.php')) . '">+ Add template</a>'
    : '';

require_once __DIR__ . '/../../includes/admin_header.php';
echo site_admin_css();
?>
    <?= theme_render_flashes() ?>
    <?php if (!$ready): ?>
        <?= site_admin_missing_notice() ?>
    <?php else: ?>

    <?php if ($previewing): ?>
        <div class="alert alert-warning ts-flash">You are previewing <strong><?= e($templates[$previewing]['name']) ?></strong> on the website. <a href="<?= e(asset_url('/index.php?preview=off')) ?>">Exit preview</a></div>
    <?php endif; ?>

    <div class="st-grid">
        <?php foreach ($templates as $key => $t):
            $isLive = $key === $live;
            $sid = Site::paletteSectionId($key);
            $parts = $t['parts'];
            $mixed = count(Site::familiesUsed($key)) > 1; ?>
            <article class="panel st-card<?= $isLive ? ' is-live' : '' ?>">
                <div class="st-thumb">
                    <?php if (is_file(dirname(__DIR__, 2) . $t['thumbnail'])): ?>
                        <img src="<?= e(asset_url($t['thumbnail'])) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="st-thumb__empty">No preview picture</span>
                    <?php endif; ?>
                    <?php if ($isLive): ?><span class="st-badge st-badge--live">Live</span><?php endif; ?>
                </div>
                <div class="st-body">
                    <h2 class="st-name"><?= e($t['name']) ?></h2>
                    <p class="st-meta"><?= $t['builtin'] ? 'Built-in template' : 'Your template · based on ' . e(Site::FAMILIES[$t['base']]['name']) ?><?= $mixed ? ' · mixed styles' : '' ?></p>
                    <?php if ($t['description'] !== ''): ?><p class="panel-desc"><?= e($t['description']) ?></p><?php endif; ?>
                    <dl class="st-parts">
                        <div><dt>Header</dt><dd><?= e(Site::FAMILIES[$parts['header']]['name']) ?></dd></div>
                        <div><dt>Hero</dt><dd><?= e(Site::FAMILIES[$parts['hero']]['name']) ?></dd></div>
                        <div><dt>Footer</dt><dd><?= e(Site::FAMILIES[$parts['footer']]['name']) ?></dd></div>
                    </dl>
                    <div class="st-actions">
                        <?php if (!$isLive): ?>
                            <form method="post"><?= theme_csrf_field() ?><button class="btn btn-accent btn-sm" name="activate" value="<?= e($key) ?>" data-confirm="Make “<?= e($t['name']) ?>” the live website template?">Make live</button></form>
                        <?php endif; ?>
                        <a class="btn btn-sm" href="<?= e(asset_url('/index.php?preview=' . urlencode($key))) ?>" target="_blank" rel="noopener">Preview</a>
                        <a class="btn btn-sm" href="<?= e(site_admin_url('site_template_form.php', ['key' => $key])) ?>"><?= $t['builtin'] ? 'Details' : 'Edit' ?></a>
                        <?php if ($sid): ?><a class="btn btn-sm" href="<?= e(site_admin_url('theme_settings.php', ['section' => $sid])) ?>">Colours &amp; fonts</a><?php endif; ?>
                        <a class="btn btn-sm" href="<?= e(site_admin_url('site_content.php', ['template' => $key])) ?>">Page layouts</a>
                        <form method="post"><?= theme_csrf_field() ?><button class="btn btn-sm btn-ghost" name="duplicate" value="<?= e($key) ?>">Duplicate</button></form>
                        <?php if (!$t['builtin'] && !$isLive): ?>
                            <form method="post"><?= theme_csrf_field() ?><button class="btn btn-sm btn-danger" name="delete" value="<?= e($key) ?>" data-confirm="Delete “<?= e($t['name']) ?>” with its colours and page layouts? Page content is shared and stays.">Delete</button></form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="panel st-help">
        <h2>How templates work</h2>
        <p class="panel-desc">Each template chooses a style for the header, hero, footer and every section type, and has its own colours and fonts.
            Page text and images are shared by all templates and edited in <a href="<?= e(site_admin_url('site_content.php')) ?>">Page Content</a>.
            To make a new look, use <strong>Add template</strong> or <strong>Duplicate</strong>, then mix the Atelier, Heritage and Noir styles part by part.</p>
    </div>
    <?php endif; ?>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
