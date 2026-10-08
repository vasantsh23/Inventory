<?php
/**
 * bootstrap.php — entry point for every public website page.
 *
 *   <?php require __DIR__ . '/site/bootstrap.php'; site_render('about');
 *
 * Loads the existing app services (config, DB, session, auth, theme) without
 * modifying them, then renders the page with the active template.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';      // config, db, session, security, functions
require_once __DIR__ . '/../includes/ThemeSettings.php';
require_once __DIR__ . '/../includes/theme.php';       // theme_scoped_head_tags()
require_once __DIR__ . '/Site.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/contact_form.php';
require_once __DIR__ . '/view_helpers.php';

/** Include a partial with $vars in its own scope */
function site_include(string $file, array $vars): void
{
    extract($vars, EXTR_SKIP);
    require $file;
}

/** Path of a block partial: the family's own version, else the shared one */
function site_block_file(string $family, string $type): ?string
{
    foreach ([__DIR__ . "/templates/$family/blocks/$type.php", __DIR__ . "/templates/_shared/blocks/$type.php"] as $f) {
        if (is_file($f)) {
            return $f;
        }
    }
    return null;
}

/** Handle ?preview=<template>|off for admins, then drop the parameter from the URL */
function site_handle_preview(string $page): void
{
    if (!isset($_GET['preview'])) {
        return;
    }
    $want = (string) $_GET['preview'];
    if (Site::canPreview()) {
        if ($want === 'off') {
            unset($_SESSION['site_preview_template']);
        } elseif (Site::isTemplate($want)) {
            $_SESSION['site_preview_template'] = $want;
        }
    }
    header('Location: ' . Site::pageUrl($page));
    exit;
}

/** Stylesheets + font links for the template in use */
function site_head_tags(string $template): string
{
    $palette = Site::paletteValues($template);
    $html = '';
    if ($url = google_fonts_url([$palette['heading-font'], $palette['body-font']])) {
        $html .= '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n"
               . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n"
               . '<link rel="stylesheet" href="' . e($url) . '">' . "\n";
    }
    try {
        $v = ThemeSettings::version();
    } catch (Throwable $e) {
        $v = 'offline';
    }
    $html .= '<link rel="stylesheet" href="' . e(asset_url('/theme.css.php') . '?v=' . $v) . '">' . "\n"
           . '<style>' . Site::tokenCss($template) . '</style>' . "\n"
           . '<link rel="stylesheet" href="' . e(Site::asset('/assets/site/css/base.css')) . '">' . "\n";
    foreach (Site::familiesUsed($template) as $family) {
        $html .= '<link rel="stylesheet" href="' . e(Site::asset('/assets/site/css/' . $family . '.css')) . '">' . "\n";
    }
    return $html;
}

/** Render a complete public page */
function site_render(string $page): void
{
    $def = Site::pageDef($page);
    if (!$def || $page === 'global') {
        http_response_code(404);
        exit('Page not found.');
    }

    site_handle_preview($page);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_contact'])) {
        site_contact_handle($page); // redirects
    }

    $template = Site::template();
    $setup = Site::setup();
    $company = Site::company();
    $blocks = [];
    foreach (Site::layout($template, $page) as $i => $row) {
        $bdef = Site::blockDef($page, $row['key']);
        if (!$bdef) {
            continue;
        }
        $family = Site::family($template, $bdef['type']);
        if ($file = site_block_file($family, $bdef['type'])) {
            $blocks[] = [new SiteBlock($page, $row['key'], $bdef['type'], $i), $family, $file];
        }
    }
    $headerFamily = Site::family($template, 'header');
    $footerFamily = Site::family($template, 'footer');
    $heroFamily = Site::family($template, 'hero');
    $startsWithHero = $blocks !== [] && $blocks[0][0]->type === 'hero';

    $vars = [
        'template' => $template,
        'page'     => $page,
        'pageDef'  => $def,
        'setup'    => $setup,
        'company'  => $company,
        'nav'      => Site::nav($page),
        'blocks'   => $blocks,
        'heroFamily'     => $heroFamily,
        'startsWithHero' => $startsWithHero,
    ];

    $pageTitle = ($page === 'home' ? ($setup['Meta Title'] ?? '') : '') ?: ($def['title'] ?? $def['label']);
    $pageTitle .= Site::global('seo', 'title_suffix');
    $desc = (string) ($setup['Meta Desc'] ?? '') ?: (string) ($setup['Page Desc'] ?? '');
    $favicon = Site::imageUrl((string) ($setup['Favicon'] ?? ''));
    $ogImage = Site::imageUrl((string) ($setup['OG image'] ?? ''));

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<?php if ($desc !== ''): ?><meta name="description" content="<?= e($desc) ?>">
<?php endif; ?>
<?php if (!empty($setup['Meta Keyword'])): ?><meta name="keywords" content="<?= e((string) $setup['Meta Keyword']) ?>">
<?php endif; ?>
<meta property="og:title" content="<?= e((string) ($setup['OG Title'] ?? '') ?: $pageTitle) ?>">
<?php if (($og = (string) ($setup['OG Desc'] ?? '') ?: $desc) !== ''): ?><meta property="og:description" content="<?= e($og) ?>">
<?php endif; ?>
<?php if ($ogImage !== ''): ?><meta property="og:image" content="<?= e(full_url((string) $setup['OG image'])) ?>">
<?php endif; ?>
<?php if ($favicon !== ''): ?><link rel="icon" href="<?= e($favicon) ?>">
<?php endif; ?>
<?= site_head_tags($template) ?>
<?= theme_scoped_head_tags('.inv-chrome') ?>
</head>
<body class="site tpl-<?= e($template) ?> hdr-<?= e($headerFamily) ?> hero-<?= e($heroFamily) ?> page-<?= e($page) ?><?= Site::isDarkColor(Site::paletteValues($template)['bg']) ? ' is-dark' : '' ?><?= $startsWithHero ? ' starts-with-hero' : '' ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?php
    // Header and footer: the same bars as the inventory pages (Results,
    // View Cart…), not the template's own — see includes/partials/.
    // Run in their own scope so they can't overwrite this function's
    // variables.
    echo '<div class="inv-chrome">';
    (static function (): void { require __DIR__ . '/../includes/partials/header_bar.php'; })();
    echo '</div>';
    echo '<main id="main" class="site-main">';
    foreach ($blocks as [$b, $family, $file]) {
        echo '<div class="part part--' . e($family) . ' part-' . e($b->type) . '">';
        site_include($file, $vars + ['b' => $b, 'family' => $family]);
        echo '</div>';
    }
    echo '</main>';
    echo '<div class="inv-chrome">';
    (static function (): void { require __DIR__ . '/../includes/partials/footer_bar.php'; })();
    echo '</div>';

    if (Site::previewTemplate() !== null) {
        site_include(__DIR__ . '/templates/_shared/preview_bar.php', $vars);
    }
    ?>
<script src="<?= e(Site::asset('/assets/site/js/site.js')) ?>" defer></script>
</body>
</html>
<?php
}
