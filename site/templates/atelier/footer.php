<?php $contact = site_contact_lines($setup); ?>
<footer class="at-footer part--atelier">
    <div class="wrap at-footer__inner">
        <?= site_brand(true, 'brand brand--footer') ?>
        <?php if (($t = Site::global('brand', 'tagline')) !== ''): ?><p class="at-footer__tagline"><?= e($t) ?></p><?php endif; ?>
        <?php if (($about = Site::global('footer', 'about')) !== ''): ?><p class="at-footer__about"><?= e($about) ?></p><?php endif; ?>
        <?php if ($contact): ?>
            <ul class="at-footer__contact">
                <?php foreach ($contact as [$icon, $label, $html]): ?><li><?= $html ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <nav aria-label="Footer"><?= site_menu($nav, 'at-footer__menu') ?></nav>
        <?= site_social($setup, 'social at-footer__social') ?>
        <div class="at-footer__meta"><?= site_footer_meta() ?></div>
    </div>
</footer>
