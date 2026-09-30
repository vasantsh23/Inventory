<?php $contact = site_contact_lines($setup); $hours = site_hours(); ?>
<footer class="he-footer part--heritage">
    <div class="wrap he-footer__grid">
        <div class="he-footer__brand">
            <?= site_brand(true, 'brand brand--footer') ?>
            <?php if (($about = Site::global('footer', 'about')) !== ''): ?><p><?= e($about) ?></p><?php endif; ?>
            <?= site_social($setup) ?>
        </div>
        <nav class="he-footer__col" aria-label="Footer">
            <h2 class="he-footer__title">Pages</h2>
            <?= site_menu($nav, 'he-footer__links') ?>
        </nav>
        <div class="he-footer__col">
            <h2 class="he-footer__title">Contact</h2>
            <ul class="he-footer__contact">
                <?php foreach ($contact as [$icon, $label, $html]): ?><li><?= site_icon($icon) ?><span><?= $html ?></span></li><?php endforeach; ?>
            </ul>
        </div>
        <div class="he-footer__col">
            <?php if ($hours): ?>
                <h2 class="he-footer__title">Opening hours</h2>
                <dl class="s-hours"><?php foreach ($hours as [$d, $t]): ?><div><dt><?= e($d) ?></dt><dd><?= e($t) ?></dd></div><?php endforeach; ?></dl>
            <?php else: ?>
                <h2 class="he-footer__title">Inventory</h2>
                <p>Search live stock with your trade account.</p>
                <a class="btn btn--secondary" href="<?= e(asset_url('/inventory.php')) ?>">Browse inventory</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="wrap he-footer__meta"><?= site_footer_meta() ?></div>
</footer>
