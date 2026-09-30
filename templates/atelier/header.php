<?php
/** Atelier header: logo, centred menu, pill button. Variables from site_render(). */
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $siteHead ?>
</head>
<body class="site tpl-atelier page-<?= e($currentPage) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?= site_preview_bar() ?>
<header class="at-header" data-nav data-sticky-header>
    <div class="at-wrap at-header__inner">
        <?= site_brand(false, 'at-brand') ?>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="at-nav" aria-label="Open menu">
            <?= site_icon('menu', 'icon icon-menu') ?><?= site_icon('close', 'icon icon-close') ?>
        </button>
        <nav class="at-nav" id="at-nav" aria-label="Main">
            <?= site_nav_html($currentPage, 'at-nav') ?>
            <a class="at-btn at-btn--sm" href="<?= e(site_inventory_url()) ?>">View inventory</a>
        </nav>
    </div>
</header>
