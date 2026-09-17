<?php
declare(strict_types=1);
// $setup is already populated by header.php, which is always included first.
$setup = $setup ?? (function_exists('get_setup') ? (get_setup() ?? []) : []);
?>
</main>
<footer class="site-footer">
    <div class="footer-inner">
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
        <p class="footer-copy">&copy; <?= date('Y') ?> <?= e($setup['company'] ?? APP_NAME) ?>. All rights reserved.</p>
    </div>
</footer>
</body>
</html>
