<?php
/** Atelier footer: deep-colour band with four columns. */
declare(strict_types=1);
$footer = site_block('global', 'footer');
$email  = primary_email(site_setup());
$lines  = site_address_lines();
?>
<footer class="at-footer">
    <div class="at-wrap at-footer__grid">
        <div class="at-footer__brand">
            <?= site_brand(true, 'at-brand') ?>
            <?php if ($lines): ?><address><?= implode('<br>', array_map('e', $lines)) ?></address><?php endif; ?>
            <?= site_social_html('at-social') ?>
        </div>
        <nav aria-label="Footer">
            <h2 class="at-footer__title">Useful links</h2>
            <?= site_nav_html($currentPage, 'at-fnav') ?>
        </nav>
        <div>
            <h2 class="at-footer__title">Contact us</h2>
            <?php foreach (site_phones() as $ph): ?><p><a href="<?= e(site_tel_href($ph)) ?>"><?= e($ph) ?></a></p><?php endforeach; ?>
            <?php if ($email !== ''): ?><p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif; ?>
        </div>
        <div>
            <h2 class="at-footer__title"><?= st($footer['subtitle'] ?? 'Trade customers') ?></h2>
            <?= site_paras($footer['body'] ?? '') ?>
            <a class="at-btn at-btn--light at-btn--sm" href="<?= e(site_inventory_url()) ?>">View inventory</a>
        </div>
    </div>
    <div class="at-wrap at-footer__bottom"><p>&copy; <?= date('Y') ?> <?= e(site_company()) ?>. All rights reserved.</p></div>
</footer>
<?= site_scripts() ?>
</body>
</html>
