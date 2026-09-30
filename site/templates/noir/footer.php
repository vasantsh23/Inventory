<?php $contact = site_contact_lines($setup); ?>
<footer class="no-footer part--noir">
    <div class="wrap no-footer__grid">
        <div class="no-footer__brand">
            <?= site_brand(true, 'brand brand--footer') ?>
            <?php if (($about = Site::global('footer', 'about')) !== ''): ?><p><?= e($about) ?></p><?php endif; ?>
            <?= site_social($setup) ?>
        </div>
        <nav aria-label="Footer">
            <h2 class="no-footer__title">Explore</h2>
            <?= site_menu($nav, 'no-footer__links') ?>
        </nav>
        <div>
            <h2 class="no-footer__title">Head office</h2>
            <?php if (isset($contact['address'])): ?><address><?= nl2br(str_replace(', ', "\n", $contact['address'][2]), false) ?></address><?php endif; ?>
        </div>
        <div>
            <h2 class="no-footer__title">Contact</h2>
            <ul class="no-footer__links">
                <?php foreach (['phone', 'email'] as $k): if (isset($contact[$k])): ?><li><?= $contact[$k][2] ?></li><?php endif; endforeach; ?>
                <li><a href="<?= e(asset_url('/inventory.php')) ?>">Browse inventory</a></li>
            </ul>
        </div>
    </div>
    <div class="wrap no-footer__meta"><?= site_footer_meta() ?></div>
</footer>
