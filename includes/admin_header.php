<?php
declare(strict_types=1);

/**
 * admin_header.php
 * Expects (optionally) $pageTitle, $pageSubtitle, $activeNav to be set
 * before including this file. Requires at least 'admin' module access.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/crud_config.php';

require_module_access('admin');

$setup = get_setup() ?? [];
$companyName = $setup['company'] ?? APP_NAME;
$logoPath = get_logo_path();
$logoUrl = preg_match('#^https?://#i', $logoPath) ? $logoPath : asset_url($logoPath);
$theme = get_active_theme();
$activeNav = $activeNav ?? '';
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · <?= e($companyName) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url_versioned('/assets/css/style.css')) ?>">
    <?php if (array_filter($theme)): ?>
    <style>
        /* Active theme selected in Admin > Fonts & Colors, applied to
           the dashboard's main working area (panels, stat cards,
           table text). The sidebar, top bar, buttons and status
           pills intentionally keep their own fixed styling — those
           need to stay readable/semantic (danger/success/warning)
           regardless of what colors are picked for content. */
        .dash-main {
            <?php if ($theme['fontFamily']): ?>--content-font-family: <?= e($theme['fontFamily']) ?>, var(--font);<?php endif; ?>
            <?php if ($theme['fontSize']): ?>--content-font-size: <?= e(normalize_css_length($theme['fontSize'])) ?>;<?php endif; ?>
            <?php if ($theme['foreColor']): ?>--content-fg: <?= e($theme['foreColor']) ?>;<?php endif; ?>
            <?php if ($theme['backColor']): ?>--content-bg: <?= e($theme['backColor']) ?>;<?php endif; ?>
        }
    </style>
    <?php endif; ?>
</head>
<body>
<header class="site-header">
    <div class="brand">
        <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <img src="<?= e($logoUrl) ?>" alt="<?= e($companyName) ?> logo" class="logo">
        <span class="company-name"><?= e($companyName) ?> — Admin</span>
    </div>
    <nav class="main-nav">
        <ul>
            <li><a href="<?= e(asset_url('/index.php')) ?>">Public Site</a></li>
            <li class="nav-account">
                <?= e(current_user()['username']) ?> (<?= e(current_user()['usertype_label'] ?? '') ?>)
                &middot; <a href="<?= e(asset_url('/logout.php')) ?>">Log out</a>
            </li>
        </ul>
    </nav>
</header>

<div class="dash-shell" id="dashShell">
    <div class="dash-sidebar-backdrop" id="sidebarBackdrop"></div>
    <aside class="dash-sidebar" id="dashSidebar">
        <a href="<?= e(asset_url('/modules/admin/index.php')) ?>" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Overview</a>

        <div class="sidebar-section">Modules</div>
        <a href="<?= e(asset_url('/modules/user/diamond_search.php')) ?>">User Module</a>

        <?php if ((int)(current_user()['level'] ?? 0) >= 9): ?>
            <a href="<?= e(asset_url('/modules/superadmin/index.php')) ?>">Super Admin Module</a>
        <?php endif; ?>

        <div class="sidebar-section">Manage Tables</div>
        <?php foreach (CRUD_TABLES as $tableKey => $tableLabel): ?>
            <a href="<?= e(asset_url('/modules/admin/table_view.php?table=' . urlencode($tableKey))) ?>"
               class="<?= $activeNav === 'table:' . $tableKey ? 'active' : '' ?>"><?= e($tableLabel) ?></a>
        <?php endforeach; ?>

        <div class="sidebar-section">Data</div>
        <a href="<?= e(asset_url('/modules/admin/backup.php')) ?>" class="<?= $activeNav === 'backup' ? 'active' : '' ?>">Backup &amp; Restore</a>
        <a href="<?= e(asset_url('/modules/admin/diamond_data_upload.php')) ?>" class="<?= $activeNav === 'diamond_data_upload' ? 'active' : '' ?>">Diamond Data Upload</a>

        <div class="sidebar-section">Tools</div>
        <a href="<?= e(asset_url('/modules/admin/font_color_preview.php')) ?>" class="<?= $activeNav === 'font_color_preview' ? 'active' : '' ?>">Font &amp; Color Preview</a>
    </aside>

    <main class="dash-main">
        <div class="dash-header">
            <div>
                <h1><?= e($pageTitle) ?></h1>
                <?php if ($pageSubtitle !== ''): ?>
                    <p class="dash-subtitle"><?= e($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <?php if (isset($dashActionsHtml)): ?>
                <div class="dash-actions"><?= $dashActionsHtml ?></div>
            <?php endif; ?>
        </div>
