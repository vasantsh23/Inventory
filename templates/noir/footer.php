<?php
/** Noir footer: four columns. */
declare(strict_types=1);
$footer = site_block('global', 'footer');
$email  = primary_email(site_setup());
$lines  = site_address_lines();
?>
<footer class="nr-footer">
    <div class="nr-wrap nr-footer__grid">
        <div class="nr-footer__brand">
            <?= site_brand(true, 'nr-brand') ?>
            <?= site_paras($footer['body'] ?? '') ?>
            <?= site_social_html('nr-social') ?>
        </div>
        <nav aria-label="Footer">
            <h2 class="nr-footer__title">Explore</h2>
            <?= site_nav_html($currentPage, 'nr-fnav') ?>
        </nav>
        <?php if ($lines): ?>
            <div>
                <h2 class="nr-footer__title">Head office</h2>
                <address><?= implode('<br>', array_map('e', $lines)) ?></address>
            </div>
        <?php endif; ?>
        <div>
            <h2 class="nr-footer__title">Contact</h2>
            <?php if ($email !== ''): ?><p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif; ?>
            <?php foreach (site_phones() as $ph): ?><p><a href="<?= e(site_tel_href($ph)) ?>"><?= e($ph) ?></a></p><?php endforeach; ?>
            <p><a href="<?= e(site_inventory_url()) ?>">Trade inventory</a></p>
        </div>
    </div>
    <div class="nr-footer__bottom"><p>&copy; <?= date('Y') ?> <?= e(site_company()) ?>. All rights reserved.</p></div>
</footer>
<?= site_scripts() ?>
</body>
</html>
