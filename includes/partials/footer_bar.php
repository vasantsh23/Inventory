<?php
/**
 * includes/partials/footer_bar.php
 * The site footer (company, address, Tel | email, copyright, version).
 * Shared by every inventory page (via includes/footer.php) and the
 * website pages — Home, About, Contact — (via site/bootstrap.php).
 * Optional $footerExtraRight (pre-escaped HTML) adds a right-aligned
 * block — currently only Diamond Search uses it.
 */
declare(strict_types=1);

$setup = $setup ?? (function_exists('get_setup') ? (get_setup() ?? []) : []);
$footerExtraRight = $footerExtraRight ?? '';

// "Client login" link — controlled by Admin -> Website -> Page Content ->
// Footer -> "Show a small Client login link" (website_content:
// global / footer / show_login, default 'yes'). Hidden for signed-in
// users and on the login page itself.
$showClientLogin = (static function (): bool {
    if (function_exists('is_logged_in') && is_logged_in()) {
        return false;
    }
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'login.php') {
        return false;
    }
    if (class_exists('Site')) {
        return Site::raw('global', 'footer', 'show_login') === 'yes';
    }
    try {
        $stmt = get_db()->prepare(
            "SELECT content_value FROM website_content
             WHERE page_key = 'global' AND block_key = 'footer' AND field_key = 'show_login'
             LIMIT 1"
        );
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return $val === false || $val === null ? true : $val === 'yes';
    } catch (Throwable $e) {
        return true; // table missing -> use the field's default ('yes')
    }
})();
?>
<footer class="site-footer">
    <div class="footer-inner<?= $footerExtraRight !== '' ? ' footer-inner-split' : '' ?>">
        <div class="footer-main">
            <p class="footer-company"><?= e($setup['company'] ?? APP_NAME) ?></p>
            <?php if (($addr = format_address($setup)) !== ''): ?>
                <p class="footer-address"><?= e($addr) ?></p>
            <?php endif; ?>
            <?php
            $phone = primary_phone($setup);
            $email = primary_email($setup);
            ?>
            <?php if ($phone !== '' || $email !== ''): ?>
                <div class="footer-contact">
                    <?php if ($phone !== ''): ?>
                        <p class="footer-phone">Tel: <?= e($phone) ?></p>
                    <?php endif; ?>
                    <?php if ($email !== ''): ?>
                        <p class="footer-email">
                            <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <p class="footer-copy">&copy; <?= date('Y') ?> <?= e($setup['company'] ?? APP_NAME) ?>. All rights reserved. <span class="footer-version"><?= e(APP_VERSION) ?></span><?php if ($showClientLogin): ?>
                &middot; <a class="footer-login" href="<?= e(asset_url('/login.php')) ?>">Client login</a><?php endif; ?></p>
        </div>
        <?php if ($footerExtraRight !== ''): ?>
            <div class="footer-extra-right"><?= $footerExtraRight ?></div>
        <?php endif; ?>
    </div>
</footer>
