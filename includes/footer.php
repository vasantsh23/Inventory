<?php
declare(strict_types=1);
// $setup is already populated by header.php, which is always included first.
$setup = $setup ?? (function_exists('get_setup') ? (get_setup() ?? []) : []);
// Optional: a page can set $footerExtraRight (pre-escaped HTML) before
// requiring this file to add a right-aligned block inside the footer
// — currently only Diamond Search uses this, for its "data last
// updated" info (see includes/functions.php's get_latest_upload_log()).
// Every other page renders exactly as before, unchanged.
$footerExtraRight = $footerExtraRight ?? '';
?>
</main>
<footer class="site-footer">
    <div class="footer-inner<?= $footerExtraRight !== '' ? ' footer-inner-split' : '' ?>">
        <div class="footer-main">
            <p class="footer-company"><?= e($setup['company'] ?? APP_NAME) ?></p>
            <?php if (($addr = format_address($setup)) !== ''): ?>
                <p class="footer-address"><?= e($addr) ?></p>
            <?php endif; ?>
            <?php if (($phone = primary_phone($setup)) !== ''): ?>
                <p class="footer-phone">Tel: <?= e($phone) ?></p>
            <?php endif; ?>
            <?php if (($email = primary_email($setup)) !== ''): ?>
                <p class="footer-email">
                    <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                </p>
            <?php endif; ?>
            <p class="footer-copy">&copy; <?= date('Y') ?> <?= e($setup['company'] ?? APP_NAME) ?>. All rights reserved. <span class="footer-version"><?= e(APP_VERSION) ?></span></p>
        </div>
        <?php if ($footerExtraRight !== ''): ?>
            <div class="footer-extra-right"><?= $footerExtraRight ?></div>
        <?php endif; ?>
    </div>
</footer>
</body>
</html>
