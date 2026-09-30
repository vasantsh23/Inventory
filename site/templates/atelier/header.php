<?php $tagline = Site::global('brand', 'tagline'); ?>
<header class="at-header part--atelier" data-header>
    <div class="wrap at-header__bar">
        <div class="at-header__side">
            <?php if (($m = primary_email($setup)) !== ''): ?><a class="at-header__meta" href="mailto:<?= e($m) ?>"><?= e($m) ?></a><?php endif; ?>
        </div>
        <div class="at-header__brand">
            <?= site_brand(false) ?>
            <?php if ($tagline !== ''): ?><p class="at-header__tagline"><?= e($tagline) ?></p><?php endif; ?>
        </div>
        <div class="at-header__side at-header__side--end">
            <?= site_header_button('at-header__cta') ?>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu" data-menu-toggle>
                <?= site_icon('menu', 'ico ico-open') ?><?= site_icon('close', 'ico ico-close') ?><span class="visually-hidden">Menu</span>
            </button>
        </div>
    </div>
    <nav class="at-nav" id="site-menu" aria-label="Main">
        <?= site_menu($nav, 'menu wrap') ?>
        <?= site_header_button('btn btn--primary at-nav__cta') ?>
    </nav>
</header>
