<?php
/** Maison header. Variables from site_render(): $siteHead, $currentPage, $hasHero, $tpl */
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $siteHead ?>
</head>
<body class="site tpl-maison page-<?= e($currentPage) ?><?= $hasHero ? ' has-hero' : '' ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?= site_preview_bar() ?>
<header class="mz-header" data-nav data-sticky-header>
    <div class="mz-wrap mz-header__inner">
        <?= site_brand(true, 'mz-brand') ?>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mz-nav" aria-label="Open menu">
            <?= site_icon('menu', 'icon icon-menu') ?><?= site_icon('close', 'icon icon-close') ?>
        </button>
        <nav class="mz-nav" id="mz-nav" aria-label="Main">
            <?= site_nav_html($currentPage, 'mz-nav') ?>
            <a class="mz-nav__inventory" href="<?= e(site_inventory_url()) ?>">Inventory</a>
        </nav>
    </div>
</header>
