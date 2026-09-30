<?php
/**
 * Website Template: choose which design the public website uses.
 * Switching changes the layout of every public page (Home, About,
 * Services, Diamonds, Responsible Practices, Sustainability, Contact)
 * while keeping the same content. Colours and fonts of each template
 * are edited in Theme Settings; page text in Website Content.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme_ui.php';
require_once __DIR__ . '/../../includes/site.php';

require_module_access('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    theme_csrf_check();
    $key = (string) ($_POST['activate'] ?? '');
    if (!SiteTemplates::exists($key)) {
        theme_flash('error', 'That template could not be found. Choose one of the templates below.');
    } else {
        try {
            SiteTemplates::setActive($key);
            unset($_SESSION['site_preview_template']);
            theme_flash('success', SiteTemplates::get($key)['name'] . ' is now live on the website.');
        } catch (Throwable $e) {
            error_log('Template activation failed: ' . $e->getMessage());
            theme_flash('error', 'The template was not changed. Check that sql/migration_site_templates.sql has been run.');
        }
    }
    theme_redirect('site_template.php');
}

$templates  = SiteTemplates::all();
$activeKey  = SiteTemplates::activeKey();
$previewKey = SiteTemplates::previewKey();

// Health check: the migration creates these tables
$missing = [];
foreach (['site_options', 'site_content', 'contact_messages'] as $t) {
    try {
        get_db()->query("SELECT 1 FROM `$t` LIMIT 1");
    } catch (Throwable $e) {
        $missing[] = $t;
    }
}
$newEnquiries = 0;
if (!in_array('contact_messages', $missing, true)) {
    $newEnquiries = (int) get_db()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
}

const SITE_BLOCK_LABELS = [
    'hero' => 'Hero banner', 'stats' => 'Key figures', 'intro' => 'Introduction', 'specialities' => 'Specialities',
    'feature' => 'Feature', 'process' => 'How it works', 'faq' => 'FAQ', 'cta' => 'Call to action',
    'story' => 'Our story', 'timeline' => 'Milestones', 'values' => 'Values', 'quote' => 'Quote',
    'services' => 'Services', 'categories' => 'Diamond range', 'inventory' => 'Inventory link',
    'education' => 'Diamond basics', 'practices' => 'Practices', 'standards' => 'Standards',
    'pillars' => 'Focus areas', 'closing' => 'Closing message', 'form' => 'Contact form and details',
];

$pageTitle    = 'Website Template';
$pageSubtitle = 'Choose the design of the public website. Every page keeps its content; only the layout, colours and fonts change.';
$activeNav    = 'site_template';
$dashActionsHtml = '<a class="btn" href="' . e(asset_url('/index.php')) . '" target="_blank" rel="noopener">View website</a>';

require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <?= theme_render_flashes() ?>

    <?php if ($missing): ?>
        <div class="alert alert-error">
            The website tables are missing (<?= e(implode(', ', $missing)) ?>). Back up the database, then run
            <code>sql/migration_site_templates.sql</code> in phpMyAdmin.
        </div>
    <?php endif; ?>

    <?php if ($previewKey !== null): ?>
        <div class="alert alert-warning">
            You are previewing <strong><?= e($templates[$previewKey]['name']) ?></strong> in this browser.
            <a href="<?= e(asset_url('/index.php?preview=off')) ?>">Exit preview</a>
        </div>
    <?php endif; ?>

    <div class="st-grid">
        <?php foreach ($templates as $key => $t):
            $isLive  = $key === $activeKey;
            $thumb   = is_file(dirname(__DIR__, 2) . '/assets/img/templates/' . $key . '.jpg')
                ? asset_url_versioned('/assets/img/templates/' . $key . '.jpg') : '';
            $section = SiteTemplates::themeSectionId($key);
        ?>
            <article class="panel st-card<?= $isLive ? ' is-live' : '' ?>">
                <div class="st-thumb">
                    <?php if ($thumb !== ''): ?>
                        <img src="<?= e($thumb) ?>" alt="Home page of the <?= e($t['name']) ?> template" loading="lazy">
                    <?php else: ?>
                        <span><?= e($t['name']) ?></span>
                    <?php endif; ?>
                    <?php if ($isLive): ?><span class="pill pill-success st-badge">Live</span><?php endif; ?>
                </div>
                <div class="st-body">
                    <h2><?= e($t['name']) ?></h2>
                    <p class="st-tagline"><?= e($t['tagline'] ?? '') ?></p>
                    <p class="panel-desc"><?= e($t['description'] ?? '') ?></p>
                    <?php if (!empty($t['inspired_by'])): ?><p class="st-meta">Based on: <?= e($t['inspired_by']) ?></p><?php endif; ?>

                    <details class="st-layout">
                        <summary>Page layouts</summary>
                        <dl>
                            <?php foreach (SITE_PAGES as $pk => $p): ?>
                                <div>
                                    <dt><?= e($p['label']) ?></dt>
                                    <dd><?= e(implode(', ', array_map(fn($s) => SITE_BLOCK_LABELS[$s['block']] ?? $s['block'], $t['pages'][$pk] ?? []))) ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </details>

                    <div class="st-actions">
                        <?php if ($isLive): ?>
                            <span class="btn btn-ghost" aria-disabled="true">Live on the website</span>
                        <?php else: ?>
                            <form method="post" action="<?= e(asset_url('/modules/admin/site_template.php')) ?>"
                                  data-confirm="Make <?= e($t['name']) ?> the live design of the website? Visitors see the change immediately.">
                                <?= theme_csrf_field() ?>
                                <input type="hidden" name="activate" value="<?= e($key) ?>">
                                <button class="btn btn-accent" type="submit">Make live</button>
                            </form>
                            <a class="btn" href="<?= e(asset_url('/index.php?preview=' . urlencode($key))) ?>" target="_blank" rel="noopener">Preview</a>
                        <?php endif; ?>
                        <?php if ($section): ?>
                            <a class="btn btn-ghost" href="<?= e(asset_url('/modules/admin/theme_settings.php?section=' . $section)) ?>">Colours and fonts</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="panel">
        <h2>Website content</h2>
        <p class="panel-desc">
            Headings, text, images and buttons for every page are shared by all templates. Each row belongs to a page and a
            block (for example page <code>home</code>, block <code>hero</code>); rows with the same page and block form a list,
            shown in <em>sort order</em>. Use <code>{company}</code> in any text to insert the company name from Site Setup.
            Images are paths to files on this website, such as <code>/assets/img/site/hero.jpg</code>; leave empty to use the
            template's built-in diamond artwork. Icons: <?= e(implode(', ', array_diff(array_keys(SITE_ICONS), ['chevron', 'chevron-left', 'plus', 'menu', 'close']))) ?>.
        </p>
        <div class="st-actions">
            <a class="btn btn-accent" href="<?= e(asset_url('/modules/admin/table_view.php?table=site_content')) ?>">Edit website content</a>
            <a class="btn" href="<?= e(asset_url('/modules/admin/table_view.php?table=contact_messages')) ?>">
                Website enquiries<?php if ($newEnquiries > 0): ?> <span class="pill pill-warning"><?= $newEnquiries ?> new</span><?php endif; ?>
            </a>
            <a class="btn btn-ghost" href="<?= e(asset_url('/modules/admin/table_view.php?table=setup')) ?>">Company details (Site Setup)</a>
        </div>
    </div>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
