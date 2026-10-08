<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/theme.php';

$setup = get_setup() ?? [];
$companyName = $setup['company'] ?? APP_NAME;
$logoPath    = get_logo_path();
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
    <?php // Colours and fonts come from the theme_settings table (Admin → Theme Settings).
          // A page that sets $useDefaultTheme = true (the login screen) gets the
          // default theme (default_value) instead. ?>
    <?= theme_head_tags(false, !empty($useDefaultTheme)) ?>
    <link rel="stylesheet" href="<?= e(asset_url_versioned('/assets/css/style.css')) ?>">
</head>
<body<?= !empty($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
<?php require __DIR__ . '/partials/header_bar.php'; ?>
<main class="site-content<?= !empty($wideContent) ? ' site-content--wide' : '' ?><?= !empty($compactTop) ? ' site-content--compact' : '' ?>">
