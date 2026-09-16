<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crud_engine.php';
require_once __DIR__ . '/../../includes/backup.php';

require_module_access('admin');

$pageTitle = 'Dashboard Overview';
$pageSubtitle = 'Manage every table, back up your data, and keep the system running smoothly.';
$activeNav = 'dashboard';

$stats = [];
foreach (CRUD_TABLES as $tableKey => $label) {
    $stmt = get_db()->query("SELECT COUNT(*) AS c FROM `$tableKey`");
    $stats[$tableKey] = ['label' => $label, 'count' => (int)$stmt->fetch()['c']];
}
$recentBackups = array_slice(list_backups(), 0, 3);

require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <div class="stat-grid">
        <?php foreach ($stats as $tableKey => $s): ?>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($s['count']) ?></div>
                <div class="stat-label"><?= e($s['label']) ?> rows</div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="panel">
        <h2>Manage Tables</h2>
        <p class="panel-desc">Add, edit, delete, import from Excel, or export to Excel — for every table in the system.</p>
        <div class="table-picker-grid">
            <?php foreach (CRUD_TABLES as $tableKey => $label): ?>
                <a class="table-picker-card" href="<?= e(asset_url('/modules/admin/table_view.php?table=' . urlencode($tableKey))) ?>">
                    <div class="tpc-icon"><?= e(strtoupper(substr($label, 0, 1))) ?></div>
                    <h3><?= e($label) ?></h3>
                    <p><?= number_format($stats[$tableKey]['count']) ?> records</p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel">
        <h2>Backup &amp; Restore</h2>
        <p class="panel-desc">Download a full snapshot of the database, or restore from a previous backup.</p>
        <?php if ($recentBackups !== []): ?>
            <ul class="backup-list">
                <?php foreach ($recentBackups as $b): ?>
                    <li>
                        <span><?= e($b['filename']) ?></span>
                        <span class="backup-meta"><?= e(number_format(($b['size_bytes'] ?? 0) / 1024, 1)) ?> KB &middot; <?= e($b['created_at']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="panel-desc">No backups yet.</p>
        <?php endif; ?>
        <a class="btn btn-accent" href="<?= e(asset_url('/modules/admin/backup.php')) ?>">Open Backup &amp; Restore</a>
    </div>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
