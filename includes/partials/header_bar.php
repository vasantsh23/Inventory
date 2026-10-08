<?php
/**
 * includes/partials/header_bar.php
 * The site header bar (logo, company name, Home / About Us / Inventory /
 * Contact Us, signed-in user). Shared by every inventory page (via
 * includes/header.php) and the website pages — Home, About, Contact —
 * (via site/bootstrap.php), so both always look the same.
 */
declare(strict_types=1);

$hbSetup = function_exists('get_setup') ? (get_setup() ?? []) : [];
$hbCompany = $hbSetup['company'] ?? APP_NAME;
$hbLogoPath = get_logo_path();
// A stored "/assets/…" path gets BASE_URL in front; an absolute http(s)
// URL (e.g. a CDN link) is used as-is.
$hbLogoUrl = preg_match('#^https?://#i', $hbLogoPath) ? $hbLogoPath : asset_url($hbLogoPath);
?>
<header class="site-header">
    <div class="brand">
        <img src="<?= e($hbLogoUrl) ?>" alt="<?= e($hbCompany) ?> logo" class="logo">
        <span class="company-name"><?= e($hbCompany) ?></span>
    </div>
    <nav class="main-nav">
        <ul>
            <li><a href="<?= e(asset_url('/index.php')) ?>">Home</a></li>
            <li><a href="<?= e(asset_url('/about.php')) ?>">About Us</a></li>
            <li><a href="<?= e(asset_url('/inventory.php')) ?>">Inventory</a></li>
            <li><a href="<?= e(asset_url('/contact.php')) ?>">Contact Us</a></li>
            <?php if (is_logged_in()): ?>
                <li class="nav-account">
                    Signed in as <?= e(current_user()['username']) ?>
                    &middot; <a href="<?= e(asset_url('/logout.php')) ?>">Log out</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
