<?php
$overlay = $startsWithHero && $heroFamily === 'noir';
$phone = primary_phone($setup);
$mail = primary_email($setup);
$addr = format_address($setup); ?>
<header class="no-header part--noir<?= $overlay ? ' is-overlay' : '' ?>" data-header>
    <?php if ($phone !== '' || $mail !== '' || $addr !== ''): ?>
        <div class="no-topbar">
            <div class="wrap no-topbar__inner">
                <?php if ($mail !== ''): ?><a href="mailto:<?= e($mail) ?>"><?= site_icon('mail') ?><?= e($mail) ?></a><?php endif; ?>
                <?php if ($addr !== ''): ?><span class="no-topbar__addr"><?= site_icon('pin') ?><?= e($addr) ?></span><?php endif; ?>
                <?php if ($phone !== ''): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= site_icon('phone') ?><?= e($phone) ?></a><?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="wrap no-header__bar">
        <?= site_brand(true) ?>
        <nav class="no-nav" id="site-menu" aria-label="Main">
            <?= site_menu($nav, 'menu') ?>
            <?= site_header_button('no-nav__cta') ?>
        </nav>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu" data-menu-toggle>
            <?= site_icon('menu', 'ico ico-open') ?><?= site_icon('close', 'ico ico-close') ?><span class="visually-hidden">Menu</span>
        </button>
    </div>
</header>
