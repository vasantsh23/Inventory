<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_module_access('superadmin');
require_once __DIR__ . '/../../includes/header.php';
?>
    <section class="page-content">
        <h1>Super Admin Module</h1>
        <p>Welcome, <?= e(current_user()['username']) ?>. Full system control: users, admins, and global configuration.</p>
        <ul>
            <li><a href="<?= e(asset_url('/modules/admin/index.php')) ?>">Open Admin Module</a></li>
            <li><a href="<?= e(asset_url('/modules/user/diamond_search.php')) ?>">Open User Module</a></li>
            <!-- TODO: manage admin accounts, roles, and system-wide settings. -->
        </ul>
    </section>
<?php
require_once __DIR__ . '/../../includes/footer.php';
