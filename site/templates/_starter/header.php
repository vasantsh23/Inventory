<?php
/**
 * HEADER of this design. Used when design.json has "own_header": true
 * (otherwise the shared header bar is shown).
 *
 * RULE: print the header buttons with site_header_buttons() — never type
 * the menu links by hand. The buttons come from the `buttons` table
 * (Admin → Manage Tables → Header Buttons), so they stay the same in
 * every design.
 *
 * Variables you can use: $setup, $page, $template, $family (this folder's
 * name), $startsWithHero, $heroFamily.
 * Helpers: site_brand(), site_header_buttons(), site_header_button(),
 * site_icon(), primary_email($setup), primary_phone($setup), e().
 */
if (!function_exists('site_render')) { http_response_code(404); exit; } // only via site/bootstrap.php

// Signed-in clients see their name and a Log out link after the buttons.
$account = '';
if (is_logged_in()) {
    $account = '<li class="hdr-account">' . e(current_user()['username'])
             . ' &middot; <a href="' . e(asset_url('/logout.php')) . '">Log out</a></li>';
}
?>
<header class="md-header part--<?= e($family) ?>" data-header>
    <div class="wrap md-header__bar">
        <?= site_brand(false) ?>

        <nav class="md-nav" id="site-menu" aria-label="Main">
            <?= site_header_buttons('menu', '', $account) ?>
        </nav>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu" data-menu-toggle>
            <?= site_icon('menu', 'ico ico-open') ?><?= site_icon('close', 'ico ico-close') ?><span class="visually-hidden">Menu</span>
        </button>
    </div>
</header>
