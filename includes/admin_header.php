<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/crud_config.php';
require_once __DIR__ . '/theme.php';

require_module_access('admin');

$setup = get_setup() ?? [];
$companyName = $setup['company'] ?? APP_NAME;
$logoPath = get_logo_path();
$logoUrl = preg_match('#^https?://#i', $logoPath) ? $logoPath : asset_url($logoPath);
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
    <?php // Same theme_settings-driven theme as the public site (Admin → Theme Settings) ?>
    <?= theme_head_tags(!empty($loadAllThemeFonts)) ?>
    <link rel="stylesheet" href="<?= e(asset_url_versioned('/assets/css/style.css')) ?>">
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
        <a href="<?= e(asset_url('/modules/admin/maindata_hold.php')) ?>" class="<?= $activeNav === 'maindata_hold' ? 'active' : '' ?>">Hold Selection</a>

        <div class="sidebar-section">Appearance</div>
        <a href="<?= e(asset_url('/modules/admin/theme_settings.php')) ?>" class="<?= $activeNav === 'theme_settings' ? 'active' : '' ?>">Theme Settings</a>
        <a href="<?= e(asset_url('/modules/admin/theme_sections.php')) ?>" class="<?= $activeNav === 'theme_sections' ? 'active' : '' ?>">Theme Sections</a>
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
