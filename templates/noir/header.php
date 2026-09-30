<?php
/** Noir header: info bar + logo and menu. Variables from site_render(). */
declare(strict_types=1);
$email  = primary_email(site_setup());
$phones = site_phones();
$lines  = site_address_lines();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $siteHead ?>
</head>
<body class="site tpl-noir page-<?= e($currentPage) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?= site_preview_bar() ?>
<div class="nr-topbar">
    <div class="nr-wrap nr-topbar__inner">
        <?php if ($email !== ''): ?><a href="mailto:<?= e($email) ?>"><?= site_icon('mail') ?><?= e($email) ?></a><?php endif; ?>
        <?php if ($lines): ?><span class="nr-topbar__addr"><?= site_icon('pin') ?><?= e($lines[0]) ?></span><?php endif; ?>
        <?php if ($phones): ?><a href="<?= e(site_tel_href($phones[0])) ?>"><?= site_icon('phone') ?><?= e($phones[0]) ?></a><?php endif; ?>
    </div>
</div>
<header class="nr-header" data-nav data-sticky-header>
    <div class="nr-wrap nr-header__inner">
        <?= site_brand(true, 'nr-brand') ?>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nr-nav" aria-label="Open menu">
            <?= site_icon('menu', 'icon icon-menu') ?><?= site_icon('close', 'icon icon-close') ?>
        </button>
        <nav class="nr-nav" id="nr-nav" aria-label="Main">
            <?= site_nav_html($currentPage, 'nr-nav') ?>
            <a class="nr-nav__inventory" href="<?= e(site_inventory_url()) ?>">Inventory</a>
        </nav>
    </div>
</header>
