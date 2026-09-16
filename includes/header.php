<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';

$setup = get_setup() ?? [];
$companyName = $setup['company'] ?? APP_NAME;
$logoPath    = get_logo_path();
$theme       = get_active_theme();
// The path stored in the `path` table is a server-local path
// (e.g. "/assets/img/logo.png"); prefix it with BASE_URL so it
// resolves correctly whether the app lives at the domain root or
// in a subfolder. If an absolute http(s) URL is stored instead
// (e.g. a CDN link), leave it untouched.
$logoUrl = preg_match('#^https?://#i', $logoPath) ? $logoPath : asset_url($logoPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($setup['Page title'] ?? $companyName) ?></title>
    <?php if (!empty($setup['Page Desc'])): ?>
        <meta name="description" content="<?= e($setup['Page Desc']) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset_url_versioned('/assets/css/style.css')) ?>">
    <?php
    // The custom theme from Admin > Fonts & Colors is only applied on
    // the four content pages it's meant for (Home, About Us,
    // Inventory, Contact Us) — pages set $applyPublicTheme = true
    // before including this file. Login/logout and other auth/utility
    // screens deliberately keep the app's own consistent, readable
    // styling regardless of what colors an admin picks for content.
    if (!empty($applyPublicTheme) && array_filter($theme)): ?>
    <style>
        /* Active theme selected in Admin > Fonts & Colors, applied to
           the public page content area only — the header/nav/footer
           chrome keeps the app's own consistent styling. Setting
           these as custom properties (rather than plain font-family/
           color/background) means every descendant element that
           references var(--content-fg, ...) etc. — cards, headings,
           paragraphs — picks up the theme too, not just the outer
           wrapper. */
        .site-content {
            <?php if ($theme['fontFamily']): ?>--content-font-family: <?= e($theme['fontFamily']) ?>, var(--font);<?php endif; ?>
            <?php if ($theme['fontSize']): ?>--content-font-size: <?= e(normalize_css_length($theme['fontSize'])) ?>;<?php endif; ?>
            <?php if ($theme['foreColor']): ?>--content-fg: <?= e($theme['foreColor']) ?>;<?php endif; ?>
            <?php if ($theme['backColor']): ?>--content-bg: <?= e($theme['backColor']) ?>;<?php endif; ?>
        }
    </style>
    <?php endif; ?>
</head>
<body<?= !empty($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
<header class="site-header">
    <div class="brand">
        <img src="<?= e($logoUrl) ?>" alt="<?= e($companyName) ?> logo" class="logo">
        <span class="company-name"><?= e($companyName) ?></span>
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
<main class="site-content<?= !empty($wideContent) ? ' site-content--wide' : '' ?>">
