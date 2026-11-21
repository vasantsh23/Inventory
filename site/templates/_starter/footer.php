<?php
/**
 * FOOTER of this design. Used when design.json has "own_footer": true
 * (otherwise the shared footer bar is shown).
 * Helpers: site_brand(), site_menu($nav), site_contact_lines($setup),
 * site_social($setup), site_footer_meta(), Site::global('footer', 'about').
 */
if (!function_exists('site_render')) { http_response_code(404); exit; } // only via site/bootstrap.php
$contact = site_contact_lines($setup);
?>
<footer class="md-footer part--<?= e($family) ?>">
    <div class="wrap md-footer__grid">
        <div>
            <?= site_brand(true, 'brand brand--footer') ?>
            <?php if (($about = Site::global('footer', 'about')) !== ''): ?><p><?= e($about) ?></p><?php endif; ?>
            <?= site_social($setup) ?>
        </div>
        <nav aria-label="Footer">
            <h2 class="md-footer__title">Explore</h2>
            <?= site_header_buttons('md-footer__links') ?>
        </nav>
        <div>
            <h2 class="md-footer__title">Contact</h2>
            <ul class="md-footer__links">
                <?php foreach ($contact as [$icon, $label, $html]): ?>
                    <li><?= site_icon($icon) ?> <?= $html ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="wrap md-footer__meta"><?= site_footer_meta() ?></div>
</footer>
