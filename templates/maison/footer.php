<?php
/** Maison footer */
declare(strict_types=1);
$footer = site_block('global', 'footer');
$email  = primary_email(site_setup());
$lines  = site_address_lines();
?>
<footer class="mz-footer">
    <div class="mz-wrap mz-footer__inner">
        <?= site_brand(true, 'mz-footer-brand') ?>
        <?php if (!empty($footer['subtitle'])): ?><p class="mz-footer__tagline"><?= st($footer['subtitle']) ?></p><?php endif; ?>
        <?php if ($lines): ?><address class="mz-footer__address"><?= implode('<br>', array_map('e', $lines)) ?></address><?php endif; ?>
        <p class="mz-footer__contact">
            <?php foreach (site_phones() as $ph): ?><a href="<?= e(site_tel_href($ph)) ?>"><?= e($ph) ?></a><?php endforeach; ?>
            <?php if ($email !== ''): ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
        </p>
        <?= site_social_html('mz-social') ?>
        <nav class="mz-footer__nav" aria-label="Footer"><?= site_nav_html($currentPage, 'mz-fnav') ?></nav>
    </div>
    <div class="mz-footer__bottom">
        <p>&copy; <?= date('Y') ?> <?= e(site_company()) ?>. All rights reserved.</p>
    </div>
</footer>
<?= site_scripts() ?>
</body>
</html>
