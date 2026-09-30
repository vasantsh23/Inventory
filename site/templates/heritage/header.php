<header class="he-header part--heritage" data-header>
    <div class="wrap he-header__bar">
        <?= site_brand(false) ?>
        <nav class="he-nav" id="site-menu" aria-label="Main"><?= site_menu($nav, 'menu') ?><?= site_header_button('btn btn--primary he-nav__cta') ?></nav>
        <div class="he-header__end">
            <?= site_header_button('btn btn--primary he-header__cta') ?>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu" data-menu-toggle>
                <?= site_icon('menu', 'ico ico-open') ?><?= site_icon('close', 'ico ico-close') ?><span class="visually-hidden">Menu</span>
            </button>
        </div>
    </div>
</header>
